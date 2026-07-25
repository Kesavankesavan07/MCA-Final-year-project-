-- ==========================================================
-- AutoMaster Pro 2026
-- Premium Garage Workshop & Billing Management System
-- Database Part 1
-- Version 1.0
-- ==========================================================

DROP DATABASE IF EXISTS automasterpro;
CREATE DATABASE automasterpro
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE automasterpro;

-- ==========================================================
-- USERS
-- ==========================================================

CREATE TABLE users (

    user_id INT AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(50) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    full_name VARCHAR(100) NOT NULL,

    role ENUM('Administrator','Manager','Staff')
    DEFAULT 'Staff',

    phone VARCHAR(20),

    email VARCHAR(100),

    profile_image VARCHAR(255)
    DEFAULT 'default.png',

    status ENUM('Active','Inactive')
    DEFAULT 'Active',

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP

);

INSERT INTO users
(
username,
password,
full_name,
role,
phone,
email
)

VALUES
(
'kesavan',
'admin07',
'Kesavan',
'Administrator',
'9876543210',
'kesavan@automasterpro.com'
);

-- ==========================================================
-- CUSTOMERS
-- ==========================================================

CREATE TABLE customers (

    customer_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_name VARCHAR(120) NOT NULL,

    phone VARCHAR(20) UNIQUE,

    email VARCHAR(120),

    address TEXT,

    city VARCHAR(80),

    state VARCHAR(80),

    pincode VARCHAR(10),

    status ENUM('Active','Inactive') DEFAULT 'Active',

    vehicle_count INT DEFAULT 0,

    created_by INT,

    updated_by INT,

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_customer_created_by
    FOREIGN KEY(created_by)
    REFERENCES users(user_id)
    ON DELETE SET NULL,

    CONSTRAINT fk_customer_updated_by
    FOREIGN KEY(updated_by)
    REFERENCES users(user_id)
    ON DELETE SET NULL

);

-- ==========================================================
-- VEHICLES
-- ==========================================================

CREATE TABLE vehicles (

    vehicle_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    vehicle_number VARCHAR(30)
    UNIQUE,

    vehicle_name VARCHAR(80),

    brand VARCHAR(80),

    model VARCHAR(80),

    fuel_type ENUM(
    'Petrol',
    'Diesel',
    'CNG',
    'Electric',
    'Hybrid'
    ),

    manufacture_year YEAR,

    odometer INT DEFAULT 0,

    color VARCHAR(50),

    chassis_number VARCHAR(100),

    engine_number VARCHAR(100),

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_vehicle_customer
    FOREIGN KEY(customer_id)
    REFERENCES customers(customer_id)
    ON DELETE CASCADE

);

-- ==========================================================
-- MECHANICS
-- ==========================================================

CREATE TABLE mechanics (

    mechanic_id INT AUTO_INCREMENT PRIMARY KEY,

    mechanic_name VARCHAR(120),

    phone VARCHAR(20),

    specialization VARCHAR(120),

    experience INT,

    salary DECIMAL(10,2),

    status ENUM(
    'Available',
    'Busy',
    'Leave'
    )

    DEFAULT 'Available',

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP

);

INSERT INTO mechanics
(mechanic_name,phone,specialization,experience)

VALUES

('Arun','9000000001','Engine Repair',5),

('Vijay','9000000002','General Service',3),

('Kumar','9000000003','Electrical',7),

('Siva','9000000004','AC Service',4);

-- ==========================================================
-- SERVICE TYPES
-- ==========================================================

CREATE TABLE service_types (

    service_type_id INT AUTO_INCREMENT PRIMARY KEY,

    service_name VARCHAR(120),

    estimated_cost DECIMAL(10,2),

    estimated_hours INT

);

INSERT INTO service_types
(service_name,estimated_cost,estimated_hours)

VALUES

('General Service',1500,2),

('Oil Change',800,1),

('Brake Service',2500,3),

('Wheel Alignment',1200,1),

('AC Service',3000,4),

('Engine Repair',12000,12),

('Battery Replacement',4500,1),

('Clutch Replacement',8000,8);

-- ==========================================================
-- SERVICES / JOB CARDS
-- ==========================================================

