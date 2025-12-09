# Database Auto-Initialization

This directory contains SQL files that automatically initialize the MySQL database when the container first starts.

## How It Works

MySQL's official Docker image automatically runs any `.sql` files found in `/docker-entrypoint-initdb.d/` on **first container startup only**.

The `docker-compose.yml` mounts this directory:
```yaml
volumes:
  - ./sql:/docker-entrypoint-initdb.d:ro
```

## Files

### `00-complete-schema.sql` ✅ ACTIVE
**This is the main initialization file that runs automatically.**

Contains everything needed to set up the FreeRADIUS database:

#### 1. Database Creation
- Drops and recreates `radius` database
- Sets UTF-8 character encoding

#### 2. RADIUS Core Tables
- `nas` - Network Access Servers (AP Controllers)
- `radcheck` - Per-user check attributes
- `radreply` - Per-user reply attributes
- `radgroupcheck` - Group check attributes
- `radgroupreply` - Group reply attributes
- `radusergroup` - User-to-group mappings
- `radpostauth` - Authentication log with VLAN and error tracking
- `radacct` - Accounting/session data

#### 3. Management Tables
- `operators` - Admin users with role-based access
- `userinfo` - Extended user information
- `billing_plans` - Billing/subscription plans

#### 4. Reporting Tables
- `daily_stats` - Daily authentication statistics
- `auth_error_summary` - Error type breakdown
- `nas_statistics` - Per-NAS performance metrics

#### 5. Database Views
- `active_sessions` - Currently connected users
- `recent_failed_auth` - Recent authentication failures
- `user_session_summary` - Historical session stats per user
- `user_bandwidth_today` - Today's bandwidth usage

#### 6. Stored Procedures
- `cleanup_old_accounting(days)` - Remove old accounting records
- `update_daily_stats(date)` - Generate daily statistics
- `update_error_summary(date)` - Generate error summaries

#### 7. Initial Data
- **Admin account**: username `admin`, password `admin123` (MD5 hash)
- **Default NAS entries**: localhost IPv4 and IPv6

### `*.sql.old` Files ❌ DISABLED
These are old/legacy SQL files that have been renamed to prevent execution.
They are kept for reference only.

## First-Time Setup

When you run `docker-compose up -d` for the **first time**:

1. MySQL container starts
2. Detects empty data volume
3. Runs `00-complete-schema.sql`
4. Creates all tables, views, procedures
5. Inserts admin user and default data
6. Database is ready!

**Total setup time**: ~2-5 seconds

## Subsequent Startups

On subsequent container restarts:
- SQL files are **NOT** run again
- Existing database is preserved
- Data persists in the `mysql_data` volume

## Testing the Auto-Init

### Method 1: Fresh Start (Recommended)
```bash
# Stop and remove everything
docker-compose down -v  # -v removes volumes!

# Start fresh (auto-init will run)
docker-compose up -d

# Wait for MySQL to be ready
sleep 10

# Verify database
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SHOW TABLES"
```

### Method 2: Check Logs
```bash
# View MySQL initialization logs
docker logs radius-mysql 2>&1 | grep -i "schema\|initialized\|admin"
```

You should see:
```
FreeRADIUS Database Initialized Successfully
Database Name: radius
Total Tables: 13
Total Views: 4
Total Procedures: 3
Default Admin Account Created:
  Username: admin
  Password: admin123
```

### Method 3: Verify Admin Account
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
SELECT username, LENGTH(password) as pwd_len, firstname, lastname, createusers
FROM operators
WHERE username='admin'"
```

Expected output:
```
username | pwd_len | firstname | lastname      | createusers
---------|---------|-----------|---------------|------------
admin    | 32      | System    | Administrator | 1
```

## Manual Initialization (If Needed)

If you need to manually run the SQL file:

```bash
# Copy SQL file to container
docker cp sql/00-complete-schema.sql radius-mysql:/tmp/

