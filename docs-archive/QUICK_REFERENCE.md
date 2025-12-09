# Quick Reference Guide - VLAN Display Feature

## Access Information

### Web Dashboard
- **URL:** http://localhost:8080
- **Username:** admin
- **Password:** admin123

### Key Pages

1. **Authentication Log** (with VLAN column)
   - URL: http://localhost:8080/index.php?page=auth-log
   - Features: View all authentication attempts with VLAN assignments

2. **Daily Report** (with VLAN statistics)
   - URL: http://localhost:8080/index.php?page=reports&action=daily-auth
   - Features: VLAN distribution and error breakdown

## Database Access

```bash
# Connect to database
docker exec -it radius-mysql mysql -u radius -pRadiusDbPass2024! radius

# View recent authentications with VLAN
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, reply, vlan, authdate FROM radpostauth ORDER BY authdate DESC LIMIT 10"

# Check VLAN distribution
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT vlan, COUNT(*) as count FROM radpostauth WHERE vlan IS NOT NULL GROUP BY vlan"
```

## Container Management

```bash
# Check container status
docker ps --filter "name=radius"

# View webapp logs
docker logs radius-webapp

# View FreeRADIUS logs
docker logs freeradius-google-ldap

# View MySQL logs
docker logs radius-mysql

# Restart webapp (if needed)
docker-compose restart webapp

# Rebuild webapp (after code changes)
docker-compose build webapp
docker-compose up -d webapp
```

## File Locations

### Modified Files
- `radius-gui/app/controllers/AuthLogController.php` - Auth log with VLAN
- `radius-gui/app/views/auth-log/index.php` - Auth log UI
- `radius-gui/app/controllers/ReportsController.php` - Reports with VLAN stats
- `radius-gui/app/views/reports/daily-auth.php` - Report UI

### Documentation
- `UI_VLAN_DISPLAY_UPDATE.md` - Detailed UI implementation
- `VLAN_ERROR_LOGGING_UPDATE.md` - Backend logging
- `IMPLEMENTATION_COMPLETE.md` - Complete implementation summary
- `QUICK_REFERENCE.md` - This file

## Common Tasks

### Export Authentication Log with VLAN
1. Navigate to http://localhost:8080/index.php?page=auth-log
2. Set date range
3. Click "Export CSV" button
4. CSV includes VLAN column

### View VLAN Statistics
1. Navigate to http://localhost:8080/index.php?page=reports&action=daily-auth
2. Select date
3. View "VLAN Assignments" section
4. View "Failed Authentication Breakdown" section

### Check Admin Account
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, MD5('admin123') as expected_hash, password as current_hash FROM operators WHERE username='admin'"
```

## Database Schema

### radpostauth Table (with VLAN)
```sql
CREATE TABLE radpostauth (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) NOT NULL,
  pass VARCHAR(64) NOT NULL,
  reply VARCHAR(32) NOT NULL,
  reply_message TEXT,
  error_type VARCHAR(64),
  vlan VARCHAR(16),           -- NEW COLUMN
  authdate TIMESTAMP NOT NULL,
  authdate_utc TIMESTAMP NULL,
  INDEX idx_authdate (authdate),
  INDEX idx_username (username),
  INDEX idx_reply (reply),
  INDEX idx_error_type (error_type),
  INDEX idx_vlan (vlan)       -- NEW INDEX
);
```

## Troubleshooting

### VLAN Column Not Found
```bash
# Check if VLAN column exists
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "DESCRIBE radpostauth"

# Add VLAN column if missing
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "ALTER TABLE radpostauth ADD COLUMN vlan VARCHAR(16) DEFAULT NULL AFTER error_type"

# Add index
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "ALTER TABLE radpostauth ADD INDEX idx_vlan (vlan)"
```

### Admin Login Not Working
```bash
# Reset admin password
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "UPDATE operators SET password = '0192023a7bbd73250516f069df18b500' WHERE username = 'admin'"
```

### Page Not Loading
```bash
# Check webapp logs for errors
docker logs radius-webapp --tail 50

# Restart webapp
docker-compose restart webapp
```

### No VLAN Data Showing
This is normal for:
- Historical authentications (before VLAN column was added)
- Test authentications that don't include VLAN attribute

VLAN data will appear for:
- New authentications from real users
- Authentications that receive `Tunnel-Private-Group-Id` attribute from FreeRADIUS

## Testing Authentication

```bash
# Test invalid domain (should show error_type)
echo "User-Name = 'test', User-Password = 'test'" | \
  radclient -x localhost:1812 auth KreaRadiusSecret20252024!

# Test valid authentication (should show VLAN if configured)
echo "User-Name = 'user@krea.edu.in', User-Password = 'correctpass'" | \
  radclient -x localhost:1812 auth KreaRadiusSecret20252024!

# Check results in database
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "SELECT username, reply, error_type, vlan, authdate FROM radpostauth ORDER BY id DESC LIMIT 5"
```

## Key Features

### Authentication Log
- ✅ VLAN column with blue badge display
- ✅ CSV export includes VLAN data
- ✅ Filterable by username, date, result
- ✅ Shows error types for failed authentications

### Daily Reports
- ✅ VLAN Assignments section with distribution
- ✅ Failed Authentication Breakdown by error type
- ✅ Progress bars for visual representation
- ✅ Unique user counts per VLAN
- ✅ PDF export capability

## Status Indicators

### Badge Colors
- 🔵 Blue (`bg-info`) - VLAN IDs
- 🟢 Green (`bg-success`) - Successful authentications
- 🔴 Red (`bg-danger`) - Failed authentications
- 🟡 Yellow (`bg-warning`) - Error types

## Quick SQL Queries

### VLAN Distribution
```sql
SELECT vlan, COUNT(*) as auths, COUNT(DISTINCT username) as users
FROM radpostauth
WHERE reply = 'Access-Accept' AND vlan IS NOT NULL
GROUP BY vlan
ORDER BY auths DESC;
```

### Error Type Distribution
```sql
SELECT error_type, COUNT(*) as count, COUNT(DISTINCT username) as users
FROM radpostauth
WHERE reply != 'Access-Accept' AND error_type IS NOT NULL
GROUP BY error_type
ORDER BY count DESC;
```

### Recent Authentications with VLAN
```sql
SELECT username, reply, vlan, error_type, authdate
FROM radpostauth
ORDER BY authdate DESC
LIMIT 20;
```

### VLAN by Domain
```sql
SELECT
  SUBSTRING_INDEX(username, '@', -1) as domain,
  vlan,
  COUNT(*) as count
FROM radpostauth
WHERE vlan IS NOT NULL
GROUP BY domain, vlan
ORDER BY count DESC;
```

---

**Last Updated:** December 8, 2024
**Implementation Status:** ✅ Complete and Deployed
