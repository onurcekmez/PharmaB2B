<?php
/**
 * AJAX ENDPOINT: Order Status
 * 
 * Accepts GET parameters:
 * - order_id: the order to check
 * 
 * Returns JSON with current order status.
 * Used for dynamic status refresh without page reload.
 */
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

// Get current order status (prepared statement)
$stmt = $pdo->prepare("SELECT order_id, order_status, total_amount FROM orders WHERE order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if ($order) {
    echo json_encode([
        'order_id' => $order['order_id'],
        'status'   => $order['order_status'],
        'total'    => $order['total_amount']
    ]);
} else {
    echo json_encode(['error' => 'Order not found']);
}
