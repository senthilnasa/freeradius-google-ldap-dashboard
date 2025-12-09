-- ============================================================================
-- Add VLAN tracking to radpostauth table
-- ============================================================================
-- This migration adds a vlan column to track VLAN assignments in auth logs
-- Created: 2024-12-08
-- ============================================================================

USE radius;

-- Add VLAN column to radpostauth table
ALTER TABLE radpostauth
ADD COLUMN vlan VARCHAR(16) DEFAULT NULL COMMENT 'Assigned VLAN ID'
AFTER error_type;

-- Add index for VLAN-based queries
ALTER TABLE radpostauth
ADD INDEX idx_vlan (vlan);

-- Display success message
SELECT 'VLAN column added to radpostauth table successfully!' AS status;
