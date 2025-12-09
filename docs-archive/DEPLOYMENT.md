# RADIUS Reporting GUI - Complete Deployment Guide

## Overview

This guide provides complete instructions for deploying the RADIUS Reporting GUI application with PDF generation capabilities.

## System Requirements

### Server Requirements
- **OS**: Ubuntu 20.04/22.04, Debian 11+, or CentOS 8+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Memory**: Minimum 512MB RAM (1GB+ recommended)
- **Disk**: Minimum 1GB free space

### PHP Extensions Required
```bash
php-cli
php-fpm (for Nginx)
php-mysql / php-pdo
php-mbstring
php-json
php-gd
php-xml
php-zip
```

### Additional Tools
- Composer (PHP dependency manager)
- Git (optional, for version control)

---

## Installation Methods

### Method 1: Automated Installation (Recommended)

```bash
# Download or copy the application files
cd /var/www/html
git clone <your-repo> radius-gui
cd radius-gui

# Make installation script executable
chmod +x install.sh

# Run installation script as root
sudo ./install.sh
```

The script will:
1. Check system requirements
2. Install Composer dependencies (including TCPDF)
3. Set file permissions
4. Create .env configuration
5. Optionally apply database migrations
6. Optionally create admin account

### Method 2: Manual Installation

#### Step 1: Copy Files

```bash
# Create directory
sudo mkdir -p /var/www/html/radius-gui
cd /var/www/html/radius-gui

# Copy application files
sudo cp -r /path/to/source/* .
```

#### Step 2: Install Dependencies

```bash
# Install Composer if not already installed
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install PHP dependencies (including TCPDF for PDF generation)
composer install --no-dev --optimize-autoloader
```

This will install:
- `tecnickcom/tcpdf` - PDF generation library
- All other required dependencies

#### Step 3: Configure Environment

```bash
# Create .env file from example
cp .env.example .env

# Edit configuration
nano .env
```

Update the following in `.env`:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=radius
DB_USER=radius
DB_PASSWORD=your_secure_password

APP_URL=http://your-server.com/radius-gui/public
APP_TIMEZONE=Asia/Kolkata

SESSION_SECURE=false  # Set to true if using HTTPS
SESSION_LIFETIME=7200
```

#### Step 4: Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/radius-gui

# Set directory permissions
sudo find /var/www/html/radius-gui -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/radius-gui -type f -exec chmod 644 {} \;

# Writable directories
sudo chmod -R 775 /var/www/html/radius-gui/logs
```

#### Step 5: Database Setup

Apply the enhanced error tracking migration if not already done:

```bash
mysql -u radius -p radius < ../sql/01-add-error-tracking-columns.sql
```

Or manually:

```sql
ALTER TABLE radpostauth
ADD COLUMN IF NOT EXISTS reply_message TEXT DEFAULT NULL AFTER reply;

ALTER TABLE radpostauth
ADD COLUMN IF NOT EXISTS error_type VARCHAR(64) DEFAULT NULL AFTER reply_message;

ALTER TABLE radpostauth
ADD COLUMN IF NOT EXISTS authdate_utc TIMESTAMP NULL DEFAULT NULL AFTER error_type;

ALTER TABLE radpostauth
ADD INDEX IF NOT EXISTS idx_error_type (error_type);

ALTER TABLE radpostauth
ADD INDEX IF NOT EXISTS idx_reply (reply);
```

#### Step 6: Create Initial Admin Account

```bash
mysql -u radius -p radius
```

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

---

## Web Server Configuration

### Apache Configuration

