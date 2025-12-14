<?php
/**
 * Authentication Log Controller
 */

require_once APP_PATH . '/models/AuthLog.php';

class AuthLogController
{
    private $db;
    private $authLogModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->authLogModel = new AuthLog();
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
                    request_log,
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
                    request_log,
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
            'Message',
            'Request Log (JSON)'
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
                $log['reply_message'] ?? '-',
                $log['request_log'] ?? ''
            ];
        }

        Utils::exportCsv($filename, $headers, $data);
    }

    /**
     * Show detailed view of a single authentication log entry
     */
    public function detailAction()
    {
        $id = Utils::get('id');

        if (!$id) {
            header('Location: index.php?page=auth-log');
            exit;
        }

        $log = $this->authLogModel->getDetailedById($id);

        if (!$log) {
            $_SESSION['error'] = 'Authentication log entry not found';
            header('Location: index.php?page=auth-log');
            exit;
        }

        // Return JSON for AJAX modal requests
        if (Utils::get('ajax') === '1') {
            header('Content-Type: application/json');

            // Ensure request_log_parsed is set
            if (!isset($log['request_log_parsed'])) {
                $log['request_log_parsed'] = [];
            }

            $json = json_encode($log);
            if ($json === false) {
                // JSON encoding failed, return error
                echo json_encode([
                    'error' => 'Failed to encode log data',
                    'json_error' => json_last_error_msg()
                ]);
            } else {
                echo $json;
            }
            exit;
        }

        // Full page view
        $pageTitle = 'Authentication Log Details';
        require APP_PATH . '/views/layouts/header.php';
        require APP_PATH . '/views/auth-log/detail.php';
        require APP_PATH . '/views/layouts/footer.php';
    }

    /**
     * Enhanced index with advanced filtering
     */
    public function filteredAction()
    {
        $pageTitle = 'Authentication Log - Advanced Filters';

        // Get all filter parameters
        $filters = [
            'date_from' => Utils::get('date_from', date('Y-m-d', strtotime('-7 days'))),
            'date_to' => Utils::get('date_to', date('Y-m-d')),
            'username' => Utils::get('username', ''),
            'reply' => Utils::get('reply', ''),
            'error_type' => Utils::get('error_type', ''),
            'user_type' => Utils::get('user_type', ''),
            'vlan' => Utils::get('vlan', ''),
            'nas_ip' => Utils::get('nas_ip', ''),
            'location' => Utils::get('location', ''),
            'ap_group' => Utils::get('ap_group', ''),
            'ssid' => Utils::get('ssid', '')
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== '';
        });

        // Pagination
        $page = max(1, (int)Utils::get('page_num', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Get filtered logs
        $logs = $this->authLogModel->getFiltered($filters, $perPage, $offset);
        $totalRecords = $this->authLogModel->getFilteredCount($filters);

        // Get filter options for dropdowns
        $filterOptions = $this->authLogModel->getFilterOptions();

        // Pagination
        $pagination = Utils::paginate($totalRecords, $perPage, $page);

        // Clear VLAN for failed authentications
        foreach ($logs as &$log) {
            if ($log['reply'] !== 'Access-Accept') {
                $log['vlan'] = '';
            }
            // Parse request_log JSON if available
            if (!empty($log['request_log'])) {
                $log['request_log_parsed'] = json_decode($log['request_log'], true);
            }
        }

        require APP_PATH . '/views/layouts/header.php';
        require APP_PATH . '/views/auth-log/filtered.php';
        require APP_PATH . '/views/layouts/footer.php';
    }
}
