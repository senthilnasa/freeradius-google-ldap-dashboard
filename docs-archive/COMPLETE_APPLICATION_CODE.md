# Complete Application Code - All Remaining Files

This file contains all remaining controllers, views, and components to complete the RADIUS Reporting GUI.

## Top Users Controller

**File: `app/controllers/TopUsersController.php`**

```php
<?php
class TopUsersController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('top_users.view');
    }

    public function indexAction()
    {
        $pageTitle = 'Top Users by Data Usage';

        $fromDate = Utils::get('from_date', date('Y-m-d', strtotime('-7 days')));
        $toDate = Utils::get('to_date', date('Y-m-d'));
        $limit = Utils::get('limit', 10);

        $topUsers = $this->db->fetchAll(
            "SELECT
                username,
                COUNT(*) as session_count,
                SUM(acctinputoctets) as total_download,
                SUM(acctoutputoctets) as total_upload,
                SUM(acctinputoctets + acctoutputoctets) as total_data
            FROM radacct
            WHERE DATE(acctstarttime) BETWEEN ? AND ?
            GROUP BY username
            ORDER BY total_data DESC
            LIMIT ?",
            [$fromDate, $toDate, (int)$limit]
        );

        if (Utils::get('export') === 'csv') {
            $this->exportCsv($topUsers);
        }

        require APP_PATH . '/views/top-users/index.php';
    }

    private function exportCsv($users)
    {
        $filename = 'top_users_' . date('Y-m-d_His') . '.csv';
        $headers = ['Rank', 'Username', 'Sessions', 'Download', 'Upload', 'Total'];

        $data = [];
        $rank = 1;
        foreach ($users as $user) {
            $data[] = [
                $rank++,
                $user['username'],
                $user['session_count'],
                Utils::formatBytes($user['total_download']),
                Utils::formatBytes($user['total_upload']),
                Utils::formatBytes($user['total_data'])
            ];
        }

        Utils::exportCsv($filename, $headers, $data);
    }
}
```

**File: `app/views/top-users/index.php`**

```php
<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="card card-custom">
    <div class="card-header">
        <i class="fas fa-chart-bar"></i> Top Users by Data Usage
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3 mb-3">
            <input type="hidden" name="page" value="top-users">

            <div class="col-md-3">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date" value="<?= Utils::e($fromDate) ?>">
            </div>

            <div class="col-md-3">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date" value="<?= Utils::e($toDate) ?>">
            </div>

            <div class="col-md-2">
                <label for="limit" class="form-label">Top N Users</label>
                <select class="form-select" id="limit" name="limit">
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>Top 10</option>
                    <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>Top 20</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>Top 50</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <a href="?page=top-users&export=csv&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>&limit=<?= $limit ?>" class="btn btn-success w-100">
                    <i class="fas fa-file-csv"></i> Export
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <?php if (empty($topUsers)): ?>
                <div class="alert alert-info">No data available for the selected period.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Username</th>
                            <th>Sessions</th>
                            <th>Download</th>
                            <th>Upload</th>
                            <th>Total Data</th>
                            <th>Chart</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        $maxData = !empty($topUsers) ? $topUsers[0]['total_data'] : 1;
                        foreach ($topUsers as $user):
                            $percentage = ($user['total_data'] / $maxData) * 100;
                        ?>
                            <tr>
                                <td><strong>#<?= $rank++ ?></strong></td>
                                <td><?= Utils::e($user['username']) ?></td>
                                <td><?= number_format($user['session_count']) ?></td>
                                <td><?= Utils::formatBytes($user['total_download']) ?></td>
                                <td><?= Utils::formatBytes($user['total_upload']) ?></td>
                                <td><strong><?= Utils::formatBytes($user['total_data']) ?></strong></td>
                                <td>
                                    <div class="progress" style="width: 100px;">
                                        <div class="progress-bar bg-primary" role="progressbar"
                                             style="width: <?= $percentage ?>%"
                                             aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
```

---

## NAS Usage Controller

**File: `app/controllers/NasUsageController.php`**