CREATE TABLE services (

    service_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT,

    vehicle_id INT,

    mechanic_id INT,

    service_type_id INT,

    complaint TEXT,

    diagnosis TEXT,

    labour_charge DECIMAL(10,2)
    DEFAULT 0,

    service_status ENUM(

    'Pending',

    'In Progress',

    'Waiting Parts',

    'Completed',

    'Delivered'

    )

    DEFAULT 'Pending',

    service_date DATE,

    expected_delivery DATE,

    completed_date DATE,

    remarks TEXT,

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_service_customer
    FOREIGN KEY(customer_id)
    REFERENCES customers(customer_id),

    CONSTRAINT fk_service_vehicle
    FOREIGN KEY(vehicle_id)
    REFERENCES vehicles(vehicle_id),

    CONSTRAINT fk_service_mechanic
    FOREIGN KEY(mechanic_id)
    REFERENCES mechanics(mechanic_id),

    CONSTRAINT fk_service_type
    FOREIGN KEY(service_type_id)
    REFERENCES service_types(service_type_id)

);

-- ==========================================================
-- AutoMaster Pro 2026
-- Database Part 2
-- Inventory Management
-- ==========================================================

-- ==========================================================
-- BRANDS
-- ==========================================================

CREATE TABLE brands (

    brand_id INT AUTO_INCREMENT PRIMARY KEY,

    brand_name VARCHAR(100) NOT NULL UNIQUE,

    brand_description VARCHAR(255),

    status ENUM('Active','Inactive')
    DEFAULT 'Active',

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP

);

INSERT INTO brands (brand_name) VALUES

('Bosch'),
('Castrol'),
('Shell'),
('MRF'),
('Bridgestone'),
('Exide'),
('NGK'),
('TVS');


-- ==========================================================
-- CATEGORIES
-- ==========================================================

CREATE TABLE categories (

    category_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    category_description VARCHAR(255),

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP

);

INSERT INTO categories(category_name) VALUES

('Engine Parts'),
('Brake Parts'),
('Electrical'),
('Filters'),
('Lubricants'),
('Accessories'),
('Battery'),
('Tyres');


-- ==========================================================
-- SUPPLIERS
-- ==========================================================

CREATE TABLE suppliers (

    supplier_id INT AUTO_INCREMENT PRIMARY KEY,

    supplier_name VARCHAR(120) NOT NULL,

    contact_person VARCHAR(120),

    phone VARCHAR(20),

    email VARCHAR(120),

    gst_number VARCHAR(50),

    address TEXT,

    city VARCHAR(80),

    state VARCHAR(80),

    status ENUM('Active','Inactive')
    DEFAULT 'Active',

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP

);

INSERT INTO suppliers
(
supplier_name,
contact_person,
phone,
email,
city
)

VALUES

('ABC Auto Parts','Ramesh','9876543001','abc@parts.com','Coimbatore'),

('Speed Motors','Karthik','9876543002','speed@parts.com','Salem'),

('Premium Spares','Vignesh','9876543003','premium@parts.com','Erode');


-- ==========================================================
-- PRODUCTS / SPARE PARTS
-- ==========================================================

CREATE TABLE products (

    product_id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NOT NULL,

    brand_id INT NOT NULL,

    supplier_id INT,

    part_code VARCHAR(50) UNIQUE,

    barcode VARCHAR(100),

    part_name VARCHAR(150) NOT NULL,

    description TEXT,

    purchase_price DECIMAL(10,2) DEFAULT 0,

    selling_price DECIMAL(10,2) DEFAULT 0,

    stock_quantity INT DEFAULT 0,

    minimum_stock INT DEFAULT 10,

    unit VARCHAR(30)
    DEFAULT 'Piece',

    rack_location VARCHAR(50),

    product_image VARCHAR(255)
    DEFAULT 'default-part.png',

    status ENUM('Active','Inactive')
    DEFAULT 'Active',

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_category
    FOREIGN KEY(category_id)
    REFERENCES categories(category_id),

    CONSTRAINT fk_product_brand
    FOREIGN KEY(brand_id)
    REFERENCES brands(brand_id),

    CONSTRAINT fk_product_supplier
    FOREIGN KEY(supplier_id)
    REFERENCES suppliers(supplier_id)

);


-- ==========================================================
-- SAMPLE PRODUCTS
-- ==========================================================

INSERT INTO products
(
category_id,
brand_id,
supplier_id,
part_code,
part_name,
purchase_price,
selling_price,
stock_quantity,
minimum_stock,
rack_location
)

VALUES

(1,1,1,'ENG001','Engine Oil Filter',250,420,30,10,'A-01'),

