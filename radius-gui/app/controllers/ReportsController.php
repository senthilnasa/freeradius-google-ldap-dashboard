<?php
/**
 * Reports Controller
 */

require_once APP_PATH . '/helpers/PdfHelper.php';

class ReportsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('reports.view');
    }

    public function indexAction()
    {
        $pageTitle = 'Reports Hub';
        require APP_PATH . '/views/reports/index.php';
    }

    public function dailyAuthAction()
    {
        $pageTitle = 'Daily Authentication Summary';

        $date = Utils::get('date', date('Y-m-d'));

        // Get daily stats
        $stats = $this->db->fetchOne(
            "SELECT * FROM daily_stats WHERE stat_date = ?",
            [$date]
        );

        // If not found, calculate manually
        if (!$stats) {
            $totalAttempts = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) = ?",
                [$date]
            ) ?: 0;

            $successfulLogins = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) = ? AND reply = 'Access-Accept'",
                [$date]
            ) ?: 0;

            $failedLogins = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) = ? AND reply != 'Access-Accept'",
                [$date]
            ) ?: 0;

            $uniqueUsers = $this->db->fetchColumn(
                "SELECT COUNT(DISTINCT username) FROM radpostauth WHERE DATE(authdate) = ?",
                [$date]
            ) ?: 0;

            $successRate = $totalAttempts > 0 ? ($successfulLogins / $totalAttempts * 100) : 0;

            $stats = [
                'date' => $date,
                'total_attempts' => $totalAttempts,
                'successful_logins' => $successfulLogins,
                'failed_logins' => $failedLogins,
                'unique_users' => $uniqueUsers,
                'success_rate' => $successRate
            ];
        }

        // Hourly breakdown - fill all 24 hours with default data
        $hourlyData = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyData[] = [
                'hour' => $h,
                'attempts' => 0,
                'successful' => 0,
                'failed' => 0
            ];
        }

        // Get actual hourly breakdown
        $actualHourly = $this->db->fetchAll(
            "SELECT
                HOUR(authdate) as hour,
                COUNT(*) as attempts,
                SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN reply != 'Access-Accept' THEN 1 ELSE 0 END) as failed
            FROM radpostauth
            WHERE DATE(authdate) = ?
            GROUP BY HOUR(authdate)
            ORDER BY hour",
            [$date]
        );

        // Merge actual data
        foreach ($actualHourly as $data) {
            $hourlyData[$data['hour']] = $data;
        }

        // Get VLAN breakdown for successful authentications
        $vlanStats = $this->db->fetchAll(
            "SELECT
                vlan,
                COUNT(*) as auth_count,
                COUNT(DISTINCT username) as unique_users
            FROM radpostauth
            WHERE DATE(authdate) = ?
              AND reply = 'Access-Accept'
              AND vlan IS NOT NULL
              AND vlan != ''
            GROUP BY vlan
            ORDER BY auth_count DESC",
            [$date]
        );

        // Get error type breakdown for failed authentications
        $errorStats = $this->db->fetchAll(
            "SELECT
                error_type,
                COUNT(*) as error_count,
                COUNT(DISTINCT username) as affected_users
            FROM radpostauth
            WHERE DATE(authdate) = ?
              AND reply != 'Access-Accept'
              AND error_type IS NOT NULL
              AND error_type != ''
            GROUP BY error_type
            ORDER BY error_count DESC",
            [$date]
        );

        // Handle PDF export
        if (Utils::get('export') === 'pdf') {
            PdfHelper::generateDailyAuthReport($date, $stats, $hourlyData, $vlanStats, $errorStats);
        }

        require APP_PATH . '/views/reports/daily-auth.php';
    }

    public function monthlyUsageAction()
    {
        $pageTitle = 'Monthly Usage Summary';

        $month = Utils::get('month', date('Y-m'));
        list($year, $monthNum) = explode('-', $month);

        // Get daily breakdown for the month
        $dailyData = $this->db->fetchAll(
            "SELECT
                DATE(acctstarttime) as date,
                COUNT(*) as total_sessions,
                COUNT(DISTINCT username) as unique_users,
                SUM(acctsessiontime) as total_online_time,
                SUM(acctinputoctets + acctoutputoctets) as total_data
            FROM radacct
            WHERE YEAR(acctstarttime) = ? AND MONTH(acctstarttime) = ?
            GROUP BY DATE(acctstarttime)
            ORDER BY date",
            [$year, $monthNum]
        );

        // Calculate totals
        $totalSessions = array_sum(array_column($dailyData, 'total_sessions'));
        $totalOnlineTime = array_sum(array_column($dailyData, 'total_online_time'));
        $totalData = array_sum(array_column($dailyData, 'total_data'));

        // Count unique users for the month
        $uniqueUsers = $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT username) FROM radacct WHERE YEAR(acctstarttime) = ? AND MONTH(acctstarttime) = ?",
            [$year, $monthNum]
        );

        // Handle PDF export
        if (Utils::get('export') === 'pdf') {
            PdfHelper::generateMonthlyUsageReport($month, $dailyData, [
                'total_sessions' => $totalSessions,
                'unique_users' => $uniqueUsers,
                'total_online_time' => $totalOnlineTime,
                'total_data' => $totalData
            ]);
        }

        require APP_PATH . '/views/reports/monthly-usage.php';
    }

    public function failedLoginsAction()
    {
        $pageTitle = 'Failed Login Report';

        $fromDate = Utils::get('from_date', date('Y-m-d', strtotime('-7 days')));
        $toDate = Utils::get('to_date', date('Y-m-d'));
        $threshold = Utils::get('threshold', 5);

        $failedLogins = $this->db->fetchAll(
            "SELECT
                username,
                error_type,
                COUNT(*) as failure_count,
                MIN(authdate) as first_failure,
                MAX(authdate) as last_failure
            FROM radpostauth
            WHERE reply != 'Access-Accept'
              AND DATE(authdate) BETWEEN ? AND ?
            GROUP BY username, error_type
            HAVING failure_count >= ?
            ORDER BY failure_count DESC",
            [$fromDate, $toDate, (int)$threshold]
        );

        // Handle PDF export
        if (Utils::get('export') === 'pdf') {
            PdfHelper::generateFailedLoginsReport($fromDate, $toDate, $threshold, $failedLogins);
        }

        require APP_PATH . '/views/reports/failed-logins.php';
    }

    public function systemHealthAction()
    {
        $pageTitle = 'System Health Report';

        // Database statistics
        $dbStats = [
            'total_auth_records' => $this->db->fetchColumn("SELECT COUNT(*) FROM radpostauth") ?: 0,
            'total_acct_records' => $this->db->fetchColumn("SELECT COUNT(*) FROM radacct") ?: 0,
            'total_operators' => $this->db->fetchColumn("SELECT COUNT(*) FROM operators") ?: 0,
            'total_nas' => $this->db->fetchColumn("SELECT COUNT(*) FROM nas") ?: 0,
            'total_users' => $this->db->fetchColumn("SELECT COUNT(*) FROM radcheck") ?: 0,
            'online_sessions' => $this->db->fetchColumn("SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL") ?: 0,
            'database_size' => $this->getDatabaseSize(),
            'oldest_auth_date' => $this->db->fetchColumn("SELECT MIN(authdate) FROM radpostauth WHERE authdate IS NOT NULL"),
            'newest_auth_date' => $this->db->fetchColumn("SELECT MAX(authdate) FROM radpostauth WHERE authdate IS NOT NULL")
        ];

        // Authentication statistics (last 24 hours)
        $authStats = [
            'total_attempts' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radpostauth WHERE authdate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ) ?: 0,
            'successful' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radpostauth WHERE authdate >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND reply = 'Access-Accept'"
            ) ?: 0,
            'failed' => $this->db->fetchColumn(
                "SELECT COUNT(*) FROM radpostauth WHERE authdate >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND reply != 'Access-Accept'"
            ) ?: 0
        ];

        $authStats['success_rate'] = $authStats['total_attempts'] > 0
            ? ($authStats['successful'] / $authStats['total_attempts'] * 100)
            : 0;

        // Error breakdown (last 24 hours)
        $errorBreakdown = $this->db->fetchAll(
            "SELECT
                error_type,
                COUNT(*) as count
            FROM radpostauth
            WHERE authdate >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              AND reply != 'Access-Accept'
              AND error_type IS NOT NULL
            GROUP BY error_type
            ORDER BY count DESC"
        );

        // System performance metrics
        $performanceMetrics = [
            'avg_session_duration' => $this->db->fetchColumn(
                "SELECT AVG(acctsessiontime) FROM radacct WHERE acctstoptime >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            ) ?: 0,
            'avg_data_per_session' => $this->db->fetchColumn(
                "SELECT AVG(acctinputoctets + acctoutputoctets) FROM radacct WHERE acctstoptime >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            ) ?: 0,
            'peak_concurrent_users' => $this->getPeakConcurrentUsers(),
        ];

        // Top NAS devices by activity
        $topNasDevices = $this->db->fetchAll(
            "SELECT
                n.nasname,
                n.shortname,
                COUNT(DISTINCT ra.username) as unique_users,
                COUNT(*) as total_sessions,
                SUM(ra.acctinputoctets + ra.acctoutputoctets) as total_data
            FROM radacct ra
            JOIN nas n ON ra.nasipaddress = n.nasname
            WHERE ra.acctstarttime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY n.nasname, n.shortname
            ORDER BY total_sessions DESC
            LIMIT 10"
        );

        // Recent system alerts (failed authentications > 10 in last hour)
        $recentAlerts = $this->db->fetchAll(
            "SELECT
                username,
                error_type,
                COUNT(*) as failure_count,
                MAX(authdate) as last_failure
            FROM radpostauth
            WHERE authdate >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
              AND reply != 'Access-Accept'
            GROUP BY username, error_type
            HAVING failure_count >= 10
            ORDER BY failure_count DESC
            LIMIT 10"
        );

        // Handle PDF export
        if (Utils::get('export') === 'pdf') {
            PdfHelper::generateSystemHealthReport($dbStats, $authStats, $errorBreakdown, $performanceMetrics, $topNasDevices, $recentAlerts);
        }

        require APP_PATH . '/views/reports/system-health.php';
    }

    public function userTypeDistributionAction()
    {
        $pageTitle = 'User Type Distribution Report';

        $fromDate = Utils::get('from_date', date('Y-m-d', strtotime('-30 days')));
        $toDate = Utils::get('to_date', date('Y-m-d'));

        // Get user type distribution (successful authentications only)
        $userTypeStats = $this->db->fetchAll(
            "SELECT
                user_type,
                COUNT(*) as auth_count,
                COUNT(DISTINCT username) as unique_users,
                COUNT(DISTINCT DATE(authdate)) as active_days
            FROM radpostauth
            WHERE DATE(authdate) BETWEEN ? AND ?
              AND reply = 'Access-Accept'
              AND user_type IS NOT NULL
              AND user_type != ''
            GROUP BY user_type
            ORDER BY auth_count DESC",
            [$fromDate, $toDate]
        );

        // Calculate totals
        $totalAuths = array_sum(array_column($userTypeStats, 'auth_count'));
        $totalUsers = array_sum(array_column($userTypeStats, 'unique_users'));

        // Get user type with VLAN correlation
        $userTypeVlan = $this->db->fetchAll(
            "SELECT
                user_type,
                vlan,
                COUNT(*) as count
            FROM radpostauth
            WHERE DATE(authdate) BETWEEN ? AND ?
              AND reply = 'Access-Accept'
              AND user_type IS NOT NULL
              AND user_type != ''
            GROUP BY user_type, vlan
            ORDER BY user_type, count DESC",
            [$fromDate, $toDate]
        );

        // Get daily breakdown by user type
        $dailyBreakdown = $this->db->fetchAll(
            "SELECT
                DATE(authdate) as date,
                user_type,
                COUNT(*) as auth_count
            FROM radpostauth
            WHERE DATE(authdate) BETWEEN ? AND ?
              AND reply = 'Access-Accept'
              AND user_type IS NOT NULL
              AND user_type != ''
            GROUP BY DATE(authdate), user_type
            ORDER BY date DESC, auth_count DESC",
            [$fromDate, $toDate]
        );

        // Get failed authentications by inferred user type (based on username pattern)
        $failedByType = $this->db->fetchAll(
            "SELECT
                CASE
                    WHEN username LIKE '%.mba@%' THEN 'Student-MBA'
                    WHEN username LIKE '%.sias@%' THEN 'Student-SIAS'
                    WHEN username LIKE '%.bba@%' THEN 'Student-BBA'
                    WHEN username LIKE '%.phd@%' THEN 'Student-Ph D'
                    WHEN username LIKE '%@krea.edu.in' THEN 'Staff'
                    ELSE 'Others'
                END as inferred_type,
                error_type,
                COUNT(*) as failure_count
            FROM radpostauth
            WHERE DATE(authdate) BETWEEN ? AND ?
              AND reply != 'Access-Accept'
            GROUP BY inferred_type, error_type
            ORDER BY failure_count DESC",
            [$fromDate, $toDate]
        );

        // Handle PDF export
        // TODO: Implement PDF export for user type distribution report
        /*
        if (Utils::get('export') === 'pdf') {
            require_once APP_PATH . '/helpers/PdfHelper.php';
            PdfHelper::generateUserTypeDistributionReport(
                $fromDate,
                $toDate,
                $userTypeStats,
                $totalAuths,
                $totalUsers,
                $userTypeVlan,
                $failedByType
            );
        }
        */

        require APP_PATH . '/views/reports/user-type-distribution.php';
    }

    private function getDatabaseSize()
    {
        $result = $this->db->fetchOne(
            "SELECT
                SUM(data_length + index_length) as size
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()"
        );
        return $result['size'] ?? 0;
    }

    private function getPeakConcurrentUsers()
    {
        // Get peak concurrent users in the last 7 days by checking overlapping sessions
        $result = $this->db->fetchColumn(
            "SELECT MAX(concurrent) FROM (
                SELECT
                    acctstarttime as event_time,
                    COUNT(*) as concurrent
                FROM radacct
                WHERE acctstarttime >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY acctstarttime
            ) as concurrent_sessions"
        );
        return $result ?: 0;
    }
}
