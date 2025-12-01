<?php
require_once '../auth.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Get online users
    $stmt = $pdo->query("SELECT COUNT(*) FROM radacct WHERE acctstoptime IS NULL");
    $onlineUsers = $stmt->fetchColumn();

    // Get total sessions today
    $stmt = $pdo->query("SELECT COUNT(*) FROM radacct WHERE DATE(acctstarttime) = CURDATE()");
    $totalSessions = $stmt->fetchColumn();

    // Get auth success rate
    $stmt = $pdo->query("
        SELECT
            ROUND(
                (SUM(CASE WHEN authdate >= CURDATE() AND reply = 'Access-Accept' THEN 1 ELSE 0 END) /
                 NULLIF(COUNT(*), 0) * 100), 1
            ) as success_rate
        FROM radpostauth
        WHERE authdate >= CURDATE()
    ");
    $successRate = $stmt->fetchColumn() ?: 0;

    // Get failed auths today
    $stmt = $pdo->query("SELECT COUNT(*) FROM radpostauth WHERE authdate >= CURDATE() AND reply != 'Access-Accept'");
    $failedAuths = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'onlineUsers' => $onlineUsers,
        'totalSessions' => $totalSessions,
        'successRate' => $successRate,
        'failedAuths' => $failedAuths
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
