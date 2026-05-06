<?php
/**
 * AJAX ENDPOINT: Filter by Stock Level
 * 
 * Accepts GET parameters:
 * - min_stock: minimum stock level
 * - max_stock: maximum stock level
 * - category: filter by category
 * 
 * Returns JSON array of filtered medicines.
 * Demonstrates AJAX stock filtering without page refresh.
 */
require_once '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$min_stock = isset($_GET['min_stock']) ? intval($_GET['min_stock']) : null;
$max_stock = isset($_GET['max_stock']) ? intval($_GET['max_stock']) : null;
$category  = trim($_GET['category'] ?? '');

$sql = "SELECT medicine_id, medicine_name, category, stock_quantity, unit_price, expiration_date 
        FROM medicines WHERE 1=1";
$params = [];

if ($min_stock !== null) {
    $sql .= " AND stock_quantity >= ?";
    $params[] = $min_stock;
}

if ($max_stock !== null) {
    $sql .= " AND stock_quantity <= ?";
    $params[] = $max_stock;
}

if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY stock_quantity ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

echo json_encode($medicines, JSON_UNESCAPED_UNICODE);