```php
<?php
class NasUsageController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('nas_usage.view');
    }

    public function indexAction()
    {
        $pageTitle = 'NAS / AP Usage';

        $fromDate = Utils::get('from_date', date('Y-m-d', strtotime('-7 days')));
        $toDate = Utils::get('to_date', date('Y-m-d'));

        $nasUsage = $this->db->fetchAll(
            "SELECT
                r.nasipaddress,
                n.shortname,
                n.description,
                COUNT(*) as session_count,
                COUNT(DISTINCT r.username) as unique_users,
                SUM(r.acctinputoctets + r.acctoutputoctets) as total_data,
                AVG(r.acctsessiontime) as avg_session_duration
            FROM radacct r
            LEFT JOIN nas n ON r.nasipaddress = n.nasname
            WHERE DATE(r.acctstarttime) BETWEEN ? AND ?
            GROUP BY r.nasipaddress, n.shortname, n.description
            ORDER BY session_count DESC",
            [$fromDate, $toDate]
        );

        if (Utils::get('export') === 'csv') {
            $this->exportCsv($nasUsage);
        }

        require APP_PATH . '/views/nas-usage/index.php';
    }

    private function exportCsv($nasUsage)
    {
        $filename = 'nas_usage_' . date('Y-m-d_His') . '.csv';
        $headers = ['NAS IP', 'Name', 'Description', 'Sessions', 'Unique Users', 'Total Data', 'Avg Duration'];

        $data = [];
        foreach ($nasUsage as $nas) {
            $data[] = [
                $nas['nasipaddress'],
                $nas['shortname'] ?? '-',
                $nas['description'] ?? '-',
                $nas['session_count'],
                $nas['unique_users'],
                Utils::formatBytes($nas['total_data']),
                Utils::formatDuration($nas['avg_session_duration'])
            ];
        }

        Utils::exportCsv($filename, $headers, $data);
    }
}
```

**File: `app/views/nas-usage/index.php`**

```php
<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="card card-custom">
    <div class="card-header">
        <i class="fas fa-network-wired"></i> NAS / AP Usage
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3 mb-3">
            <input type="hidden" name="page" value="nas-usage">

            <div class="col-md-4">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date" value="<?= Utils::e($fromDate) ?>">
            </div>

            <div class="col-md-4">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date" value="<?= Utils::e($toDate) ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <a href="?page=nas-usage&export=csv&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>" class="btn btn-success w-100">
                    <i class="fas fa-file-csv"></i> Export
                </a>
            </div>
        </form>

        <div class="table-responsive">
            <?php if (empty($nasUsage)): ?>
                <div class="alert alert-info">No NAS usage data for the selected period.</div>
            <?php else: ?>
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>NAS IP</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Sessions</th>
                            <th>Unique Users</th>
                            <th>Total Data</th>
                            <th>Avg Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nasUsage as $nas): ?>
                            <tr>
                                <td><code><?= Utils::e($nas['nasipaddress']) ?></code></td>
                                <td><?= Utils::e($nas['shortname'] ?? '-') ?></td>
                                <td><?= Utils::e($nas['description'] ?? '-') ?></td>
                                <td><?= number_format($nas['session_count']) ?></td>
                                <td><?= number_format($nas['unique_users']) ?></td>
                                <td><strong><?= Utils::formatBytes($nas['total_data']) ?></strong></td>
                                <td><?= Utils::formatDuration($nas['avg_session_duration']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
```

---

## Error Analytics Controller

**File: `app/controllers/ErrorAnalyticsController.php`**

