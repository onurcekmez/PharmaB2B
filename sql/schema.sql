-- =====================================================
-- Pharmacy Supply Chain B2B Platform
-- Database Schema (DDL)
-- CPE210 Database Systems
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS pharmacy_b2b
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pharmacy_b2b;

-- =====================================================
-- 1. USERS TABLE (Supertype)
-- Subtypes: pharmacy, warehouse, admin (via role column)
-- =====================================================
CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(64)  NOT NULL,           -- SHA-256 hash (64 hex chars)
    email         VARCHAR(100) NOT NULL UNIQUE,
    role          ENUM('pharmacy','warehouse','admin') NOT NULL DEFAULT 'pharmacy',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- 2. PHARMACIES TABLE (Subtype of Users - pharmacy role)
-- One pharmacy belongs to one user
-- =====================================================
CREATE TABLE pharmacies (
    pharmacy_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL UNIQUE,
    pharmacy_name  VARCHAR(100) NOT NULL,
    address        VARCHAR(255) NOT NULL,
    phone          VARCHAR(20)  NOT NULL,
    tax_number     VARCHAR(20)  NULL,
    license_number VARCHAR(30)  NULL,
    city           VARCHAR(50)  NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 3. MEDICINES TABLE
-- Medical products in the central warehouse
-- =====================================================
CREATE TABLE medicines (
    medicine_id    INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name  VARCHAR(100)   NOT NULL,
    category       VARCHAR(50)    NOT NULL,
    stock_quantity INT            NOT NULL DEFAULT 0,
    unit_price     DECIMAL(10,2)  NOT NULL,
    expiration_date DATE          NOT NULL,

    CONSTRAINT chk_stock CHECK (stock_quantity >= 0),
    CONSTRAINT chk_price CHECK (unit_price > 0)
) ENGINE=InnoDB;

-- =====================================================
-- 4. ORDERS TABLE
-- Pharmacy purchase orders
-- =====================================================
CREATE TABLE orders (
    order_id     INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id  INT NOT NULL,
    order_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    order_status ENUM('pending','approved','rejected','shipped','delivered','cancelled')
                 NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    FOREIGN KEY (pharmacy_id) REFERENCES pharmacies(pharmacy_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 5. ORDER_ITEMS TABLE (Bridge entity)
-- Resolves many-to-many between Orders and Medicines
-- =====================================================
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT NOT NULL,
    medicine_id   INT NOT NULL,
    quantity      INT NOT NULL,
    unit_price    DECIMAL(10,2) NOT NULL,

    CONSTRAINT chk_qty CHECK (quantity > 0),

    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 6. VEHICLES TABLE
-- Delivery fleet vehicles
-- =====================================================
CREATE TABLE vehicles (
    vehicle_id   INT AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20)  NOT NULL UNIQUE,
    model        VARCHAR(50)  NOT NULL,
    capacity     INT          NOT NULL,
    vehicle_type ENUM('van','truck','motorcycle') NOT NULL DEFAULT 'van'
) ENGINE=InnoDB;

-- =====================================================
-- 7. STAFF TABLE (Supertype with role-based subtypes)
-- Subtypes: driver, warehouse_employee (via role column)
-- =====================================================
CREATE TABLE staff (
    staff_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT          NULL UNIQUE,            -- optional link to users table
    first_name VARCHAR(50)  NOT NULL,
    last_name  VARCHAR(50)  NOT NULL,
    role       ENUM('driver','warehouse_employee') NOT NULL DEFAULT 'warehouse_employee',
    phone      VARCHAR(20)  NOT NULL,
    salary     DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 8. SHIPMENTS TABLE
-- Delivery shipments for approved orders
-- =====================================================
CREATE TABLE shipments (
    shipment_id     INT AUTO_INCREMENT PRIMARY KEY,
    order_id        INT NOT NULL UNIQUE,            -- one shipment per order
    vehicle_id      INT NULL,
    staff_id        INT NULL,
    shipment_status ENUM('preparing','in_transit','delivered')
                    NOT NULL DEFAULT 'preparing',
    shipment_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    delivery_date   DATETIME NULL,

    FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;
