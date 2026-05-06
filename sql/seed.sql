-- =====================================================
-- Pharmacy Supply Chain B2B Platform
-- Sample Seed Data
-- =====================================================

USE pharmacy_b2b;

-- =====================================================
-- USERS (password = SHA-256 hash of '123456')
-- SHA-256 of '123456' = 8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92
-- =====================================================
INSERT INTO users (username, password_hash, email, role) VALUES
('eczane_ayse',   '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ayse@eczane.com',     'pharmacy'),
('eczane_mehmet', '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'mehmet@eczane.com',   'pharmacy'),
('depo_ali',      '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'ali@depo.com',        'warehouse'),
('admin',         '8d969eef6ecad3c29a3a629280e686cf0c3f5d5a86aff3ca12020c923adc6c92', 'admin@system.com',    'admin');

-- =====================================================
-- PHARMACIES
-- =====================================================
INSERT INTO pharmacies (user_id, pharmacy_name, address, phone, tax_number, license_number, city) VALUES
(1, 'Ayşe Eczanesi',   'Atatürk Cad. No:15',  '0532-111-2233', '1234567890', 'ECZ-2024-001', 'İstanbul'),
(2, 'Mehmet Eczanesi',  'Cumhuriyet Blv. No:8', '0533-444-5566', '9876543210', 'ECZ-2024-002', 'Ankara');

-- =====================================================
-- MEDICINES (diverse categories + expiration dates)
-- =====================================================
INSERT INTO medicines (medicine_name, category, stock_quantity, unit_price, expiration_date) VALUES
('Parol 500mg',          'Ağrı Kesici',     200, 25.50,  '2027-06-15'),
('Aspirin 100mg',        'Ağrı Kesici',     150, 18.00,  '2027-03-20'),
('Augmentin 1000mg',     'Antibiyotik',     80,  65.00,  '2026-12-01'),
('Amoksisilin 500mg',    'Antibiyotik',     120, 42.00,  '2027-01-10'),
('Ventolin İnhaler',     'Solunum',         60,  89.90,  '2027-09-30'),
('Nexium 40mg',          'Sindirim',        90,  55.00,  '2026-11-15'),
('Coraspin 100mg',       'Kardiyoloji',     300, 12.50,  '2028-02-28'),
('Euthyrox 50mcg',       'Hormon',          110, 35.00,  '2027-07-20'),
('Xanax 0.5mg',          'Psikiyatri',      40,  78.00,  '2026-10-05'),
('Majezik 100mg',        'Ağrı Kesici',     180, 30.00,  '2027-04-12'),
('Cipro 500mg',          'Antibiyotik',     70,  48.50,  '2026-08-25'),
('Losec 20mg',           'Sindirim',        95,  62.00,  '2027-05-18');

-- =====================================================
-- VEHICLES
-- =====================================================
INSERT INTO vehicles (plate_number, model, capacity, vehicle_type) VALUES
('34 ABC 001', 'Ford Transit',   500, 'van'),
('06 DEF 002', 'Mercedes Atego', 2000, 'truck'),
('35 GHI 003', 'Honda PCX',      50,  'motorcycle');

-- =====================================================
-- STAFF
-- =====================================================
INSERT INTO staff (user_id, first_name, last_name, role, phone, salary) VALUES
(3,    'Ali',     'Yılmaz',   'warehouse_employee', '0534-777-8899', 18000.00),
(NULL, 'Hasan',   'Demir',    'driver',             '0535-222-3344', 15000.00),
(NULL, 'Fatma',   'Kaya',     'driver',             '0536-555-6677', 15500.00);

-- =====================================================
-- ORDERS (various statuses for demonstration)
-- =====================================================
INSERT INTO orders (pharmacy_id, order_date, order_status, total_amount) VALUES
(1, '2026-05-01 10:30:00', 'delivered',  225.50),
(1, '2026-05-03 14:00:00', 'approved',   178.00),
(2, '2026-05-02 09:15:00', 'pending',    330.00),
(2, '2026-05-04 16:45:00', 'pending',    89.90),
(1, '2026-05-05 11:00:00', 'rejected',   120.00);

-- =====================================================
-- ORDER_ITEMS
-- =====================================================
INSERT INTO order_items (order_id, medicine_id, quantity, unit_price) VALUES
-- Order 1: Parol x5 + Aspirin x3
(1, 1, 5, 25.50),
(1, 2, 3, 18.00),
-- Order 2: Augmentin x2 + Amoksisilin x1
(2, 3, 2, 65.00),
(2, 4, 1, 42.00),
-- Order 3: Coraspin x10 + Nexium x3
(3, 7, 10, 12.50),
(3, 6, 3, 55.00),
-- Order 4: Ventolin x1
(4, 5, 1, 89.90),
-- Order 5: Majezik x4
(5, 10, 4, 30.00);

-- =====================================================
-- SHIPMENTS
-- =====================================================
INSERT INTO shipments (order_id, vehicle_id, staff_id, shipment_status, shipment_date, delivery_date) VALUES
(1, 1, 2, 'delivered',  '2026-05-01 15:00:00', '2026-05-02 09:00:00'),
(2, 2, 3, 'in_transit', '2026-05-04 08:00:00', NULL);
