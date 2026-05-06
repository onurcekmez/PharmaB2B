<?php
/**
 * Navigation Bar (Shared Component)
 * 
 * Displays different menu items based on user role.
 * Include this at the top of every page after <body>.
 */

$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';
?>

<nav class="navbar">
    <div class="nav-brand">
        <a href="dashboard.php">💊 PharmaB2B</a>
    </div>
    <div class="nav-links">
        <?php if ($role === 'pharmacy'): ?>
            <!-- Pharmacy menu -->
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Panel</a>
            <a href="medicines.php" class="<?= $current_page === 'medicines.php' ? 'active' : '' ?>">İlaçlar</a>
            <a href="create_order.php" class="<?= $current_page === 'create_order.php' ? 'active' : '' ?>">Sipariş Oluştur</a>
            <a href="my_orders.php" class="<?= $current_page === 'my_orders.php' ? 'active' : '' ?>">Siparişlerim</a>
            <a href="profile.php" class="<?= $current_page === 'profile.php' ? 'active' : '' ?>">Profilim</a>

        <?php elseif ($role === 'warehouse'): ?>
            <!-- Warehouse menu -->
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Panel</a>
            <a href="manage_orders.php" class="<?= $current_page === 'manage_orders.php' ? 'active' : '' ?>">Siparişler</a>
            <a href="manage_shipments.php" class="<?= $current_page === 'manage_shipments.php' ? 'active' : '' ?>">Sevkiyat</a>
            <a href="manage_stock.php" class="<?= $current_page === 'manage_stock.php' ? 'active' : '' ?>">Stok</a>

        <?php elseif ($role === 'admin'): ?>
            <!-- Admin menu -->
            <a href="dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">Panel</a>
            <a href="admin_panel.php" class="<?= $current_page === 'admin_panel.php' ? 'active' : '' ?>">Yönetim</a>
            <a href="medicines.php" class="<?= $current_page === 'medicines.php' ? 'active' : '' ?>">İlaçlar</a>
            <a href="manage_orders.php" class="<?= $current_page === 'manage_orders.php' ? 'active' : '' ?>">Siparişler</a>
            <a href="manage_shipments.php" class="<?= $current_page === 'manage_shipments.php' ? 'active' : '' ?>">Sevkiyat</a>
        <?php endif; ?>
    </div>
    <div class="nav-user">
        <span class="nav-username">👤 <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)</span>
        <a href="logout.php" class="btn-logout">Çıkış</a>
    </div>
</nav>
