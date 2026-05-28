<?php
/**
 * Admin Users API
 *
 * Handles CRUD for user accounts (doctor, receptionist).
 * Requires an active admin session.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($db_error) || !$conn) {
    http_response_code(500);
    send_json_response(false, $db_error ?: 'Database connection failed');
}

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    send_json_response(false, 'Access denied. Admin only.');
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_users($conn);
        break;
    case 'POST':
        handle_create_user($conn);
        break;
    case 'PUT':
        handle_update_user($conn);
        break;
    case 'DELETE':
        handle_deactivate_user($conn);
        break;
    default:
        http_response_code(405);
        send_json_response(false, 'Method not allowed');
}

/**
 * GET: List users with optional role/search/page filters
 */
function handle_get_users($conn) {
    $role   = $_GET['role']   ?? '';
    $search = $_GET['search'] ?? '';
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    $where  = [];
    $params = [];
    $types  = '';

    $allowed_roles = ['doctor', 'receptionist', 'patient', 'admin'];

    if (!empty($role) && in_array($role, $allowed_roles)) {
        $where[]  = 'r.role_name = ?';
        $params[] = $role;
        $types   .= 's';
    }

    if (!empty($search)) {
        $like     = '%' . $search . '%';
        $where[]  = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types   .= 'sss';
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Total count
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM users u JOIN roles r ON u.role_id = r.id $whereClause");
    if ($types) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = intval($countStmt->get_result()->fetch_assoc()['total']);

    // Fetch page
    $fetchParams   = $params;
    $fetchParams[] = $limit;
    $fetchParams[] = $offset;
    $fetchTypes    = $types . 'ii';

    $stmt = $conn->prepare(
        "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.gender,
                r.role_name AS user_role, u.status, u.created_at, u.last_login
         FROM users u
         JOIN roles r ON u.role_id = r.id
         $whereClause
         ORDER BY u.created_at DESC
         LIMIT ? OFFSET ?"
    );

    $stmt->bind_param($fetchTypes, ...$fetchParams);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    send_json_response(true, 'Users retrieved', [
        'users'       => $users,
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => (int) ceil($total / $limit),
    ]);
}

/**
 * POST: Create a new doctor or receptionist account
 */
function handle_create_user($conn) {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $first_name = trim($data['first_name'] ?? '');
    $last_name  = trim($data['last_name']  ?? '');
    $email      = trim($data['email']      ?? '');
    $password   = $data['password']        ?? '';
    $phone      = trim($data['phone']      ?? '');
    $gender     = trim($data['gender']     ?? '');
    $user_role  = trim($data['user_role']  ?? '');

    $errors = [];

    if (empty($first_name))                               $errors['first_name'] = 'First name is required';
    if (empty($last_name))                                $errors['last_name']  = 'Last name is required';
    if (empty($email))                                    $errors['email']      = 'Email is required';
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
                                                          $errors['email']      = 'Invalid email format';
    if (strlen($password) < 8)                            $errors['password']   = 'Password must be at least 8 characters';
    if (empty($phone))                                    $errors['phone']      = 'Phone number is required';
    if (empty($gender) || !in_array($gender, ['Male', 'Female', 'Other']))
                                                          $errors['gender']     = 'Please select a valid gender';
    if (!in_array($user_role, ['doctor', 'receptionist'])) $errors['user_role'] = 'Role must be Doctor or Receptionist';

    if (!empty($errors)) {
        send_json_response(false, 'Please correct the highlighted fields', ['errors' => $errors]);
    }

    // Check for duplicate email
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        send_json_response(false, 'Email already exists', ['errors' => ['email' => 'This email is already registered']]);
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $roleStmt = $conn->prepare('SELECT id FROM roles WHERE role_name = ? LIMIT 1');
    $roleStmt->bind_param('s', $user_role);
    $roleStmt->execute();
    $roleRow = $roleStmt->get_result()->fetch_assoc();
    if (!$roleRow) {
        send_json_response(false, 'Invalid role specified');
    }
    $role_id = (int) $roleRow['id'];
    $roleStmt->close();

    $stmt = $conn->prepare(
        "INSERT INTO users (role_id, first_name, last_name, email, password_hash, phone, gender, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'active')"
    );
    $stmt->bind_param('issssss', $role_id, $first_name, $last_name, $email, $password_hash, $phone, $gender);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;

        log_event('INFO', 'Admin created user account', [
            'created_by' => $_SESSION['user_id'],
            'new_user_id' => $new_id,
            'role'        => $user_role,
            'email'       => $email,
        ]);

        send_json_response(true, ucfirst($user_role) . ' account created successfully', [
            'user_id' => $new_id,
        ]);
    } else {
        send_json_response(false, 'Failed to create account: ' . $stmt->error);
    }
}

/**
 * PUT: Update a user's status or role
 */
function handle_update_user($conn) {
    $data    = json_decode(file_get_contents('php://input'), true) ?: [];
    $user_id = intval($data['user_id'] ?? 0);

    if (!$user_id) {
        send_json_response(false, 'User ID is required');
    }

    $updates = [];
    $params  = [];
    $types   = '';

    if (isset($data['status']) && in_array($data['status'], ['active', 'inactive', 'suspended'])) {
        $updates[] = 'status = ?';
        $params[]  = $data['status'];
        $types    .= 's';
    }

    if (isset($data['user_role']) && in_array($data['user_role'], ['doctor', 'receptionist', 'patient', 'admin'])) {
        $rStmt = $conn->prepare('SELECT id FROM roles WHERE role_name = ? LIMIT 1');
        $rStmt->bind_param('s', $data['user_role']);
        $rStmt->execute();
        $rRow = $rStmt->get_result()->fetch_assoc();
        $rStmt->close();
        if ($rRow) {
            $updates[] = 'role_id = ?';
            $params[]  = (int) $rRow['id'];
            $types    .= 'i';
        }
    }

    if (empty($updates)) {
        send_json_response(false, 'No valid fields to update');
    }

    $params[] = $user_id;
    $types   .= 'i';

    $sql  = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        log_event('INFO', 'Admin updated user', [
            'updated_by' => $_SESSION['user_id'],
            'user_id'    => $user_id,
            'changes'    => array_filter($data, function ($k) { return $k !== 'user_id'; }, ARRAY_FILTER_USE_KEY),
        ]);

        send_json_response(true, 'User updated successfully');
    } else {
        send_json_response(false, 'Update failed: ' . $stmt->error);
    }
}

/**
 * DELETE: Soft-deactivate a user (sets status to 'inactive')
 */
function handle_deactivate_user($conn) {
    $data    = json_decode(file_get_contents('php://input'), true) ?: [];
    $user_id = intval($data['user_id'] ?? 0);

    if (!$user_id) {
        send_json_response(false, 'User ID is required');
    }

    if ($user_id === intval($_SESSION['user_id'])) {
        send_json_response(false, 'You cannot deactivate your own account');
    }

    $stmt = $conn->prepare("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $user_id);

    if ($stmt->execute()) {
        log_event('INFO', 'Admin deactivated user account', [
            'deactivated_by' => $_SESSION['user_id'],
            'user_id'        => $user_id,
        ]);

        send_json_response(true, 'User account deactivated');
    } else {
        send_json_response(false, 'Deactivation failed: ' . $stmt->error);
    }
}
?>
