# Deployment Guide for Enhanced Authentication Logging

## Quick Start

Follow these steps to deploy the enhanced authentication logging features to your FreeRADIUS installation.

## Prerequisites

- Running FreeRADIUS Google LDAP Dashboard installation
- Docker and Docker Compose
- MySQL/MariaDB database access
- Backup of current database and configuration

## Step-by-Step Deployment

### 1. Backup Current System

```bash
# Backup database
docker exec freeradius-mysql mysqldump -u radius -p radius > backup_$(date +%Y%m%d).sql

# Backup configuration
tar -czf configs_backup_$(date +%Y%m%d).tar.gz configs/
```

### 2. Stop FreeRADIUS Service

```bash
docker-compose stop freeradius
```

### 3. Apply Database Migration

**Option A: For NEW installations**

The schema is already updated in `sql/00-init-radius-schema.sql`. Simply rebuild:

```bash
docker-compose down
docker volume rm freeradius-google-ldap-dashboard_mysql_data  # WARNING: This deletes all data!
docker-compose up -d
```

**Option B: For EXISTING installations** (Recommended)

Apply the migration script without data loss:

```bash
# Apply migration
docker exec -i freeradius-mysql mysql -u radius -p${DB_PASSWORD} radius < sql/01-add-error-tracking-columns.sql

# Verify columns were added
docker exec -i freeradius-mysql mysql -u radius -p${DB_PASSWORD} radius -e "DESCRIBE radpostauth;"
```

Expected output should include:
```
+---------------+--------------+------+-----+-------------------+
| Field         | Type         | Null | Key | Default           |
+---------------+--------------+------+-----+-------------------+
| reply_message | text         | YES  |     | NULL              |
| error_type    | varchar(64)  | YES  | MUL | NULL              |
| authdate_utc  | timestamp    | YES  |     | NULL              |
+---------------+--------------+------+-----+-------------------+
```

### 4. Update Configuration Files

The following files have been updated:

✅ `configs/queries.conf` - Enhanced SQL queries
✅ `configs/default` - Error categorization logic
✅ `dashboard/index.php` - Enhanced display
✅ `dashboard/api/error-stats.php` - New API endpoint (optional)

Configuration files are automatically mounted in Docker. No rebuild needed.

### 5. Restart FreeRADIUS

```bash
docker-compose restart freeradius
```

### 6. Verify FreeRADIUS Configuration

```bash
# Test configuration syntax
docker exec freeradius-server radiusd -XC

# Should end with:
# Configuration appears to be OK
```

### 7. Test Authentication Logging

**Test 1: Failed Authentication**

```bash
radtest wronguser@yourdomain.com wrongpassword localhost:1812 0 testing123
```

Check database:
```sql
SELECT authdate, username, reply, error_type, reply_message, authdate_utc
FROM radpostauth
ORDER BY id DESC
LIMIT 1;
```

Should show:
- `reply`: Access-Reject
- `error_type`: password_wrong (or similar)
- `reply_message`: Detailed error message
- `authdate_utc`: UTC timestamp

**Test 2: Successful Authentication** (with valid credentials)

```bash
radtest validuser@yourdomain.com correctpassword localhost:1812 0 testing123
```

Check database:
```sql
SELECT authdate, username, reply, error_type, reply_message, authdate_utc
FROM radpostauth
ORDER BY id DESC
LIMIT 1;
```

Should show:
- `reply`: Access-Accept
- `error_type`: NULL or empty
- `reply_message`: Success message
- `authdate_utc`: UTC timestamp

### 8. Verify Dashboard Display

1. Open browser: `http://your-server/dashboard/`
2. Navigate to authentication attempts table
3. Verify new columns appear:
   - Error Type
   - Message
   - Time shows as IST

### 9. Monitor Logs

```bash
# Watch FreeRADIUS logs
docker logs -f freeradius-server

# Watch MySQL query logs
docker exec freeradius-mysql tail -f /var/log/mysql/query.log

# Check SQL trace (if enabled)
docker exec freeradius-server tail -f /var/log/freeradius/sqltrace.sql
```

## Troubleshooting

### Issue: SQL Insert Errors

**Symptom**: FreeRADIUS logs show SQL errors

**Solution**:
```bash
# Check if columns exist
docker exec -i freeradius-mysql mysql -u radius -p${DB_PASSWORD} radius -e "SHOW COLUMNS FROM radpostauth;"

# If missing, reapply migration
docker exec -i freeradius-mysql mysql -u radius -p${DB_PASSWORD} radius < sql/01-add-error-tracking-columns.sql
```

### Issue: Error Type Always NULL

**Symptom**: `error_type` column is always NULL

**Solution**:
1. Verify `configs/default` has Error-Type settings:
```bash
docker exec freeradius-server grep -A 2 "Error-Type" /etc/freeradius/sites-enabled/default
```

