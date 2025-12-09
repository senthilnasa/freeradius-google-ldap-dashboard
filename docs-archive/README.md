# RADIUS Reporting GUI

A comprehensive web-based reporting and monitoring dashboard for FreeRADIUS deployments.

## Overview

This PHP application provides a complete reporting interface for FreeRADIUS with:
- Real-time monitoring of online users
- Detailed authentication logs with error tracking
- User session history and bandwidth usage reports
- Network Access Server (NAS/AP) analytics
- Error analytics with categorized error types
- Role-based access control (Superadmin, Network Admin, Helpdesk)
- CSV export functionality

## Features

### Authentication & Authorization
- Secure login using `operators` table
- Password hashing with bcrypt (auto-upgrades legacy hashes)
- Role-based access control (RBAC)
- Session management with CSRF protection

### Dashboards & Reports

1. **Main Dashboard**
   - Real-time KPIs (auth attempts, success/failure rates, online users)
   - Top users by bandwidth
   - Top NAS by sessions
   - Error summary

2. **Online Users**
   - Real-time active sessions
   - Device MAC, IP, NAS information
   - Session duration and bandwidth usage

3. **Authentication Log**
   - Complete authentication history
   - Enhanced error tracking (error_type, reply_message)
   - UTC timestamp support
   - Advanced filtering

4. **User Session History**
   - Per-user session details
   - Bandwidth consumption
   - Session duration analytics

5. **Top Users by Data Usage**
   - Bandwidth leaders
   - Session counts
   - Data transfer statistics

6. **NAS/AP Usage**
   - Access point analytics
   - Session counts per NAS
   - Bandwidth per NAS

7. **Error Analytics**
   - Error type breakdown
   - Recent failures
   - Trend analysis

8. **Reports**
   - Daily authentication summary
   - Monthly usage summary
   - Failed login report

9. **User Management** (Superadmin only)
   - Manage operators
   - Role assignment
   - Password management

## Project Structure

```
radius-gui/
├── public/
│   ├── index.php           # Main entry point
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── app.js          # Custom JavaScript
├── app/
│   ├── config/
│   │   ├── database.php    # Database configuration
│   │   └── app.php         # Application settings
│   ├── controllers/
│   │   ├── LoginController.php
│   │   ├── DashboardController.php
│   │   ├── OnlineUsersController.php
│   │   ├── AuthLogController.php
│   │   ├── UserHistoryController.php
│   │   ├── TopUsersController.php
│   │   ├── NasUsageController.php
│   │   ├── ErrorAnalyticsController.php
│   │   ├── ReportsController.php
│   │   ├── UsersController.php
│   │   └── SettingsController.php
│   ├── models/
│   │   ├── RadacctModel.php
│   │   ├── RadpostauthModel.php
│   │   ├── NasModel.php
│   │   └── OperatorModel.php
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   └── sidebar.php
│   │   ├── auth/
│   │   │   └── login.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── online-users/
│   │   │   └── index.php
│   │   ├── auth-log/
│   │   │   └── index.php
│   │   ├── user-history/
│   │   │   └── index.php
│   │   ├── top-users/
│   │   │   └── index.php
│   │   ├── nas-usage/
│   │   │   └── index.php
│   │   ├── error-analytics/
│   │   │   └── index.php
│   │   ├── reports/
│   │   │   ├── index.php
│   │   │   ├── daily-auth.php
│   │   │   ├── monthly-usage.php
│   │   │   └── failed-logins.php
│   │   ├── users/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   └── settings/
│   │       └── index.php
│   └── helpers/
│       ├── Database.php      # PDO database wrapper
│       ├── Auth.php           # Authentication helper
│       └── Utils.php          # Utility functions
└── logs/
    └── .gitkeep
```

## Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Existing FreeRADIUS database

### Setup Steps

1. **Copy files to web directory**
   ```bash
   cp -r radius-gui /var/www/html/
   ```

2. **Configure database connection**

   Edit `app/config/database.php` or set environment variables:
   ```bash
   export DB_HOST=localhost
   export DB_PORT=3306
   export DB_NAME=radius
   export DB_USER=radius
   export DB_PASSWORD=your_password
   ```

3. **Ensure database schema is up to date**

   The application requires the enhanced `radpostauth` table with error tracking columns.
   Run the migration if not already applied:
   ```sql
   source sql/01-add-error-tracking-columns.sql
   ```

4. **Set permissions**
   ```bash
   chown -R www-data:www-data /var/www/html/radius-gui
   chmod -R 755 /var/www/html/radius-gui
   chmod -R 775 /var/www/html/radius-gui/logs
   ```

5. **Configure web server**

   **Apache (.htaccess)**:
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]
   ```

   **Nginx**:
   ```nginx
   location /radius-gui {
       try_files $uri $uri/ /radius-gui/public/index.php?$query_string;
   }
   ```

6. **Create initial operator account**

   If no operators exist, create one manually:
   ```sql
   INSERT INTO operators (username, password, firstname, lastname, email, createusers)
   VALUES (
       'admin',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
       'System',
       'Administrator',
       'admin@example.com',
       1
   );
   ```

7. **Access the application**

   Navigate to: `http://your-server/radius-gui/public/`

   Default credentials (if using above SQL):
   - Username: `admin`
   - Password: `password`

## Configuration

### Application Settings (`app/config/app.php`)

- **Pagination**: Configure default rows per page
- **Roles**: Define role permissions
- **Role Mapping**: Map usernames to roles
- **Export Limits**: Set CSV export limits

