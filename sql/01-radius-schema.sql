-- FreeRADIUS MySQL Schema for Accounting and User Management
-- This creates the basic tables needed for RADIUS accounting and future GUI integration

USE radius;

-- Users table for authentication (can be used alongside LDAP)
CREATE TABLE IF NOT EXISTS radcheck (
    id int(11) unsigned NOT NULL auto_increment,
    username varchar(64) NOT NULL default '',
    attribute varchar(64) NOT NULL default '',
    op char(2) NOT NULL DEFAULT '==',
    value varchar(253) NOT NULL default '',
    PRIMARY KEY (id),
    KEY username (username(32))
);

-- User attributes/permissions 
CREATE TABLE IF NOT EXISTS radreply (
    id int(11) unsigned NOT NULL auto_increment,
    username varchar(64) NOT NULL default '',
    attribute varchar(64) NOT NULL default '',
    op char(2) NOT NULL DEFAULT '=',
    value varchar(253) NOT NULL default '',
    PRIMARY KEY (id),
    KEY username (username(32))
);

-- Group definitions
CREATE TABLE IF NOT EXISTS radgroupcheck (
    id int(11) unsigned NOT NULL auto_increment,
    groupname varchar(64) NOT NULL default '',
    attribute varchar(64) NOT NULL default '',
    op char(2) NOT NULL DEFAULT '==',
    value varchar(253) NOT NULL default '',
    PRIMARY KEY (id),
    KEY groupname (groupname(32))
);

-- Group attributes
CREATE TABLE IF NOT EXISTS radgroupreply (
    id int(11) unsigned NOT NULL auto_increment,
    groupname varchar(64) NOT NULL default '',
    attribute varchar(64) NOT NULL default '',
    op char(2) NOT NULL DEFAULT '=',
    value varchar(253) NOT NULL default '',
    PRIMARY KEY (id),
    KEY groupname (groupname(32))
);

-- User group membership
CREATE TABLE IF NOT EXISTS radusergroup (
    username varchar(64) NOT NULL default '',
    groupname varchar(64) NOT NULL default '',
    priority int(11) NOT NULL default '1',
    KEY username (username(32))
);

-- Accounting table for session tracking
CREATE TABLE IF NOT EXISTS radacct (
    radacctid bigint(21) NOT NULL auto_increment,
    acctsessionid varchar(64) NOT NULL default '',
    acctuniqueid varchar(32) NOT NULL default '',
    username varchar(64) NOT NULL default '',
    groupname varchar(64) NOT NULL default '',
    realm varchar(64) default '',
    nasipaddress varchar(15) NOT NULL default '',
    nasportid varchar(15) default NULL,
    nasporttype varchar(32) default NULL,
    acctstarttime datetime NULL default NULL,
    acctupdatetime datetime NULL default NULL,
    acctstoptime datetime NULL default NULL,
    acctsessiontime int(12) unsigned default NULL,
    acctauthentic varchar(32) default NULL,
    connectinfo_start varchar(50) default NULL,
    connectinfo_stop varchar(50) default NULL,
    acctinputoctets bigint(20) unsigned default NULL,
    acctoutputoctets bigint(20) unsigned default NULL,
    calledstationid varchar(50) NOT NULL default '',
    callingstationid varchar(50) NOT NULL default '',
    acctterminatecause varchar(32) NOT NULL default '',
    servicetype varchar(32) default NULL,
    framedprotocol varchar(32) default NULL,
    framedipaddress varchar(15) NOT NULL default '',
    PRIMARY KEY (radacctid),
    UNIQUE KEY acctuniqueid (acctuniqueid),
    KEY username (username),
    KEY framedipaddress (framedipaddress),
    KEY acctsessionid (acctsessionid),
    KEY acctsessiontime (acctsessiontime),
    KEY acctstarttime (acctstarttime),
    KEY acctstoptime (acctstoptime),
    KEY nasipaddress (nasipaddress)
);

-- Post-auth logging (for login attempts)
CREATE TABLE IF NOT EXISTS radpostauth (
    id int(11) NOT NULL auto_increment,
    username varchar(64) NOT NULL default '',
    pass varchar(64) NOT NULL default '',
    reply varchar(32) NOT NULL default '',
    authdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY username (username),
    KEY authdate (authdate)
);

-- NAS (Network Access Server) table for access point management
CREATE TABLE IF NOT EXISTS nas (
    id int(10) NOT NULL auto_increment,
    nasname varchar(128) NOT NULL,
    shortname varchar(32) default NULL,
    type varchar(30) NOT NULL DEFAULT 'other',
    ports int(5) default NULL,
    secret varchar(60) NOT NULL default 'secret',
    server varchar(64) default NULL,
    community varchar(50) default NULL,
    description varchar(200) default 'RADIUS Client',
    PRIMARY KEY (id),
    KEY nasname (nasname)
);

-- Insert sample groups for your multi-domain setup
INSERT IGNORE INTO radgroupcheck (groupname, attribute, op, value) VALUES
('staff', 'Auth-Type', ':=', 'Accept'),
('student', 'Auth-Type', ':=', 'Accept');

INSERT IGNORE INTO radgroupreply (groupname, attribute, op, value) VALUES
('staff', 'Reply-Message', '=', 'Authenticated as staff'),
('staff', 'Session-Timeout', '=', '43200'),
('staff', 'Idle-Timeout', '=', '3600'),
('staff', 'Class', '=', '0x5354414646'),
('student', 'Reply-Message', '=', 'Authenticated as student'),
('student', 'Session-Timeout', '=', '28800'),
('student', 'Idle-Timeout', '=', '1800'),
('student', 'Class', '=', '0x53545544454e54');

-- Insert your access points
INSERT IGNORE INTO nas (nasname, shortname, type, secret, description) VALUES
('10.10.200.5', 'AP-MB-1F', 'wireless', 'testing123', 'Main Block 1st Floor Access Point'),
('10.10.0.0/16', 'Campus-Network', 'wireless', 'testing123', 'Campus WiFi Network Range');

-- Create indexes for performance with high user load
ALTER TABLE radacct ADD INDEX idx_username_start (username, acctstarttime);
ALTER TABLE radacct ADD INDEX idx_nas_start (nasipaddress, acctstarttime);
ALTER TABLE radpostauth ADD INDEX idx_username_date (username, authdate);

-- Create a view for active sessions
CREATE OR REPLACE VIEW active_sessions AS
SELECT 
    username,
    nasipaddress,
    acctsessionid,
    acctstarttime,
    TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) as session_duration,
    acctinputoctets,
    acctoutputoctets,
    calledstationid as wifi_network,
    callingstationid as device_mac
FROM radacct 
WHERE acctstoptime IS NULL
ORDER BY acctstarttime DESC;

-- Create a view for today's login statistics
CREATE OR REPLACE VIEW daily_stats AS
SELECT 
    DATE(authdate) as date,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as successful_logins,
    SUM(CASE WHEN reply = 'Access-Reject' THEN 1 ELSE 0 END) as failed_logins,
    COUNT(DISTINCT username) as unique_users
FROM radpostauth 
WHERE authdate >= CURDATE()
GROUP BY DATE(authdate);

COMMIT;