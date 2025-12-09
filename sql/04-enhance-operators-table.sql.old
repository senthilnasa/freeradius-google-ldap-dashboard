-- Enhance operators table for password management
-- Add columns to track password changes and force password reset

-- Add must_change_password column
ALTER TABLE operators
ADD COLUMN must_change_password TINYINT(1) DEFAULT 0 COMMENT 'Force password change on next login';

-- Add password_changed_at column
ALTER TABLE operators
ADD COLUMN password_changed_at DATETIME NULL COMMENT 'Last password change timestamp';

-- Add created_at column
ALTER TABLE operators
ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Account creation timestamp';

-- Add updated_at column
ALTER TABLE operators
ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp';

-- Add is_active column
ALTER TABLE operators
ADD COLUMN is_active TINYINT(1) DEFAULT 1 COMMENT 'Account active status';

-- Set must_change_password to 1 for existing admin accounts with default passwords
-- Default password is typically 'admin' which has MD5: 21232f297a57a5a743894a0e4a801fc3
-- or SHA256: 8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918
UPDATE operators
SET must_change_password = 1, password_changed_at = NULL
WHERE username IN ('administrator', 'admin')
  AND (password = '21232f297a57a5a743894a0e4a801fc3'
       OR password = '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918');
