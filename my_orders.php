<?php
/**
 * MY ORDERS PAGE
 * 
 * Pharmacy users can view, update, and cancel their orders.
 * 
 * Features:
 * - List all orders with status badges
 * - Cancel pending orders (restores stock)
 * - View order items detail
 * - AJAX status refresh button
 * 
 * Uses JOIN query to display order items with medicine names.
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize('pharmacy');

$user_id = $_SESSION['user_id'];

// Get pharmacy_id
$stmt = $pdo->prepare("SELECT pharmacy_id FROM pharmacies WHERE user_id = ?");
$stmt->execute([$user_id]);
$pharmacy_id = $stmt->fetch()['pharmacy_id'] ?? 0;

$success = '';
$error = '';

// -- Handle cancel order --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $cancel_id = intval($_POST['cancel_order_id']);

    // Verify order belongs to this pharmacy and is pending
    $stmt = $pdo->prepare("SELECT order_id, order_status FROM orders WHERE order_id = ? AND pharmacy_id = ?");
    $stmt->execute([$cancel_id, $pharmacy_id]);
    $order = $stmt->fetch();

    if ($order && $order['order_status'] === 'pending') {
        try {
            $pdo->beginTransaction();

            // Restore stock for cancelled order items
            $stmt = $pdo->prepare("SELECT medicine_id, quantity FROM order_items WHERE order_id = ?");
            $stmt->execute([$cancel_id]);
            $items = $stmt->fetchAll();

            $stmt_restore = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity + ? WHERE medicine_id = ?");
            foreach ($items as $item) {
                $stmt_restore->execute([$item['quantity'], $item['medicine_id']]);
            }

            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled' WHERE order_id = ?");
            $stmt->execute([$cancel_id]);

            $pdo->commit();
            $success = "Sipariş #$cancel_id iptal edildi.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'İptal sırasında hata: ' . $e->getMessage();
        }
    } else {
        $error = 'Bu sipariş iptal edilemez.';
    }
}

// -- Fetch all orders for this pharmacy (JOIN query) --
$stmt = $pdo->prepare("
    SELECT o.order_id, o.order_date, o.order_status, o.total_amount
    FROM orders o
    WHERE o.pharmacy_id = ?
    ORDER BY o.order_date DESC
");
$stmt->execute([$pharmacy_id]);
$orders = $stmt->fetchAll();

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
    <title>Siparişlerim - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header flex-between">
        <div>
            <h1>Siparişlerim</h1>
            <p>Sipariş geçmişinizi görüntüleyin</p>
        </div>
        <div>
            <a href="create_order.php" class="btn btn-primary">+ Yeni Sipariş</a>
            <button onclick="refreshAllStatuses()" class="btn btn-secondary">🔄 Durumu Güncelle</button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <?php if (empty($orders)): ?>
            <p class="text-muted">Henüz siparişiniz bulunmuyor. <a href="create_order.php">Yeni sipariş oluşturun</a>.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sipariş #</th>
                        <th>Tarih</th>
                        <th>Durum</th>
                        <th>Tutar</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order['order_id'] ?></strong></td>
                        <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $order['order_status'] ?>"
                                  data-order-id="<?= $order['order_id'] ?>">
                                <?= statusLabel($order['order_status']) ?>
                            </span>
                        </td>
                        <td><?= number_format($order['total_amount'], 2, ',', '.') ?> ₺</td>
                        <td>
                            <?php if ($order['order_status'] === 'pending'): ?>
                                <a href="edit_order.php?id=<?= $order['order_id'] ?>" class="btn btn-warning btn-sm">✏️ Düzenle</a>
                                <form method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Bu siparişi iptal etmek istediğinize emin misiniz?')">
                                    <input type="hidden" name="cancel_order_id" value="<?= $order['order_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">İptal Et</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
