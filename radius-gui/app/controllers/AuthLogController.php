<?php
/**
 * Authentication Log Controller
 */

class AuthLogController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        Auth::requirePermission('auth_log.view');
    }

    public function indexAction()
    {
        $pageTitle = 'Authentication Log';

        // Get filters
        $fromDate = Utils::get('from_date', date('Y-m-d', strtotime('-7 days')));
        $toDate = Utils::get('to_date', date('Y-m-d'));
        $username = Utils::get('username', '');
        $result = Utils::get('result', '');
        $errorType = Utils::get('error_type', '');

        // Pagination
        $page = max(1, (int)Utils::get('page_num', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Build query
        $sql = "SELECT
                    id,
                    username,
                    reply,
                    reply_message,
                    error_type,
                    vlan,
                    user_type,
                    authdate,
                    authdate_utc,
                    CONVERT_TZ(authdate_utc, '+00:00', '+05:30') as authdate_ist
                FROM radpostauth
                WHERE DATE(authdate) BETWEEN ? AND ?";

        $params = [$fromDate, $toDate];

        if (!empty($username)) {
            $sql .= " AND username LIKE ?";
            $params[] = '%' . $username . '%';
        }

        if ($result === 'success') {
            $sql .= " AND reply = 'Access-Accept'";
        } elseif ($result === 'failed') {
            $sql .= " AND reply != 'Access-Accept'";
        }

        if (!empty($errorType)) {
            $sql .= " AND error_type = ?";
            $params[] = $errorType;
        }

        // Get total count
        $countSql = "SELECT COUNT(*) FROM radpostauth WHERE DATE(authdate) BETWEEN ? AND ?";
        $countParams = [$fromDate, $toDate];

        if (!empty($username)) {
            $countSql .= " AND username LIKE ?";
            $countParams[] = '%' . $username . '%';
        }

        if ($result === 'success') {
            $countSql .= " AND reply = 'Access-Accept'";
        } elseif ($result === 'failed') {
            $countSql .= " AND reply != 'Access-Accept'";
        }

        if (!empty($errorType)) {
            $countSql .= " AND error_type = ?";
            $countParams[] = $errorType;
        }

        $totalRecords = $this->db->fetchColumn($countSql, $countParams);

        // Get paginated results
        $sql .= " ORDER BY authdate DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $logs = $this->db->fetchAll($sql, $params);

        // Clear VLAN for failed authentications
        foreach ($logs as &$log) {
            if ($log['reply'] !== 'Access-Accept') {
                $log['vlan'] = '';
            }
        }

        // Get distinct error types for filter
        $errorTypes = $this->db->fetchAll(
            "SELECT DISTINCT error_type FROM radpostauth
             WHERE error_type IS NOT NULL AND error_type != ''
             ORDER BY error_type"
        );

        // Handle CSV export
        if (Utils::get('export') === 'csv') {
            $this->exportCsv($fromDate, $toDate, $username, $result, $errorType);
        }

        // Pagination
        $pagination = Utils::paginate($totalRecords, $perPage, $page);

        require APP_PATH . '/views/auth-log/index.php';
    }

    private function exportCsv($fromDate, $toDate, $username, $result, $errorType)
    {
        $sql = "SELECT
                    username,
                    reply,
                    reply_message,
                    error_type,
                    vlan,
                    user_type,
                    authdate,
                    authdate_utc
                FROM radpostauth
                WHERE DATE(authdate) BETWEEN ? AND ?";

        $params = [$fromDate, $toDate];

        if (!empty($username)) {
            $sql .= " AND username LIKE ?";
            $params[] = '%' . $username . '%';
        }

        if ($result === 'success') {
            $sql .= " AND reply = 'Access-Accept'";
        } elseif ($result === 'failed') {
            $sql .= " AND reply != 'Access-Accept'";
        }

        if (!empty($errorType)) {
            $sql .= " AND error_type = ?";
            $params[] = $errorType;
        }

        $sql .= " ORDER BY authdate DESC LIMIT 10000";

        $logs = $this->db->fetchAll($sql, $params);

        $filename = 'auth_log_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Date & Time (IST)',
            'UTC Time',
            'Username',
            'Result',
            'VLAN',
            'User Type',
            'Error Type',
            'Message'
        ];

        $data = [];
        foreach ($logs as $log) {
            // Only show VLAN and User Type for successful authentications
            $vlan = ($log['reply'] === 'Access-Accept') ? ($log['vlan'] ?? '-') : '';
            $userType = ($log['reply'] === 'Access-Accept') ? ($log['user_type'] ?? '-') : '';

            $data[] = [
                $log['authdate'],
                $log['authdate_utc'] ?? '-',
                $log['username'],
                $log['reply'],
                $vlan,
                $userType,
                $log['error_type'] ?? '-',
                $log['reply_message'] ?? '-'
            ];
        }

        Utils::exportCsv($filename, $headers, $data);
    }
}
