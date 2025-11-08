-- Simplified DaloRADIUS Tables for Basic Functionality

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
    PRIMARY KEY (id),
    KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Insert default DaloRADIUS administrator (password = 'password' hashed with SHA-256)
INSERT IGNORE INTO operators (username, password, firstname, lastname, title, department, company) VALUES
('administrator', '5e884898da28047151d0e56f8dc6292773603d0d6aabbdd62a11ef721d1542d8', 'Default', 'Administrator', 'Administrator', 'IT', 'Krea University');

-- Add columns to NAS table if they don't exist (ignore errors if they already exist)
SET SQL_MODE = '';
ALTER TABLE nas ADD COLUMN ports int(5) DEFAULT NULL;
ALTER TABLE nas ADD COLUMN server varchar(64) DEFAULT NULL;
ALTER TABLE nas ADD COLUMN community varchar(50) DEFAULT NULL;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

COMMIT;