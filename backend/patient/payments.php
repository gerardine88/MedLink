<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'patient') {
    http_response_code(403);
    send_json_response(false, 'Access denied');
}
if (!$conn) send_json_response(false, $db_error ?: 'Database unavailable');

$user_id = (int)$_SESSION['user_id'];

// Get patient record
$pStmt = $conn->prepare("SELECT id FROM patients WHERE user_id = ?");
$pStmt->bind_param('i', $user_id);
$pStmt->execute();
$patient = $pStmt->get_result()->fetch_assoc();

if (!$patient) {
    send_json_response(true, 'No records', [
        'summary'  => ['total_due' => 0, 'total_paid' => 0, 'unpaid_count' => 0, 'last_payment_amount' => null, 'last_payment_date' => null],
        'bills'    => [],
        'payments' => [],
    ]);
}
$pid = (int)$patient['id'];

// Summary stats
$sumStmt = $conn->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN payment_status IN ('unpaid','partial') THEN total_amount - amount_paid ELSE 0 END), 0) AS total_due,
        COALESCE(SUM(amount_paid), 0) AS total_paid,
        COUNT(CASE WHEN payment_status IN ('unpaid','partial') THEN 1 END) AS unpaid_count
     FROM bills WHERE patient_id = ?"
);
$sumStmt->bind_param('i', $pid);
$sumStmt->execute();
$summary = $sumStmt->get_result()->fetch_assoc();

// Last payment
$lastPay = $conn->prepare(
    "SELECT p.amount_paid, p.paid_at, p.payment_method
     FROM payments p
     JOIN bills b ON p.bill_id = b.id
     WHERE b.patient_id = ?
     ORDER BY p.paid_at DESC
     LIMIT 1"
);
$lastPay->bind_param('i', $pid);
$lastPay->execute();
$lp = $lastPay->get_result()->fetch_assoc();
$summary['last_payment_amount'] = $lp ? $lp['amount_paid']    : null;
$summary['last_payment_date']   = $lp ? $lp['paid_at']        : null;
$summary['last_payment_method'] = $lp ? $lp['payment_method'] : null;

// All bills with appointment info
$billStmt = $conn->prepare(
    "SELECT b.id, b.invoice_number, b.subtotal, b.tax, b.discount,
            b.total_amount, b.amount_paid, b.payment_status,
            b.due_date, b.notes, b.created_at,
            a.appointment_date,
            CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
            COALESCE(s.service_name,'General Consultation') AS service_name
     FROM bills b
     LEFT JOIN appointments a ON b.appointment_id = a.id
     LEFT JOIN doctors doc ON a.doctor_id = doc.id
     LEFT JOIN users u ON doc.user_id = u.id
     LEFT JOIN services s ON a.service_id = s.id
     WHERE b.patient_id = ?
     ORDER BY b.created_at DESC"
);
$billStmt->bind_param('i', $pid);
$billStmt->execute();
$bills = $billStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Payment history
$payStmt = $conn->prepare(
    "SELECT p.id, p.amount_paid, p.payment_method, p.transaction_reference,
            p.notes, p.paid_at,
            b.invoice_number, b.total_amount,
            CONCAT(ru.first_name,' ',ru.last_name) AS received_by_name
     FROM payments p
     JOIN bills b ON p.bill_id = b.id
     LEFT JOIN users ru ON p.received_by = ru.id
     WHERE b.patient_id = ?
     ORDER BY p.paid_at DESC"
);
$payStmt->bind_param('i', $pid);
$payStmt->execute();
$payments = $payStmt->get_result()->fetch_all(MYSQLI_ASSOC);

send_json_response(true, 'OK', [
    'summary'  => $summary,
    'bills'    => $bills,
    'payments' => $payments,
]);