(2,1,1,'BRK001','Brake Pad Set',650,980,15,10,'A-02'),

(4,3,2,'FLT001','Air Filter',180,320,45,15,'B-01'),

(5,2,2,'OIL001','Engine Oil 5W30',900,1300,20,10,'B-03'),

(7,6,3,'BAT001','Car Battery 12V',4200,5200,6,5,'C-01'),

(8,4,2,'TYR001','MRF Tyre',3200,3900,8,6,'C-03');


-- ==========================================================
-- PURCHASES
-- ==========================================================

CREATE TABLE purchases (

    purchase_id INT AUTO_INCREMENT PRIMARY KEY,

    supplier_id INT NOT NULL,

    invoice_number VARCHAR(100),

    purchase_date DATE,

    total_amount DECIMAL(12,2),

    payment_status ENUM(
    'Paid',
    'Pending'
    )

    DEFAULT 'Pending',

    remarks TEXT,

    created_at TIMESTAMP
    DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_purchase_supplier
    FOREIGN KEY(supplier_id)
    REFERENCES suppliers(supplier_id)

);


-- ==========================================================
-- PURCHASE ITEMS
-- ==========================================================

CREATE TABLE purchase_items (

    purchase_item_id INT AUTO_INCREMENT PRIMARY KEY,

    purchase_id INT,

    product_id INT,

    quantity INT,

    purchase_price DECIMAL(10,2),

    total DECIMAL(12,2),

    CONSTRAINT fk_purchaseitem_purchase
    FOREIGN KEY(purchase_id)
    REFERENCES purchases(purchase_id)
    ON DELETE CASCADE,

    CONSTRAINT fk_purchaseitem_product
    FOREIGN KEY(product_id)
    REFERENCES products(product_id)

);

-- ==========================================================
-- AutoMaster Pro 2026
-- Database Part 3
-- Billing, Dashboard & System Settings
-- ==========================================================

-- ==========================================================
-- INVOICES
-- ==========================================================

CREATE TABLE invoices (

    invoice_id INT AUTO_INCREMENT PRIMARY KEY,

    invoice_number VARCHAR(30) UNIQUE NOT NULL,

    customer_id INT NOT NULL,

    vehicle_id INT NOT NULL,

    service_id INT,

    invoice_date DATE NOT NULL,

    subtotal DECIMAL(12,2) DEFAULT 0,

    gst_percentage DECIMAL(5,2) DEFAULT 18,

    gst_amount DECIMAL(12,2) DEFAULT 0,

    discount DECIMAL(12,2) DEFAULT 0,

    grand_total DECIMAL(12,2) DEFAULT 0,

    payment_status ENUM(
        'Pending',
        'Partial',
        'Paid'
    ) DEFAULT 'Pending',

    remarks TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_invoice_customer
        FOREIGN KEY(customer_id)
        REFERENCES customers(customer_id),

    CONSTRAINT fk_invoice_vehicle
        FOREIGN KEY(vehicle_id)
        REFERENCES vehicles(vehicle_id),

    CONSTRAINT fk_invoice_service
        FOREIGN KEY(service_id)
        REFERENCES services(service_id)

);

-- ==========================================================
-- INVOICE ITEMS
-- ==========================================================

