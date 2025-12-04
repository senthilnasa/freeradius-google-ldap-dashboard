-- Migration: Add error tracking and UTC timestamp columns to radpostauth table
-- Purpose: Store detailed error messages, error types, and UTC timestamps
-- Date: 2025-12-03

-- Add reply_message column to store the detailed error/success message
ALTER TABLE radpostauth
ADD COLUMN reply_message TEXT DEFAULT NULL AFTER reply;

-- Add error_type column to categorize errors (password_wrong, ssl_error, ldap_error, etc.)
ALTER TABLE radpostauth
ADD COLUMN error_type VARCHAR(64) DEFAULT NULL AFTER reply_message;

-- Add authdate_utc column to store GMT/UTC timestamp
ALTER TABLE radpostauth
ADD COLUMN authdate_utc TIMESTAMP NULL DEFAULT NULL AFTER error_type;

-- Add indexes for performance
ALTER TABLE radpostauth
ADD INDEX idx_error_type (error_type);

ALTER TABLE radpostauth
ADD INDEX idx_reply (reply);

-- Show the updated table structure
DESCRIBE radpostauth;
