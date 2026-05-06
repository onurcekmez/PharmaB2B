# PharmaB2B - Eczane Tedarik Zinciri B2B Platformu

Eczanelerin merkezi depodan ilac siparisi verebildigi ve sevkiyat süreclerini takip edebildigi bir B2B web uygulamasi.

> **Ders Projeleri:** CPE210 Veritabani Sistemleri + CPE212 Internet Tabanli Programlama

---

## Kurulum (XAMPP)

### 1. XAMPP Kurun ve Baslatin
- [XAMPP indir](https://www.apachefriends.org/)
- XAMPP Control Panel'den **Apache** ve **MySQL** servislerini baslatin

### 2. Proje Dosyalarini Kopyalayin
Bu klasörü XAMPP'in `htdocs` dizinine kopyalayin:
```
C:\xampp\htdocs\pharma_b2b\
```

### 3. Veritabanini Olusturun
1. Tarayicida `http://localhost/phpmyadmin` adresini acin
2. Üstte **"SQL"** sekmesine tiklayin
3. `sql/schema.sql` dosyasinin icerigini kopyalayip yapistirin → **Git** butonuna basin
4. Ardindan `sql/seed.sql` dosyasinin icerigini kopyalayip yapistirin → **Git** butonuna basin

### 4. Uygulamayi Acin
```
http://localhost/pharma_b2b/login.php
```

---

## Kurulum (MAMP - macOS)

1. MAMP'i baslatin (Apache + MySQL yesil yanmali)
2. Projeyi `/Applications/MAMP/htdocs/pharma_b2b/` altina kopyalayin
3. `http://localhost:8888/phpmyadmin` üzerinden SQL dosyalarini calistirin
4. `http://localhost:8888/pharma_b2b/login.php` adresini acin

> **Not:** `includes/db.php` dosyasi XAMPP ve MAMP ortamlarini otomatik algilar, ayar degistirmenize gerek yoktur.

---

## Demo Kullanicilar

| Kullanici Adi  | Sifre  | Rol       |
|----------------|--------|-----------|
| eczane_ayse    | 123456 | Eczane    |
| eczane_mehmet  | 123456 | Eczane    |
| depo_ali       | 123456 | Depo      |
| admin          | 123456 | Admin     |

---

## Proje Yapisi

```
/pharma_b2b
├── /css
│   └── style.css              # Stil dosyasi
├── /js
│   └── app.js                 # JavaScript (validasyon + AJAX)
├── /ajax
│   ├── search_medicines.php   # AJAX: Ilac arama
│   ├── filter_stock.php       # AJAX: Stok filtreleme
│   ├── order_status.php       # AJAX: Siparis durumu
│   └── pharmacy_info.php      # AJAX: Eczane bilgi popup
├── /includes
│   ├── db.php                 # Veritabani baglantisi (PDO)
│   ├── auth.php               # Oturum kontrol
│   └── navbar.php             # Navigasyon bileseni
├── /sql
│   ├── schema.sql             # DDL: Tablo yapilari
│   ├── seed.sql               # Örnek veriler
│   └── queries.sql            # SQL sorgu örnekleri
├── /screenshots               # Ekran görüntüleri
├── login.php                  # Giris sayfasi
├── logout.php                 # Cikis
├── dashboard.php              # Ana panel
├── medicines.php              # Ilac katalogu
├── create_order.php           # Siparis olusturma
├── edit_order.php             # Siparis düzenleme
├── my_orders.php              # Siparislerim
├── profile.php                # Eczane profili
├── manage_orders.php          # Siparis yönetimi (Depo)
├── manage_shipments.php       # Sevkiyat yönetimi (Depo)
├── manage_stock.php           # Stok yönetimi (Depo)
├── admin_panel.php            # Admin paneli
└── README.md
```

---

## Teknolojiler

| Katman     | Teknoloji          |
|------------|--------------------|
| Backend    | PHP 8+             |
| Frontend   | HTML5, CSS3, JS    |
| Veritabani | MySQL              |
| AJAX       | Fetch API          |
| Ortam      | XAMPP veya MAMP    |
