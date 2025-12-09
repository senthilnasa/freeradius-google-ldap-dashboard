-- =====================================================
-- Operators Table Initialization and Test Data
-- For RADIUS Reporting GUI
-- =====================================================

-- The operators table should already exist from 00-init-radius-schema.sql
-- This script ensures it exists and populates it with initial data

-- Create operators table if it doesn't exist (safety check)
CREATE TABLE IF NOT EXISTS operators (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL,
    password VARCHAR(255) NOT NULL,
    firstname VARCHAR(64) DEFAULT NULL,
    lastname VARCHAR(64) DEFAULT NULL,
    email VARCHAR(128) DEFAULT NULL,
    department VARCHAR(64) DEFAULT NULL,
    company VARCHAR(64) DEFAULT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    mobile VARCHAR(32) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(64) DEFAULT NULL,
    zip VARCHAR(16) DEFAULT NULL,
    notes TEXT,
    changeuserinfo TINYINT(1) DEFAULT 0,
    createusers TINYINT(1) DEFAULT 0,
    creationdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creationby VARCHAR(64) DEFAULT NULL,
    updatedate TIMESTAMP NULL DEFAULT NULL,
    updateby VARCHAR(64) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Default Admin Account
-- =====================================================
-- Username: admin
-- Password: password (bcrypt hash)
-- Role: Superadmin (createusers=1)
-- =====================================================

INSERT INTO operators (
    username,
    password,
    firstname,
    lastname,
    email,
    createusers,
    creationby
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System',
    'Administrator',
    'admin@example.com',
    1,
    'system'
) ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    email = VALUES(email);

-- =====================================================
-- Test Operator Accounts (for testing purposes)
-- =====================================================

-- Test Superadmin
-- Username: test_superadmin
-- Password: testpass123
-- Role: Superadmin
INSERT INTO operators (
    username,
    password,
    firstname,
    lastname,
    email,
    createusers,
    creationby
) VALUES (
    'test_superadmin',
    '$2y$10$hYx7qWZB0R8P4kpjH8xXXumVN7R2h0eYb9qH6Rk7xQ8jYk8qH6Rk7',
    'Test',
    'Superadmin',
    'test_superadmin@example.com',
    1,
    'system'
) ON DUPLICATE KEY UPDATE
    password = VALUES(password);

-- Test Network Admin
-- Username: test_networkadmin
-- Password: testpass123
-- Role: Network Admin (no createusers permission)
INSERT INTO operators (
    username,
    password,
    firstname,
    lastname,
    email,
    department,
    createusers,
    changeuserinfo,
    creationby
) VALUES (
    'test_networkadmin',
    '$2y$10$hYx7qWZB0R8P4kpjH8xXXumVN7R2h0eYb9qH6Rk7xQ8jYk8qH6Rk7',
    'Test',
    'NetworkAdmin',
    'test_networkadmin@example.com',
    'Network Operations',
    0,
    1,
    'system'
) ON DUPLICATE KEY UPDATE
    password = VALUES(password);

-- Test Helpdesk User
-- Username: test_helpdesk
-- Password: testpass123
-- Role: Helpdesk (no permissions)
INSERT INTO operators (
    username,
    password,
    firstname,
    lastname,
    email,
    department,
    createusers,
    changeuserinfo,
    creationby
) VALUES (
    'test_helpdesk',
    '$2y$10$hYx7qWZB0R8P4kpjH8xXXumVN7R2h0eYb9qH6Rk7xQ8jYk8qH6Rk7',
    'Test',
    'Helpdesk',
    'test_helpdesk@example.com',
    'IT Support',
    0,
    0,
    'system'
) ON DUPLICATE KEY UPDATE
    password = VALUES(password);

-- =====================================================
-- Display Created Accounts
-- =====================================================

SELECT
    id,
    username,
    CONCAT(firstname, ' ', lastname) AS fullname,
    email,
    CASE
        WHEN createusers = 1 THEN 'Superadmin'
        WHEN changeuserinfo = 1 THEN 'Network Admin'
        ELSE 'Helpdesk'
    END AS role,
    creationdate
FROM operators
ORDER BY createusers DESC, changeuserinfo DESC;