### Database Settings (`app/config/database.php`)

- Database connection parameters
- Session configuration
- Timezone settings

### Role Permissions

Three built-in roles:

1. **Superadmin** (`*` permissions)
   - Full access to all features
   - User management
   - Settings configuration

2. **Network Admin**
   - All dashboards and reports
   - Cannot manage users
   - Cannot change settings

3. **Helpdesk**
   - Dashboard (view only)
   - Online users
   - Authentication log
   - User session history

## Usage

### Dashboard

The main dashboard provides an at-a-glance view of your RADIUS infrastructure:
- Authentication metrics (today)
- Currently online users
- Top bandwidth consumers
- Top NAS by sessions
- Error summary

### Online Users

View real-time active sessions with:
- Filter by username or NAS
- See device MAC addresses
- Monitor bandwidth usage
- Check session duration

### Authentication Log

Complete authentication history with enhanced error tracking:
- Filter by date range, username, result, or error type
- View detailed error messages
- See both local and UTC timestamps
- Export to CSV

### User Session History

Look up a specific user's session history:
- Enter username
- Select date range
- View all sessions with details
- Export to CSV

### Top Users by Data Usage

Identify bandwidth-heavy users:
- Select date range
- Choose top N users (10/20/50)
- View download/upload/total data
- Export to CSV

### NAS/AP Usage

Monitor access point utilization:
- Sessions per NAS
- Unique users per NAS
- Bandwidth per NAS
- Average session duration

### Error Analytics

Deep dive into authentication failures:
- Error breakdown by type
- Recent failure log
- Hourly trends
- Filter by error type

### Reports

Generate standard reports:
- **Daily Auth Summary**: Authentication stats for a specific day
- **Monthly Usage**: Bandwidth and session data for a month
- **Failed Logins**: Users with multiple failures

### User Management (Superadmin Only)

Manage operator accounts:
- Create new operators
- Edit existing operators
- Change passwords (auto-hashed with bcrypt)
- Assign roles
- Delete operators

## Security

- **Password Hashing**: All passwords stored with bcrypt
- **Legacy Password Migration**: Auto-upgrades SHA-256/MD5 hashes to bcrypt
- **CSRF Protection**: All forms protected with CSRF tokens
- **Session Security**: Secure session configuration with regeneration
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Protection**: All output escaped
- **Role-Based Access**: Granular permission system

## CSV Export

All list views support CSV export:
- Current filtered results
- UTF-8 with BOM for Excel compatibility
- Configurable row limits
- Sensitive data (passwords) excluded

## Database Schema Requirements

The application requires:

### Core RADIUS Tables
- `radacct` - Accounting/session data
- `radpostauth` - Authentication logs with error tracking
- `nas` - RADIUS clients
- `operators` - GUI user accounts

### Enhanced radpostauth Columns
```sql
reply_message TEXT DEFAULT NULL
error_type VARCHAR(64) DEFAULT NULL
authdate_utc TIMESTAMP NULL DEFAULT NULL
```

### Existing Views
- `active_sessions`
- `daily_stats`
- `user_bandwidth_today`

## Extending the Application

### Adding New Pages

1. Create controller in `app/controllers/`
2. Create view in `app/views/`
3. Add menu item in `app/views/layouts/sidebar.php`
4. Add permission check if needed

### Adding New Roles

Edit `app/config/app.php`:
```php
'roles' => [
    'custom_role' => [
        'name' => 'Custom Role',
        'permissions' => [
            'dashboard.view',
            'custom.permission'
        ]
    ]
]
```

### Custom Reports

Create new controller method in `ReportsController.php`:
```php
public function customReportAction()
{
    Auth::requirePermission('reports.view');

    // Query logic
    $data = [];

    // Render view
    require APP_PATH . '/views/reports/custom.php';
}
```

## Troubleshooting

### Cannot login
- Check database connection
- Verify `operators` table has users
- Check password hash format
- Review error logs

### Database connection errors
- Verify credentials in `app/config/database.php`
- Check MySQL is running
- Ensure database exists
- Check firewall rules

### Permission denied errors
- Review user role in session
- Check permission definitions in `app/config/app.php`
- Verify role mapping

### Missing data in reports
- Ensure `radpostauth` migration applied
- Check FreeRADIUS is logging correctly
- Verify table indexes exist

## Performance Tips

- Enable MySQL query cache
- Add indexes to frequently queried columns
- Partition large tables (radacct, radpostauth) by date
- Archive old data periodically
- Use persistent connections (configure in database.php)

## Maintenance

### Log Rotation

Configure log rotation for `logs/` directory:
```bash
/var/www/html/radius-gui/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
}
```

### Database Maintenance

Periodically archive old records:
```sql
-- Archive radpostauth older than 90 days
INSERT INTO radpostauth_archive SELECT * FROM radpostauth WHERE authdate < NOW() - INTERVAL 90 DAY;
DELETE FROM radpostauth WHERE authdate < NOW() - INTERVAL 90 DAY;

-- Archive radacct older than 90 days
INSERT INTO radacct_archive SELECT * FROM radacct WHERE acctstarttime < NOW() - INTERVAL 90 DAY;
DELETE FROM radacct WHERE acctstarttime < NOW() - INTERVAL 90 DAY;
```

## License

This application is provided as-is for use with FreeRADIUS deployments.

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review FreeRADIUS logs
3. Check MySQL error logs
4. Review application logs in `logs/` directory

## Credits

Built for FreeRADIUS Google LDAP Dashboard integration with enhanced error tracking and reporting capabilities.
