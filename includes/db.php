<?php
/**
 * Database Connection (PDO)
 * 
 * Otomatik olarak XAMPP veya MAMP ortamini algilar.
 * Eger ikisi de calismiyorsa hata mesaji gösterir.
 */

// -- Ortam Algila: XAMPP mi MAMP mi? --
if (file_exists('/Applications/MAMP/tmp/mysql/mysql.sock')) {
    // MAMP (macOS)
    $dsn = "mysql:unix_socket=/Applications/MAMP/tmp/mysql/mysql.sock;dbname=pharmacy_b2b;charset=utf8mb4";
    $db_user = 'root';
    $db_pass = 'root';
} else {
    // XAMPP / Windows / Linux (varsayilan)
    $dsn = "mysql:host=localhost;port=3306;dbname=pharmacy_b2b;charset=utf8mb4";
    $db_user = 'root';
    $db_pass = '';  // XAMPP varsayilan: bos sifre
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    die('<div style="padding:2rem;text-align:center;font-family:sans-serif;">
        <h2>Veritabani Baglanti Hatasi</h2>
        <p>MySQL servisinin calistigindan ve <b>pharmacy_b2b</b> veritabaninin olusturuldugundan emin olun.</p>
        <p style="color:#999;font-size:0.85rem;">' . htmlspecialchars($e->getMessage()) . '</p>
    </div>');
}
