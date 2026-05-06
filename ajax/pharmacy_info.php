<?php
/**
 * AJAX ENDPOINT: Pharmacy Info
 * 
 * Accepts GET parameter:
 * - pharmacy_id: the pharmacy to look up
 * 
 * Returns JSON with pharmacy details.
 * Used by the info (i) button on manage_orders.php
 */
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$pharmacy_id = intval($_GET['pharmacy_id'] ?? 0);

if ($pharmacy_id <= 0) {
    echo json_encode(['error' => 'Gecersiz eczane ID']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.pharmacy_name, p.address, p.phone, p.city, 
           p.tax_number, p.license_number, u.email
    FROM pharmacies p
    INNER JOIN users u ON p.user_id = u.user_id
    WHERE p.pharmacy_id = ?
");
$stmt->execute([$pharmacy_id]);
$pharmacy = $stmt->fetch();

if ($pharmacy) {
    echo json_encode($pharmacy, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Eczane bulunamadi']);
}
