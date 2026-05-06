<?php
/**
 * PHARMACY PROFILE PAGE
 * 
 * Eczane kullanicilari kendi profil bilgilerini görüntüleyebilir
 * ve güncelleyebilir: adres, telefon, vergi no, ruhsat no.
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';
authorize('pharmacy');

$user_id = $_SESSION['user_id'];

$success = '';
$error = '';

// Get pharmacy info
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.email 
    FROM pharmacies p 
    INNER JOIN users u ON p.user_id = u.user_id 
    WHERE p.user_id = ?
");
$stmt->execute([$user_id]);
$pharmacy = $stmt->fetch();

if (!$pharmacy) {
    die('Eczane bilgisi bulunamadi.');
}

// -- Handle profile update --
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pharmacy_name  = trim($_POST['pharmacy_name'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $tax_number     = trim($_POST['tax_number'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');

    // PHP validation
    if (empty($pharmacy_name)) {
        $error = 'Eczane adi bos birakilamaz.';
    } elseif (empty($address)) {
        $error = 'Adres bos birakilamaz.';
    } elseif (empty($phone)) {
        $error = 'Telefon numarasi bos birakilamaz.';
    } elseif (empty($city)) {
        $error = 'Sehir bos birakilamaz.';
    } else {
        // Update with prepared statement
        $stmt = $pdo->prepare("
            UPDATE pharmacies 
            SET pharmacy_name = ?, address = ?, phone = ?, city = ?, 
                tax_number = ?, license_number = ?
            WHERE pharmacy_id = ?
        ");
        $stmt->execute([
            $pharmacy_name, $address, $phone, $city,
            $tax_number ?: null, $license_number ?: null,
            $pharmacy['pharmacy_id']
        ]);

        $success = 'Profil bilgileri güncellendi.';

        // Refresh data
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.email 
            FROM pharmacies p 
            INNER JOIN users u ON p.user_id = u.user_id 
            WHERE p.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $pharmacy = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - PharmaB2B</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="page-header">
        <h1>Eczane Profili</h1>
        <p>Eczane bilgilerinizi görüntüleyin ve güncelleyin</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Hesap Bilgileri (salt okunur) -->
    <div class="card mb-2">
        <div class="card-header">Hesap Bilgileri</div>
        <table>
            <tr>
                <td style="width:180px;font-weight:bold;">Kullanici Adi</td>
                <td><?= htmlspecialchars($pharmacy['username']) ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">E-posta</td>
                <td><?= htmlspecialchars($pharmacy['email']) ?></td>
            </tr>
        </table>
    </div>

    <!-- Eczane Bilgileri (düzenlenebilir) -->
    <form method="POST">
        <div class="card">
            <div class="card-header">Eczane Bilgileri</div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="pharmacy_name">Eczane Adi</label>
                    <input type="text" id="pharmacy_name" name="pharmacy_name" class="form-control" 
                           value="<?= htmlspecialchars($pharmacy['pharmacy_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="city">Sehir</label>
                    <input type="text" id="city" name="city" class="form-control"
                           value="<?= htmlspecialchars($pharmacy['city']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="address">Adres</label>
                <input type="text" id="address" name="address" class="form-control"
                       value="<?= htmlspecialchars($pharmacy['address']) ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="text" id="phone" name="phone" class="form-control"
                           value="<?= htmlspecialchars($pharmacy['phone']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="tax_number">Vergi Numarasi</label>
                    <input type="text" id="tax_number" name="tax_number" class="form-control"
                           value="<?= htmlspecialchars($pharmacy['tax_number'] ?? '') ?>"
                           placeholder="Opsiyonel">
                </div>
            </div>

            <div class="form-group">
                <label for="license_number">Eczane Ruhsat No</label>
                <input type="text" id="license_number" name="license_number" class="form-control"
                       value="<?= htmlspecialchars($pharmacy['license_number'] ?? '') ?>"
                       placeholder="Opsiyonel" style="max-width:300px;">
            </div>

            <div class="mt-2">
                <button type="submit" class="btn btn-primary">Bilgileri Kaydet</button>
            </div>
        </div>
    </form>
</div>

<script src="js/app.js"></script>
</body>
</html>
