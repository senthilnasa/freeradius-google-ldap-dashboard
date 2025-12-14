<?php
/**
 * AuthLog Model
 *
 * Handles authentication log operations (radpostauth table)
 */

class AuthLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get recent authentication attempts
     */
    public function getRecent($limit = 100, $offset = 0)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             ORDER BY authdate DESC
             LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }

    /**
     * Get authentication attempts by username
     */
    public function getByUsername($username, $limit = 50)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE username = ?
             ORDER BY authdate DESC
             LIMIT ?",
            [$username, $limit]
        );
    }

    /**
     * Get failed authentication attempts
     */
    public function getFailedAttempts($limit = 100, $hours = 24)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE reply != 'Access-Accept'
               AND authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             ORDER BY authdate DESC
             LIMIT ?",
            [$hours, $limit]
        );
    }

    /**
     * Get authentication attempts by error type
     */
    public function getByErrorType($errorType, $limit = 100)
    {
        return $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE error_type = ?
             ORDER BY authdate DESC
             LIMIT ?",
            [$errorType, $limit]
        );
    }

    /**
     * Get authentication statistics
     */
    public function getStats($hours = 24)
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_attempts,
                SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN reply != 'Access-Accept' THEN 1 ELSE 0 END) as failed,
                COUNT(DISTINCT username) as unique_users
             FROM radpostauth
             WHERE authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)",
            [$hours]
        );
    }

    /**
     * Get error type breakdown
     */
    public function getErrorBreakdown($hours = 24)
    {
        return $this->db->fetchAll(
            "SELECT
                error_type,
                COUNT(*) as count
             FROM radpostauth
             WHERE reply != 'Access-Accept'
               AND error_type IS NOT NULL
               AND authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             GROUP BY error_type
             ORDER BY count DESC",
            [$hours]
        );
    }

    /**
     * Get authentication attempts by date range
     */
    public function getByDateRange($startDate, $endDate, $limit = null)
    {
        $sql = "SELECT * FROM radpostauth
                WHERE DATE(authdate) BETWEEN ? AND ?
                ORDER BY authdate DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$startDate, $endDate, $limit]);
        }

        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }

    /**
     * Get total authentication count
     */
    public function getTotalCount()
    {
        return $this->db->fetchColumn("SELECT COUNT(*) FROM radpostauth") ?: 0;
    }

    /**
     * Get users with most failed attempts
     */
    public function getTopFailedUsers($limit = 10, $hours = 24)
    {
        return $this->db->fetchAll(
            "SELECT
                username,
                error_type,
                COUNT(*) as failure_count,
                MAX(authdate) as last_attempt
             FROM radpostauth
             WHERE reply != 'Access-Accept'
               AND authdate >= DATE_SUB(NOW(), INTERVAL ? HOUR)
             GROUP BY username, error_type
             ORDER BY failure_count DESC
             LIMIT ?",
            [$hours, $limit]
        );
    }

    /**
     * Search authentication logs
     */
    public function search($query, $limit = 100)
    {
        $results = $this->db->fetchAll(
            "SELECT * FROM radpostauth
             WHERE username LIKE ?
                OR reply_message LIKE ?
             ORDER BY authdate DESC
             LIMIT ?",
            ['%' . $query . '%', '%' . $query . '%', $limit]
        );

        // Parse request_log JSON for each record
        foreach ($results as &$log) {
            if (!empty($log['request_log'])) {
                $decoded_log = quoted_printable_decode($log['request_log']);
                $log['request_log_parsed'] = json_decode($decoded_log, true);
                if ($log['request_log_parsed'] === null) {
                    $log['request_log_parsed'] = json_decode($log['request_log'], true);
                }
            } else {
                $log['request_log_parsed'] = [];
            }
        }

        return $results;
    }

    /**
     * Get authentication logs with advanced filters
     *
     * @param array $filters Associative array of filter criteria
     * @param int $limit Maximum number of records to return
     * @param int $offset Starting offset for pagination
     * @return array Array of authentication log records
     */
    public function getFiltered($filters = [], $limit = 100, $offset = 0)
    {
        $where = [];
        $params = [];

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(authdate) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(authdate) <= ?";
            $params[] = $filters['date_to'];
        }

        // Filter by username
        if (!empty($filters['username'])) {
            $where[] = "username LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
        }

        // Filter by reply status
        if (!empty($filters['reply'])) {
            $where[] = "reply = ?";
            $params[] = $filters['reply'];
        }

        // Filter by error type
        if (!empty($filters['error_type'])) {
            $where[] = "error_type = ?";
            $params[] = $filters['error_type'];
        }

        // Filter by user type
        if (!empty($filters['user_type'])) {
            $where[] = "user_type = ?";
            $params[] = $filters['user_type'];
        }

        // Filter by VLAN
        if (!empty($filters['vlan'])) {
            $where[] = "vlan = ?";
            $params[] = $filters['vlan'];
        }

        // Filter by NAS IP (from request_log JSON)
        if (!empty($filters['nas_ip'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.NAS-IP-Address') = ?";
            $params[] = $filters['nas_ip'];
        }

        // Filter by Location (from request_log JSON)
        if (!empty($filters['location'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.Aruba-Location-Id') LIKE ?";
            $params[] = '%' . $filters['location'] . '%';
        }

        // Filter by AP Group (from request_log JSON)
        if (!empty($filters['ap_group'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.Aruba-AP-Group') = ?";
            $params[] = $filters['ap_group'];
        }

        // Filter by SSID (from request_log JSON)
        if (!empty($filters['ssid'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.Aruba-Essid-Name') = ?";
            $params[] = $filters['ssid'];
        }

        // Build WHERE clause
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT * FROM radpostauth
                $whereClause
                ORDER BY authdate DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $results = $this->db->fetchAll($sql, $params);

        // Parse request_log JSON for each record
        foreach ($results as &$log) {
            if (!empty($log['request_log'])) {
                // FreeRADIUS uses quoted-printable encoding for special characters
                $decoded_log = quoted_printable_decode($log['request_log']);
                $log['request_log_parsed'] = json_decode($decoded_log, true);

                // If JSON decode fails, try without decoding (fallback)
                if ($log['request_log_parsed'] === null) {
                    $log['request_log_parsed'] = json_decode($log['request_log'], true);
                }
            } else {
                $log['request_log_parsed'] = [];
            }
        }

        return $results;
    }

    /**
     * Get count of filtered records (for pagination)
     */
    public function getFilteredCount($filters = [])
    {
        $where = [];
        $params = [];

        // Apply same filters as getFiltered()
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(authdate) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(authdate) <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['username'])) {
            $where[] = "username LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
        }
        if (!empty($filters['reply'])) {
            $where[] = "reply = ?";
            $params[] = $filters['reply'];
        }
        if (!empty($filters['error_type'])) {
            $where[] = "error_type = ?";
            $params[] = $filters['error_type'];
        }
        if (!empty($filters['user_type'])) {
            $where[] = "user_type = ?";
            $params[] = $filters['user_type'];
        }
        if (!empty($filters['vlan'])) {
            $where[] = "vlan = ?";
            $params[] = $filters['vlan'];
        }
        if (!empty($filters['nas_ip'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.NAS-IP-Address') = ?";
            $params[] = $filters['nas_ip'];
        }
        if (!empty($filters['location'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.Aruba-Location-Id') LIKE ?";
            $params[] = '%' . $filters['location'] . '%';
        }
        if (!empty($filters['ap_group'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.Aruba-AP-Group') = ?";
            $params[] = $filters['ap_group'];
        }
        if (!empty($filters['ssid'])) {
            $where[] = "JSON_EXTRACT(request_log, '$.Aruba-Essid-Name') = ?";
            $params[] = $filters['ssid'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) FROM radpostauth $whereClause";

        return $this->db->fetchColumn($sql, $params) ?: 0;
    }

    /**
     * Get unique values for filter dropdowns
     */
    public function getFilterOptions()
    {
        return [
            'error_types' => $this->db->fetchAll(
                "SELECT DISTINCT error_type
                 FROM radpostauth
                 WHERE error_type IS NOT NULL
                   AND error_type != ''
                 ORDER BY error_type"
            ),
            'user_types' => $this->db->fetchAll(
                "SELECT DISTINCT user_type
                 FROM radpostauth
                 WHERE user_type IS NOT NULL
                   AND user_type != ''
                 ORDER BY user_type"
            ),
            'vlans' => $this->db->fetchAll(
                "SELECT DISTINCT vlan
                 FROM radpostauth
                 WHERE vlan IS NOT NULL
                   AND vlan != ''
                 ORDER BY CAST(vlan AS UNSIGNED)"
            ),
            'nas_ips' => $this->db->fetchAll(
                "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(request_log, '$.NAS-IP-Address')) as nas_ip
                 FROM radpostauth
                 WHERE request_log IS NOT NULL
                   AND JSON_EXTRACT(request_log, '$.NAS-IP-Address') IS NOT NULL
                 ORDER BY nas_ip"
            ),
            'locations' => $this->db->fetchAll(
                "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(request_log, '$.Aruba-Location-Id')) as location
                 FROM radpostauth
                 WHERE request_log IS NOT NULL
                   AND JSON_EXTRACT(request_log, '$.Aruba-Location-Id') IS NOT NULL
                 ORDER BY location"
            ),
            'ap_groups' => $this->db->fetchAll(
                "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(request_log, '$.Aruba-AP-Group')) as ap_group
                 FROM radpostauth
                 WHERE request_log IS NOT NULL
                   AND JSON_EXTRACT(request_log, '$.Aruba-AP-Group') IS NOT NULL
                 ORDER BY ap_group"
            ),
            'ssids' => $this->db->fetchAll(
                "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(request_log, '$.Aruba-Essid-Name')) as ssid
                 FROM radpostauth
                 WHERE request_log IS NOT NULL
                   AND JSON_EXTRACT(request_log, '$.Aruba-Essid-Name') IS NOT NULL
                 ORDER BY ssid"
            )
        ];
    }

    /**
     * Get detailed authentication log with parsed request_log
     */
    public function getDetailedById($id)
    {
        $log = $this->db->fetchOne(
            "SELECT * FROM radpostauth WHERE id = ?",
            [$id]
        );

        if ($log && $log['request_log']) {
            // FreeRADIUS uses quoted-printable encoding for special characters
            $decoded_log = quoted_printable_decode($log['request_log']);
            $log['request_log_parsed'] = json_decode($decoded_log, true);

            // If JSON decode fails, try without decoding (in case it's already properly formatted)
            if ($log['request_log_parsed'] === null) {
                $log['request_log_parsed'] = json_decode($log['request_log'], true);
            }
        }

        return $log;
    }
}

