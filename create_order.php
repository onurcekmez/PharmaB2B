<?php
/**
 * CREATE ORDER PAGE
 * 
 * Pharmacy users can select medicines and quantities to create an order.
 * 
 * Features:
 * - Displays available medicines with quantity inputs
 * - JavaScript calculates order total in real-time
 * - Server-side stock validation before insert
 * - Uses prepared statements for INSERT
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize('pharmacy');

$user_id = $_SESSION['user_id'];

// Get pharmacy_id
$stmt = $pdo->prepare("SELECT pharmacy_id FROM pharmacies WHERE user_id = ?");
$stmt->execute([$user_id]);
$pharmacy = $stmt->fetch();

if (!$pharmacy) {
    die('Eczane bilgisi bulunamadı.');
}
$pharmacy_id = $pharmacy['pharmacy_id'];

$success = '';
$error = '';

// -- Handle order submission --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = $_POST['items'] ?? [];
    $order_items = [];
    $total = 0;

    // Validate and collect order items
    foreach ($items as $medicine_id => $quantity) {
        $quantity = intval($quantity);
        if ($quantity <= 0) continue;

        // Verify medicine exists and has stock (prepared statement)
        $stmt = $pdo->prepare("SELECT medicine_id, medicine_name, stock_quantity, unit_price FROM medicines WHERE medicine_id = ?");
        $stmt->execute([$medicine_id]);
        $medicine = $stmt->fetch();

        if (!$medicine) {
            $error = "İlaç bulunamadı (ID: $medicine_id).";
            break;
        }

        if ($quantity > $medicine['stock_quantity']) {
            $error = htmlspecialchars($medicine['medicine_name']) . " için yeterli stok yok. Mevcut: " . $medicine['stock_quantity'];
            break;
        }

        $order_items[] = [
            'medicine_id' => $medicine['medicine_id'],
            'quantity'    => $quantity,
            'unit_price'  => $medicine['unit_price']
        ];
        $total += $quantity * $medicine['unit_price'];
    }

    if (empty($error) && empty($order_items)) {
        $error = 'Lütfen en az bir ilaç seçin.';
    }

    // Insert order if no errors
    if (empty($error) && !empty($order_items)) {
        try {
            $pdo->beginTransaction();

            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (pharmacy_id, order_status, total_amount) VALUES (?, 'pending', ?)");
            $stmt->execute([$pharmacy_id, $total]);
            $order_id = $pdo->lastInsertId();

            // Insert order items and update stock
            $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
            $stmt_stock = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?");

            foreach ($order_items as $item) {
                $stmt_item->execute([$order_id, $item['medicine_id'], $item['quantity'], $item['unit_price']]);
                $stmt_stock->execute([$item['quantity'], $item['medicine_id']]);
            }

            $pdo->commit();
            $success = "Sipariş #$order_id başarıyla oluşturuldu! Toplam: " . number_format($total, 2, ',', '.') . " ₺";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Sipariş oluşturulurken hata: ' . $e->getMessage();
        }
    }
}

// Get available medicines (stock > 0)
$medicines = $pdo->query("SELECT * FROM medicines WHERE stock_quantity > 0 ORDER BY category, medicine_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Oluştur - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Yeni Sipariş Oluştur</h1>
        <p>İlaçları seçin ve miktarları belirleyin</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateOrderForm()">
        <div class="card">
            <div class="card-header">İlaç Seçimi</div>
            <!-- İlaç adı arama filtresi -->
            <div class="table-filter">
                <input type="text" id="medicine-table-filter" placeholder="İlaç adı ile filtrele..." onkeyup="filterTable(this, 'medicine-select-tbody')">
            </div>
            <div class="table-wrapper">
                <table class="medicine-select-table">
                    <thead>
                        <tr>
                            <th>İlaç Adı</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Birim Fiyat (₺)</th>
                            <th>Miktar</th>
                        </tr>
                    </thead>
                    <tbody id="medicine-select-tbody">
                        <?php foreach ($medicines as $med): ?>
                        <tr>
                            <td><?= htmlspecialchars($med['medicine_name']) ?></td>
                            <td><?= htmlspecialchars($med['category']) ?></td>
                            <td><?= $med['stock_quantity'] ?></td>
                            <td><?= number_format($med['unit_price'], 2, ',', '.') ?></td>
                            <td>
                                <input type="number" 
                                       name="items[<?= $med['medicine_id'] ?>]" 
                                       class="form-control qty-input" 
                                       value="0" min="0" max="<?= $med['stock_quantity'] ?>"
                                       data-price="<?= $med['unit_price'] ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Order total (calculated via JavaScript) -->
            <div class="order-summary">
                Toplam Tutar: <span id="order-total">0.00 ₺</span>
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary">📦 Siparişi Oluştur</button>
            <a href="my_orders.php" class="btn btn-secondary">İptal</a>
        </div>
    </form>
</div>

<script src="js/app.js"></script>
</body>
</html>