Create a virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/radius-gui.conf
```

```apache
<VirtualHost *:80>
    ServerName radius-gui.example.com
    DocumentRoot /var/www/html/radius-gui/public

    <Directory /var/www/html/radius-gui/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Rewrite rules for clean URLs (if .htaccess doesn't work)
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?page=$1 [QSA,L]
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/radius-gui-error.log
    CustomLog ${APACHE_LOG_DIR}/radius-gui-access.log combined

    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

Enable the site:

```bash
sudo a2enmod rewrite headers
sudo a2ensite radius-gui.conf
sudo systemctl reload apache2
```

### Nginx Configuration

```bash
sudo nano /etc/nginx/sites-available/radius-gui
```

```nginx
server {
    listen 80;
    server_name radius-gui.example.com;

    root /var/www/html/radius-gui/public;
    index index.php index.html;

    # Security headers
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/radius-gui-access.log;
    error_log /var/log/nginx/radius-gui-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/radius-gui /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## PDF Generation Setup

The application uses TCPDF for PDF generation. It's automatically installed via Composer.

### Verify TCPDF Installation

```bash
cd /var/www/html/radius-gui
ls -la vendor/tecnickcom/tcpdf/
```

You should see the TCPDF library files.

### TCPDF Configuration

The application automatically configures TCPDF. No additional setup needed.

### Supported PDF Reports

1. **Daily Authentication Summary**
   - Navigate to: Reports > Daily Authentication Summary
   - Select date and click "Export PDF"

2. **Monthly Usage Summary**
   - Navigate to: Reports > Monthly Usage Summary
   - Select month and click "Export PDF"

3. **Failed Logins Report**
   - Navigate to: Reports > Failed Login Report
   - Set filters and click "Export PDF"

### PDF Generation Troubleshooting

If PDF export fails:

```bash
# Check TCPDF is installed
composer show tecnickcom/tcpdf

# Reinstall if needed
composer require tecnickcom/tcpdf

# Check PHP memory limit (PDF generation needs 128MB+)
php -i | grep memory_limit

# Increase if needed
sudo nano /etc/php/8.0/fpm/php.ini
# Set: memory_limit = 256M

# Restart PHP-FPM
sudo systemctl restart php8.0-fpm
```

---

## Post-Installation

### 1. Access the Application

Navigate to: `http://your-server/radius-gui/public/`

**Default Login:**
- Username: `admin`
- Password: `password`

**⚠️ IMPORTANT:** Change the default password immediately!

### 2. Security Checklist

- [ ] Change default admin password
- [ ] Enable HTTPS (recommended)
- [ ] Set `SESSION_SECURE=true` in .env if using HTTPS
- [ ] Review file permissions
- [ ] Configure firewall rules
- [ ] Enable error logging
- [ ] Set up regular backups

### 3. Configure Application

Navigate to **Settings** (superadmin only) to:
- View database statistics
- Check system configuration
- Monitor application health

### 4. Create Additional Users

Navigate to **User Management** > **Create Operator**

Three role levels:
- **Superadmin**: Full access (user management, settings)
- **Network Admin**: All reports, no user management
- **Helpdesk**: View-only (dashboard, online users, auth log, user history)

---

## Features Overview

### Dashboards
- **Main Dashboard**: Real-time KPIs, top users, top NAS, error summary
- **Online Users**: Active sessions with filters
- **Authentication Log**: Complete history with error tracking

### Reports
- **User Session History**: Per-user session lookup
- **Top Users by Data**: Bandwidth leaders
- **NAS/AP Usage**: Access point analytics
- **Error Analytics**: Deep dive into failures

### Report Generation
- **Daily Authentication Summary**: Daily stats with hourly breakdown
- **Monthly Usage Summary**: Month-wise usage statistics
- **Failed Login Report**: Users with multiple failures

### Export Options
- **CSV Export**: Available on all list views
- **PDF Export**: Available on all reports
- UTF-8 with BOM for Excel compatibility

---

## Maintenance

### Log Rotation

```bash
sudo nano /etc/logrotate.d/radius-gui
```

```
/var/www/html/radius-gui/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

### Database Maintenance

Archive old records periodically:

```sql
-- Archive radpostauth older than 90 days
CREATE TABLE IF NOT EXISTS radpostauth_archive LIKE radpostauth;

INSERT INTO radpostauth_archive
SELECT * FROM radpostauth WHERE authdate < NOW() - INTERVAL 90 DAY;

DELETE FROM radpostauth WHERE authdate < NOW() - INTERVAL 90 DAY;

-- Archive radacct older than 90 days
CREATE TABLE IF NOT EXISTS radacct_archive LIKE radacct;

INSERT INTO radacct_archive
SELECT * FROM radacct WHERE acctstarttime < NOW() - INTERVAL 90 DAY;

DELETE FROM radacct WHERE acctstarttime < NOW() - INTERVAL 90 DAY;
```

### Backups

```bash
# Backup database
mysqldump -u radius -p radius > radius_backup_$(date +%Y%m%d).sql

# Backup application
tar -czf radius-gui_backup_$(date +%Y%m%d).tar.gz /var/www/html/radius-gui
```

---

## Troubleshooting

### Cannot Login

**Check database connection:**
```bash
mysql -u radius -p
USE radius;
SELECT * FROM operators;
```

**Check operator password:**
```bash
# Reset password
mysql -u radius -p radius
UPDATE operators SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';
# Password is: password
```

### Permission Errors

```bash
sudo chown -R www-data:www-data /var/www/html/radius-gui
sudo chmod -R 755 /var/www/html/radius-gui
sudo chmod -R 775 /var/www/html/radius-gui/logs
```

### PDF Export Not Working

```bash
# Check TCPDF installation
composer show tecnickcom/tcpdf

# Reinstall
cd /var/www/html/radius-gui
composer require tecnickcom/tcpdf

# Check PHP memory
php -i | grep memory_limit
# Should be 128M or higher
```

### Database Connection Errors

Check `.env` file:
```bash
cat /var/www/html/radius-gui/.env
```

Test connection:
```bash
mysql -h <DB_HOST> -u <DB_USER> -p<DB_PASSWORD> <DB_NAME>
```

### Missing Data in Reports

Ensure migration applied:
```sql
SHOW COLUMNS FROM radpostauth LIKE 'reply_message';
SHOW COLUMNS FROM radpostauth LIKE 'error_type';
SHOW COLUMNS FROM radpostauth LIKE 'authdate_utc';
```

If columns missing, apply migration:
```bash
mysql -u radius -p radius < sql/01-add-error-tracking-columns.sql
```

---

## Performance Optimization

### MySQL Optimization

```sql
-- Add indexes for better performance
ALTER TABLE radpostauth ADD INDEX idx_authdate_reply (authdate, reply);
ALTER TABLE radacct ADD INDEX idx_acctstarttime (acctstarttime);

-- Enable query cache (if available)
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;
```

### PHP-FPM Tuning

```bash
sudo nano /etc/php/8.0/fpm/pool.d/www.conf
```

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500
```

### Enable OpCache

```bash
sudo nano /etc/php/8.0/fpm/php.ini
```

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

---

## Upgrading

### From Version 1.0 to 1.1 (Example)

```bash
# Backup
mysqldump -u radius -p radius > backup_before_upgrade.sql
tar -czf radius-gui_backup.tar.gz /var/www/html/radius-gui

# Pull latest code
cd /var/www/html/radius-gui
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader

# Run migrations (if any)
mysql -u radius -p radius < migrations/upgrade_1.1.sql

# Clear cache
rm -rf logs/*.log

# Restart services
sudo systemctl reload apache2  # or nginx
```

---

## Support

### Logs to Check

1. **Application logs**: `/var/www/html/radius-gui/logs/`
2. **Apache logs**: `/var/log/apache2/radius-gui-error.log`
3. **Nginx logs**: `/var/log/nginx/radius-gui-error.log`
4. **PHP-FPM logs**: `/var/log/php8.0-fpm.log`
5. **MySQL logs**: `/var/log/mysql/error.log`

### Debug Mode

Enable debug mode in `.env`:
```env
APP_DEBUG=true
```

**⚠️ Disable in production!**

---

## Security Best Practices

1. **Use HTTPS**: Always use SSL/TLS in production
2. **Strong Passwords**: Enforce minimum 12 characters
3. **Firewall**: Restrict access to trusted IPs
4. **Regular Updates**: Keep PHP, MySQL, and dependencies updated
5. **Disable Directory Listing**: Ensure `-Indexes` in Apache config
6. **Hide PHP Version**: Set `expose_php = Off` in php.ini
7. **File Upload Restrictions**: Disable if not needed
8. **SQL Injection Protection**: Application uses prepared statements
9. **XSS Protection**: All output is escaped
10. **CSRF Protection**: All forms use CSRF tokens

---

## License

This application is provided as-is for use with FreeRADIUS deployments.

---

## Credits

- Built for FreeRADIUS Google LDAP Dashboard
- Enhanced error tracking and reporting
- PDF generation powered by TCPDF

**Version**: 1.0.0
**Last Updated**: December 2025
