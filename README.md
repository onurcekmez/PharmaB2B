# Pharma B2B - Eczane Tedarik Zinciri Platformu

Eczanelerin merkezi depodan ilaç siparişi verebildiği, stokları görüntüleyebildiği ve sevkiyat süreçlerini takip edebildiği kapsamlı bir B2B (Business-to-Business) web uygulamasıdır. 

Bu proje, **CPE210 Veritabanı Sistemleri** ve **CPE212 İnternet Tabanlı Programlama** dersleri kapsamındaki proje gereksinimlerini karşılamak üzere geliştirilmiştir.

## 🚀 Proje Hakkında

Pharma B2B, eczaneler ile ecza depoları arasındaki sipariş ve tedarik sürecini dijitalleştirir. Proje iki ana modüle ayrılmıştır: Eczane Arayüzü (Müşteri) ve Depo/Admin Arayüzü (Yönetici).

### Temel İşlevler:
- **Eczaneler:** İlaç kataloğunu görüntüleme, stok durumuna göre sipariş oluşturma, geçmiş siparişleri ve sevkiyat durumunu takip etme.
- **Depo Görevlileri:** Gelen siparişleri onaylama/reddetme, stok yönetimi yapma, siparişleri araçlara ve şoförlere atayarak sevkiyat planlama.
- **Yöneticiler (Admin):** Sistemdeki tüm kullanıcıları (eczane, depo görevlisi) ve ilaç stoklarını yönetme.

---

## 📚 Ders Gereksinimleri ve Uygulama Detayları

Proje, ilgili derslerin gereksinimlerini aşağıdaki şekillerde karşılamaktadır:

### 1. İnternet Tabanlı Programlama Gereksinimleri

#### a. Kullanıcı Kimlik Doğrulaması
- **JS Doğrulaması:** Frontend tarafında form gönderilmeden önce boş alan, e-posta formatı ve şifre uzunluğu kontrolleri `js/app.js` üzerinden yapılmaktadır.
- **PHP Doğrulaması:** Backend tarafında `login.php` ve `includes/auth.php` ile güvenli session (oturum) yönetimi ve veri validasyonu sağlanmaktadır.
- **Şifre Güvenliği:** Kullanıcı şifreleri veritabanına düz metin yerine **SHA-256** hash algoritması kullanılarak şifrelenip kaydedilmektedir.
- **MySQL Sorgulama:** Kimlik doğrulama süreçleri PDO kullanılarak güvenli (SQL Injection önlemli) MySQL sorguları ile gerçekleştirilmektedir.

#### b. Ana İşlevsellik (Sipariş Yönetimi)
Projenin temel süreci "Eczane İlaç Siparişi" üzerinedir.
- **PHP (CRUD):** Sipariş oluşturma (`create_order.php`), düzenleme (`edit_order.php`) ve listeleme (`my_orders.php`) işlemleri PHP ile geliştirilmiştir. Depo tarafında ise sipariş onay/red süreçleri (`manage_orders.php`) bulunmaktadır.
- **JavaScript:** Sayfa yenilenmeden ilaç arama, sepet tutarı hesaplama ve form doğrulama işlemleri JS ile yapılmıştır.
- **CSS:** Arayüz modern, responsive ve kullanıcı dostu olacak şekilde `css/style.css` ile tasarlanmıştır.
- **MySQL:** Sipariş verileri, ilaç stokları ve kullanıcı bilgileri ilişkisel veritabanında tutulmaktadır.

#### c. AJAX Entegrasyonu
- Fetch API kullanılarak asenkron veri çekme işlemleri projeye entegre edilmiştir.
- `ajax/search_medicines.php` ile anlık ilaç araması yapılmaktadır.
- `ajax/order_status.php` ve `ajax/filter_stock.php` ile sayfa yenilenmeden veri filtreleme ve getirme sağlanmıştır.

### 2. Veritabanı Sistemleri Gereksinimleri

#### a. Veritabanı Tasarımı (ERD) ve Varlıklar
Veritabanı en az 7 varlık içerecek şekilde, **Üsttip-Alttip** (Supertype/Subtype) yapısı kullanılarak Oracle notasyonuna uygun olarak tasarlanmıştır.

1. **USERS (Üsttip):** Tüm sistem kullanıcılarının temel bilgileri. (Alttipleri: Pharmacy, Admin)
2. **PHARMACIES (Alttip):** Eczanelere özel bilgiler. (Users tablosu ile ilişkili)
3. **STAFF (Üsttip):** Çalışan bilgileri. (Alttipleri: Driver, Warehouse_Employee)
4. **MEDICINES:** İlaç katalog ve stok bilgileri.
5. **ORDERS:** Eczanelerin verdiği siparişlerin başlık bilgileri.
6. **ORDER_ITEMS:** Siparişlerin içerdiği ilaçlar ve adetleri (Orders ve Medicines arasında köprü varlık).
7. **VEHICLES:** Sevkiyatta kullanılan araç bilgileri.
8. **SHIPMENTS:** Siparişlerin sevkiyat atamaları ve durum takibi.

