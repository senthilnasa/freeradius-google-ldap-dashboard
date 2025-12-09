-- ============================================================================
-- FreeRADIUS Complete Database Schema - Auto-initialization
-- This file runs automatically when MySQL container first starts
-- Contains: All tables, views, stored procedures, and initial data
-- ============================================================================

-- Drop and recreate database for clean installation
DROP DATABASE IF EXISTS radius;
CREATE DATABASE radius CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE radius;

-- ============================================================================
-- RADIUS CORE TABLES (FreeRADIUS Standard Schema)
-- ============================================================================

-- NAS (Network Access Servers) Table
CREATE TABLE nas (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nasname VARCHAR(128) NOT NULL,
    shortname VARCHAR(32),
    type VARCHAR(30) DEFAULT 'other',
    ports INT,
    secret VARCHAR(60) NOT NULL DEFAULT 'secret',
    server VARCHAR(64),
    community VARCHAR(50),
    description VARCHAR(200) DEFAULT 'RADIUS Client',
    INDEX nasname (nasname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Check Attributes (per-user authentication settings)
CREATE TABLE radcheck (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '==',
    value VARCHAR(253) NOT NULL DEFAULT '',
    INDEX username (username(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Reply Attributes (per-user authorization settings)
CREATE TABLE radreply (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '=',
    value VARCHAR(253) NOT NULL DEFAULT '',
    INDEX username (username(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group Check Attributes (group authentication settings)
CREATE TABLE radgroupcheck (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '==',
    value VARCHAR(253) NOT NULL DEFAULT '',
    INDEX groupname (groupname(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group Reply Attributes (group authorization settings)
CREATE TABLE radgroupreply (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '=',
    value VARCHAR(253) NOT NULL DEFAULT '',
    INDEX groupname (groupname(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User to Group Mapping
CREATE TABLE radusergroup (
    username VARCHAR(64) NOT NULL DEFAULT '',
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    priority INT NOT NULL DEFAULT 1,
    INDEX username (username(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Authentication Log with Enhanced Error and VLAN Tracking
CREATE TABLE radpostauth (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL DEFAULT '',
    pass VARCHAR(64) NOT NULL DEFAULT '',
    reply VARCHAR(32) NOT NULL DEFAULT '',
    reply_message TEXT COMMENT 'Detailed authentication result message',
    error_type VARCHAR(64) DEFAULT NULL COMMENT 'Categorized error: password_wrong, invalid_domain, ldap_error, etc.',
    vlan VARCHAR(16) DEFAULT NULL COMMENT 'Assigned VLAN ID from Tunnel-Private-Group-Id attribute',
    user_type VARCHAR(64) DEFAULT NULL COMMENT 'User type from domain config (Student-MBA, Staff, etc.)',
    authdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Local timestamp (IST or configured timezone)',
    authdate_utc TIMESTAMP NULL DEFAULT NULL COMMENT 'UTC timestamp for cross-timezone consistency',
    INDEX idx_username (username),
    INDEX idx_authdate (authdate),
    INDEX idx_reply (reply),
    INDEX idx_error_type (error_type),
    INDEX idx_vlan (vlan),
    INDEX idx_user_type (user_type),
    INDEX idx_username_date (username, authdate),
    INDEX idx_reply_date (reply, authdate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Accounting Table (Session tracking)
CREATE TABLE radacct (
    radacctid BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    acctsessionid VARCHAR(64) NOT NULL DEFAULT '',
    acctuniqueid VARCHAR(32) NOT NULL DEFAULT '',
    username VARCHAR(64) NOT NULL DEFAULT '',
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    realm VARCHAR(64) DEFAULT '',
    nasipaddress VARCHAR(15) NOT NULL DEFAULT '',
    nasportid VARCHAR(15),
    nasporttype VARCHAR(32),
    acctstarttime DATETIME NULL DEFAULT NULL,
    acctupdatetime DATETIME NULL DEFAULT NULL,
    acctstoptime DATETIME NULL DEFAULT NULL,
    acctinterval INT,
    acctsessiontime INT UNSIGNED,
    acctauthentic VARCHAR(32),
    connectinfo_start VARCHAR(50),
    connectinfo_stop VARCHAR(50),
    acctinputoctets BIGINT,
    acctoutputoctets BIGINT,
    calledstationid VARCHAR(50) NOT NULL DEFAULT '',
    callingstationid VARCHAR(50) NOT NULL DEFAULT '',
    acctterminatecause VARCHAR(32) NOT NULL DEFAULT '',
    servicetype VARCHAR(32),
    framedprotocol VARCHAR(32),
    framedipaddress VARCHAR(15) NOT NULL DEFAULT '',
    INDEX idx_username (username),
    INDEX idx_framedipaddress (framedipaddress),
    INDEX idx_acctsessionid (acctsessionid),
    INDEX idx_acctuniqueId (acctuniqueid),
    INDEX idx_acctstarttime (acctstarttime),
    INDEX idx_acctstoptime (acctstoptime),
    INDEX idx_nasipaddress (nasipaddress),
    INDEX idx_username_start (username, acctstarttime),
    INDEX idx_session_time (acctsessiontime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- MANAGEMENT TABLES
-- ============================================================================

-- Operators/Admin Users Table
CREATE TABLE operators (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(32) NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'MD5 (32 chars) or SHA-256 (64 chars)',
    firstname VARCHAR(50) DEFAULT '',
    lastname VARCHAR(50) DEFAULT '',
    title VARCHAR(32) DEFAULT '',
    department VARCHAR(32) DEFAULT '',
    company VARCHAR(32) DEFAULT '',
    phone1 VARCHAR(32) DEFAULT '',
    phone2 VARCHAR(32) DEFAULT '',
    email VARCHAR(128) DEFAULT NULL,
    email1 VARCHAR(32) DEFAULT '',
    email2 VARCHAR(32) DEFAULT '',
    messenger1 VARCHAR(32) DEFAULT '',
    messenger2 VARCHAR(32) DEFAULT '',
    notes VARCHAR(128) DEFAULT '',
    changeuserinfo INT DEFAULT 0,
    createusers INT DEFAULT 0 COMMENT '1 = Superadmin with user creation privileges',
    creationdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creationby VARCHAR(64) DEFAULT NULL,
    updatedate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updateby VARCHAR(64) DEFAULT NULL,
    must_change_password TINYINT(1) DEFAULT 0 COMMENT 'Force password change on next login',
    password_changed_at DATETIME DEFAULT NULL COMMENT 'Timestamp of last password change',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = Active, 0 = Disabled',
    UNIQUE KEY username (username),
    INDEX idx_is_active (is_active),
    INDEX idx_createusers (createusers)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Information Table (Extended user data for RADIUS users)
CREATE TABLE userinfo (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(128) NOT NULL,
    firstname VARCHAR(200) DEFAULT '',
    lastname VARCHAR(200) DEFAULT '',
    email VARCHAR(200) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    company VARCHAR(200) DEFAULT '',
    workphone VARCHAR(200) DEFAULT '',
    homephone VARCHAR(200) DEFAULT '',
    mobilephone VARCHAR(200) DEFAULT '',
    address VARCHAR(200) DEFAULT '',
    city VARCHAR(200) DEFAULT '',
    state VARCHAR(200) DEFAULT '',
    country VARCHAR(100) DEFAULT '',
    zip VARCHAR(200) DEFAULT '',
    notes TEXT,
    changeuserinfo VARCHAR(128) DEFAULT '0',
    portalloginpassword VARCHAR(128) DEFAULT '',
    enableportallogin INT DEFAULT 0,
    creationdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creationby VARCHAR(128) DEFAULT '',
    updatedate TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    updateby VARCHAR(128) DEFAULT '',
    UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- REPORTING AND ANALYTICS TABLES
-- ============================================================================

-- Daily Statistics Summary
CREATE TABLE daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL,
    total_authentications INT DEFAULT 0,
    successful_authentications INT DEFAULT 0,
    failed_authentications INT DEFAULT 0,
    unique_users INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY stat_date (stat_date),
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Authentication Error Summary (Daily breakdown by error type)
CREATE TABLE auth_error_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_date DATE NOT NULL,
    error_type VARCHAR(64) NOT NULL,
    error_count INT DEFAULT 0,
    affected_users INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY date_error (error_date, error_type),
    INDEX idx_error_date (error_date),
    INDEX idx_error_type (error_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NAS Statistics (Per-NAS performance metrics)
CREATE TABLE nas_statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nas_ip VARCHAR(15) NOT NULL,
    stat_date DATE NOT NULL,
    total_requests INT DEFAULT 0,
    successful_auths INT DEFAULT 0,
    failed_auths INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY nas_date (nas_ip, stat_date),
    INDEX idx_nas_ip (nas_ip),
    INDEX idx_stat_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Billing Plans Table (Optional - for future billing integration)
CREATE TABLE billing_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(64) NOT NULL,
    bandwidth_limit BIGINT COMMENT 'Monthly bandwidth limit in bytes',
    session_timeout INT COMMENT 'Maximum session duration in seconds',
    idle_timeout INT COMMENT 'Idle disconnect timeout in seconds',
    monthly_fee DECIMAL(10,2) COMMENT 'Monthly subscription fee',
    description TEXT,
    vlan_id VARCHAR(16) COMMENT 'Default VLAN assignment for this plan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE KEY plan_name (plan_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- VIEWS FOR REPORTING AND REAL-TIME MONITORING
-- ============================================================================

-- View: Active Sessions (Currently connected users)
CREATE OR REPLACE VIEW active_sessions AS
SELECT
    username,
    nasipaddress AS nas_ip,
    nasportid AS nas_port,
    framedipaddress AS user_ip,
    callingstationid AS mac_address,
    acctstarttime AS session_start,
    TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) AS session_duration_seconds,
    FLOOR(TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) / 3600) AS hours,
    FLOOR((TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) % 3600) / 60) AS minutes,
    acctinputoctets AS download_bytes,
    acctoutputoctets AS upload_bytes,
    (acctinputoctets + acctoutputoctets) AS total_bytes,
    ROUND((acctinputoctets + acctoutputoctets) / 1024 / 1024, 2) AS total_mb
FROM radacct
WHERE acctstoptime IS NULL
ORDER BY acctstarttime DESC;

-- View: Recent Failed Authentications
CREATE OR REPLACE VIEW recent_failed_auth AS
SELECT
    id,
    username,
    error_type,
    reply_message,
    vlan,
    authdate AS failed_at_local,
    authdate_utc AS failed_at_utc,
    CASE
        WHEN error_type = 'password_wrong' THEN 'Wrong Password'
        WHEN error_type = 'user_not_found' THEN 'User Not Found'
        WHEN error_type = 'invalid_domain' THEN 'Invalid Domain'
        WHEN error_type = 'ldap_connection_failed' THEN 'LDAP Connection Failed'
        WHEN error_type = 'ssl_certificate_error' THEN 'Certificate Error'
        ELSE 'Authentication Failed'
    END AS error_description
FROM radpostauth
WHERE reply != 'Access-Accept'
ORDER BY authdate DESC
LIMIT 100;

-- View: User Session Summary (Historical session statistics per user)
CREATE OR REPLACE VIEW user_session_summary AS
SELECT
    username,
    COUNT(*) AS total_sessions,
    SUM(acctsessiontime) AS total_time_seconds,
    ROUND(SUM(acctsessiontime) / 3600, 2) AS total_hours,
    SUM(acctinputoctets + acctoutputoctets) AS total_bytes,
    ROUND(SUM(acctinputoctets + acctoutputoctets) / 1024 / 1024, 2) AS total_mb,
    ROUND(SUM(acctinputoctets + acctoutputoctets) / 1024 / 1024 / 1024, 2) AS total_gb,
    MAX(acctstarttime) AS last_session,
    MIN(acctstarttime) AS first_session,
    AVG(acctsessiontime) AS avg_session_seconds
FROM radacct
WHERE acctstoptime IS NOT NULL
GROUP BY username;

-- View: User Bandwidth Today (Current day usage per user)
CREATE OR REPLACE VIEW user_bandwidth_today AS
SELECT
    username,
    SUM(acctinputoctets) AS download_bytes,
    SUM(acctoutputoctets) AS upload_bytes,
    SUM(acctinputoctets + acctoutputoctets) AS total_bytes,
    ROUND(SUM(acctinputoctets) / 1024 / 1024, 2) AS download_mb,
    ROUND(SUM(acctoutputoctets) / 1024 / 1024, 2) AS upload_mb,
    ROUND(SUM(acctinputoctets + acctoutputoctets) / 1024 / 1024, 2) AS total_mb,
    COUNT(*) AS session_count,
    COUNT(DISTINCT callingstationid) AS unique_devices
FROM radacct
WHERE DATE(acctstarttime) = CURDATE()
GROUP BY username
ORDER BY total_bytes DESC;

-- ============================================================================
-- STORED PROCEDURES FOR MAINTENANCE AND REPORTING
-- ============================================================================

-- Procedure: Clean up old accounting records
DELIMITER //
CREATE PROCEDURE cleanup_old_accounting(IN days_to_keep INT)
BEGIN
    DECLARE deleted_count INT;

    DELETE FROM radacct
    WHERE acctstoptime IS NOT NULL
      AND acctstoptime < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);

    SET deleted_count = ROW_COUNT();

    SELECT
        deleted_count AS records_deleted,
        days_to_keep AS retention_days,
        NOW() AS cleanup_timestamp;
END //
DELIMITER ;

-- Procedure: Update daily statistics
DELIMITER //
CREATE PROCEDURE update_daily_stats(IN target_date DATE)
BEGIN
    INSERT INTO daily_stats (
        stat_date,
        total_authentications,
        successful_authentications,
        failed_authentications,
        unique_users
    )
    SELECT
        DATE(authdate) AS stat_date,
        COUNT(*) AS total_authentications,
        SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) AS successful_authentications,
        SUM(CASE WHEN reply != 'Access-Accept' THEN 1 ELSE 0 END) AS failed_authentications,
        COUNT(DISTINCT username) AS unique_users
    FROM radpostauth
    WHERE DATE(authdate) = target_date
    GROUP BY DATE(authdate)
    ON DUPLICATE KEY UPDATE
        total_authentications = VALUES(total_authentications),
        successful_authentications = VALUES(successful_authentications),
        failed_authentications = VALUES(failed_authentications),
        unique_users = VALUES(unique_users);

    SELECT
        'Daily statistics updated' AS status,
        target_date AS date,
        NOW() AS update_timestamp;
END //
DELIMITER ;

-- Procedure: Generate auth error summary
DELIMITER //
CREATE PROCEDURE update_error_summary(IN target_date DATE)
BEGIN
    INSERT INTO auth_error_summary (
        error_date,
        error_type,
        error_count,
        affected_users
    )
    SELECT
        DATE(authdate) AS error_date,
        error_type,
        COUNT(*) AS error_count,
        COUNT(DISTINCT username) AS affected_users
    FROM radpostauth
    WHERE DATE(authdate) = target_date
      AND reply != 'Access-Accept'
      AND error_type IS NOT NULL
      AND error_type != ''
    GROUP BY DATE(authdate), error_type
    ON DUPLICATE KEY UPDATE
        error_count = VALUES(error_count),
        affected_users = VALUES(affected_users);

    SELECT
        'Error summary updated' AS status,
        target_date AS date,
        NOW() AS update_timestamp;
END //
DELIMITER ;

-- ============================================================================
-- INITIAL DATA
-- ============================================================================

-- Insert default admin user
-- Username: admin
-- Password: admin123
-- MD5 Hash: 0192023a7bbd73250516f069df18b500
INSERT INTO operators (
    username,
    password,
    firstname,
    lastname,
    email,
    createusers,
    is_active,
    must_change_password
)
VALUES (
    'admin',
    '0192023a7bbd73250516f069df18b500',
    'System',
    'Administrator',
    'admin@localhost',
    1,
    1,
    1
);

-- Insert default NAS entries for testing
INSERT INTO nas (nasname, shortname, type, ports, secret, description)
VALUES
    ('127.0.0.1', 'localhost', 'other', 1812, 'testing123', 'Local testing NAS (IPv4)'),
    ('::1', 'localhost-ipv6', 'other', 1812, 'testing123', 'Local testing NAS (IPv6)');

-- ============================================================================
-- GRANTS AND PERMISSIONS
-- ============================================================================

-- Note: User grants are handled by docker-entrypoint.sh
-- No additional grants needed here

-- ============================================================================
-- DATABASE INITIALIZATION COMPLETE
-- ============================================================================

-- Display initialization summary
SELECT '============================================' AS '';
SELECT 'FreeRADIUS Database Initialized Successfully' AS 'Status';
SELECT '============================================' AS '';
SELECT DATABASE() AS 'Database Name';
SELECT VERSION() AS 'MySQL Version';
SELECT COUNT(*) AS 'Total Tables' FROM information_schema.tables WHERE table_schema = 'radius' AND table_type = 'BASE TABLE';
SELECT COUNT(*) AS 'Total Views' FROM information_schema.views WHERE table_schema = 'radius';
SELECT COUNT(*) AS 'Total Procedures' FROM information_schema.routines WHERE routine_schema = 'radius' AND routine_type = 'PROCEDURE';
SELECT '============================================' AS '';
SELECT 'Default Admin Account Created:' AS '';
SELECT '  URL: http://localhost:8080' AS '';
SELECT '  Username: admin' AS '';
SELECT '  Password: admin123' AS '';
SELECT '  Role: Superadmin' AS '';
SELECT '============================================' AS '';
SELECT 'Database ready for FreeRADIUS operations' AS 'Ready';
SELECT '============================================' AS '';
