# Migration Guide: Legacy Dashboard → Modern Dashboard

This guide helps you migrate from the old dashboard (`dashboard/`) to the new modern dashboard (`radius-gui/`).

---

## Quick Overview

✅ **Good News:** Migration is straightforward! The new dashboard uses the same database schema, so no data migration is needed.

**Time Required:** 10-15 minutes
**Downtime:** Minimal (only during Docker container restart)
**Data Migration:** None required (100% compatible)

---

## Table of Contents

1. [Pre-Migration Checklist](#pre-migration-checklist)
2. [Step-by-Step Migration](#step-by-step-migration)
3. [Feature Comparison](#feature-comparison)
4. [Configuration Changes](#configuration-changes)
5. [Testing the New Dashboard](#testing-the-new-dashboard)
6. [Rollback Procedure](#rollback-procedure)
7. [Troubleshooting](#troubleshooting)
8. [FAQ](#faq)

---

## Pre-Migration Checklist

Before starting the migration, ensure you have:

- [ ] Access to the server running Docker
- [ ] Backup of current `.env` file
- [ ] Database backup (recommended but optional)
- [ ] Admin credentials for operators table
- [ ] Review of new features in [README.md](README.md)

### Optional: Create Backup

```bash
# Backup database
docker exec radius-mysql mysqldump -u radius -p radius > backup_$(date +%Y%m%d).sql

# Backup .env file
cp .env .env.backup

# Backup docker-compose.yml
cp docker-compose.yml docker-compose.yml.backup
```

---

## Step-by-Step Migration

### Step 1: Update Docker Compose Configuration

The main `docker-compose.yml` has already been updated to use the new dashboard. If you're using a custom version, update the dashboard service:

**Before (Old Dashboard):**
```yaml
dashboard:
  build:
    context: ./dashboard
    dockerfile: Dockerfile
  container_name: radius-dashboard
  # ... old config
```

**After (New Dashboard):**
```yaml
webapp:
  build:
    context: ./radius-gui
    dockerfile: Dockerfile
  container_name: radius-webapp
  # ... new config
```

### Step 2: Update Environment Variables

Add new webapp-specific variables to your `.env` file:

```bash
# Add these new variables for the modern dashboard
APP_URL=http://localhost:8080
APP_TIMEZONE=Asia/Kolkata
SESSION_SECURE=false
SESSION_LIFETIME=7200
APP_ENV=production
APP_DEBUG=false
```

You can keep the old dashboard variables for reference, but they're no longer used.

### Step 3: Install PHP Dependencies

The new dashboard uses Composer for dependency management (including TCPDF for PDF generation):

```bash
cd radius-gui
composer install --no-dev --optimize-autoloader
```

Or let Docker handle it automatically during build.

### Step 4: Apply Database Migrations

Ensure the enhanced error tracking columns are present:

```bash
# Connect to MySQL
docker exec -it radius-mysql mysql -u radius -p

# Check if columns exist
SHOW COLUMNS FROM radpostauth LIKE 'reply_message';
SHOW COLUMNS FROM radpostauth LIKE 'error_type';

# If missing, apply migration
docker exec -i radius-mysql mysql -u radius -p radius < sql/01-add-error-tracking-columns.sql
```

### Step 5: Verify Operators Table

The new dashboard uses the existing `operators` table for authentication. Verify you have at least one admin account:

```bash
docker exec -it radius-mysql mysql -u radius -p

# Check operators
SELECT username, firstname, lastname, createusers FROM operators;

# If no accounts exist, create default admin
INSERT INTO operators (username, password, firstname, lastname, email, createusers)
VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'System',
    'Administrator',
    'admin@example.com',
    1
);
# Default password: password
```

### Step 6: Stop Old Dashboard and Start New Dashboard

```bash
# Stop the old dashboard
docker-compose stop dashboard

# Remove old dashboard container
docker-compose rm -f dashboard

# Build and start new webapp
docker-compose up -d --build webapp

# Check logs
docker-compose logs -f webapp
```

### Step 7: Verify New Dashboard

1. **Access the dashboard:** http://localhost:8080/radius-gui/public/
   - Or: http://your-server:8080/radius-gui/public/

2. **Login with existing credentials:**
   - Username: Your existing operator username (e.g., `admin`)
   - Password: Your existing operator password (e.g., `password`)

3. **Verify functionality:**
   - Dashboard loads with KPI cards
   - Online users page shows active sessions
   - Authentication log displays recent authentications
   - CSV export works
   - PDF export works (try a report)

---

## Feature Comparison

| Feature | Legacy Dashboard | New Dashboard |
|---------|-----------------|---------------|
| **Architecture** | Monolithic (single file) | MVC (modular) |
| **Total Pages** | 5 basic pages | 14 comprehensive pages |
| **Authentication Log** | Basic list | Enhanced with error tracking |
| **Error Tracking** | Limited | Full error categorization |
| **Reports** | None | 3 advanced reports |
| **Export Formats** | CSV only | CSV + PDF |
| **PDF Generation** | ❌ No | ✅ Yes (TCPDF) |
| **Role-Based Access** | Basic | 3-tier RBAC |
| **User Management** | ❌ No | ✅ Yes (CRUD operators) |
| **Settings Page** | ❌ No | ✅ Yes |
| **UI Framework** | Basic HTML/CSS | Bootstrap 5 |
| **DataTables** | ❌ No | ✅ Yes |
| **Chart.js** | ❌ No | ✅ Yes |
| **Security** | Basic | Enterprise-grade |
| **Password Hashing** | SHA-256/MD5 | Bcrypt (auto-upgrade) |
| **CSRF Protection** | Limited | Full coverage |
| **Session Management** | Basic | Secure with regeneration |

---

## Configuration Changes

### Database Configuration

**No changes required!** Both dashboards use the same database configuration:

- Host: Same (`DB_HOST`)
- Database: Same (`DB_NAME`)
- User: Same (`DB_USER`)
- Password: Same (`DB_PASSWORD`)

### Authentication

**Old Dashboard:**
- Used JWT tokens
- Custom authentication logic
- Environment variables for admin user

**New Dashboard:**
- Uses PHP sessions
- Database-based authentication (operators table)
- Bcrypt password hashing
- Auto-upgrades legacy passwords (SHA-256/MD5 → bcrypt)

### URL Structure

**Old Dashboard:**
- Root: `http://localhost:8080/`
- Pages: `http://localhost:8080/?page=...`

**New Dashboard:**
- Root: `http://localhost:8080/radius-gui/public/`
- Pages: `http://localhost:8080/radius-gui/public/?page=...`

You can configure Apache/Nginx to make the new dashboard available at root (`/`) if desired.

---

## Testing the New Dashboard

### Test Checklist

After migration, test these key features:

#### Authentication
- [ ] Login with admin credentials
- [ ] Login with non-admin operator
- [ ] Logout functionality
- [ ] Session persistence
- [ ] Password change (if implemented)

#### Pages
- [ ] Dashboard loads with KPIs
- [ ] Online Users page shows active sessions
- [ ] Authentication Log displays recent logs
- [ ] User History lookup works
- [ ] Top Users by Data displays correctly
- [ ] NAS Usage shows statistics
- [ ] Error Analytics displays error breakdown
- [ ] Reports Hub is accessible

#### Reports
- [ ] Daily Authentication Summary
- [ ] Monthly Usage Summary
- [ ] Failed Login Report

#### Exports
- [ ] CSV export from Online Users
- [ ] CSV export from Authentication Log
- [ ] PDF export from Daily Auth report
- [ ] PDF export from Monthly Usage report
- [ ] PDF export from Failed Logins report

#### User Management (Superadmin only)
- [ ] View operators list
- [ ] Create new operator
- [ ] Edit existing operator
- [ ] Delete operator (with safeguards)

#### Settings (Superadmin only)
- [ ] View database statistics
- [ ] View configuration
- [ ] System information display

---

## Rollback Procedure

If you need to rollback to the old dashboard:

### Option 1: Quick Rollback (Temporary)

```bash
# Stop new dashboard
docker-compose stop webapp

# Uncomment the dashboard-legacy service in docker-compose.yml
# (See the commented section at the bottom of the file)

# Start old dashboard
docker-compose up -d dashboard-legacy
```

### Option 2: Full Rollback (Restore from Backup)

```bash
# Stop all services
docker-compose down

# Restore docker-compose.yml
cp docker-compose.yml.backup docker-compose.yml

# Restore .env
cp .env.backup .env

# Restore database (if needed)
docker-compose up -d mysql
docker exec -i radius-mysql mysql -u radius -p radius < backup_YYYYMMDD.sql

# Start old dashboard
docker-compose up -d dashboard
```

---

## Troubleshooting

### Issue: Cannot Login

**Symptoms:** Login page loads, but credentials don't work.

**Solutions:**

1. **Verify operators table exists:**
   ```sql
   SHOW TABLES LIKE 'operators';
   ```

2. **Check if admin account exists:**
   ```sql
   SELECT * FROM operators WHERE username='admin';
   ```

3. **Reset admin password:**
   ```sql
   UPDATE operators
   SET password='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
   WHERE username='admin';
   -- Password: password
   ```

### Issue: Page Not Found / 404 Error

**Symptoms:** Accessing dashboard returns 404.

**Solutions:**

1. **Check URL:** Ensure you're accessing `/radius-gui/public/`
2. **Check Apache mod_rewrite:**
   ```bash
   docker exec radius-webapp apache2ctl -M | grep rewrite
   ```
3. **Check file permissions:**
   ```bash
   docker exec radius-webapp ls -la /var/www/html/radius-gui/public/
   ```

### Issue: PDF Export Not Working

**Symptoms:** PDF download fails or returns error.

**Solutions:**

1. **Check TCPDF is installed:**
   ```bash
   docker exec radius-webapp ls -la vendor/tecnickcom/tcpdf/
   ```

2. **Reinstall dependencies:**
   ```bash
   docker exec radius-webapp composer require tecnickcom/tcpdf
   ```

3. **Check PHP memory limit:**
   ```bash
   docker exec radius-webapp php -i | grep memory_limit
   ```

### Issue: Database Connection Error

**Symptoms:** "Cannot connect to database" error.

**Solutions:**

1. **Verify .env variables:**
   ```bash
   cat .env | grep DB_
   ```

2. **Test MySQL connection:**
   ```bash
   docker exec radius-webapp mysql -h $DB_HOST -u $DB_USER -p$DB_PASSWORD $DB_NAME
   ```

3. **Check MySQL is running:**
   ```bash
   docker ps | grep mysql
   ```

---

## FAQ

### Q: Do I need to migrate my data?

**A:** No! The new dashboard uses the same database tables as the old dashboard. All your existing data (authentication logs, accounting records, users) will be immediately available.

### Q: Will my existing operator accounts work?

**A:** Yes! The new dashboard uses the same `operators` table. All existing accounts will work. If your password is hashed with SHA-256 or MD5, it will be automatically upgraded to bcrypt on first login.

### Q: Can I run both dashboards simultaneously?

**A:** Yes, temporarily. Uncomment the `dashboard-legacy` service in docker-compose.yml and change its port to 8081. However, this is not recommended for production.

### Q: What happens to my custom modifications to the old dashboard?

**A:** Custom modifications to the old dashboard code will need to be re-implemented in the new dashboard. The new MVC architecture makes this easier - contact support for guidance.

### Q: Do I need to update FreeRADIUS configuration?

**A:** No. The dashboard change doesn't affect FreeRADIUS. Your RADIUS authentication will continue working without any changes.

### Q: What about my existing reports and exports?

**A:** The new dashboard provides the same data plus more. You can export the same information in both CSV and PDF formats.

### Q: Is the new dashboard slower?

**A:** No. The new dashboard is optimized with proper indexing, prepared statements, and efficient queries. Most users report similar or better performance.

### Q: Can I customize the new dashboard?

**A:** Yes! The MVC architecture makes customization much easier. You can:
- Add new pages (create controller + view)
- Modify existing pages (edit views)
- Add new features (extend controllers)
- Change styling (Bootstrap 5 customization)

---

## Support

Need help with migration?

- **Documentation:** [README.md](README.md), [DEPLOYMENT.md](DEPLOYMENT.md)
- **Issues:** Check [TESTING.md](../TESTING.md) for common problems
- **Legacy Dashboard:** See [archive/dashboard-legacy/README.md](../archive/dashboard-legacy/README.md)

---

**Migration Version:** 1.0
**Last Updated:** December 2024
**Compatibility:** Legacy Dashboard (all versions) → Modern Dashboard 1.0+
