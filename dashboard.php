<?php
/**
 * DASHBOARD
 * 
 * Role-based dashboard showing different stats for each user type.
 * - Pharmacy: order count, recent orders
 * - Warehouse: pending orders, shipment stats
 * - Admin: system-wide statistics
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize(); // Any logged-in user

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch stats based on role
if ($role === 'pharmacy') {
    // Get pharmacy_id for current user
    $stmt = $pdo->prepare("SELECT pharmacy_id, pharmacy_name FROM pharmacies WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $pharmacy = $stmt->fetch();
    $pharmacy_id = $pharmacy['pharmacy_id'] ?? 0;

    // My order stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE pharmacy_id = ?");
    $stmt->execute([$pharmacy_id]);
    $total_orders = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE pharmacy_id = ? AND order_status = 'pending'");
    $stmt->execute([$pharmacy_id]);
    $pending_orders = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE pharmacy_id = ? AND order_status = 'delivered'");
    $stmt->execute([$pharmacy_id]);
    $delivered_orders = $stmt->fetch()['total'];

    // Recent orders
    $stmt = $pdo->prepare("SELECT order_id, order_date, order_status, total_amount 
                           FROM orders WHERE pharmacy_id = ? ORDER BY order_date DESC LIMIT 5");
    $stmt->execute([$pharmacy_id]);
    $recent_orders = $stmt->fetchAll();

} elseif ($role === 'warehouse') {
    // Warehouse stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'pending'");
    $pending_orders = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments WHERE shipment_status = 'in_transit'");
    $active_shipments = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medicines WHERE stock_quantity < 50");
    $low_stock = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch()['total'];

} else {
    // Admin stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $total_users = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $total_orders = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM medicines");
    $total_medicines = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount),0) as total FROM orders WHERE order_status = 'delivered'");
    $total_revenue = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM shipments");
    $total_shipments = $stmt->fetch()['total'];
}

// Status label helper
function statusLabel($status) {
    $labels = [
        'pending'   => 'Beklemede',
        'approved'  => 'Onaylandı',
        'rejected'  => 'Reddedildi',
        'shipped'   => 'Kargoda',
        'delivered' => 'Teslim Edildi',
        'cancelled' => 'İptal'
    ];
    return $labels[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Hoş Geldiniz, <?= htmlspecialchars($_SESSION['username']) ?></h1>
        <p>Sistem genel durumu</p>
    </div>

    <?php if ($role === 'pharmacy'): ?>
    <!-- ===== PHARMACY DASHBOARD ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📦</div>
            <div class="stat-info">
                <h3><?= $total_orders ?></h3>
                <p>Toplam Sipariş</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div class="stat-info">
                <h3><?= $pending_orders ?></h3>
                <p>Bekleyen Sipariş</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?= $delivered_orders ?></h3>
                <p>Teslim Edilen</p>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">Son Siparişler</div>
        <?php if (empty($recent_orders)): ?>
            <p class="text-muted">Henüz siparişiniz bulunmuyor.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sipariş #</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th>Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                    <tr>
                        <td>#<?= $order['order_id'] ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $order['order_status'] ?>" 
                                  data-order-id="<?= $order['order_id'] ?>">
                                <?= statusLabel($order['order_status']) ?>
                            </span>
                        </td>
                        <td><?= number_format($order['total_amount'], 2, ',', '.') ?> ₺</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($role === 'warehouse'): ?>
    <!-- ===== WAREHOUSE DASHBOARD ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div class="stat-info">
                <h3><?= $pending_orders ?></h3>
                <p>Bekleyen Sipariş</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">🚚</div>
            <div class="stat-info">
                <h3><?= $active_shipments ?></h3>
                <p>Aktif Sevkiyat</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">📉</div>
            <div class="stat-info">
                <h3><?= $low_stock ?></h3>
                <p>Düşük Stok İlaç</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">📦</div>
            <div class="stat-info">
                <h3><?= $total_orders ?></h3>
                <p>Toplam Sipariş</p>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ===== ADMIN DASHBOARD ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div class="stat-info">
                <h3><?= $total_users ?></h3>
                <p>Toplam Kullanıcı</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">📦</div>
            <div class="stat-info">
                <h3><?= $total_orders ?></h3>
                <p>Toplam Sipariş</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💊</div>
            <div class="stat-info">
                <h3><?= $total_medicines ?></h3>
                <p>İlaç Çeşidi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cyan">💰</div>
            <div class="stat-info">
                <h3><?= number_format($total_revenue, 0, ',', '.') ?> ₺</h3>
                <p>Toplam Gelir</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">🚚</div>
            <div class="stat-info">
                <h3><?= $total_shipments ?></h3>
                <p>Toplam Sevkiyat</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="js/app.js"></script>
</body>
</html>
