<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - RADIUS Dashboard' : 'RADIUS Dashboard' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            padding-top: 20px;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            color: white;
            z-index: 1000;
        }
        
        .sidebar .brand {
            padding: 0 20px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }
        
        .sidebar .brand h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .sidebar .brand small {
            opacity: 0.8;
        }
        
        .sidebar .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar .nav-menu li {
            margin: 0;
        }
        
        .sidebar .nav-menu a {
            display: block;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar .nav-menu a:hover,
        .sidebar .nav-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: white;
        }
        
        .sidebar .nav-menu i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-navbar .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .top-navbar .user-menu .user-name {
            font-weight: 500;
            color: #333;
        }
        
        .card-custom {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .card-custom .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 8px 8px 0 0;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-custom .card-body {
            padding: 20px;
        }
        
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .page-header h1 {
            color: #333;
            margin: 0;
            font-weight: 600;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-wifi"></i> RADIUS</h3>
            <small>Dashboard</small>
        </div>
        
        <ul class="nav-menu">
            <li><a href="index.php?page=dashboard" class="<?= (isset($_GET['page']) && $_GET['page'] === 'dashboard') || !isset($_GET['page']) ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="index.php?page=online-users" class="<?= isset($_GET['page']) && $_GET['page'] === 'online-users' ? 'active' : '' ?>"><i class="fas fa-users"></i> Online Users</a></li>
            <li><a href="index.php?page=auth-log" class="<?= isset($_GET['page']) && $_GET['page'] === 'auth-log' ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> Auth Log</a></li>
            <li><a href="index.php?page=user-history" class="<?= isset($_GET['page']) && $_GET['page'] === 'user-history' ? 'active' : '' ?>"><i class="fas fa-history"></i> User History</a></li>
            <li><a href="index.php?page=reports" class="<?= isset($_GET['page']) && $_GET['page'] === 'reports' ? 'active' : '' ?>"><i class="fas fa-file-pdf"></i> Reports</a></li>
            <li><a href="index.php?page=nas" class="<?= isset($_GET['page']) && $_GET['page'] === 'nas' ? 'active' : '' ?>"><i class="fas fa-network-wired"></i> NAS Management</a></li>
            <li><a href="index.php?page=settings" class="<?= isset($_GET['page']) && $_GET['page'] === 'settings' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="index.php?page=users" class="<?= isset($_GET['page']) && $_GET['page'] === 'users' ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> User Management</a></li>
            <li style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 20px;">
                <a href="index.php?page=users&action=change-password"><i class="fas fa-key"></i> Change Password</a>
            </li>
            <li>
                <a href="index.php?page=login&action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <h5 style="margin: 0;"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></h5>
            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-circle"></i>
                    <?= htmlspecialchars(Auth::user()['firstname'] ?? 'User') ?>
                </span>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="page-content">