# Execute it
docker exec radius-mysql sh -c "mysql -u root -p\$MYSQL_ROOT_PASSWORD < /tmp/00-complete-schema.sql"
```

## Troubleshooting

### Issue: "Database already exists" Error

**Cause**: Container has already initialized with old SQL files

**Solution**: Remove the volume and restart
```bash
docker-compose down -v
docker-compose up -d
```

### Issue: Admin login not working

**Check password hash**:
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
SELECT username, password, MD5('admin123') as expected_hash
FROM operators
WHERE username='admin'"
```

If `password` != `expected_hash`, reset it:
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "
UPDATE operators
SET password = '0192023a7bbd73250516f069df18b500'
WHERE username = 'admin'"
```

### Issue: Tables not created

**Check if SQL ran**:
```bash
docker logs radius-mysql 2>&1 | grep "complete-schema"
```

**Manually verify**:
```bash
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius -e "SHOW TABLES"
```

## Adding New Tables/Data

To add new tables or initial data:

1. **Option A**: Edit `00-complete-schema.sql`
   - Add your SQL at the end (before the summary section)
   - Remove volume and restart: `docker-compose down -v && docker-compose up -d`

2. **Option B**: Create new SQL file
   - Name it with higher number: `01-my-additions.sql`
   - Files run in alphabetical order
   - Remove volume and restart

3. **Option C**: Manual migration (for existing databases)
   ```bash
   docker exec -i radius-mysql mysql -u radius -pRadiusDbPass2024! radius < my-migration.sql
   ```

## File Naming Convention

MySQL runs SQL files in **alphabetical order**:
- `00-complete-schema.sql` - Base schema (runs first)
- `01-custom-additions.sql` - Custom additions
- `02-more-stuff.sql` - Additional customizations
- etc.

**Note**: Files ending in `.old`, `.bak`, `.txt` are ignored.

## Default Credentials

### Admin Dashboard
- **URL**: http://localhost:8080
- **Username**: `admin`
- **Password**: `admin123`
- **Hash Type**: MD5
- **Permissions**: Superadmin (can create users)

### Database
- **Host**: localhost:3306
- **Database**: `radius`
- **Username**: `radius`
- **Password**: `RadiusDbPass2024!` (from `.env`)

## Schema Summary

**Total Tables**: 13
- Core RADIUS: 6 (nas, radcheck, radreply, radgroupcheck, radgroupreply, radusergroup)
- Accounting: 1 (radacct)
- Authentication: 1 (radpostauth)
- Management: 2 (operators, userinfo)
- Reporting: 3 (daily_stats, auth_error_summary, nas_statistics)
- Optional: 1 (billing_plans)

**Total Views**: 4
- active_sessions
- recent_failed_auth
- user_session_summary
- user_bandwidth_today

**Total Procedures**: 3
- cleanup_old_accounting
- update_daily_stats
- update_error_summary

## Maintenance

### Cleanup Old Data
```bash
# Remove accounting records older than 90 days
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "CALL cleanup_old_accounting(90)"
```

### Update Statistics
```bash
# Generate stats for yesterday
docker exec radius-mysql mysql -u radius -pRadiusDbPass2024! radius \
  -e "CALL update_daily_stats(CURDATE() - INTERVAL 1 DAY)"
```

### Backup Database
```bash
# Create backup
docker exec radius-mysql mysqldump -u radius -pRadiusDbPass2024! radius > backup-$(date +%Y%m%d).sql

# Restore from backup
docker exec -i radius-mysql mysql -u radius -pRadiusDbPass2024! radius < backup-20241209.sql
```

## Related Documentation

- [ADMIN_LOGIN_FIXED.md](../ADMIN_LOGIN_FIXED.md) - Admin account details
- [VLAN_LOGGING_FIX.md](../VLAN_LOGGING_FIX.md) - VLAN tracking implementation
- [STATUS_REPORT.md](../STATUS_REPORT.md) - Overall system status

---

**Auto-initialization Status**: ✅ Active
**Last Updated**: December 9, 2024
**Schema Version**: 1.0
