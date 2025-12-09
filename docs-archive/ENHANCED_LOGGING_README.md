# Enhanced Authentication Logging and Error Tracking

This document describes the enhanced authentication logging features implemented in the FreeRADIUS Google LDAP Dashboard.

## Overview

The system now captures detailed authentication information including:
- **Access-Accept** and **Access-Reject** responses
- **Detailed error messages** for failed authentications
- **Error categorization** for analysis and reporting
- **UTC timestamps** stored alongside IST timestamps for accurate time tracking

## Database Schema Changes

### New Columns in `radpostauth` Table

| Column | Type | Description |
|--------|------|-------------|
| `reply_message` | TEXT | Detailed success/error message from RADIUS Reply-Message attribute |
| `error_type` | VARCHAR(64) | Categorized error type for analysis |
| `authdate` | TIMESTAMP | Local timestamp (IST by default, configurable) |
| `authdate_utc` | TIMESTAMP | UTC timestamp for accurate cross-timezone tracking |

### Migration

To upgrade an existing database, run:

```bash
docker exec -i freeradius-mysql mysql -u radius -p${DB_PASSWORD} radius < sql/01-add-error-tracking-columns.sql
```

Or manually apply the migration:

```sql
ALTER TABLE radpostauth ADD COLUMN reply_message TEXT DEFAULT NULL AFTER reply;
ALTER TABLE radpostauth ADD COLUMN error_type VARCHAR(64) DEFAULT NULL AFTER reply_message;
ALTER TABLE radpostauth ADD COLUMN authdate_utc TIMESTAMP NULL DEFAULT NULL AFTER error_type;
ALTER TABLE radpostauth ADD INDEX idx_error_type (error_type);
ALTER TABLE radpostauth ADD INDEX idx_reply (reply);
```

## Error Type Categories

The system automatically categorizes authentication failures into the following types:

| Error Type | Description | Trigger Condition |
|------------|-------------|-------------------|
| `password_wrong` | Invalid username or password | LDAP bind failure |
| `user_not_found` | User account doesn't exist | LDAP user not found |
| `ldap_connection_failed` | Cannot reach LDAP server | LDAP connection timeout or failure |
| `ssl_certificate_error` | Certificate validation issues | TLS/certificate errors |
| `invalid_domain` | Unsupported email domain | Domain not in allowed list |
| `authentication_failed` | Generic authentication failure | Other authentication errors |

## Timezone Handling

### Storage Strategy

1. **`authdate`**: Stores timestamp in the configured local timezone (IST by default)
   - Set via `MYSQL_TIMEZONE=Asia/Kolkata` in `.env`
   - Configurable to any timezone

2. **`authdate_utc`**: Stores UTC timestamp using MySQL `UTC_TIMESTAMP()`
   - Always in UTC (GMT+0)
   - Enables accurate time tracking across timezones

### Display in Dashboard

The dashboard automatically converts UTC to IST for display:

```sql
CONVERT_TZ(authdate_utc, '+00:00', '+05:30') as authdate_ist
```

This ensures consistent time display regardless of server timezone configuration.

## Reply Messages

### Access-Accept Messages

For successful authentications:
```
"Authenticated as student"  -- or faculty, staff, admin depending on user type
```

### Access-Reject Messages

Detailed error messages help users understand why authentication failed:

1. **Password Wrong**:
   ```
   "Authentication failed: Invalid username or password. Please check your credentials and try again."
   ```

2. **User Not Found**:
   ```
   "Authentication failed: User account not found. Please contact IT support."
   ```

3. **LDAP Connection Failed**:
   ```
   "Authentication failed: Unable to reach authentication server. Please try again later."
   ```

4. **SSL Certificate Error**:
   ```
   "Authentication failed: Certificate error. Please check your device security settings."
   ```

5. **Invalid Domain**:
   ```
   "Authentication failed: Email domain not supported. Use your institutional email address."
   ```

6. **Generic Failure**:
   ```
   "Authentication failed: Invalid credentials. Please verify your username and password."
   ```

## Dashboard Features

### Authentication Attempts Table

Now displays:
- **Date**: Authentication date
- **Time (IST)**: Time in Indian Standard Time
- **Username**: User attempting authentication
- **Domain**: Email domain extracted from username
- **Status**: Success/Failed with icon
- **Reply**: RADIUS reply type (Access-Accept/Access-Reject)
- **Error Type**: Categorized error (badge-formatted)
- **Message**: Detailed error or success message (truncated with tooltip)

### Error Statistics API

