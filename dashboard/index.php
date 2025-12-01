<?php
require_once 'auth.php';
requireLogin();

// Use PDO connection from auth.php
// $pdo is already available from auth.php

// Parse DOMAIN_CONFIG from environment variable
function getDomainConfig() {
    $domainConfigJson = getenv('DOMAIN_CONFIG');
    if (!$domainConfigJson) {
        // Fallback to default if not set
        return [
            ['domain' => 'krea.edu.in', 'Type' => 'Staff', 'VLAN' => '156'],
            ['domain' => 'krea.ac.in', 'Type' => 'Student', 'VLAN' => '156'],
            ['domain' => 'ifmr.ac.in', 'Type' => 'Other Center', 'VLAN' => '156']
        ];
    }
    
    $config = json_decode($domainConfigJson, true);
    return $config ?: [];
}

// Create domain to role mapping
function getDomainRoleMap() {
    $domains = getDomainConfig();
    $map = [];
    foreach ($domains as $domain) {
        $map[$domain['domain']] = $domain['Type'];
    }
    return $map;
}

// Generate SQL CASE statement for role determination
function generateRoleCaseStatement() {
    $domains = getDomainConfig();
    if (empty($domains)) {
        return "'Other'";
    }
    
    $caseStatement = "CASE ";
    foreach ($domains as $domain) {
        $domainEscaped = addslashes($domain['domain']);
        $typeEscaped = addslashes($domain['Type']);
        $caseStatement .= "WHEN SUBSTRING_INDEX(username, '@', -1) = '{$domainEscaped}' THEN '{$typeEscaped}' ";
    }
    $caseStatement .= "ELSE 'Other' END";
    
    return $caseStatement;
}

