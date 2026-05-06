<?php
/**
 * EDIT ORDER PAGE
 * 
 * Pharmacy users can update quantities of pending orders.
 * Only pending orders can be edited.
 * Fiyat depo tarafindan belirlenir, eczane sadece miktar degistirir.
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize('pharmacy');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT pharmacy_id FROM pharmacies WHERE user_id = ?");
$stmt->execute([$user_id]);
$pharmacy_id = $stmt->fetch()['pharmacy_id'] ?? 0;

$order_id = intval($_GET['id'] ?? 0);
$success = '';
$error = '';

// Verify order belongs to this pharmacy and is pending
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND pharmacy_id = ? AND order_status = 'pending'");
$stmt->execute([$order_id, $pharmacy_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my_orders.php');
    exit;
}

// -- Handle update --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated_qtys = $_POST['quantities'] ?? [];
    $new_total = 0;
    $valid = true;

    try {
        $pdo->beginTransaction();

        foreach ($updated_qtys as $item_id => $new_qty) {
            $new_qty = intval($new_qty);
            $item_id = intval($item_id);

            $stmt = $pdo->prepare("SELECT oi.*, m.stock_quantity, m.medicine_name 
                                   FROM order_items oi 
                                   INNER JOIN medicines m ON oi.medicine_id = m.medicine_id 
                                   WHERE oi.order_item_id = ? AND oi.order_id = ?");
            $stmt->execute([$item_id, $order_id]);
            $item = $stmt->fetch();

            if (!$item) continue;

            $old_qty = $item['quantity'];
            $qty_diff = $new_qty - $old_qty;

            if ($new_qty < 0) {
                $error = 'Miktar negatif olamaz.';
                $valid = false;
                break;
            }

            if ($qty_diff > 0 && $qty_diff > $item['stock_quantity']) {
                $error = htmlspecialchars($item['medicine_name']) . ' icin yeterli stok yok. Mevcut: ' . $item['stock_quantity'];
                $valid = false;
                break;
            }

            if ($new_qty == 0) {
                $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_item_id = ?");
                $stmt->execute([$item_id]);
                $stmt = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity + ? WHERE medicine_id = ?");
                $stmt->execute([$old_qty, $item['medicine_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE order_items SET quantity = ? WHERE order_item_id = ?");
                $stmt->execute([$new_qty, $item_id]);

                $stmt = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?");
                $stmt->execute([$qty_diff, $item['medicine_id']]);

                $new_total += $new_qty * $item['unit_price'];
            }
        }

        if ($valid) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $remaining = $stmt->fetch()['cnt'];

            if ($remaining == 0) {
                $stmt = $pdo->prepare("UPDATE orders SET order_status = 'cancelled', total_amount = 0 WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $pdo->commit();
                header('Location: my_orders.php');
                exit;
            }

            $stmt = $pdo->prepare("UPDATE orders SET total_amount = ? WHERE order_id = ?");
            $stmt->execute([$new_total, $order_id]);

            $pdo->commit();
            $success = 'Siparis basariyla güncellendi!';

            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
        } else {
            $pdo->rollBack();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Güncelleme sirasinda hata: ' . $e->getMessage();
    }
}

// Get current order items (JOIN query)
$stmt = $pdo->prepare("
    SELECT oi.order_item_id, oi.quantity, oi.unit_price,
           m.medicine_id, m.medicine_name, m.category, m.stock_quantity
    FROM order_items oi
    INNER JOIN medicines m ON oi.medicine_id = m.medicine_id
    WHERE oi.order_id = ?
    ORDER BY m.medicine_name
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siparis Düzenle #<?= $order_id ?> - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Siparis #<?= $order_id ?> Düzenle</h1>
        <p>Miktarlari güncelleyebilirsiniz (fiyatlar depo tarafindan belirlenir)</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateOrderForm()">
        <div class="card">
            <div class="card-header">Siparis Kalemleri</div>
            <div class="table-wrapper">
                <table class="medicine-select-table">
                    <thead>
                        <tr>
                            <th>Ilac Adi</th>
                            <th>Kategori</th>
                            <th>Mevcut Stok</th>
                            <th>Birim Fiyat (₺)</th>
                            <th>Miktar</th>
                            <th>Ara Toplam</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                            <td><?= htmlspecialchars($item['category']) ?></td>
                            <td><?= $item['stock_quantity'] ?></td>
                            <td><?= number_format($item['unit_price'], 2, ',', '.') ?></td>
                            <td>
                                <input type="number" 
                                       name="quantities[<?= $item['order_item_id'] ?>]" 
                                       class="form-control qty-input" 
                                       value="<?= $item['quantity'] ?>" 
                                       min="0" 
                                       max="<?= $item['stock_quantity'] + $item['quantity'] ?>"
                                       data-price="<?= $item['unit_price'] ?>">
                            </td>
                            <td>
                                <?= number_format($item['quantity'] * $item['unit_price'], 2, ',', '.') ?> ₺
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="order-summary">
                Toplam Tutar: <span id="order-total"><?= number_format($order['total_amount'], 2, ',', '.') ?> ₺</span>
            </div>

            <p class="text-muted mt-1" style="font-size:0.85rem;">
                Miktari 0 yaparsaniz o kalem siparisten cikarilir.
            </p>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Degisiklikleri Kaydet</button>
            <a href="my_orders.php" class="btn btn-secondary">Geri Dön</a>
        </div>
    </form>
</div>

<script src="js/app.js"></script>
</body>
</html>
