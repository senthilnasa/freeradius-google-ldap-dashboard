<?php
session_start();

// Configuration
$config = [
    'db_host' => $_ENV['DB_HOST'] ?? 'mysql',
    'db_port' => $_ENV['DB_PORT'] ?? '3306',
    'db_name' => $_ENV['DB_NAME'] ?? 'radius',
    'db_user' => $_ENV['DB_USER'] ?? 'radius',
    'db_password' => $_ENV['DB_PASSWORD'] ?? 'radiuspass',
    'admin_username' => $_ENV['ADMIN_USERNAME'] ?? 'admin',
    'admin_password' => $_ENV['ADMIN_PASSWORD'] ?? 'admin123',
    'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com',
    'force_password_change' => filter_var($_ENV['FORCE_PASSWORD_CHANGE'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    'session_timeout' => intval($_ENV['ADMIN_SESSION_TIMEOUT'] ?? '3600'),
    'bcrypt_rounds' => intval($_ENV['BCRYPT_ROUNDS'] ?? '12')
];

// Database connection
function getDbConnection($config) {
    try {
        $dsn = "mysql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Initialize admin user table
function initializeAdminUser($pdo, $config) {
    try {
        // Create admin_users table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                first_login BOOLEAN DEFAULT TRUE,
                password_changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        // Check if default admin user exists
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
        $stmt->execute([$config['admin_username']]);
        
        if (!$stmt->fetch()) {
            // Create default admin user
            $password_hash = password_hash($config['admin_password'], PASSWORD_BCRYPT, ['cost' => $config['bcrypt_rounds']]);
            $stmt = $pdo->prepare("
                INSERT INTO admin_users (username, password_hash, email, first_login) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $config['admin_username'], 
                $password_hash, 
                $config['admin_email'], 
                $config['force_password_change']
            ]);
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to initialize admin user: " . $e->getMessage());
        return false;
    }
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    // Check session timeout
    global $config;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $config['session_timeout']) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    
    $_SESSION['last_activity'] = time();
}

function login($username, $password, $pdo, $config) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, first_login FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['first_login'] = $user['first_login'];
            $_SESSION['last_activity'] = time();
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login failed: " . $e->getMessage());
        return false;
    }
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

function changePassword($userId, $newPassword, $pdo, $config) {
    try {
        $password_hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $config['bcrypt_rounds']]);
        $stmt = $pdo->prepare("
            UPDATE admin_users 
            SET password_hash = ?, first_login = FALSE, password_changed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$password_hash, $userId]);
        $_SESSION['first_login'] = false;
        return true;
    } catch (PDOException $e) {
        error_log("Password change failed: " . $e->getMessage());
        return false;
    }
}

// Initialize database and admin user
$pdo = getDbConnection($config);
if ($pdo) {
    initializeAdminUser($pdo, $config);
}
?>