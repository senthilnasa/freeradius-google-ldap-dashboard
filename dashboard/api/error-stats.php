<?php
require_once '../auth.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Get error breakdown by type for today
    $stmt = $pdo->query("
        SELECT
            COALESCE(error_type, 'unknown') as error_type,
            COUNT(*) as count,
            ROUND((COUNT(*) / (SELECT COUNT(*) FROM radpostauth WHERE authdate >= CURDATE() AND reply != 'Access-Accept') * 100), 1) as percentage
        FROM radpostauth
        WHERE authdate >= CURDATE() AND reply != 'Access-Accept'
        GROUP BY error_type
        ORDER BY count DESC
    ");
    $errorBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent failed attempts with details
    $stmt = $pdo->query("
        SELECT
            authdate,
            CONVERT_TZ(authdate_utc, '+00:00', '+05:30') as authdate_ist,
            username,
            error_type,
            reply_message
        FROM radpostauth
        WHERE reply != 'Access-Accept'
        ORDER BY authdate DESC
        LIMIT 20
    ");
    $recentFailures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get hourly failure trend for today
    $stmt = $pdo->query("
        SELECT
            HOUR(authdate) as hour,
            COUNT(*) as failures
        FROM radpostauth
        WHERE authdate >= CURDATE() AND reply != 'Access-Accept'
        GROUP BY HOUR(authdate)
        ORDER BY hour
    ");
    $hourlyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'errorBreakdown' => $errorBreakdown,
        'recentFailures' => $recentFailures,
        'hourlyTrend' => $hourlyTrend
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