2. Restart FreeRADIUS:
```bash
docker-compose restart freeradius
```

### Issue: UTC Timestamps NULL

**Symptom**: `authdate_utc` is always NULL

**Solution**:
1. Check MySQL timezone support:
```bash
docker exec -i freeradius-mysql mysql -u root -p -e "SELECT NOW(), UTC_TIMESTAMP();"
```

2. Load timezone data if needed:
```bash
docker exec -i freeradius-mysql mysql_tzinfo_to_sql /usr/share/zoneinfo | docker exec -i freeradius-mysql mysql -u root -p mysql
```

### Issue: Dashboard Not Showing New Columns

**Symptom**: Dashboard still shows old table format

**Solution**:
1. Clear browser cache (Ctrl+F5)
2. Verify PHP file updated:
```bash
docker exec freeradius-server grep "reply_message" /var/www/html/dashboard/index.php
```

3. Restart web server:
```bash
docker-compose restart freeradius
```

### Issue: Timezone Showing Wrong Time

**Symptom**: Times don't match IST

**Solution**:
1. Check `.env` timezone settings:
```bash
grep MYSQL_TIMEZONE .env
```

Should be:
```
MYSQL_TIMEZONE=Asia/Kolkata
MYSQL_TIMEZONE_OFFSET=+05:30
```

2. Verify MySQL timezone:
```bash
docker exec -i freeradius-mysql mysql -u root -p -e "SELECT @@global.time_zone, @@session.time_zone;"
```

3. Restart MySQL:
```bash
docker-compose restart mysql
```

## Rollback Procedure

If you need to rollback the changes:

### 1. Restore Configuration

```bash
# Stop services
docker-compose stop

# Restore old configs
tar -xzf configs_backup_YYYYMMDD.tar.gz

# Restart
docker-compose start
```

### 2. Restore Database (Optional)

```bash
# Stop MySQL
docker-compose stop mysql

# Restore backup
docker exec -i freeradius-mysql mysql -u radius -p radius < backup_YYYYMMDD.sql

# Start MySQL
docker-compose start mysql
```

### 3. Remove New Columns (Optional)

```sql
ALTER TABLE radpostauth DROP COLUMN reply_message;
ALTER TABLE radpostauth DROP COLUMN error_type;
ALTER TABLE radpostauth DROP COLUMN authdate_utc;
ALTER TABLE radpostauth DROP INDEX idx_error_type;
ALTER TABLE radpostauth DROP INDEX idx_reply;
```

## Post-Deployment Verification Checklist

- [ ] Database migration applied successfully
- [ ] New columns present in radpostauth table
- [ ] FreeRADIUS configuration valid (`radiusd -XC` passes)
- [ ] Failed authentication creates error_type
- [ ] Successful authentication logs properly
- [ ] UTC timestamps populated
- [ ] Dashboard displays new columns
- [ ] IST time conversion working
- [ ] Error messages displayed correctly
- [ ] API endpoint responding (if deployed)

## Performance Notes

**Expected Impact:**
- Minimal CPU impact (< 1%)
- Slight increase in database writes (~50 bytes per auth)
- Additional indexes may slightly slow writes but improve query performance

**Recommended for High-Volume Installations:**
- Enable query caching in MySQL
- Consider partitioning radpostauth by date
- Archive old records periodically

## Monitoring

After deployment, monitor:

```bash
# Check authentication rate
watch -n 5 'docker exec -i freeradius-mysql mysql -u radius -p${DB_PASSWORD} radius -e "SELECT COUNT(*) as total, SUM(reply=\"Access-Accept\") as success FROM radpostauth WHERE authdate >= NOW() - INTERVAL 5 MINUTE"'

# Check error breakdown
docker exec -i freeradius-mysql mysql -u radius -p${DB_PASSWORD} radius -e "SELECT error_type, COUNT(*) FROM radpostauth WHERE authdate >= CURDATE() GROUP BY error_type"
```

## Support

If you encounter issues:

1. Check logs: `docker logs freeradius-server`
2. Verify SQL trace: `docker exec freeradius-server cat /var/log/freeradius/sqltrace.sql`
3. Test database connection: `docker exec -it freeradius-mysql mysql -u radius -p`
4. Review configuration: `docker exec freeradius-server radiusd -X` (debug mode)

## Success Criteria

Deployment is successful when:

✅ FreeRADIUS starts without errors
✅ Authentication attempts logged with error details
✅ Dashboard shows error types and messages
✅ UTC and IST timestamps both populated
✅ No increase in authentication latency
✅ Error categorization working correctly

---

**Deployment completed! Your FreeRADIUS installation now has enhanced authentication logging and error tracking.**