CREATE TABLE invoice_items (

    invoice_item_id INT AUTO_INCREMENT PRIMARY KEY,

    invoice_id INT NOT NULL,

    product_id INT,

    description VARCHAR(255),

    quantity INT DEFAULT 1,

    unit_price DECIMAL(10,2),

    total DECIMAL(12,2),

    CONSTRAINT fk_item_invoice
        FOREIGN KEY(invoice_id)
        REFERENCES invoices(invoice_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_item_product
        FOREIGN KEY(product_id)
        REFERENCES products(product_id)

);

-- ==========================================================
-- PAYMENTS
-- ==========================================================

CREATE TABLE payments (

    payment_id INT AUTO_INCREMENT PRIMARY KEY,

    invoice_id INT NOT NULL,

    payment_date DATE,

    payment_method ENUM(

        'Cash',
        'UPI',
        'Card',
        'Bank Transfer'

    ) DEFAULT 'Cash',

    amount DECIMAL(12,2),

    reference_number VARCHAR(100),

    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_payment_invoice
        FOREIGN KEY(invoice_id)
        REFERENCES invoices(invoice_id)
        ON DELETE CASCADE

);

-- ==========================================================
-- COMPANY SETTINGS
-- ==========================================================

CREATE TABLE company_settings (

    company_id INT AUTO_INCREMENT PRIMARY KEY,

    company_name VARCHAR(150),

    owner_name VARCHAR(120),

    address TEXT,

    city VARCHAR(100),

    state VARCHAR(100),

    pincode VARCHAR(20),

    phone VARCHAR(20),

    email VARCHAR(120),

    website VARCHAR(120),

    gst_number VARCHAR(50),

    logo VARCHAR(255),

    currency_symbol VARCHAR(10)
    DEFAULT '₹',

    timezone VARCHAR(80)
    DEFAULT 'Asia/Kolkata'

);

INSERT INTO company_settings(

company_name,

owner_name,

address,

city,

state,

phone,

email,

gst_number,

logo

)

VALUES(

'AutoMaster Pro',

'Kesavan',

'Main Road',

'Coimbatore',

'Tamil Nadu',

'9876543210',

'info@automasterpro.com',

'33ABCDE1234F1Z5',

'logo.png'

);

-- ==========================================================
-- NOTIFICATIONS
-- ==========================================================

CREATE TABLE notifications (

    notification_id INT AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(150),

    message TEXT,

    notification_type ENUM(

        'Info',

        'Success',

        'Warning',

        'Danger'

    ) DEFAULT 'Info',

    is_read TINYINT(1)

    DEFAULT 0,

    created_at TIMESTAMP

    DEFAULT CURRENT_TIMESTAMP

);

-- ==========================================================
-- DASHBOARD VIEW
-- ==========================================================

CREATE VIEW dashboard_summary AS

SELECT

    (SELECT COUNT(*) FROM customers) AS total_customers,

    (SELECT COUNT(*) FROM vehicles) AS total_vehicles,

    (SELECT COUNT(*) FROM invoices) AS total_invoices,

    (SELECT IFNULL(SUM(grand_total),0)

        FROM invoices

        WHERE invoice_date = CURDATE()

    ) AS today_revenue;

-- ==========================================================
-- INDEXES
-- ==========================================================

CREATE INDEX idx_customer_name
ON customers(customer_name);

CREATE INDEX idx_vehicle_number
ON vehicles(vehicle_number);

CREATE INDEX idx_service_status
ON services(service_status);

CREATE INDEX idx_invoice_date
ON invoices(invoice_date);

CREATE INDEX idx_product_name
ON products(part_name);

CREATE INDEX idx_stock
ON products(stock_quantity);

-- ==========================================================
-- SAMPLE NOTIFICATIONS
-- ==========================================================

INSERT INTO notifications(

title,

message,

notification_type

)

VALUES

('Welcome',

'Welcome to AutoMaster Pro 2026',

'Success'),

('Low Stock',

'Check inventory for low stock parts.',

'Warning'),

('Backup',

'Remember to backup your database weekly.',

'Info');

-- ==========================================================
-- SAMPLE CUSTOMERS, VEHICLES, SERVICES, INVOICES
-- ==========================================================

INSERT INTO customers (customer_id, customer_name, phone, email, address, city, state, pincode, status, vehicle_count, created_by, updated_by) VALUES
(1, 'Amit Verma', '9876543211', 'amit@gmail.com', 'A-12, Sector 4', 'Coimbatore', 'Tamil Nadu', '641001', 'Active', 1, 1, 1),
(2, 'Rahul Sharma', '9876543212', 'rahul@gmail.com', 'B-45, Phase 2', 'Coimbatore', 'Tamil Nadu', '641002', 'Active', 1, 1, 1),
(3, 'Vikas Singh', '9876543213', 'vikas@gmail.com', 'C-78, Cross Road', 'Salem', 'Tamil Nadu', '636001', 'Active', 1, 1, 1),
(4, 'Neha Patel', '9876543214', 'neha@gmail.com', 'D-90, Main St', 'Coimbatore', 'Tamil Nadu', '641003', 'Active', 1, 1, 1),
(5, 'Ravi Kumar', '9876543215', 'ravi@gmail.com', 'E-12, Gandhi Nagar', 'Erode', 'Tamil Nadu', '638001', 'Active', 1, 1, 1);

INSERT INTO vehicles (vehicle_id, customer_id, vehicle_number, vehicle_name, brand, model, fuel_type, manufacture_year, odometer, color, chassis_number, engine_number) VALUES
(1, 1, 'TN-37-BY-1234', 'BMW 3 Series', 'BMW', '320d', 'Diesel', 2022, 12500, 'White', 'CHAS1234567890', 'ENG1234567890'),
(2, 2, 'TN-37-CA-5678', 'Maruti Swift', 'Maruti Suzuki', 'Swift VXI', 'Petrol', 2020, 32000, 'Red', 'CHAS9876543210', 'ENG9876543210'),
(3, 3, 'TN-30-DF-4321', 'Hyundai Creta', 'Hyundai', 'Creta SX', 'Diesel', 2021, 24000, 'Black', 'CHAS4567890123', 'ENG4567890123'),
(4, 4, 'TN-37-EB-9999', 'Honda City', 'Honda', 'City ZX', 'Petrol', 2019, 45000, 'Silver', 'CHAS3210987654', 'ENG3210987654'),
(5, 5, 'TN-33-AA-1111', 'Tata Nexon', 'Tata', 'Nexon XZ+', 'Petrol', 2023, 8500, 'Blue', 'CHAS8901234567', 'ENG8901234567');

INSERT INTO services (service_id, customer_id, vehicle_id, mechanic_id, service_type_id, complaint, diagnosis, labour_charge, service_status, service_date, expected_delivery, completed_date, remarks) VALUES
(1, 1, 1, 1, 1, 'General servicing and checkup', 'All checks normal, changed engine oil and filters', 1500.00, 'Completed', '2026-06-25', '2026-06-25', '2026-06-25', 'Vehicle runs smoothly'),
(2, 2, 2, 2, 2, 'Engine oil change and filter check', 'Replaced oil and filter', 800.00, 'Completed', '2026-06-26', '2026-06-26', '2026-06-26', 'Serviced successfully'),
(3, 3, 3, 3, 3, 'Brake noise and general service', 'Brake pads worn out, need replacement', 2500.00, 'Pending', '2026-06-28', '2026-06-29', NULL, 'Awaiting approval for brake pad parts'),
(4, 4, 4, 4, 5, 'AC not cooling properly', 'AC gas leakage found and sealed, refilled gas', 3000.00, 'Completed', '2026-06-27', '2026-06-27', '2026-06-27', 'AC working fine now'),
(5, 5, 5, 1, 6, 'Engine noise and vibration', 'Engine mount replacement needed', 12000.00, 'Pending', '2026-06-28', '2026-06-30', NULL, 'Parts ordered');

INSERT INTO invoices (invoice_id, invoice_number, customer_id, vehicle_id, service_id, invoice_date, subtotal, gst_percentage, gst_amount, discount, grand_total, payment_status, remarks) VALUES
(1, 'INV-2026-1520', 1, 1, 1, '2026-06-25', 2000.00, 18.00, 360.00, 0.00, 2450.00, 'Paid', 'Thank you'),
(2, 'INV-2026-1519', 2, 2, 2, '2026-06-26', 1500.00, 18.00, 270.00, 0.00, 1850.00, 'Paid', 'General checkup oil change'),
(3, 'INV-2026-1518', 3, 3, 3, '2026-06-28', 2700.00, 18.00, 486.00, 0.00, 3200.00, 'Pending', 'Brake repairs'),
(4, 'INV-2026-1517', 4, 4, 4, '2026-06-27', 1400.00, 18.00, 252.00, 0.00, 1650.00, 'Paid', 'AC gas refills'),
(5, 'INV-2026-1516', 5, 5, 5, '2026-06-28', 2450.00, 18.00, 441.00, 0.00, 2900.00, 'Pending', 'Engine noise check');

INSERT INTO invoice_items (invoice_item_id, invoice_id, product_id, description, quantity, unit_price, total) VALUES
(1, 1, 1, 'Engine Oil Filter', 1, 420.00, 420.00),
(2, 1, 4, 'Engine Oil 5W30', 1, 1300.00, 1300.00),
(3, 2, 1, 'Engine Oil Filter', 1, 420.00, 420.00),
(4, 3, 2, 'Brake Pad Set', 1, 980.00, 980.00),
(5, 4, 3, 'Air Filter', 1, 320.00, 320.00),
(6, 5, 5, 'Car Battery 12V', 1, 5200.00, 5200.00);

UPDATE customers SET vehicle_count = 1 WHERE customer_id IN (1, 2, 3, 4, 5);

-- ==========================================================
-- END OF DATABASE
-- ==========================================================