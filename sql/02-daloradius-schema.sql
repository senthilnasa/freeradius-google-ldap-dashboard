-- DaloRADIUS Additional Tables and Data
-- This extends the basic RADIUS schema with DaloRADIUS specific tables

-- Operators table for DaloRADIUS user management
CREATE TABLE IF NOT EXISTS operators (
    id int(32) NOT NULL auto_increment,
    username varchar(32) NOT NULL default '',
    password varchar(32) NOT NULL default '',
    firstname varchar(32) NOT NULL default '',
    lastname varchar(32) NOT NULL default '',
    title varchar(32) NOT NULL default '',
    department varchar(32) NOT NULL default '',
    company varchar(32) NOT NULL default '',
    phone1 varchar(32) NOT NULL default '',
    phone2 varchar(32) NOT NULL default '',
    email1 varchar(32) NOT NULL default '',
    email2 varchar(32) NOT NULL default '',
    messenger1 varchar(32) NOT NULL default '',
    messenger2 varchar(32) NOT NULL default '',
    notes varchar(128) NOT NULL default '',
    changeuserinfo int(32) NOT NULL default '0',
    createusers int(32) NOT NULL default '0',
    PRIMARY KEY (id),
    KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Billing tables
CREATE TABLE IF NOT EXISTS billing_plans (
    id int(10) NOT NULL auto_increment,
    planname varchar(128) default NULL,
    plancost float default NULL,
    plancurrency varchar(32) default NULL,
    plangroup varchar(128) default NULL,
    plantype varchar(32) default NULL,
    planTimeType varchar(32) default NULL,
    planTimeBank varchar(32) default NULL,
    planTimeRefillCost float default NULL,
    planBandwidthUp varchar(128) default NULL,
    planBandwidthDown varchar(128) default NULL,
    planTrafficTotal varchar(128) default NULL,
    planRecurring varchar(32) default NULL,
    planRecurringPeriod varchar(32) default NULL,
    planActive varchar(32) default NULL,
    creationdate datetime NOT NULL default CURRENT_TIMESTAMP,
    creationby varchar(128) NOT NULL default '',
    updatedate datetime NOT NULL default CURRENT_TIMESTAMP,
    updateby varchar(128) NOT NULL default '',
    PRIMARY KEY (id),
    KEY planname (planname)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- User info table for extended user management
CREATE TABLE IF NOT EXISTS userinfo (
    id int(32) NOT NULL auto_increment,
    username varchar(128) NOT NULL default '',
    firstname varchar(200) NOT NULL default '',
    lastname varchar(200) NOT NULL default '',
    email varchar(200) NOT NULL default '',
    department varchar(200) NOT NULL default '',
    company varchar(200) NOT NULL default '',
    workphone varchar(200) NOT NULL default '',
    homephone varchar(200) NOT NULL default '',
    mobilephone varchar(200) NOT NULL default '',
    address varchar(200) NOT NULL default '',
    city varchar(200) NOT NULL default '',
    state varchar(200) NOT NULL default '',
    country varchar(100) NOT NULL default '',
    zip varchar(200) NOT NULL default '',
    notes varchar(200) NOT NULL default '',
    changeuserinfo varchar(128) NOT NULL default '',
    portalloginpassword varchar(128) NOT NULL default '',
    enableportallogin int(32) default 0,
    creationdate datetime NOT NULL default CURRENT_TIMESTAMP,
    creationby varchar(128) NOT NULL default '',
    updatedate datetime NOT NULL default CURRENT_TIMESTAMP,
    updateby varchar(128) NOT NULL default '',
    PRIMARY KEY (id),
    KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Insert default DaloRADIUS administrator
INSERT IGNORE INTO operators (username, password, firstname, lastname, title, department, company) VALUES
('administrator', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'Default', 'Administrator', 'Administrator', 'IT', 'Krea University');

-- Insert sample billing plans for your university
INSERT IGNORE INTO billing_plans (planname, plancost, plancurrency, plangroup, plantype, planTimeType, planTimeBank, planActive, creationdate, creationby) VALUES
('Staff Plan', 0.00, 'INR', 'staff', 'Unlimited', 'Daily', '86400', 'yes', NOW(), 'system'),
('Student Plan', 0.00, 'INR', 'student', 'Limited', 'Daily', '28800', 'yes', NOW(), 'system');

-- Extended NAS table for DaloRADIUS
ALTER TABLE nas ADD COLUMN IF NOT EXISTS ports int(5) DEFAULT NULL;
ALTER TABLE nas ADD COLUMN IF NOT EXISTS server varchar(64) DEFAULT NULL;
ALTER TABLE nas ADD COLUMN IF NOT EXISTS community varchar(50) DEFAULT NULL;

-- Insert your access points with extended info
INSERT IGNORE INTO nas (nasname, shortname, type, ports, secret, server, community, description) VALUES
('10.10.200.5', 'AP-MB-1F', 'wireless', 50, 'testing123', 'freeradius-multi-domain', 'public', 'Main Block 1st Floor Access Point'),
('10.10.0.0/16', 'Campus-Net', 'wireless', 1000, 'testing123', 'freeradius-multi-domain', 'public', 'Campus WiFi Network Range');

COMMIT;