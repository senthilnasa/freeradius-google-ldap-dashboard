-- Create views for enhanced reporting and queries
-- Author: RADIUS Dashboard Team
-- Created: 2024

USE radius;

-- ============================================================================
-- ACTIVE SESSIONS VIEW
-- ============================================================================
-- Provides a comprehensive view of all currently active user sessions
-- with calculated durations and data usage
-- ============================================================================

CREATE OR REPLACE VIEW active_sessions AS
SELECT
    ra.radacctid,
    ra.acctsessionid,
    ra.username,
    ra.nasipaddress,
    ra.nasportid,
    ra.framedipaddress,
    ra.callingstationid AS device_mac,
    ra.calledstationid AS wifi_network,
    ra.acctstarttime,
    ra.acctupdatetime,
    NULL AS acctstoptime,
    TIMESTAMPDIFF(SECOND, ra.acctstarttime, COALESCE(ra.acctupdatetime, NOW())) AS session_duration,
    ra.acctinputoctets,
    ra.acctoutputoctets,
    (ra.acctinputoctets + ra.acctoutputoctets) AS total_bytes,
    ra.acctterminatecause,
    n.shortname AS nas_name,
    n.description AS nas_description
FROM radacct ra
LEFT JOIN nas n ON ra.nasipaddress = n.nasname
WHERE ra.acctstoptime IS NULL
ORDER BY ra.acctstarttime DESC;

-- ============================================================================
-- DAILY STATS VIEW
-- ============================================================================
-- Provides daily aggregated statistics for authentication attempts
-- ============================================================================

CREATE OR REPLACE VIEW daily_stats AS
SELECT
    DATE(authdate) AS date,
    COUNT(*) AS total_attempts,
    SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) AS successful_logins,
    SUM(CASE WHEN reply != 'Access-Accept' THEN 1 ELSE 0 END) AS failed_logins,
    COUNT(DISTINCT username) AS unique_users,
    ROUND(
        (SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) / COUNT(*)) * 100,
        2
    ) AS success_rate
FROM radpostauth
GROUP BY DATE(authdate)
ORDER BY date DESC;

-- ============================================================================
-- USER SESSION SUMMARY VIEW
-- ============================================================================
-- Provides aggregated session data per user
-- ============================================================================

CREATE OR REPLACE VIEW user_session_summary AS
SELECT
    username,
    COUNT(*) AS total_sessions,
    SUM(acctsessiontime) AS total_session_time,
    SUM(acctinputoctets) AS total_download,
    SUM(acctoutputoctets) AS total_upload,
    SUM(acctinputoctets + acctoutputoctets) AS total_data,
    AVG(acctsessiontime) AS avg_session_time,
    MIN(acctstarttime) AS first_session,
    MAX(acctstarttime) AS last_session
FROM radacct
WHERE acctstoptime IS NOT NULL
GROUP BY username;

-- ============================================================================
-- RECENT FAILED AUTHENTICATIONS VIEW
-- ============================================================================
-- Shows recent failed authentication attempts with error details
-- ============================================================================

CREATE OR REPLACE VIEW recent_failed_auth AS
SELECT
    id,
    username,
    reply,
    reply_message,
    error_type,
    authdate,
    authdate_utc,
    CASE
        WHEN error_type = 'password_wrong' THEN 'Incorrect Password'
        WHEN error_type = 'user_not_found' THEN 'User Not Found'
        WHEN error_type = 'ldap_connection_failed' THEN 'LDAP Connection Failed'
        WHEN error_type = 'ssl_certificate_error' THEN 'SSL Certificate Error'
        WHEN error_type = 'invalid_domain' THEN 'Invalid Domain'
        WHEN error_type = 'authentication_failed' THEN 'Authentication Failed'
        ELSE 'Unknown Error'
    END AS error_description
FROM radpostauth
WHERE reply != 'Access-Accept'
  AND authdate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY authdate DESC;

-- ============================================================================
-- NAS DEVICE STATISTICS VIEW
-- ============================================================================
-- Provides statistics for each NAS device
-- ============================================================================

CREATE OR REPLACE VIEW nas_statistics AS
SELECT
    n.id,
    n.nasname,
    n.shortname,
    n.type,
    n.description,
    COUNT(DISTINCT ra.username) AS unique_users,
    COUNT(ra.radacctid) AS total_sessions,
    SUM(ra.acctsessiontime) AS total_session_time,
    SUM(ra.acctinputoctets + ra.acctoutputoctets) AS total_data,
    COUNT(CASE WHEN ra.acctstoptime IS NULL THEN 1 END) AS active_sessions,
    MAX(ra.acctstarttime) AS last_activity
FROM nas n
LEFT JOIN radacct ra ON n.nasname = ra.nasipaddress
GROUP BY n.id, n.nasname, n.shortname, n.type, n.description;

-- ============================================================================
-- AUTHENTICATION ERROR SUMMARY VIEW
-- ============================================================================
-- Aggregates authentication errors by type for the last 7 days
-- ============================================================================

CREATE OR REPLACE VIEW auth_error_summary AS
SELECT
    error_type,
    COUNT(*) AS error_count,
    COUNT(DISTINCT username) AS affected_users,
    MIN(authdate) AS first_occurrence,
    MAX(authdate) AS last_occurrence,
    ROUND(
        (COUNT(*) * 100.0) / (SELECT COUNT(*) FROM radpostauth WHERE reply != 'Access-Accept' AND authdate >= DATE_SUB(NOW(), INTERVAL 7 DAY)),
        2
    ) AS percentage_of_errors
FROM radpostauth
WHERE reply != 'Access-Accept'
  AND error_type IS NOT NULL
  AND authdate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY error_type
ORDER BY error_count DESC;
