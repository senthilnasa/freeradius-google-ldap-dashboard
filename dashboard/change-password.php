<?php
require_once 'auth.php';
requireLogin();

$error = '';
$success = '';
$is_first_login = isset($_GET['first']) && $_GET['first'] == '1';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($current_password, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            // Change password
            if (changePassword($_SESSION['admin_user_id'], $new_password, $pdo, $config)) {
                $success = 'Password changed successfully!';
                if ($is_first_login) {
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Failed to change password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - FreeRADIUS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .change-password-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            width: 100%;
            max-width: 500px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .form-control {
            border-radius: 15px;
            border: 2px solid #e0e0e0;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            padding: 15px;
            font-size: 16px;
            font-weight: 500;
        }
        .btn-secondary {
            border-radius: 15px;
            padding: 15px;
            font-size: 16px;
            font-weight: 500;
        }
        .alert {
            border-radius: 15px;
            border: none;
        }
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            font-size: 14px;
        }
        .input-group-text {
            background: transparent;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 15px 0 0 15px;
            color: #666;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 15px 15px 0;
        }
    </style>
</head>
<body>
    <div class="change-password-card">
        <div class="form-header">
            <i class="fas fa-key"></i>
            <h2>Change Password</h2>
            <?php if ($is_first_login): ?>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    You must change your password before continuing.
                </p>
            <?php else: ?>
                <p class="text-muted">Update your account password</p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" name="current_password" placeholder="Current Password" required>
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-key"></i>
                    </span>
                    <input type="password" class="form-control" name="new_password" placeholder="New Password" required minlength="8">
                </div>
            </div>

            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-check"></i>
                    </span>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
                </div>
            </div>

            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul class="mb-0">
                    <li>At least 8 characters long</li>
                    <li>Mix of uppercase and lowercase letters recommended</li>
                    <li>Include numbers and special characters for better security</li>
                </ul>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    Change Password
                </button>
                <?php if (!$is_first_login): ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>