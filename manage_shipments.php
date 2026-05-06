<?php
/**
 * MANAGE SHIPMENTS PAGE (Warehouse)
 * 
 * Warehouse staff can:
 * - Create shipments for approved orders
 * - Assign vehicles and staff
 * - Update shipment status (preparing → in_transit → delivered)
 * 
 * Demonstrates:
 * - JOIN queries (shipment + order + pharmacy + vehicle + staff)
 * - Prepared statements for INSERT and UPDATE
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize(['warehouse', 'admin']);

$success = '';
$error = '';

// -- Handle create shipment --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_shipment'])) {
    $order_id   = intval($_POST['order_id'] ?? 0);
    $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
    $staff_id   = intval($_POST['staff_id'] ?? 0);

    // PHP validation
    if ($order_id <= 0) {
        $error = 'Geçerli bir sipariş seçin.';
    } elseif ($vehicle_id <= 0) {
        $error = 'Araç seçimi gereklidir.';
    } elseif ($staff_id <= 0) {
        $error = 'Personel seçimi gereklidir.';
    } else {
        // Check if shipment already exists for this order
        $stmt = $pdo->prepare("SELECT shipment_id FROM shipments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        if ($stmt->fetch()) {
            $error = 'Bu sipariş için zaten sevkiyat oluşturulmuş.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO shipments (order_id, vehicle_id, staff_id, shipment_status) VALUES (?, ?, ?, 'preparing')");
            $stmt->execute([$order_id, $vehicle_id, $staff_id]);

            // Update order status to shipped
            $stmt = $pdo->prepare("UPDATE orders SET order_status = 'shipped' WHERE order_id = ?");
            $stmt->execute([$order_id]);

            $success = "Sipariş #$order_id için sevkiyat oluşturuldu.";
        }
    }
}

// -- Handle update shipment status --
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $shipment_id = intval($_POST['shipment_id'] ?? 0);
    $new_status  = $_POST['new_status'] ?? '';

    if ($shipment_id > 0 && in_array($new_status, ['in_transit', 'delivered'])) {
        $delivery_date = $new_status === 'delivered' ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare("UPDATE shipments SET shipment_status = ?, delivery_date = COALESCE(?, delivery_date) WHERE shipment_id = ?");
        $stmt->execute([$new_status, $delivery_date, $shipment_id]);

        // If delivered, update order status too
        if ($new_status === 'delivered') {
            $stmt = $pdo->prepare("SELECT order_id FROM shipments WHERE shipment_id = ?");
            $stmt->execute([$shipment_id]);
            $shipment = $stmt->fetch();
            if ($shipment) {
                $stmt = $pdo->prepare("UPDATE orders SET order_status = 'delivered' WHERE order_id = ?");
                $stmt->execute([$shipment['order_id']]);
            }
        }

        $label = $new_status === 'in_transit' ? 'Yola çıktı' : 'Teslim edildi';
        $success = "Sevkiyat #$shipment_id: $label.";
    }
}

// -- Get approved orders without shipment (for new shipment creation) --
$approved_orders = $pdo->query("
    SELECT o.order_id, o.total_amount, p.pharmacy_name, p.city
    FROM orders o
    INNER JOIN pharmacies p ON o.pharmacy_id = p.pharmacy_id
    LEFT JOIN shipments s ON o.order_id = s.order_id
    WHERE o.order_status = 'approved' AND s.shipment_id IS NULL
    ORDER BY o.order_date ASC
")->fetchAll();

// -- Get all vehicles --
$vehicles = $pdo->query("SELECT * FROM vehicles ORDER BY plate_number")->fetchAll();

// -- Get all staff (drivers) --
$staff_list = $pdo->query("SELECT * FROM staff WHERE role = 'driver' ORDER BY first_name")->fetchAll();

// -- Get existing shipments --
$shipments = $pdo->query("
    SELECT s.*, o.order_id, o.total_amount, 
           p.pharmacy_name, p.city,
           v.plate_number, v.model AS vehicle_model,
           CONCAT(st.first_name, ' ', st.last_name) AS staff_name
    FROM shipments s
    INNER JOIN orders o ON s.order_id = o.order_id
    INNER JOIN pharmacies p ON o.pharmacy_id = p.pharmacy_id
    LEFT JOIN vehicles v ON s.vehicle_id = v.vehicle_id
    LEFT JOIN staff st ON s.staff_id = st.staff_id
    ORDER BY s.shipment_date DESC
")->fetchAll();

function shipmentStatusLabel($status) {
    $labels = [
        'preparing'  => 'Hazırlanıyor',
        'in_transit' => 'Yolda',
        'delivered'  => 'Teslim Edildi'
    ];
    return $labels[$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sevkiyat Yönetimi - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Sevkiyat Yönetimi</h1>
        <p>Sevkiyat oluşturun, araç ve personel atayın</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Create New Shipment -->
    <?php if (!empty($approved_orders)): ?>
    <div class="card mb-3">
        <div class="card-header">Yeni Sevkiyat Oluştur</div>
        <form method="POST">
            <input type="hidden" name="create_shipment" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="order_id">Sipariş</label>
                    <select name="order_id" id="order_id" class="form-control" required>
                        <option value="">Sipariş Seçin</option>
                        <?php foreach ($approved_orders as $o): ?>
                            <option value="<?= $o['order_id'] ?>">
                                #<?= $o['order_id'] ?> - <?= htmlspecialchars($o['pharmacy_name']) ?> (<?= $o['city'] ?>) - <?= number_format($o['total_amount'], 2, ',', '.') ?> ₺
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="vehicle_id">Araç</label>
                    <select name="vehicle_id" id="vehicle_id" class="form-control" required>
                        <option value="">Araç Seçin</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['vehicle_id'] ?>">
                                <?= htmlspecialchars($v['plate_number']) ?> - <?= htmlspecialchars($v['model']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="staff_id">Sürücü / Personel</label>
                <select name="staff_id" id="staff_id" class="form-control" required style="max-width:400px;">
                    <option value="">Personel Seçin</option>
                    <?php foreach ($staff_list as $s): ?>
                        <option value="<?= $s['staff_id'] ?>">
                            <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?> (<?= $s['role'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">🚚 Sevkiyat Oluştur</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Existing Shipments -->
    <div class="card">
        <div class="card-header">Mevcut Sevkiyatlar</div>
        <?php if (empty($shipments)): ?>
            <p class="text-muted">Henüz sevkiyat bulunmuyor.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Sevkiyat #</th>
                        <th>Sipariş #</th>
                        <th>Eczane</th>
                        <th>Araç</th>
                        <th>Personel</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shipments as $s): ?>
                    <tr>
                        <td><strong>#<?= $s['shipment_id'] ?></strong></td>
                        <td>#<?= $s['order_id'] ?></td>
                        <td><?= htmlspecialchars($s['pharmacy_name']) ?></td>
                        <td><?= htmlspecialchars($s['plate_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($s['staff_name'] ?? '-') ?></td>
                        <td>
                            <span class="badge badge-<?= $s['shipment_status'] ?>">
                                <?= shipmentStatusLabel($s['shipment_status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= date('d.m.Y H:i', strtotime($s['shipment_date'])) ?>
                            <?php if ($s['delivery_date']): ?>
                                <br><small class="text-muted">Teslim: <?= date('d.m.Y H:i', strtotime($s['delivery_date'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['shipment_status'] === 'preparing'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="shipment_id" value="<?= $s['shipment_id'] ?>">
                                    <button type="submit" name="new_status" value="in_transit" class="btn btn-warning btn-sm">🚛 Yola Çıkar</button>
                                </form>
                            <?php elseif ($s['shipment_status'] === 'in_transit'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="shipment_id" value="<?= $s['shipment_id'] ?>">
                                    <button type="submit" name="new_status" value="delivered" class="btn btn-success btn-sm">✓ Teslim Et</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Tamamlandı</span>
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
