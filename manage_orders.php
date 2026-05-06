<?php
/**
 * MANAGE ORDERS PAGE (Warehouse)
 * 
 * Warehouse staff can view and approve/reject pharmacy orders.
 * 
 * Uses JOIN query to display orders with pharmacy information.
 * Demonstrates prepared statements for UPDATE operations.
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize(['warehouse', 'admin']);

$success = '';
$error = '';

// -- Handle approve/reject actions --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($order_id > 0 && in_array($action, ['approved', 'rejected'])) {
        // Verify order is pending
        $stmt = $pdo->prepare("SELECT order_id, order_status FROM orders WHERE order_id = ? AND order_status = 'pending'");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if ($order) {
            // If rejecting, restore stock
            if ($action === 'rejected') {
                $stmt = $pdo->prepare("SELECT medicine_id, quantity FROM order_items WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $items = $stmt->fetchAll();

                $stmt_restore = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity + ? WHERE medicine_id = ?");
                foreach ($items as $item) {
                    $stmt_restore->execute([$item['quantity'], $item['medicine_id']]);
                }
            }

            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
            $stmt->execute([$action, $order_id]);

            $label = $action === 'approved' ? 'onaylandı' : 'reddedildi';
            $success = "Sipariş #$order_id $label.";
        } else {
            $error = 'Sipariş bulunamadı veya zaten işlem görmüş.';
        }
    }
}

// -- Fetch all orders with pharmacy info (JOIN query) --
$stmt = $pdo->query("
    SELECT o.order_id, o.order_date, o.order_status, o.total_amount,
           p.pharmacy_id, p.pharmacy_name, p.city
    FROM orders o
    INNER JOIN pharmacies p ON o.pharmacy_id = p.pharmacy_id
    ORDER BY 
        CASE o.order_status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            ELSE 3 
        END,
        o.order_date DESC
");
$orders = $stmt->fetchAll();

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
    <title>Sipariş Yönetimi - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Sipariş Yönetimi</h1>
        <p>Gelen siparişleri onaylayın veya reddedin</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sipariş #</th>
                        <th>Eczane</th>
                        <th>Şehir</th>
                        <th>Tarih</th>
                        <th>Tutar</th>
                        <th>Durum</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order['order_id'] ?></strong></td>
                        <td>
                            <?= htmlspecialchars($order['pharmacy_name']) ?>
                            <span class="info-btn" onclick="showPharmacyInfo(<?= $order['pharmacy_id'] ?>)" title="Eczane bilgilerini gör">ℹ️</span>
                        </td>
                        <td><?= htmlspecialchars($order['city']) ?></td>
                        <td><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></td>
                        <td><?= number_format($order['total_amount'], 2, ',', '.') ?> ₺</td>
                        <td>
                            <span class="badge badge-<?= $order['order_status'] ?>">
                                <?= statusLabel($order['order_status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($order['order_status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                    <button type="submit" name="action" value="approved" class="btn btn-success btn-sm">✓ Onayla</button>
                                    <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm">✗ Reddet</button>
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
    </div>
</div>

<!-- Eczane Bilgi Popup -->
<div id="pharmacy-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:999;">
    <div style="background:#fff;max-width:400px;margin:100px auto;padding:20px;border-radius:6px;border:1px solid #ccc;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <strong>Eczane Bilgileri</strong>
            <span onclick="closePharmacyModal()" style="cursor:pointer;font-size:1.2rem;">✕</span>
        </div>
        <div id="pharmacy-modal-content">Yükleniyor...</div>
    </div>
</div>

<script src="js/app.js"></script>
<script>
// Eczane bilgi popup - AJAX ile bilgileri getir
function showPharmacyInfo(pharmacyId) {
    var modal = document.getElementById('pharmacy-modal');
    var content = document.getElementById('pharmacy-modal-content');
    modal.style.display = 'block';
    content.innerHTML = 'Yükleniyor...';

    fetch('ajax/pharmacy_info.php?pharmacy_id=' + pharmacyId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                content.innerHTML = '<p>' + data.error + '</p>';
            } else {
                content.innerHTML = '<table style="width:100%">'
                    + '<tr><td style="font-weight:bold;padding:4px 8px;">Eczane</td><td style="padding:4px 8px;">' + (data.pharmacy_name || '-') + '</td></tr>'
                    + '<tr><td style="font-weight:bold;padding:4px 8px;">Adres</td><td style="padding:4px 8px;">' + (data.address || '-') + '</td></tr>'
                    + '<tr><td style="font-weight:bold;padding:4px 8px;">Sehir</td><td style="padding:4px 8px;">' + (data.city || '-') + '</td></tr>'
                    + '<tr><td style="font-weight:bold;padding:4px 8px;">Telefon</td><td style="padding:4px 8px;">' + (data.phone || '-') + '</td></tr>'
                    + '<tr><td style="font-weight:bold;padding:4px 8px;">E-posta</td><td style="padding:4px 8px;">' + (data.email || '-') + '</td></tr>'
                    + '<tr><td style="font-weight:bold;padding:4px 8px;">Vergi No</td><td style="padding:4px 8px;">' + (data.tax_number || '-') + '</td></tr>'
                    + '<tr><td style="font-weight:bold;padding:4px 8px;">Ruhsat No</td><td style="padding:4px 8px;">' + (data.license_number || '-') + '</td></tr>'
                    + '</table>';
            }
        })
        .catch(function() {
            content.innerHTML = '<p>Hata olustu.</p>';
        });
}

function closePharmacyModal() {
    document.getElementById('pharmacy-modal').style.display = 'none';
}

// Disariya tiklaninca kapat
document.getElementById('pharmacy-modal').addEventListener('click', function(e) {
    if (e.target === this) closePharmacyModal();
});
</script>
</body>
</html>
