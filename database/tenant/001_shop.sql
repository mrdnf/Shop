-- Schema for ONE shop. Every shop gets its own copy of this database.

CREATE TABLE settings (
    k VARCHAR(100) NOT NULL PRIMARY KEY,
    v MEDIUMTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff','driver','customer') NOT NULL DEFAULT 'customer',
    status ENUM('active','blocked') NOT NULL DEFAULT 'active',
    locale VARCHAR(5) NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(60) NULL,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    country VARCHAR(80) NULL,
    city VARCHAR(80) NOT NULL,
    area VARCHAR(120) NULL,
    street VARCHAR(190) NOT NULL,
    building VARCHAR(120) NULL,
    notes VARCHAR(255) NULL,
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    name_en VARCHAR(120) NOT NULL,
    name_ar VARCHAR(120) NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    icon VARCHAR(40) NULL,
    image VARCHAR(255) NULL,
    sort INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    name_en VARCHAR(190) NOT NULL,
    name_ar VARCHAR(190) NULL,
    slug VARCHAR(210) NOT NULL UNIQUE,
    description_en TEXT NULL,
    description_ar TEXT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    compare_price DECIMAL(12,2) NULL,
    cost DECIMAL(12,2) NULL,
    stock INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 5,
    track_stock TINYINT(1) NOT NULL DEFAULT 1,
    stock_source ENUM('auto','local') NOT NULL DEFAULT 'auto',
    stock_synced_at DATETIME NULL,
    weight DECIMAL(10,3) NULL,
    image VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_products_category (category_id),
    KEY idx_products_active (is_active, is_featured),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    change_qty INT NOT NULL,
    balance INT NOT NULL,
    reason VARCHAR(190) NULL,
    source VARCHAR(40) NOT NULL DEFAULT 'manual',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_inventory_logs_product (product_id),
    CONSTRAINT fk_inventory_logs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_sync_runs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(40) NOT NULL,
    trigger_type VARCHAR(20) NOT NULL DEFAULT 'manual',
    status ENUM('running','success','error') NOT NULL DEFAULT 'running',
    items_received INT NOT NULL DEFAULT 0,
    items_updated INT NOT NULL DEFAULT 0,
    message TEXT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipping_zones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_en VARCHAR(120) NOT NULL,
    name_ar VARCHAR(120) NULL,
    cities TEXT NULL,
    rate DECIMAL(10,2) NOT NULL DEFAULT 0,
    free_over DECIMAL(10,2) NULL,
    eta_min_days INT NOT NULL DEFAULT 1,
    eta_max_days INT NOT NULL DEFAULT 3,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(30) NOT NULL UNIQUE,
    user_id INT UNSIGNED NULL,
    customer_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(40) NOT NULL,
    country VARCHAR(80) NULL,
    city VARCHAR(80) NOT NULL,
    area VARCHAR(120) NULL,
    street VARCHAR(190) NOT NULL,
    building VARCHAR(120) NULL,
    address_notes VARCHAR(255) NULL,
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    zone_id INT UNSIGNED NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    shipping DECIMAL(12,2) NOT NULL DEFAULT 0,
    tax DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency VARCHAR(10) NOT NULL DEFAULT 'USD',
    payment_method VARCHAR(30) NOT NULL DEFAULT 'cod',
    payment_status ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    locale VARCHAR(5) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_orders_status (status),
    KEY idx_orders_created (created_at),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_zone FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    sku VARCHAR(100) NOT NULL,
    name VARCHAR(190) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    qty INT NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL,
    note VARCHAR(255) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE couriers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    driver VARCHAR(30) NOT NULL DEFAULT 'inhouse',
    config TEXT NULL,
    tracking_url_template VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE shipments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    courier_id INT UNSIGNED NULL,
    driver_id INT UNSIGNED NULL,
    tracking_number VARCHAR(80) NOT NULL UNIQUE,
    external_id VARCHAR(120) NULL,
    label_url VARCHAR(255) NULL,
    status ENUM('pending','ready','picked_up','in_transit','out_for_delivery','delivered','failed','returned','cancelled') NOT NULL DEFAULT 'pending',
    last_lat DECIMAL(10,7) NULL,
    last_lng DECIMAL(10,7) NULL,
    last_location_at DATETIME NULL,
    eta DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    delivered_at DATETIME NULL,
    KEY idx_shipments_status (status),
    KEY idx_shipments_driver (driver_id),
    CONSTRAINT fk_shipments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_shipments_courier FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE SET NULL,
    CONSTRAINT fk_shipments_driver FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tracking_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL,
    note VARCHAR(255) NULL,
    location VARCHAR(190) NULL,
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'system',
    external_ref VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tracking_shipment (shipment_id),
    CONSTRAINT fk_tracking_shipment FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    key_prefix VARCHAR(16) NOT NULL,
    key_hash CHAR(64) NOT NULL UNIQUE,
    last_used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
