<?php
require_once 'auth.php';
requireLogin();

// Use PDO connection from auth.php
// $pdo is already available from auth.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RADIUS Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Online Users</h4>
                                <h2>
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
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Total Sessions Today</h4>
                                <h2>
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
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Auth Success Rate</h4>
                                <h2>
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
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title">Failed Auths Today</h4>
                                <h2>
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

        <!-- Domain Statistics -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-globe"></i> Domain Statistics (Last 24 Hours)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Domain</th>
                                        <th>Role</th>
                                        <th>Active Sessions</th>
                                        <th>Total Sessions</th>
                                        <th>Success Rate</th>
                                        <th>Data Transferred</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT 
                                            SUBSTRING_INDEX(username, '@', -1) as domain,
                                            CASE 
                                                WHEN SUBSTRING_INDEX(username, '@', -1) IN ('krea.edu.in', 'ifmr.ac.in') THEN 'Staff'
                                                WHEN SUBSTRING_INDEX(username, '@', -1) = 'krea.ac.in' THEN 'Student'
                                                ELSE 'Other'
                                            END as role,
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
                                    ");

                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $data_mb = round($row['total_bytes'] / (1024 * 1024), 2);
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-primary'>{$row['domain']}</span></td>";
                                        echo "<td><span class='badge " . ($row['role'] == 'Staff' ? 'bg-success' : 'bg-info') . "'>{$row['role']}</span></td>";
                                        echo "<td>{$row['active_sessions']}</td>";
                                        echo "<td>{$row['total_sessions']}</td>";
                                        echo "<td>{$row['success_rate']}%</td>";
                                        echo "<td>{$data_mb} MB</td>";
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
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history"></i> Recent Authentication Attempts
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Username</th>
                                        <th>Client IP</th>
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
                                        LIMIT 20
                                    ");

                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $status_class = $row['reply'] == 'Access-Accept' ? 'success' : 'danger';
                                        $status_icon = $row['reply'] == 'Access-Accept' ? 'check-circle' : 'times-circle';
                                        
                                        echo "<tr>";
                                        echo "<td>" . date('H:i:s', strtotime($row['authdate'])) . "</td>";
                                        echo "<td>{$row['username']}</td>";
                                        echo "<td>N/A</td>";
                                        echo "<td><i class='fas fa-{$status_icon} text-{$status_class}'></i></td>";
                                        echo "<td><span class='badge bg-{$status_class}'>{$row['reply']}</span></td>";
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-wifi"></i> Active Sessions
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Session Started</th>
                                        <th>Client IP</th>
                                        <th>NAS IP</th>
                                        <th>Data In/Out</th>
                                        <th>Duration</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT username, acctstarttime, framedipaddress, nasipaddress, 
                                               acctinputoctets, acctoutputoctets, radacctid,
                                               TIMESTAMPDIFF(SECOND, acctstarttime, NOW()) as duration_seconds
                                        FROM radacct 
                                        WHERE acctstoptime IS NULL 
                                        ORDER BY acctstarttime DESC
                                    ");

                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $data_in_mb = round(($row['acctinputoctets'] ?? 0) / (1024 * 1024), 2);
                                        $data_out_mb = round(($row['acctoutputoctets'] ?? 0) / (1024 * 1024), 2);
                                        $duration = gmdate('H:i:s', $row['duration_seconds']);
                                        
                                        echo "<tr>";
                                        echo "<td>{$row['username']}</td>";
                                        echo "<td>" . date('Y-m-d H:i:s', strtotime($row['acctstarttime'])) . "</td>";
                                        echo "<td>" . ($row['framedipaddress'] ?? 'N/A') . "</td>";
                                        echo "<td>" . ($row['nasipaddress'] ?? 'N/A') . "</td>";
                                        echo "<td>{$data_in_mb} / {$data_out_mb} MB</td>";
                                        echo "<td>{$duration}</td>";
                                        echo "<td><button class='btn btn-sm btn-danger' onclick='disconnectSession({$row['radacctid']})'>";
                                        echo "<i class='fas fa-stop'></i> Disconnect</button></td>";
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
    <script>
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
                        location.reload();
                    } else {
                        alert('Failed to disconnect session: ' + data.error);
                    }
                });
            }
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>