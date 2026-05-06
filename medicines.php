<?php
/**
 * MEDICINES PAGE
 * 
 * Displays all medicines with AJAX-powered search and filtering.
 * Available to pharmacy users (and admin).
 * 
 * AJAX Integration:
 * - Search input triggers ajax/search_medicines.php on keyup
 * - Category filter triggers re-fetch without page reload
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize(['pharmacy', 'admin']);

// Initial load: get all medicines
$stmt = $pdo->query("SELECT * FROM medicines ORDER BY medicine_name ASC");
$medicines = $stmt->fetchAll();

// Get unique categories for filter dropdown
$stmt = $pdo->query("SELECT DISTINCT category FROM medicines ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlaçlar - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>İlaç Kataloğu</h1>
        <p>İlaçları arayın ve stok durumlarını görüntüleyin</p>
    </div>

    <!-- Search & Filter Bar (triggers AJAX) -->
    <div class="search-bar">
        <input type="text" id="medicine-search" class="form-control" 
               placeholder="🔍 İlaç adı ile ara...">
        
        <select id="category-filter" class="form-control" style="max-width:200px;">
            <option value="">Tüm Kategoriler</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Medicine Results (updated via AJAX) -->
    <div class="card">
        <div id="medicine-results">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>İlaç Adı</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Birim Fiyat (₺)</th>
                            <th>Son Kullanma</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicines as $med): ?>
                        <tr>
                            <td><?= htmlspecialchars($med['medicine_name']) ?></td>
                            <td><?= htmlspecialchars($med['category']) ?></td>
                            <td style="<?= $med['stock_quantity'] < 50 ? 'color:#dc2626;font-weight:600' : '' ?>">
                                <?= $med['stock_quantity'] ?>
                            </td>
                            <td><?= number_format($med['unit_price'], 2, ',', '.') ?></td>
                            <td><?= date('d.m.Y', strtotime($med['expiration_date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
