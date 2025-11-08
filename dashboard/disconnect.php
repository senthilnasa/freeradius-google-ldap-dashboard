<?php
header('Content-Type: application/json');

// Database connection
$db_host = $_ENV['DB_HOST'] ?? 'mysql';
$db_name = $_ENV['DB_NAME'] ?? 'radius';
$db_user = $_ENV['DB_USER'] ?? 'radius';
$db_pass = $_ENV['DB_PASS'] ?? 'radiuspass';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $sessionId = $input['sessionId'] ?? null;

    if (!$sessionId) {
        echo json_encode(['success' => false, 'error' => 'Session ID required']);
        exit;
    }

    try {
        // Update the accounting record to mark session as stopped
        $stmt = $pdo->prepare("
            UPDATE radacct 
            SET acctstoptime = NOW(), 
                acctterminatecause = 'Admin-Reset'
            WHERE radacctid = ? AND acctstoptime IS NULL
        ");
        
        $result = $stmt->execute([$sessionId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Session disconnected successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Session not found or already disconnected']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Only POST method allowed']);
}
?>