<?php
/**
 * AJAX ENDPOINT: Search Medicines
 * 
 * Accepts GET parameters:
 * - q: search query (medicine name)
 * - category: filter by category
 * 
 * Returns JSON array of matching medicines.
 * Called from medicines.php via Fetch API on keyup.
 */
require_once '../includes/db.php';

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

$query    = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

// Build dynamic query with prepared statement
$sql = "SELECT medicine_id, medicine_name, category, stock_quantity, unit_price, expiration_date 
        FROM medicines WHERE 1=1";
$params = [];

// Filter by name (LIKE search)
if ($query !== '') {
    $sql .= " AND medicine_name LIKE ?";
    $params[] = '%' . $query . '%';
}

// Filter by category
if ($category !== '') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY medicine_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

echo json_encode($medicines, JSON_UNESCAPED_UNICODE);