$domainRoleMap = getDomainRoleMap();
$roleCaseStatement = generateRoleCaseStatement();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RADIUS Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <style>
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .export-buttons {
            margin-bottom: 15px;
        }
        .collapsible-section {
            cursor: pointer;
            user-select: none;
        }
        .section-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .section-content.show {
            max-height: 2000px;
            transition: max-height 0.5s ease-in;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-wifi"></i> FreeRADIUS Admin
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="change-password.php">
                            <i class="fas fa-key"></i> Change Password
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="login.php?logout=1">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Filters Section -->
        <div class="filter-section">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-calendar"></i> Date Range</label>
                    <select class="form-select" id="dateRangeFilter">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last7days" selected>Last 7 Days</option>
                        <option value="last30days">Last 30 Days</option>
                        <option value="thismonth">This Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-globe"></i> Domain Filter</label>
                    <select class="form-select" id="domainFilter">
                        <option value="all">All Domains</option>
                        <?php
                        $domains = getDomainConfig();
                        foreach ($domains as $domain) {
                            echo "<option value='{$domain['domain']}'>{$domain['domain']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-user-tag"></i> Role Filter</label>
                    <select class="form-select" id="roleFilter">
                        <option value="all">All Roles</option>
                        <option value="Staff">Staff</option>
                        <option value="Student">Student</option>
                        <option value="Admin">Admin</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-md-3">
                <div class="card bg-primary text-white stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Online Users</h4>
                                <h2 id="onlineUsers">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-success text-white stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Total Sessions Today</h4>
                                <h2 id="totalSessions">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM radacct WHERE DATE(acctstarttime) = CURDATE()");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-info text-white stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Auth Success Rate</h4>
                                <h2 id="successRate">
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT
                                            ROUND(
                                                (SUM(CASE WHEN authdate >= CURDATE() AND reply = 'Access-Accept' THEN 1 ELSE 0 END) /
                                                 NULLIF(COUNT(*), 0) * 100), 1
                                            ) as success_rate
                                        FROM radpostauth
                                        WHERE authdate >= CURDATE()
                                    ");
                                    $rate = $stmt->fetchColumn();
                                    echo $rate ? $rate . '%' : '0%';
                                    ?>
                                </h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-warning text-white stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Failed Auths Today</h4>
                                <h2 id="failedAuths">
                                    <?php
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM radpostauth WHERE authdate >= CURDATE() AND reply != 'Access-Accept'");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </h2>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header collapsible-section" onclick="toggleSection('sessionChart')">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar"></i> Session Trends (Last 7 Days)
                            <i class="fas fa-chevron-down float-end"></i>
                        </h5>
                    </div>
                    <div class="card-body section-content show" id="sessionChart">
                        <div class="chart-container">
                            <canvas id="sessionTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header collapsible-section" onclick="toggleSection('authChart')">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie"></i> Authentication Distribution
                            <i class="fas fa-chevron-down float-end"></i>
                        </h5>
                    </div>
                    <div class="card-body section-content show" id="authChart">
                        <div class="chart-container">
                            <canvas id="authDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Domain Statistics -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header collapsible-section" onclick="toggleSection('domainStats')">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-globe"></i> Domain Statistics (Last 24 Hours)
                            <i class="fas fa-chevron-down float-end"></i>
                        </h5>
                    </div>
                    <div class="card-body section-content show" id="domainStats">
                        <div class="export-buttons">
                            <button class="btn btn-success btn-sm" onclick="exportTableToExcel('domainStatsTable', 'Domain_Statistics')">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="exportTableToPDF('domainStatsTable', 'Domain Statistics')">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </button>
                            <button class="btn btn-info btn-sm" onclick="exportTableToCSV('domainStatsTable', 'Domain_Statistics')">
                                <i class="fas fa-file-csv"></i> Export to CSV
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="domainStatsTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Domain</th>
                                        <th>Role</th>
                                        <th>Active Sessions</th>
                                        <th>Total Sessions</th>
                                        <th>Success Rate</th>
                                        <th>Data Transferred (MB)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Use dynamic role case statement
                                    $query = "
                                        SELECT
                                            SUBSTRING_INDEX(username, '@', -1) as domain,
                                            {$roleCaseStatement} as role,
                                            COUNT(CASE WHEN acctstoptime IS NULL THEN 1 END) as active_sessions,
                                            COUNT(*) as total_sessions,
                                            ROUND(
                                                (COUNT(CASE WHEN acctstoptime IS NOT NULL OR acctstoptime IS NULL THEN 1 END) /
                                                 COUNT(*) * 100), 1
                                            ) as success_rate,
                                            COALESCE(SUM(acctinputoctets + acctoutputoctets), 0) as total_bytes
                                        FROM radacct
                                        WHERE acctstarttime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                        GROUP BY domain, role
                                        ORDER BY total_sessions DESC
                                    ";

                                    $stmt = $pdo->query($query);

                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $data_mb = round($row['total_bytes'] / (1024 * 1024), 2);

                                        // Dynamic badge color based on role type
                                        $roleBadgeClass = 'bg-info'; // default
                                        if (stripos($row['role'], 'staff') !== false || stripos($row['role'], 'faculty') !== false) {
                                            $roleBadgeClass = 'bg-success';
                                        } elseif (stripos($row['role'], 'student') !== false) {
                                            $roleBadgeClass = 'bg-primary';
                                        } elseif (stripos($row['role'], 'admin') !== false) {
                                            $roleBadgeClass = 'bg-danger';
                                        }

                                        echo "<tr>";
                                        echo "<td>{$row['domain']}</td>";
                                        echo "<td>{$row['role']}</td>";
                                        echo "<td>{$row['active_sessions']}</td>";
                                        echo "<td>{$row['total_sessions']}</td>";
                                        echo "<td>{$row['success_rate']}%</td>";
                                        echo "<td>{$data_mb}</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Authentication Attempts -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header collapsible-section" onclick="toggleSection('authAttempts')">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history"></i> Recent Authentication Attempts
                            <i class="fas fa-chevron-down float-end"></i>
                        </h5>
                    </div>
                    <div class="card-body section-content show" id="authAttempts">
                        <div class="export-buttons">
                            <button class="btn btn-success btn-sm" onclick="exportTableToExcel('authAttemptsTable', 'Authentication_Attempts')">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="exportTableToPDF('authAttemptsTable', 'Authentication Attempts')">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </button>
                            <button class="btn btn-info btn-sm" onclick="exportTableToCSV('authAttemptsTable', 'Authentication_Attempts')">
                                <i class="fas fa-file-csv"></i> Export to CSV
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="authAttemptsTable" class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Username</th>
                                        <th>Domain</th>
                                        <th>Status</th>
                                        <th>Reply</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT authdate, username, reply
                                        FROM radpostauth
                                        ORDER BY authdate DESC
                                        LIMIT 100
                                    ");

                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $status_class = $row['reply'] == 'Access-Accept' ? 'success' : 'danger';
                                        $status_icon = $row['reply'] == 'Access-Accept' ? 'check-circle' : 'times-circle';
                                        $domain = strpos($row['username'], '@') !== false ? substr($row['username'], strpos($row['username'], '@') + 1) : 'N/A';

                                        echo "<tr>";
                                        echo "<td>" . date('Y-m-d', strtotime($row['authdate'])) . "</td>";
                                        echo "<td>" . date('H:i:s', strtotime($row['authdate'])) . "</td>";
                                        echo "<td>{$row['username']}</td>";
                                        echo "<td>{$domain}</td>";
                                        echo "<td><i class='fas fa-{$status_icon} text-{$status_class}'></i> " . ($row['reply'] == 'Access-Accept' ? 'Success' : 'Failed') . "</td>";
                                        echo "<td>{$row['reply']}</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header collapsible-section" onclick="toggleSection('activeSessions')">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-wifi"></i> Active Sessions
                            <i class="fas fa-chevron-down float-end"></i>
                        </h5>
                    </div>
                    <div class="card-body section-content show" id="activeSessions">
                        <div class="export-buttons">
                            <button class="btn btn-success btn-sm" onclick="exportTableToExcel('activeSessionsTable', 'Active_Sessions')">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="exportTableToPDF('activeSessionsTable', 'Active Sessions')">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </button>
                            <button class="btn btn-info btn-sm" onclick="exportTableToCSV('activeSessionsTable', 'Active_Sessions')">
                                <i class="fas fa-file-csv"></i> Export to CSV
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="refreshActiveSessions()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table id="activeSessionsTable" class="table table-sm table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Domain</th>
                                        <th>Session Started</th>
                                        <th>Client IP</th>
                                        <th>NAS IP</th>
                                        <th>NAS Port</th>
                                        <th>Data In (MB)</th>
                                        <th>Data Out (MB)</th>
                                        <th>Total Data (MB)</th>
                                        <th>Duration</th>
                                        <th>Session ID</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT username, acctstarttime, framedipaddress, nasipaddress,
                                               acctinputoctets, acctoutputoctets, radacctid, nasportid,
                                               acctsessionid,
                                               TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) as duration_seconds
                                        FROM radacct
                                        WHERE acctstoptime IS NULL
                                        ORDER BY acctstarttime DESC
                                    ");

                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $data_in_mb = round(($row['acctinputoctets'] ?? 0) / (1024 * 1024), 2);
                                        $data_out_mb = round(($row['acctoutputoctets'] ?? 0) / (1024 * 1024), 2);
                                        $total_data_mb = $data_in_mb + $data_out_mb;
                                        $duration = gmdate('H:i:s', $row['duration_seconds']);
                                        $domain = strpos($row['username'], '@') !== false ? substr($row['username'], strpos($row['username'], '@') + 1) : 'N/A';

                                        echo "<tr>";
                                        echo "<td>{$row['username']}</td>";
                                        echo "<td>{$domain}</td>";
                                        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['acctstarttime'])) . "</td>";
                                        echo "<td>" . ($row['framedipaddress'] ?? 'N/A') . "</td>";
                                        echo "<td>" . ($row['nasipaddress'] ?? 'N/A') . "</td>";
                                        echo "<td>" . ($row['nasportid'] ?? 'N/A') . "</td>";
                                        echo "<td>{$data_in_mb}</td>";
                                        echo "<td>{$data_out_mb}</td>";
                                        echo "<td><strong>{$total_data_mb}</strong></td>";
                                        echo "<td>{$duration}</td>";
                                        echo "<td><small>" . substr($row['acctsessionid'] ?? 'N/A', 0, 20) . "...</small></td>";
                                        echo "<td><button class='btn btn-sm btn-danger' onclick='disconnectSession({$row['radacctid']})'>";
                                        echo "<i class='fas fa-stop'></i></button></td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery (required for DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        // Initialize DataTables
        $(document).ready(function() {
            // Domain Statistics Table
            $('#domainStatsTable').DataTable({
                pageLength: 10,
                order: [[3, 'desc']], // Sort by Total Sessions
                dom: 'Bfrtip',
                language: {
                    search: "Search domains:"
                }
            });

            // Authentication Attempts Table
            $('#authAttemptsTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc'], [1, 'desc']], // Sort by Date and Time
                dom: 'Bfrtip',
                language: {
                    search: "Search attempts:"
                }
            });

            // Active Sessions Table
            $('#activeSessionsTable').DataTable({
                pageLength: 15,
                order: [[2, 'desc']], // Sort by Session Started
                dom: 'Bfrtip',
                language: {
                    search: "Search sessions:"
                },
                columnDefs: [
                    { orderable: false, targets: -1 } // Disable sorting on Action column
                ]
            });

            // Initialize charts
            initializeCharts();
        });

        // Chart.js initialization
        function initializeCharts() {
            // Session Trend Chart (Last 7 Days)
            <?php
            // Get session data for last 7 days
            $stmt = $pdo->query("
                SELECT DATE(acctstarttime) as date, COUNT(*) as sessions
                FROM radacct
                WHERE acctstarttime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(acctstarttime)
                ORDER BY date ASC
            ");
            $sessionData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $dates = array_map(function($row) { return $row['date']; }, $sessionData);
            $sessions = array_map(function($row) { return $row['sessions']; }, $sessionData);
            ?>

            const sessionCtx = document.getElementById('sessionTrendChart').getContext('2d');
            new Chart(sessionCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Sessions',
                        data: <?php echo json_encode($sessions); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Authentication Distribution Chart
            <?php
            $stmt = $pdo->query("
                SELECT
                    SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as success,
                    SUM(CASE WHEN reply != 'Access-Accept' THEN 1 ELSE 0 END) as failed
                FROM radpostauth
                WHERE authdate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $authData = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>

            const authCtx = document.getElementById('authDistributionChart').getContext('2d');
            new Chart(authCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Success', 'Failed'],
                    datasets: [{
                        data: [<?php echo $authData['success']; ?>, <?php echo $authData['failed']; ?>],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(255, 99, 132, 0.8)'
                        ],
                        borderColor: [
                            'rgb(75, 192, 192)',
                            'rgb(255, 99, 132)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Toggle collapsible sections
        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId);
            section.classList.toggle('show');

            // Rotate chevron icon
            const header = section.previousElementSibling;
            const icon = header.querySelector('.fa-chevron-down');
            if (section.classList.contains('show')) {
                icon.style.transform = 'rotate(180deg)';
            } else {
                icon.style.transform = 'rotate(0deg)';
            }
        }

        // Export to Excel
        function exportTableToExcel(tableId, filename) {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
            XLSX.writeFile(wb, filename + '_' + new Date().toISOString().slice(0,10) + '.xlsx');
        }

        // Export to CSV
        function exportTableToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
            XLSX.writeFile(wb, filename + '_' + new Date().toISOString().slice(0,10) + '.csv');
        }

        // Export to PDF
        function exportTableToPDF(tableId, title) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4'); // Landscape orientation

            // Add title
            doc.setFontSize(16);
            doc.text(title, 14, 15);

            // Add date
            doc.setFontSize(10);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 22);

            // Get table data
            const table = document.getElementById(tableId);

            doc.autoTable({
                html: table,
                startY: 30,
                theme: 'striped',
                headStyles: { fillColor: [102, 126, 234] },
                styles: { fontSize: 8 },
                margin: { top: 30 }
            });

            doc.save(title.replace(/\s+/g, '_') + '_' + new Date().toISOString().slice(0,10) + '.pdf');
        }

        // Apply Filters
        function applyFilters() {
            const dateRange = document.getElementById('dateRangeFilter').value;
            const domain = document.getElementById('domainFilter').value;
            const role = document.getElementById('roleFilter').value;

            // Apply filters using DataTables API
            const domainTable = $('#domainStatsTable').DataTable();
            const authTable = $('#authAttemptsTable').DataTable();
            const sessionTable = $('#activeSessionsTable').DataTable();

            // Apply domain filter
            if (domain !== 'all') {
                domainTable.column(0).search(domain).draw();
                authTable.column(3).search(domain).draw();
                sessionTable.column(1).search(domain).draw();
            } else {
                domainTable.column(0).search('').draw();
                authTable.column(3).search('').draw();
                sessionTable.column(1).search('').draw();
            }

            // Apply role filter
            if (role !== 'all') {
                domainTable.column(1).search(role).draw();
            } else {
                domainTable.column(1).search('').draw();
            }

            // Note: Date range filter would require server-side implementation
            if (dateRange !== 'last7days') {
                alert('Date range filter requires page reload. This will be implemented with AJAX in future updates.');
            }
        }

        // Refresh Active Sessions
        function refreshActiveSessions() {
            location.reload();
        }

        // Disconnect Session
        function disconnectSession(sessionId) {
            if (confirm('Are you sure you want to disconnect this session?')) {
                fetch('disconnect.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ sessionId: sessionId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Session disconnected successfully!');
                        location.reload();
                    } else {
                        alert('Failed to disconnect session: ' + data.error);
                    }
                });
            }
        }

        // Auto-refresh statistics every 60 seconds (without full page reload)
        setInterval(() => {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('onlineUsers').textContent = data.onlineUsers;
                        document.getElementById('totalSessions').textContent = data.totalSessions;
                        document.getElementById('successRate').textContent = data.successRate + '%';
                        document.getElementById('failedAuths').textContent = data.failedAuths;
                    }
                })
                .catch(error => console.log('Stats refresh failed:', error));
        }, 60000);
    </script>
</body>
</html>