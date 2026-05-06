<?php
/**
 * ADMIN PANEL
 * 
 * System overview for admin users.
 * Displays:
 * - All users
 * - System statistics
 * - Recent activity
 * 
 * Demonstrates:
 * - GROUP BY query (revenue by category)
 * - Subquery (below-average stock)
 * - Date function (current month orders)
 * - Character function (UPPER pharmacy names)
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize('admin');

// -- All users --
$users = $pdo->query("SELECT user_id, username, email, role, created_at FROM users ORDER BY user_id")->fetchAll();

// -- GROUP BY: Revenue by category --
$revenue_by_category = $pdo->query("
    SELECT m.category,
           COUNT(oi.order_item_id) AS total_items,
           SUM(oi.quantity * oi.unit_price) AS total_revenue
    FROM order_items oi
    INNER JOIN medicines m ON oi.medicine_id = m.medicine_id
    GROUP BY m.category
    ORDER BY total_revenue DESC
")->fetchAll();

// -- SUBQUERY: Medicines below average stock --
$low_stock_medicines = $pdo->query("
    SELECT medicine_name, category, stock_quantity,
           (SELECT ROUND(AVG(stock_quantity)) FROM medicines) AS avg_stock
    FROM medicines
    WHERE stock_quantity < (SELECT AVG(stock_quantity) FROM medicines)
    ORDER BY stock_quantity ASC
")->fetchAll();

// -- DATE FUNCTION: Orders this month --
$monthly_orders = $pdo->query("
    SELECT o.order_id, p.pharmacy_name, o.order_date, o.order_status, o.total_amount
    FROM orders o
    INNER JOIN pharmacies p ON o.pharmacy_id = p.pharmacy_id
    WHERE MONTH(o.order_date) = MONTH(CURRENT_DATE())
      AND YEAR(o.order_date) = YEAR(CURRENT_DATE())
    ORDER BY o.order_date DESC
")->fetchAll();

// -- CHARACTER FUNCTION: Pharmacy names in uppercase --
$pharmacies_upper = $pdo->query("
    SELECT pharmacy_id,
           UPPER(pharmacy_name) AS pharmacy_name_upper,
           CONCAT(UPPER(city), ' - ', address) AS full_address,
           phone
    FROM pharmacies
    ORDER BY pharmacy_name_upper
")->fetchAll();

function statusLabel($s) {
    $l = ['pending'=>'Beklemede','approved'=>'Onaylandı','rejected'=>'Reddedildi',
          'shipped'=>'Kargoda','delivered'=>'Teslim Edildi','cancelled'=>'İptal'];
    return $l[$s] ?? $s;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Yönetim Paneli</h1>
        <p>Sistem genelindeki veriler ve SQL sorgu örnekleri</p>
    </div>

    <!-- Users List -->
    <div class="card mb-3">
        <div class="card-header">👥 Kullanıcılar</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı Adı</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Kayıt Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge badge-approved"><?= $u['role'] ?></span></td>
                        <td><?= date('d.m.Y H:i', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- GROUP BY: Revenue by Category -->
    <div class="card mb-3">
        <div class="card-header">📊 Kategoriye Göre Gelir (GROUP BY Sorgusu)</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Satılan Kalem</th>
                        <th>Toplam Gelir (₺)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revenue_by_category as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['category']) ?></td>
                        <td><?= $r['total_items'] ?></td>
                        <td><?= number_format($r['total_revenue'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SUBQUERY: Below Average Stock -->
    <div class="card mb-3">
        <div class="card-header">📉 Ortalamanın Altında Stok (Alt Sorgu / Subquery)</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>İlaç Adı</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Ortalama Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock_medicines as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['medicine_name']) ?></td>
                        <td><?= htmlspecialchars($m['category']) ?></td>
                        <td style="color:#dc2626;font-weight:600"><?= $m['stock_quantity'] ?></td>
                        <td><?= $m['avg_stock'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- DATE FUNCTION: This Month's Orders -->
    <div class="card mb-3">
        <div class="card-header">📅 Bu Ayki Siparişler (Tarih Fonksiyonu)</div>
        <?php if (empty($monthly_orders)): ?>
            <p class="text-muted">Bu ay sipariş bulunmuyor.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sipariş #</th>
                        <th>Eczane</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th>Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_orders as $o): ?>
                    <tr>
                        <td>#<?= $o['order_id'] ?></td>
                        <td><?= htmlspecialchars($o['pharmacy_name']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($o['order_date'])) ?></td>
                        <td><span class="badge badge-<?= $o['order_status'] ?>"><?= statusLabel($o['order_status']) ?></span></td>
                        <td><?= number_format($o['total_amount'], 2, ',', '.') ?> ₺</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- CHARACTER FUNCTION: Uppercase Pharmacy Names -->
    <div class="card mb-3">
        <div class="card-header">🔤 Eczane Adları - UPPER() (Karakter Fonksiyonu)</div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Eczane Adı (BÜYÜK HARF)</th>
                        <th>Tam Adres</th>
                        <th>Telefon</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pharmacies_upper as $p): ?>
                    <tr>
                        <td><?= $p['pharmacy_id'] ?></td>
                        <td><strong><?= htmlspecialchars($p['pharmacy_name_upper']) ?></strong></td>
                        <td><?= htmlspecialchars($p['full_address']) ?></td>
                        <td><?= htmlspecialchars($p['phone']) ?></td>
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
