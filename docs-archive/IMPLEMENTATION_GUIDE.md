# RADIUS GUI - Complete Implementation Guide

This guide provides all the code needed to complete the RADIUS Reporting GUI application.

## Table of Contents

1. [Views - Login & Layout](#views---login--layout)
2. [Dashboard Controller & View](#dashboard-controller--view)
3. [Online Users](#online-users)
4. [Authentication Log](#authentication-log)
5. [Models](#models)
6. [Assets (CSS/JS)](#assets-cssjs)
7. [Additional Controllers](#additional-controllers)
8. [Quick Start Commands](#quick-start-commands)

---

## Views - Login & Layout

### app/views/auth/login.php

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RADIUS Reporting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
        }
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px 10px 0 0;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: bold;
            padding: 12px;
        }
        .btn-login:hover {
            opacity: 0.9;
            color: white;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-wifi"></i> RADIUS Reporting</h2>
                <p class="mb-0">Sign in to continue</p>
            </div>
            <div class="login-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?= Utils::e($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username"
                                   required autofocus value="<?= Utils::e(Utils::post('username')) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>
        </div>
        <p class="text-center text-white mt-3">
            <small>&copy; <?= date('Y') ?> RADIUS Reporting System v1.0</small>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

### app/views/layouts/header.php

```php
<?php
Auth::requireLogin();
$user = Auth::user();
$config = require APP_PATH . '/config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= $config['name'] ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 100;
            padding-top: 20px;
        }
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-brand h4 {
            margin: 0;
            font-size: 1.2rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid #3498db;
        }
        .sidebar-menu li a i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .top-navbar {
            background: white;
            padding: 15px 30px;
            margin: -20px -20px 20px -20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card .icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        .stats-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .stats-card.primary { border-left: 4px solid #3498db; }
        .stats-card.success { border-left: 4px solid #2ecc71; }
        .stats-card.danger { border-left: 4px solid #e74c3c; }
        .stats-card.warning { border-left: 4px solid #f39c12; }
        .stats-card.info { border-left: 4px solid #9b59b6; }

        .card-custom {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card-custom .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge-role {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php require APP_PATH . '/views/layouts/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><?= $pageTitle ?? 'Dashboard' ?></h5>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3">
                    <i class="fas fa-user-circle"></i>
                    <strong><?= Utils::e($user['fullname'] ?: $user['username']) ?></strong>
                    <span class="badge bg-primary badge-role ms-2"><?= Utils::e(ucfirst($user['role'])) ?></span>
                </span>
                <a href="index.php?page=login&action=logout" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <div class="content-wrapper">
```

### app/views/layouts/sidebar.php

```php
<?php
$currentPage = Utils::get('page', 'dashboard');
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <h4><i class="fas fa-wifi"></i> RADIUS</h4>
        <small>Reporting System</small>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php?page=dashboard" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="index.php?page=online-users" class="<?= $currentPage === 'online-users' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Online Users
            </a>
        </li>

        <li>
            <a href="index.php?page=auth-log" class="<?= $currentPage === 'auth-log' ? 'active' : '' ?>">
                <i class="fas fa-list-alt"></i> Authentication Log
            </a>
        </li>

        <li>
            <a href="index.php?page=user-history" class="<?= $currentPage === 'user-history' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> User Session History
            </a>
        </li>

        <?php if (Auth::can('top_users.view')): ?>
        <li>
            <a href="index.php?page=top-users" class="<?= $currentPage === 'top-users' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> Top Users by Usage
            </a>
        </li>
        <?php endif; ?>

        <?php if (Auth::can('nas_usage.view')): ?>
        <li>
            <a href="index.php?page=nas-usage" class="<?= $currentPage === 'nas-usage' ? 'active' : '' ?>">
                <i class="fas fa-network-wired"></i> NAS / AP Usage
            </a>
        </li>
        <?php endif; ?>

        <?php if (Auth::can('error_analytics.view')): ?>
        <li>
            <a href="index.php?page=error-analytics" class="<?= $currentPage === 'error-analytics' ? 'active' : '' ?>">
                <i class="fas fa-exclamation-triangle"></i> Error Analytics
            </a>
        </li>
        <?php endif; ?>

        <?php if (Auth::can('reports.view')): ?>
        <li>
            <a href="index.php?page=reports" class="<?= $currentPage === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> Reports
            </a>
        </li>
        <?php endif; ?>

        <?php if (Auth::hasRole('superadmin')): ?>
        <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
            <a href="index.php?page=users" class="<?= $currentPage === 'users' ? 'active' : '' ?>">
                <i class="fas fa-user-cog"></i> User Management
            </a>
        </li>

        <li>
            <a href="index.php?page=settings" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
        <?php endif; ?>
    </ul>
</div>
```

### app/views/layouts/footer.php

```php
        </div><!-- .content-wrapper -->
    </div><!-- .main-content -->

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <script>
        // Initialize DataTables
        $(document).ready(function() {
            if ($('.data-table').length) {
                $('.data-table').DataTable({
                    pageLength: 25,
                    ordering: true,
                    searching: true,
                    responsive: true,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "No entries to show",
                        infoFiltered: "(filtered from _MAX_ total entries)"
                    }
                });
            }

            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    </script>

    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>
```

---

## Dashboard Controller & View

### app/controllers/DashboardController.php

```php
<?php
/**
 * Dashboard Controller
 */

class DashboardController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('dashboard.view');
    }

    public function indexAction()
    {
        $pageTitle = 'Dashboard';

        // Get today's date
        $today = date('Y-m-d');

        // Total auth attempts today
        $totalAttempts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) = ?",
            [$today]
        ) ?: 0;

        // Successful authentications today
        $successfulAuths = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) = ? AND reply = 'Access-Accept'",
            [$today]
        ) ?: 0;

        // Failed authentications today
        $failedAuths = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) = ? AND reply != 'Access-Accept'",
            [$today]
        ) ?: 0;

        // Currently online users
        $onlineUsers = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL"
        ) ?: 0;

        // Calculate success rate
        $successRate = $totalAttempts > 0 ? Utils::percentage($successfulAuths, $totalAttempts) : 0;

        // Top 5 users by data today (from view)
        $topUsers = $this->db->fetchAll(
            "SELECT username, total_mb, session_count
             FROM user_bandwidth_today
             ORDER BY total_bytes DESC
             LIMIT 5"
        );

        // Top 5 NAS by sessions today
        $topNas = $this->db->fetchAll(
            "SELECT
                nasipaddress,
                COUNT(*) as session_count,
                COUNT(DISTINCT username) as unique_users
             FROM radacct
             WHERE DATE(acctstarttime) = ?
             GROUP BY nasipaddress
             ORDER BY session_count DESC
             LIMIT 5",
            [$today]
        );

        // Top 5 error types today
        $topErrors = $this->db->fetchAll(
            "SELECT
                COALESCE(error_type, 'Unknown') as error_type,
                COUNT(*) as count
             FROM radpostauth
             WHERE DATE(authdate) = ? AND reply != 'Access-Accept'
             GROUP BY error_type
             ORDER BY count DESC
             LIMIT 5",
            [$today]
        );

        require APP_PATH . '/views/dashboard/index.php';
    }
}
```

### app/views/dashboard/index.php

```php
<?php require APP_PATH . '/views/layouts/header.php'; ?>

<!-- KPI Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="stats-card primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Total Attempts</p>
                    <h3><?= number_format($totalAttempts) ?></h3>
                    <small>Today</small>
                </div>
                <div class="icon">
                    <i class="fas fa-key text-primary"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Successful</p>
                    <h3><?= number_format($successfulAuths) ?></h3>
                    <small><?= $successRate ?>% success rate</small>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Failed</p>
                    <h3><?= number_format($failedAuths) ?></h3>
                    <small>Today</small>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Online Now</p>
                    <h3><?= number_format($onlineUsers) ?></h3>
                    <small>Active sessions</small>
                </div>
                <div class="icon">
                    <i class="fas fa-users text-warning"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Top Users by Data -->
    <div class="col-md-6">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Top 5 Users by Data (Today)
            </div>
            <div class="card-body">
                <?php if (empty($topUsers)): ?>
                    <p class="text-muted text-center">No data available</p>
                <?php else: ?>
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Sessions</th>
                                <th>Total Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topUsers as $user): ?>
                            <tr>
                                <td><?= Utils::e($user['username']) ?></td>
                                <td><?= number_format($user['session_count']) ?></td>
                                <td><?= Utils::e($user['total_mb']) ?> MB</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top NAS by Sessions -->
    <div class="col-md-6">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-network-wired"></i> Top 5 NAS by Sessions (Today)
            </div>
            <div class="card-body">
                <?php if (empty($topNas)): ?>
                    <p class="text-muted text-center">No data available</p>
                <?php else: ?>
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>NAS IP</th>
                                <th>Sessions</th>
                                <th>Unique Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topNas as $nas): ?>
                            <tr>
                                <td><?= Utils::e($nas['nasipaddress']) ?></td>
                                <td><?= number_format($nas['session_count']) ?></td>
                                <td><?= number_format($nas['unique_users']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Error Summary -->
    <div class="col-md-12">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-exclamation-triangle"></i> Top Errors Today
            </div>
            <div class="card-body">
                <?php if (empty($topErrors)): ?>
                    <p class="text-muted text-center">No errors today 🎉</p>
                <?php else: ?>
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Error Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topErrors as $error): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-danger">
                                        <?= Utils::e(ucwords(str_replace('_', ' ', $error['error_type']))) ?>
                                    </span>
                                </td>
                                <td><?= number_format($error['count']) ?></td>
                                <td>
                                    <?= Utils::percentage($error['count'], $failedAuths) ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
```

---

Due to character limits, I'll create a comprehensive ZIP file structure document:

## Complete File Structure Reference

The complete application includes:

### Controllers (13 files)
1. LoginController.php ✓ (created)
2. DashboardController.php ✓ (created)
3. OnlineUsersController.php
4. AuthLogController.php
5. UserHistoryController.php
6. TopUsersController.php
7. NasUsageController.php
8. ErrorAnalyticsController.php
9. ReportsController.php
10. UsersController.php
11. SettingsController.php

### Views (20+ files)
- Layouts: header.php ✓, sidebar.php ✓, footer.php ✓
- Auth: login.php ✓
- Dashboard: index.php ✓
- Online Users: index.php
- Auth Log: index.php
- User History: index.php
- Top Users: index.php
- NAS Usage: index.php
- Error Analytics: index.php
- Reports: index.php, daily-auth.php, monthly-usage.php, failed-logins.php
- Users: index.php, create.php, edit.php
- Settings: index.php

### Models (4 files)
1. RadacctModel.php
2. RadpostauthModel.php
3. NasModel.php
4. OperatorModel.php

## Quick Installation

```bash
cd /var/www/html
git clone <your-repo> radius-gui
cd radius-gui

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 logs

# Configure database
cp app/config/database.php.example app/config/database.php
nano app/config/database.php

# Apply database migration (if not done)
mysql -u radius -p radius < ../sql/01-add-error-tracking-columns.sql

# Create initial admin account
mysql -u radius -p radius
INSERT INTO operators (username, password, firstname, lastname, createusers)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'System', 'Administrator', 1);
```

Access at: `http://your-server/radius-gui/public/`

Login: `admin` / `password`

---

## Next Steps

The foundation is complete. You have:

✅ Complete project structure
✅ Database connectivity with PDO
✅ Authentication system with RBAC
✅ Login page
✅ Base layout templates (header, sidebar, footer)
✅ Dashboard with KPIs
✅ Helper utilities (formatBytes, formatDuration, CSV export, etc.)

To complete the application, you need to create the remaining controllers and views following the same patterns shown above. Each follows the MVC structure:

1. Controller fetches data using Database helper
2. View receives data and displays with Bootstrap
3. All use the same header/footer layout
4. All respect role-based permissions

Would you like me to continue creating the remaining controllers (Online Users, Auth Log, etc.) or would you prefer specific pages first?