#### b. SQL DDL ve Fiziksel Veritabanı
- `sql/schema.sql` dosyasında veritabanı tablolarını oluşturan tüm DDL (CREATE TABLE, ALTER vb.) komutları yer almaktadır. Kısıtlamalar (Constraints), Primary Key ve Foreign Key ilişkileri (ON DELETE CASCADE vb.) tanımlanmıştır.

#### c. SQL DML İfadeleri
`sql/queries.sql` dosyası içerisinde projenin raporlama ve analiz ihtiyaçları için yazılmış örnek DML sorguları bulunmaktadır:
- **Alt Sorgu (Subquery):** Ortalama stok miktarının altında kalan ilaçları listeleyen sorgu.
- **Join:** Siparişleri eczane bilgileri ile birleştirerek listeleyen sorgu.
- **Group By:** İlaç kategorilerine göre toplam satış gelirini hesaplayan sorgu.
- **Tarih Fonksiyonu:** İçinde bulunulan ay oluşturulan siparişleri filtreleyen sorgu (`MONTH()`, `YEAR()`).
- **Karakter Fonksiyonu:** Eczane isimlerini büyük harfle yazdıran ve adres bilgilerini birleştiren sorgu (`UPPER()`, `CONCAT()`).

---

## 🛠️ Kurulum ve Çalıştırma Yönergeleri

Projeyi yerel ortamınızda (XAMPP / WAMP / MAMP) çalıştırmak için aşağıdaki adımları izleyin.

### 1. Dosyaların Hazırlanması
Projeyi klonlayın veya zip dosyasından çıkarıp lokal sunucunuzun root dizinine (`htdocs` veya `www`) kopyalayın.

**XAMPP (Windows) için:**
```bash
C:\xampp\htdocs\pharma_b2b
```

**MAMP (macOS) için:**
```bash
/Applications/MAMP/htdocs/pharma_b2b
```
*(MAMP kullanıyorsanız MySQL ve Apache servislerinin yeşil yandığından emin olun. Gerekirse URL'lerinizi `http://localhost:8888/...` şeklinde güncelleyin.)*

### 2. Veritabanının Kurulması
1. Tarayıcınızda `http://localhost/phpmyadmin` adresini açın.
2. Üst menüden **SQL** sekmesine tıklayın.
3. Sırasıyla `sql/schema.sql` ve ardından örnek veriler için `sql/seed.sql` dosyalarının içeriğini yapıştırıp çalıştırın (`Git / Go` butonuna basın).

*(Not: `includes/db.php` dosyası veritabanı bağlantı ayarlarını içerir. Standart XAMPP/MAMP yapılandırmasında değişiklik yapmanıza gerek yoktur.)*

### 3. Sisteme Giriş
Tarayıcınızdan uygulamayı başlatın:
`http://localhost/pharma_b2b/login.php`

**Test Kullanıcı Hesapları:**

| Rol | Kullanıcı Adı | Şifre |
| :--- | :--- | :--- |
| Eczane | `eczane_ayse` | `123456` |
| Depo Görevlisi | `depo_ali` | `123456` |
| Admin | `admin` | `123456` |

---

## 📂 Proje Dizin Yapısı

```text
/pharma_b2b
├── /ajax                # AJAX asenkron veri işleme dosyaları (İlaç arama vb.)
├── /css                 # Özel stil dosyaları (style.css)
├── /includes            # Ortak dosyalar (db.php, auth.php, navbar.php)
├── /js                  # Form doğrulamaları ve Fetch API işlemleri (app.js)
├── /sql                 # DDL, DML ve Örnek Veri (Seed) scriptleri
├── /screenshots         # Dokümantasyon için arayüz ekran görüntüleri
├── index.php            # Sisteme giriş yönlendirmesi
├── login.php            # SHA destekli kimlik doğrulama sayfası
├── dashboard.php        # Eczane / Depo ana paneli
├── create_order.php     # Sipariş oluşturma ve sepet yönetimi
├── medicines.php        # İlaç stok ve katalog sayfası
└── manage_*.php         # Depo modülleri (Sipariş, Sevkiyat, Stok yönetimi)
```

## 📝 Teslimat Notları
- **Ham Kaynak Kod:** Tüm `.php`, `.js`, `.css` ve `.sql` dosyaları OYS sistemine yüklenmek üzere klasörlenmiştir.
- **Proje Raporu:** `sql/` dizinindeki sorgular, ekran görüntüleri ve kod açıklamaları ile Word/PDF dosyasına dökülerek Turnitin'e yüklenmeye hazırdır.
- **Sunum:** Kod üzerinde istenilen değişiklikleri anlık yapabilecek esneklikte modüler bir mimari kurulmuştur.