```php
<?php
class ErrorAnalyticsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('error_analytics.view');
    }

    public function indexAction()
    {
        $pageTitle = 'Error Analytics';

        $fromDate = Utils::get('from_date', date('Y-m-d', strtotime('-7 days')));
        $toDate = Utils::get('to_date', date('Y-m-d'));
        $errorType = Utils::get('error_type', '');
        $username = Utils::get('username', '');

        // Total auth attempts
        $totalAttempts = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) BETWEEN ? AND ?",
            [$fromDate, $toDate]
        );

        // Total failures
        $totalFailures = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) BETWEEN ? AND ? AND reply != 'Access-Accept'",
            [$fromDate, $toDate]
        );

        // Error breakdown
        $errorBreakdown = $this->db->fetchAll(
            "SELECT
                COALESCE(error_type, 'Unknown') as error_type,
                COUNT(*) as count
            FROM radpostauth
            WHERE DATE(authdate) BETWEEN ? AND ?
              AND reply != 'Access-Accept'
            GROUP BY error_type
            ORDER BY count DESC",
            [$fromDate, $toDate]
        );

        // Detailed error log
        $sql = "SELECT
                    authdate,
                    CONVERT_TZ(authdate_utc, '+00:00', '+05:30') as authdate_ist,
                    username,
                    error_type,
                    reply,
                    reply_message
                FROM radpostauth
                WHERE DATE(authdate) BETWEEN ? AND ?
                  AND reply != 'Access-Accept'";

        $params = [$fromDate, $toDate];

        if (!empty($errorType)) {
            $sql .= " AND error_type = ?";
            $params[] = $errorType;
        }

        if (!empty($username)) {
            $sql .= " AND username LIKE ?";
            $params[] = '%' . $username . '%';
        }

        $sql .= " ORDER BY authdate DESC LIMIT 100";

        $errorLog = $this->db->fetchAll($sql, $params);

        // Get distinct error types for filter
        $errorTypes = $this->db->fetchAll(
            "SELECT DISTINCT error_type FROM radpostauth
             WHERE error_type IS NOT NULL AND error_type != ''
             ORDER BY error_type"
        );

        if (Utils::get('export') === 'csv') {
            $this->exportCsv($errorLog);
        }

        require APP_PATH . '/views/error-analytics/index.php';
    }

    private function exportCsv($errorLog)
    {
        $filename = 'error_analytics_' . date('Y-m-d_His') . '.csv';
        $headers = ['Date & Time', 'Username', 'Error Type', 'Reply', 'Message'];

        $data = [];
        foreach ($errorLog as $log) {
            $data[] = [
                $log['authdate'],
                $log['username'],
                $log['error_type'] ?? '-',
                $log['reply'],
                $log['reply_message'] ?? '-'
            ];
        }

        Utils::exportCsv($filename, $headers, $data);
    }
}
```

**File: `app/views/error-analytics/index.php`**

```php
<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="row">
    <div class="col-md-4">
        <div class="stats-card primary">
            <p class="text-muted mb-1">Total Attempts</p>
            <h3><?= number_format($totalAttempts) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card danger">
            <p class="text-muted mb-1">Total Failures</p>
            <h3><?= number_format($totalFailures) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stats-card warning">
            <p class="text-muted mb-1">Failure Rate</p>
            <h3><?= $totalAttempts > 0 ? Utils::percentage($totalFailures, $totalAttempts) : 0 ?>%</h3>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-chart-pie"></i> Error Breakdown
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Error Type</th>
                            <th>Count</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errorBreakdown as $error): ?>
                            <tr>
                                <td><?= Utils::e(ucwords(str_replace('_', ' ', $error['error_type']))) ?></td>
                                <td><?= number_format($error['count']) ?></td>
                                <td><?= Utils::percentage($error['count'], $totalFailures) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-custom">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filters
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <input type="hidden" name="page" value="error-analytics">

                    <div class="mb-3">
                        <label for="from_date">From Date</label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?= $fromDate ?>">
                    </div>

                    <div class="mb-3">
                        <label for="to_date">To Date</label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?= $toDate ?>">
                    </div>

                    <div class="mb-3">
                        <label for="error_type">Error Type</label>
                        <select class="form-select" id="error_type" name="error_type">
                            <option value="">All Types</option>
                            <?php foreach ($errorTypes as $et): ?>
                                <option value="<?= Utils::e($et['error_type']) ?>" <?= $errorType === $et['error_type'] ? 'selected' : '' ?>>
                                    <?= Utils::e(ucwords(str_replace('_', ' ', $et['error_type']))) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= Utils::e($username) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card card-custom mt-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="fas fa-exclamation-triangle"></i> Recent Failures</span>
        <a href="?page=error-analytics&export=csv&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>&error_type=<?= urlencode($errorType) ?>&username=<?= urlencode($username) ?>"
           class="btn btn-sm btn-success">
            <i class="fas fa-file-csv"></i> Export
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Username</th>
                        <th>Error Type</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($errorLog as $log): ?>
                        <tr>
                            <td><?= Utils::formatDate($log['authdate']) ?></td>
                            <td><?= Utils::e($log['username']) ?></td>
                            <td>
                                <span class="badge bg-danger">
                                    <?= Utils::e(ucwords(str_replace('_', ' ', $log['error_type'] ?? 'Unknown'))) ?>
                                </span>
                            </td>
                            <td><small><?= Utils::e(substr($log['reply_message'] ?? '-', 0, 100)) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
```

---

Continue in next message due to length...
