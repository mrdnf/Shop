-- Central SaaS database: knows about plans and shops (tenants), never about orders.

CREATE TABLE platform_settings (
    k VARCHAR(100) NOT NULL PRIMARY KEY,
    v MEDIUMTEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100) NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly',
    max_products INT UNSIGNED NULL,
    max_staff INT UNSIGNED NULL,
    features TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    domain VARCHAR(190) NULL UNIQUE,
    plan_id INT UNSIGNED NULL,
    status ENUM('active','trial','suspended') NOT NULL DEFAULT 'active',
    db_name VARCHAR(64) NOT NULL UNIQUE,
    db_user VARCHAR(64) NULL,
    db_pass TEXT NULL,
    owner_name VARCHAR(120) NULL,
    owner_email VARCHAR(190) NULL,
    trial_ends_at DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    KEY idx_tenants_plan (plan_id),
    CONSTRAINT fk_tenants_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
