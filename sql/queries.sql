-- =====================================================
-- Pharmacy Supply Chain B2B Platform
-- Required SQL DML Queries
-- CPE210 Database Systems
-- =====================================================

USE pharmacy_b2b;

-- =====================================================
-- 1. JOIN QUERY
-- List all orders with pharmacy name and city
-- Demonstrates INNER JOIN between orders and pharmacies
-- =====================================================
SELECT
    o.order_id,
    p.pharmacy_name,
    p.city,
    o.order_date,
    o.order_status,
    o.total_amount
FROM orders o
INNER JOIN pharmacies p ON o.pharmacy_id = p.pharmacy_id
ORDER BY o.order_date DESC;

-- =====================================================
-- 2. GROUP BY QUERY
-- Total revenue grouped by medicine category
-- Demonstrates GROUP BY with aggregate function SUM
-- =====================================================
SELECT
    m.category,
    COUNT(oi.order_item_id) AS total_items_sold,
    SUM(oi.quantity * oi.unit_price) AS total_revenue
FROM order_items oi
INNER JOIN medicines m ON oi.medicine_id = m.medicine_id
GROUP BY m.category
ORDER BY total_revenue DESC;

-- =====================================================
-- 3. SUBQUERY
-- Medicines with stock quantity below the average stock
-- Demonstrates a scalar subquery in WHERE clause
-- =====================================================
SELECT
    medicine_id,
    medicine_name,
    category,
    stock_quantity,
    (SELECT AVG(stock_quantity) FROM medicines) AS avg_stock
FROM medicines
WHERE stock_quantity < (SELECT AVG(stock_quantity) FROM medicines)
ORDER BY stock_quantity ASC;

-- =====================================================
-- 4. DATE FUNCTION QUERY
-- Orders created in the current month
-- Demonstrates MONTH() and YEAR() date functions
-- =====================================================
SELECT
    o.order_id,
    p.pharmacy_name,
    o.order_date,
    o.order_status,
    o.total_amount
FROM orders o
INNER JOIN pharmacies p ON o.pharmacy_id = p.pharmacy_id
WHERE MONTH(o.order_date) = MONTH(CURRENT_DATE())
  AND YEAR(o.order_date) = YEAR(CURRENT_DATE())
ORDER BY o.order_date DESC;

-- =====================================================
-- 5. CHARACTER FUNCTION QUERY
-- List pharmacy names in uppercase with formatted phone
-- Demonstrates UPPER() and CONCAT() character functions
-- =====================================================
SELECT
    pharmacy_id,
    UPPER(pharmacy_name) AS pharmacy_name_upper,
    CONCAT(UPPER(city), ' - ', address) AS full_address,
    phone
FROM pharmacies
ORDER BY pharmacy_name_upper;
