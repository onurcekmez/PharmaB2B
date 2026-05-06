<?php
/**
 * MANAGE STOCK PAGE (Warehouse)
 * 
 * Warehouse staff can view and update medicine stock quantities and prices.
 * 
 * Demonstrates:
 * - Prepared statements for UPDATE
 * - Server-side PHP validation
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize(['warehouse', 'admin']);

$success = '';
$error = '';

// -- Handle stock/price update --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medicine_id'])) {
    $medicine_id = intval($_POST['medicine_id']);
    $new_stock = intval($_POST['new_stock']);
    $new_price = floatval($_POST['new_price'] ?? 0);

    // PHP validation
    if ($new_stock < 0) {
        $error = 'Stok miktari negatif olamaz.';
    } elseif ($new_price <= 0) {
        $error = 'Fiyat sifirdan büyük olmalidir.';
    } else {
        $stmt = $pdo->prepare("UPDATE medicines SET stock_quantity = ?, unit_price = ? WHERE medicine_id = ?");
        $stmt->execute([$new_stock, $new_price, $medicine_id]);
        $success = 'Stok ve fiyat basariyla güncellendi.';
    }
}

// Get all medicines
$medicines = $pdo->query("SELECT * FROM medicines ORDER BY category, medicine_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Yönetimi - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Stok Yönetimi</h1>
        <p>Ilac stok miktarlarini ve fiyatlarini güncelleyin</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <!-- Ilac arama filtresi -->
        <div class="table-filter">
            <input type="text" id="stock-filter" placeholder="Ilac adi ile filtrele..." onkeyup="filterTable(this, 'stock-tbody')">
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ilac Adi</th>
                        <th>Kategori</th>
                        <th>Mevcut Stok</th>
                        <th>Birim Fiyat (₺)</th>
                        <th>Son Kullanma</th>
                        <th>Güncelle</th>
                    </tr>
                </thead>
                <tbody id="stock-tbody">
                    <?php foreach ($medicines as $med): ?>
                    <tr>
                        <td><?= $med['medicine_id'] ?></td>
                        <td><?= htmlspecialchars($med['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($med['category']) ?></td>
                        <td style="<?= $med['stock_quantity'] < 50 ? 'color:red;font-weight:bold' : '' ?>">
                            <?= $med['stock_quantity'] ?>
                        </td>
                        <td><?= number_format($med['unit_price'], 2, ',', '.') ?></td>
                        <td><?= date('d.m.Y', strtotime($med['expiration_date'])) ?></td>
                        <td>
                            <form method="POST" style="display:flex;gap:4px;align-items:center;">
                                <input type="hidden" name="medicine_id" value="<?= $med['medicine_id'] ?>">
                                <input type="number" name="new_stock" value="<?= $med['stock_quantity'] ?>" 
                                       class="form-control" style="width:70px;padding:3px;" min="0">
                                <input type="number" step="0.01" name="new_price" value="<?= $med['unit_price'] ?>" 
                                       class="form-control" style="width:85px;padding:3px;" min="0.01">
                                <button type="submit" class="btn btn-primary btn-sm">Kaydet</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
