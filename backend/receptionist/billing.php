<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'receptionist') {
    http_response_code(403); send_json_response(false, 'Access denied');
}
if (!$conn) send_json_response(false, $db_error ?: 'Database unavailable');

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

// ── GET ───────────────────────────────────────────────────
if ($method === 'GET') {
    $filter = trim($_GET['filter'] ?? 'all');
    $search = trim($_GET['search'] ?? '');
    $date   = trim($_GET['date']   ?? '');

    $where  = 'WHERE 1=1';
    $params = []; $types = '';

    if (in_array($filter, ['unpaid','partial','paid','waived'])) {
        $where .= ' AND b.payment_status = ?';
        $types .= 's'; $params[] = $filter;
    }
    if ($date) {
        $where .= ' AND DATE(b.created_at) = ?';
        $types .= 's'; $params[] = $date;
    }
    if ($search) {
        $like = '%' . $search . '%';
        $where .= ' AND (pu.first_name LIKE ? OR pu.last_name LIKE ?
                         OR CONCAT(pu.first_name," ",pu.last_name) LIKE ?
                         OR b.invoice_number LIKE ? OR p.patient_code LIKE ?)';
        $types .= 'sssss';
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }

    // Stats
    $sm = $conn->query(
        "SELECT
            COALESCE(SUM(CASE WHEN DATE(b.created_at)=CURDATE() THEN b.amount_paid ELSE 0 END),0) AS revenue_today,
            SUM(b.payment_status='paid')    AS paid_count,
            SUM(b.payment_status='unpaid')  AS unpaid_count,
            SUM(b.payment_status='partial') AS partial_count,
            COALESCE(SUM(CASE WHEN b.payment_status IN ('unpaid','partial') THEN b.total_amount-b.amount_paid ELSE 0 END),0) AS total_outstanding
         FROM bills b"
    )->fetch_assoc();

    $sql = "SELECT b.id AS bill_id, b.invoice_number, b.total_amount, b.amount_paid,
                   b.payment_status, b.due_date, b.notes, b.created_at,
                   (b.total_amount - b.amount_paid) AS balance_due,
                   CONCAT(pu.first_name,' ',pu.last_name) AS patient_name,
                   p.patient_code, p.id AS patient_id,
                   CONCAT(du.first_name,' ',du.last_name) AS doctor_name,
                   COALESCE(s.service_name,'General') AS service_name
            FROM bills b
            JOIN patients p   ON b.patient_id   = p.id
            JOIN users pu     ON p.user_id      = pu.id
            LEFT JOIN appointments a  ON b.appointment_id = a.id
            LEFT JOIN doctors doc     ON a.doctor_id      = doc.id
            LEFT JOIN users du        ON doc.user_id      = du.id
            LEFT JOIN services s      ON a.service_id     = s.id
            $where
            ORDER BY b.created_at DESC LIMIT 100";

    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    send_json_response(true, 'OK', ['stats' => $sm, 'bills' => $bills]);
}

// ── POST: create bill + optional immediate payment ────────
if ($method === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $patient_id = (int)($data['patient_id']   ?? 0);
    $appt_id    = (int)($data['appointment_id'] ?? 0) ?: null;
    $subtotal   = (float)($data['subtotal']    ?? 0);
    $tax        = (float)($data['tax']         ?? 0);
    $discount   = (float)($data['discount']    ?? 0);
    $total      = $subtotal + $tax - $discount;
    $due_date   = trim($data['due_date']       ?? '');
    $notes      = trim($data['notes']          ?? '');
    $pay_now    = (float)($data['pay_now']     ?? 0);
    $pay_method = trim($data['payment_method'] ?? 'cash');
    $pay_ref    = trim($data['transaction_ref'] ?? '');

    if (!$patient_id || $subtotal <= 0) send_json_response(false, 'Patient and amount required');

    // Generate invoice number
    $invN = 'INV-' . strtoupper(substr(md5(uniqid()), 0, 6)) . '-' . date('ymd');

    $paid_so_far  = min($pay_now, $total);
    $pay_status   = $paid_so_far >= $total ? 'paid' : ($paid_so_far > 0 ? 'partial' : 'unpaid');
    $due_val      = $due_date ?: null;

    $conn->begin_transaction();
    try {
        $bIns = $conn->prepare(
            "INSERT INTO bills (patient_id, appointment_id, invoice_number, subtotal, tax, discount, total_amount, amount_paid, payment_status, due_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $bIns->bind_param('iisdddddsss',
            $patient_id, $appt_id, $invN, $subtotal, $tax, $discount, $total, $paid_so_far, $pay_status, $due_val, $notes
        );
        $bIns->execute();
        $bill_id = $conn->insert_id;

        if ($paid_so_far > 0) {
            $pIns = $conn->prepare(
                "INSERT INTO payments (bill_id, amount_paid, payment_method, transaction_reference, received_by)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $pIns->bind_param('idssi', $bill_id, $paid_so_far, $pay_method, $pay_ref, $user_id);
            $pIns->execute();
        }
        $conn->commit();
        send_json_response(true, 'Bill created', [
            'bill_id'        => $bill_id,
            'invoice_number' => $invN,
            'payment_status' => $pay_status,
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        send_json_response(false, 'Failed: ' . $e->getMessage());
    }
}

// ── PUT: record payment on existing bill ─────────────────
if ($method === 'PUT') {
    $data       = json_decode(file_get_contents('php://input'), true) ?: [];
    $bill_id    = (int)($data['bill_id']        ?? 0);
    $amount     = (float)($data['amount']       ?? 0);
    $pay_method = trim($data['payment_method']  ?? 'cash');
    $pay_ref    = trim($data['transaction_ref'] ?? '');
    $notes      = trim($data['notes']           ?? '');

    if (!$bill_id || $amount <= 0) send_json_response(false, 'Bill ID and amount required');

    $bStmt = $conn->prepare("SELECT id, total_amount, amount_paid FROM bills WHERE id=?");
    $bStmt->bind_param('i', $bill_id); $bStmt->execute();
    $bill = $bStmt->get_result()->fetch_assoc();
    if (!$bill) send_json_response(false, 'Bill not found');

    $new_paid  = min($bill['amount_paid'] + $amount, $bill['total_amount']);
    $new_status = $new_paid >= $bill['total_amount'] ? 'paid' : 'partial';

    $conn->begin_transaction();
    try {
        $pIns = $conn->prepare(
            "INSERT INTO payments (bill_id, amount_paid, payment_method, transaction_reference, notes, received_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $pIns->bind_param('idsssi', $bill_id, $amount, $pay_method, $pay_ref, $notes, $user_id);
        $pIns->execute();

        $upd = $conn->prepare("UPDATE bills SET amount_paid=?, payment_status=? WHERE id=?");
        $upd->bind_param('dsi', $new_paid, $new_status, $bill_id);
        $upd->execute();
        $conn->commit();
        send_json_response(true, 'Payment recorded', ['new_status' => $new_status, 'new_paid' => $new_paid]);
    } catch (Exception $e) {
        $conn->rollback();
        send_json_response(false, 'Failed: ' . $e->getMessage());
    }
}

send_json_response(false, 'Method not allowed');
