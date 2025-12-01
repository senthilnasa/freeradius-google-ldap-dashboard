-- ============================================================================
-- FreeRADIUS Complete Database Schema - MySQL 8.0 Optimized
-- ============================================================================
-- Single consolidated schema for FreeRADIUS with Google LDAP
-- Includes: Base RADIUS tables, DaloRADIUS extensions, Performance optimization
-- MySQL Version: 8.0+
-- Created: 2025-11-29
-- ============================================================================

USE radius;

-- Set session variables for optimal import
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- ============================================================================
-- CORE FREERADIUS TABLES
-- ============================================================================

-- Users table for authentication (used alongside LDAP)
CREATE TABLE IF NOT EXISTS radcheck (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '==',
    value VARCHAR(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_username (username(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- User reply attributes
CREATE TABLE IF NOT EXISTS radreply (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '=',
    value VARCHAR(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_username (username(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Group check attributes
CREATE TABLE IF NOT EXISTS radgroupcheck (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '==',
    value VARCHAR(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_groupname (groupname(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Group reply attributes
CREATE TABLE IF NOT EXISTS radgroupreply (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    attribute VARCHAR(64) NOT NULL DEFAULT '',
    op CHAR(2) NOT NULL DEFAULT '=',
    value VARCHAR(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_groupname (groupname(32))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- User to group membership
CREATE TABLE IF NOT EXISTS radusergroup (
    username VARCHAR(64) NOT NULL DEFAULT '',
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    priority INT(11) NOT NULL DEFAULT 1,
    KEY idx_username (username(32)),
    KEY idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Accounting table - optimized for high performance
CREATE TABLE IF NOT EXISTS radacct (
    radacctid BIGINT(21) NOT NULL AUTO_INCREMENT,
    acctsessionid VARCHAR(64) NOT NULL DEFAULT '',
    acctuniqueid VARCHAR(32) NOT NULL DEFAULT '',
    username VARCHAR(64) NOT NULL DEFAULT '',
    groupname VARCHAR(64) NOT NULL DEFAULT '',
    realm VARCHAR(64) DEFAULT '',
    nasipaddress VARCHAR(15) NOT NULL DEFAULT '',
    nasportid VARCHAR(15) DEFAULT NULL,
    nasporttype VARCHAR(32) DEFAULT NULL,
    acctstarttime DATETIME NULL DEFAULT NULL,
    acctupdatetime DATETIME NULL DEFAULT NULL,
    acctstoptime DATETIME NULL DEFAULT NULL,
    acctsessiontime INT(12) UNSIGNED DEFAULT NULL,
    acctinterval INT(12) DEFAULT NULL,
    acctauthentic VARCHAR(32) DEFAULT NULL,
    connectinfo_start VARCHAR(50) DEFAULT NULL,
    connectinfo_stop VARCHAR(50) DEFAULT NULL,
    acctinputoctets BIGINT(20) UNSIGNED DEFAULT NULL,
    acctoutputoctets BIGINT(20) UNSIGNED DEFAULT NULL,
    calledstationid VARCHAR(50) NOT NULL DEFAULT '',
    callingstationid VARCHAR(50) NOT NULL DEFAULT '',
    acctterminatecause VARCHAR(32) NOT NULL DEFAULT '',
    servicetype VARCHAR(32) DEFAULT NULL,
    framedprotocol VARCHAR(32) DEFAULT NULL,
    framedipaddress VARCHAR(15) NOT NULL DEFAULT '',
    framedipv6address VARCHAR(45) NOT NULL DEFAULT '',
    framedipv6prefix VARCHAR(45) NOT NULL DEFAULT '',
    framedinterfaceid VARCHAR(44) NOT NULL DEFAULT '',
    delegatedipv6prefix VARCHAR(45) NOT NULL DEFAULT '',
    PRIMARY KEY (radacctid),
    UNIQUE KEY idx_acctuniqueid (acctuniqueid),
    KEY idx_username (username),
    KEY idx_framedipaddress (framedipaddress),
    KEY idx_framedipv6address (framedipv6address),
    KEY idx_acctsessionid (acctsessionid),
    KEY idx_acctsessiontime (acctsessiontime),
    KEY idx_acctstarttime (acctstarttime),
    KEY idx_acctstoptime (acctstoptime),
    KEY idx_nasipaddress (nasipaddress),
    KEY idx_username_start (username, acctstarttime),
    KEY idx_nas_start (nasipaddress, acctstarttime),
    KEY idx_acctinterval (acctinterval)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Post-authentication logging
CREATE TABLE IF NOT EXISTS radpostauth (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(64) NOT NULL DEFAULT '',
    pass VARCHAR(64) NOT NULL DEFAULT '',
    reply VARCHAR(32) NOT NULL DEFAULT '',
    authdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_username (username),
    KEY idx_authdate (authdate),
    KEY idx_username_date (username, authdate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Network Access Servers (Access Points)
CREATE TABLE IF NOT EXISTS nas (
    id INT(10) NOT NULL AUTO_INCREMENT,
    nasname VARCHAR(128) NOT NULL,
    shortname VARCHAR(32) DEFAULT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'other',
    ports INT(5) DEFAULT NULL,
    secret VARCHAR(60) NOT NULL DEFAULT 'secret',
    server VARCHAR(64) DEFAULT NULL,
    community VARCHAR(50) DEFAULT NULL,
    description VARCHAR(200) DEFAULT 'RADIUS Client',
    PRIMARY KEY (id),
    KEY idx_nasname (nasname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- ============================================================================
-- DALORADIUS EXTENSION TABLES
-- ============================================================================

-- Operators/Administrators table
CREATE TABLE IF NOT EXISTS operators (
    id INT(32) NOT NULL AUTO_INCREMENT,
    username VARCHAR(32) NOT NULL DEFAULT '',
    password VARCHAR(32) NOT NULL DEFAULT '',
    firstname VARCHAR(32) NOT NULL DEFAULT '',
    lastname VARCHAR(32) NOT NULL DEFAULT '',
    title VARCHAR(32) NOT NULL DEFAULT '',
    department VARCHAR(32) NOT NULL DEFAULT '',
    company VARCHAR(32) NOT NULL DEFAULT '',
    phone1 VARCHAR(32) NOT NULL DEFAULT '',
    phone2 VARCHAR(32) NOT NULL DEFAULT '',
    email1 VARCHAR(32) NOT NULL DEFAULT '',
    email2 VARCHAR(32) NOT NULL DEFAULT '',
    messenger1 VARCHAR(32) NOT NULL DEFAULT '',
    messenger2 VARCHAR(32) NOT NULL DEFAULT '',
    notes VARCHAR(128) NOT NULL DEFAULT '',
    changeuserinfo INT(32) NOT NULL DEFAULT 0,
    createusers INT(32) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Billing plans
CREATE TABLE IF NOT EXISTS billing_plans (
    id INT(10) NOT NULL AUTO_INCREMENT,
    planname VARCHAR(128) DEFAULT NULL,
    plancost FLOAT DEFAULT NULL,
    plancurrency VARCHAR(32) DEFAULT NULL,
    plangroup VARCHAR(128) DEFAULT NULL,
    plantype VARCHAR(32) DEFAULT NULL,
    planTimeType VARCHAR(32) DEFAULT NULL,
    planTimeBank VARCHAR(32) DEFAULT NULL,
    planTimeRefillCost FLOAT DEFAULT NULL,
    planBandwidthUp VARCHAR(128) DEFAULT NULL,
    planBandwidthDown VARCHAR(128) DEFAULT NULL,
    planTrafficTotal VARCHAR(128) DEFAULT NULL,
    planRecurring VARCHAR(32) DEFAULT NULL,
    planRecurringPeriod VARCHAR(32) DEFAULT NULL,
    planActive VARCHAR(32) DEFAULT NULL,
    creationdate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creationby VARCHAR(128) NOT NULL DEFAULT '',
    updatedate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateby VARCHAR(128) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_planname (planname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User information table
CREATE TABLE IF NOT EXISTS userinfo (
    id INT(32) NOT NULL AUTO_INCREMENT,
    username VARCHAR(128) NOT NULL DEFAULT '',
    firstname VARCHAR(200) NOT NULL DEFAULT '',
    lastname VARCHAR(200) NOT NULL DEFAULT '',
    email VARCHAR(200) NOT NULL DEFAULT '',
    department VARCHAR(200) NOT NULL DEFAULT '',
    company VARCHAR(200) NOT NULL DEFAULT '',
    workphone VARCHAR(200) NOT NULL DEFAULT '',
    homephone VARCHAR(200) NOT NULL DEFAULT '',
    mobilephone VARCHAR(200) NOT NULL DEFAULT '',
    address VARCHAR(200) NOT NULL DEFAULT '',
    city VARCHAR(200) NOT NULL DEFAULT '',
    state VARCHAR(200) NOT NULL DEFAULT '',
    country VARCHAR(100) NOT NULL DEFAULT '',
    zip VARCHAR(200) NOT NULL DEFAULT '',
    notes VARCHAR(200) NOT NULL DEFAULT '',
    changeuserinfo VARCHAR(128) NOT NULL DEFAULT '',
    portalloginpassword VARCHAR(128) NOT NULL DEFAULT '',
    enableportallogin INT(32) DEFAULT 0,
    creationdate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    creationby VARCHAR(128) NOT NULL DEFAULT '',
    updatedate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updateby VARCHAR(128) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INITIAL DATA - Default Groups and Settings
-- ============================================================================

-- Default RADIUS groups
INSERT IGNORE INTO radgroupcheck (groupname, attribute, op, value) VALUES
('staff', 'Auth-Type', ':=', 'Accept'),
('student', 'Auth-Type', ':=', 'Accept'),
('guest', 'Auth-Type', ':=', 'Accept');

-- Default group attributes
INSERT IGNORE INTO radgroupreply (groupname, attribute, op, value) VALUES
('staff', 'Reply-Message', '=', 'Authenticated as staff'),
('staff', 'Session-Timeout', '=', '43200'),
('staff', 'Idle-Timeout', '=', '3600'),
('staff', 'Class', '=', '0x5354414646'),
('student', 'Reply-Message', '=', 'Authenticated as student'),
('student', 'Session-Timeout', '=', '28800'),
('student', 'Idle-Timeout', '=', '1800'),
('student', 'Class', '=', '0x53545544454e54'),
('guest', 'Reply-Message', '=', 'Authenticated as guest'),
('guest', 'Session-Timeout', '=', '14400'),
('guest', 'Idle-Timeout', '=', '900'),
('guest', 'Class', '=', '0x4755455354');

-- Sample billing plans
INSERT IGNORE INTO billing_plans (planname, plancost, plancurrency, plangroup, plantype, planTimeType, planTimeBank, planActive, creationdate, creationby) VALUES
('Staff Plan', 0.00, 'INR', 'staff', 'Unlimited', 'Daily', '86400', 'yes', NOW(), 'system'),
('Student Plan', 0.00, 'INR', 'student', 'Limited', 'Daily', '28800', 'yes', NOW(), 'system'),
('Guest Plan', 0.00, 'INR', 'guest', 'Limited', 'Daily', '14400', 'yes', NOW(), 'system');

-- Default administrator account (password = 'password' - SHA256 hash)
-- IMPORTANT: Change this password after first login!
INSERT IGNORE INTO operators (username, password, firstname, lastname, title, department, company) VALUES
('administrator', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'Default', 'Administrator', 'Administrator', 'IT', 'Krea University');

-- Sample Access Points (update with your actual AP IP addresses)
INSERT IGNORE INTO nas (nasname, shortname, type, ports, secret, server, community, description) VALUES
('10.10.200.5', 'AP-MB-1F', 'wireless', 50, 'testing123', 'freeradius-google-ldap', 'public', 'Main Block 1st Floor Access Point'),
('10.10.0.0/16', 'Campus-Net', 'wireless', 1000, 'testing123', 'freeradius-google-ldap', 'public', 'Campus WiFi Network Range');

-- ============================================================================
-- PERFORMANCE VIEWS
-- ============================================================================

-- Active sessions view
CREATE OR REPLACE VIEW active_sessions AS
SELECT
    username,
    nasipaddress,
    acctsessionid,
    acctstarttime,
    TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) AS session_duration,
    acctinputoctets,
    acctoutputoctets,
    (acctinputoctets + acctoutputoctets) AS total_bytes,
    calledstationid AS wifi_network,
    callingstationid AS device_mac,
    framedipaddress,
    framedipv6address
FROM radacct
WHERE acctstoptime IS NULL
ORDER BY acctstarttime DESC;

-- Daily statistics view
CREATE OR REPLACE VIEW daily_stats AS
SELECT
    DATE(authdate) AS date,
    COUNT(*) AS total_attempts,
    SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) AS successful_logins,
    SUM(CASE WHEN reply = 'Access-Reject' THEN 1 ELSE 0 END) AS failed_logins,
    COUNT(DISTINCT username) AS unique_users,
    ROUND(SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) AS success_rate
FROM radpostauth
WHERE authdate >= CURDATE()
GROUP BY DATE(authdate);

-- User bandwidth usage view
CREATE OR REPLACE VIEW user_bandwidth_today AS
SELECT
    username,
    COUNT(*) AS session_count,
    SUM(acctinputoctets) AS total_download_bytes,
    SUM(acctoutputoctets) AS total_upload_bytes,
    SUM(acctinputoctets + acctoutputoctets) AS total_bytes,
    ROUND(SUM(acctinputoctets + acctoutputoctets) / 1024 / 1024, 2) AS total_mb,
    ROUND(SUM(acctinputoctets + acctoutputoctets) / 1024 / 1024 / 1024, 2) AS total_gb,
    MAX(acctstoptime) AS last_session_end
FROM radacct
WHERE DATE(acctstarttime) = CURDATE()
GROUP BY username
ORDER BY total_bytes DESC;

-- ============================================================================
-- DATABASE MAINTENANCE
-- ============================================================================

-- Analyze tables for optimal query planning
ANALYZE TABLE radacct, radpostauth, radcheck, radreply, radusergroup;

-- Commit all changes
COMMIT;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Display initialization summary
SELECT
    'FreeRADIUS Database Initialized Successfully!' AS status,
    DATABASE() AS database_name,
    VERSION() AS mysql_version,
    NOW() AS timestamp;