New endpoint: `/dashboard/api/error-stats.php`

Returns:
```json
{
  "success": true,
  "errorBreakdown": [
    {
      "error_type": "password_wrong",
      "count": 45,
      "percentage": 65.2
    }
  ],
  "recentFailures": [
    {
      "authdate": "2025-12-03 14:30:15",
      "authdate_ist": "2025-12-03 20:00:15",
      "username": "user@example.com",
      "error_type": "password_wrong",
      "reply_message": "Authentication failed: Invalid username or password..."
    }
  ],
  "hourlyTrend": [
    {"hour": 9, "failures": 12},
    {"hour": 10, "failures": 8}
  ]
}
```

## Configuration Files Modified

1. **`sql/00-init-radius-schema.sql`**
   - Updated `radpostauth` table schema

2. **`sql/01-add-error-tracking-columns.sql`** (NEW)
   - Migration script for existing databases

3. **`configs/queries.conf`**
   - Updated post-auth query to capture reply_message and error_type
   - Added UTC timestamp capture

4. **`configs/default`**
   - Added error type categorization in Post-Auth-Type REJECT section
   - Set Error-Type control attribute for each error category
   - Clear Error-Type for successful authentications

5. **`dashboard/index.php`**
   - Enhanced authentication attempts table
   - Display error types and messages
   - Convert UTC to IST for display

6. **`dashboard/api/error-stats.php`** (NEW)
   - New API endpoint for error analytics

## Usage Examples

### Query Recent Failed Logins with Details

```sql
SELECT
    authdate,
    CONVERT_TZ(authdate_utc, '+00:00', '+05:30') as ist_time,
    username,
    error_type,
    reply_message
FROM radpostauth
WHERE reply = 'Access-Reject'
ORDER BY authdate DESC
LIMIT 50;
```

### Error Breakdown Report

```sql
SELECT
    error_type,
    COUNT(*) as occurrences,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM radpostauth WHERE reply = 'Access-Reject'), 2) as percentage
FROM radpostauth
WHERE reply = 'Access-Reject'
    AND authdate >= CURDATE()
GROUP BY error_type
ORDER BY occurrences DESC;
```

### Failed Logins by User

```sql
SELECT
    username,
    COUNT(*) as failed_attempts,
    GROUP_CONCAT(DISTINCT error_type) as error_types,
    MAX(authdate) as last_failure
FROM radpostauth
WHERE reply = 'Access-Reject'
    AND authdate >= CURDATE() - INTERVAL 7 DAY
GROUP BY username
HAVING failed_attempts > 5
ORDER BY failed_attempts DESC;
```

### Timezone Comparison

```sql
SELECT
    authdate as local_time,
    authdate_utc as utc_time,
    CONVERT_TZ(authdate_utc, '+00:00', '+05:30') as ist_time,
    username,
    reply
FROM radpostauth
ORDER BY id DESC
LIMIT 10;
```

## Troubleshooting

### Error messages not appearing

1. Check FreeRADIUS is loading the updated configs:
   ```bash
   docker exec freeradius-server radiusd -XC
   ```

2. Verify SQL query syntax:
   ```bash
   docker logs freeradius-server | grep SQL
   ```

### UTC timestamps are NULL

- Ensure MySQL has UTC timezone data loaded
- Check `UTC_TIMESTAMP()` works:
  ```sql
  SELECT NOW(), UTC_TIMESTAMP();
  ```

### Timezone conversion issues

- Verify MySQL timezone tables are populated:
  ```sql
  SELECT * FROM mysql.time_zone_name LIMIT 5;
  ```

- If empty, load timezone data:
  ```bash
  mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root -p mysql
  ```

## Performance Considerations

- New indexes added: `idx_error_type`, `idx_reply`
- `reply_message` is TEXT type - may impact large result sets
- Consider partitioning `radpostauth` by date for high-volume installations

## Security Notes

- Reply messages are safe to log (no passwords)
- Password logging still controlled by `LOG_SENSITIVE_DATA` environment variable
- Error messages provide helpful info without exposing security details

## Future Enhancements

Potential additions:
- Error rate alerting
- Automated user account lockout after X failed attempts
- Machine learning-based anomaly detection
- GeoIP tracking for authentication attempts
- Integration with SIEM systems

## Support

For issues or questions:
- Check FreeRADIUS logs: `docker logs freeradius-server`
- Check MySQL logs: `docker logs freeradius-mysql`
- Review SQL trace: `logs/sqltrace.sql` (if enabled)
