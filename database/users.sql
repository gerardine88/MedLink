-- =========================================
-- MedLink Users Table
-- Database: medlink
-- =========================================

CREATE DATABASE IF NOT EXISTS medlink;

USE medlink;

-- =========================================
-- DROP TABLE IF EXISTS
-- =========================================

DROP TABLE IF EXISTS users;

-- =========================================
-- CREATE USERS TABLE
-- =========================================

CREATE TABLE users (

    id INT AUTO_INCREMENT PRIMARY KEY,

    first_name VARCHAR(50) NOT NULL,

    last_name VARCHAR(50) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    password_hash VARCHAR(255) NOT NULL,

    phone VARCHAR(20) NOT NULL,

    gender ENUM(
        'Male',
        'Female',
        'Other'
    ) NOT NULL,

    user_role ENUM(
        'patient',
        'doctor',
        'receptionist',
        'admin',
        'staff'
    ) DEFAULT 'patient',

    status ENUM(
        'active',
        'inactive',
        'suspended'
    ) DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,

    last_login TIMESTAMP NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- INDEXES
-- =========================================

CREATE INDEX idx_email
ON users(email);

CREATE INDEX idx_user_role
ON users(user_role);

CREATE INDEX idx_status
ON users(status);

CREATE INDEX idx_created_at
ON users(created_at);

-- =========================================
-- SAMPLE USERS
-- Password: Admin123@
-- =========================================

INSERT INTO users (
    first_name,
    last_name,
    email,
    password_hash,
    phone,
    gender,
    user_role,
    status
)

VALUES

(
    'Admin',
    'User',
    'admin@medlink.com',
    '$2y$10$wH7Q6m8XkL7rY5A2k4b6M.Bk3rN8vP0hE6fG1sJ2lT9xQ4wR5yU6K',
    '+250788000001',
    'Male',
    'admin',
    'active'
),

(
    'John',
    'Doe',
    'john@example.com',
    '$2y$10$wH7Q6m8XkL7rY5A2k4b6M.Bk3rN8vP0hE6fG1sJ2lT9xQ4wR5yU6K',
    '+250788000002',
    'Male',
    'patient',
    'active'
),

(
    'Jane',
    'Smith',
    'jane@example.com',
    '$2y$10$wH7Q6m8XkL7rY5A2k4b6M.Bk3rN8vP0hE6fG1sJ2lT9xQ4wR5yU6K',
    '+250788000003',
    'Female',
    'patient',
    'active'
);

-- =========================================
-- CHECK USERS
-- =========================================

SELECT * FROM users;