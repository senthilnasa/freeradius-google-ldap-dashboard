<?php
/**
 * Users Controller - Operator Management
 */

class UsersController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();

        // Check if this is the change-password action - allow all authenticated users
        $action = Utils::get('action', 'index');
        if ($action !== 'change-password') {
            // Only superadmin can access user management
            if (!Auth::hasRole('superadmin')) {
                http_response_code(403);
                die('Access Denied: Only superadmins can manage users.');
            }
        }
    }

    public function indexAction()
    {
        $pageTitle = 'User Management';

        // Get all operators
        $operators = $this->db->fetchAll(
            "SELECT
                id,
                username,
                firstname,
                lastname,
                email1 as email,
                department,
                company,
                createusers
            FROM operators
            ORDER BY username"
        );

        // Add role to each operator
        foreach ($operators as &$op) {
            $op['role'] = $this->getUserRole($op);
        }

        require APP_PATH . '/views/users/list.php';
    }

    public function createAction()
    {
        $pageTitle = 'Create Operator';
        $errors = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify CSRF token
            if (!Auth::verifyCsrfToken(Utils::post('csrf_token'))) {
                $errors[] = 'Invalid security token. Please try again.';
            } else {
                // Validate inputs
                $username = Utils::post('username');
                $password = Utils::post('password');
                $confirmPassword = Utils::post('confirm_password');
                $firstname = Utils::post('firstname');
                $lastname = Utils::post('lastname');
                $email = Utils::post('email');
                $department = Utils::post('department');
                $company = Utils::post('company');
                $role = Utils::post('role');

                if (empty($username)) {
                    $errors[] = 'Username is required.';
                }

                if (empty($password)) {
                    $errors[] = 'Password is required.';
                } elseif ($password !== $confirmPassword) {
                    $errors[] = 'Passwords do not match.';
                } elseif (strlen($password) < 8) {
                    $errors[] = 'Password must be at least 8 characters.';
                }

                if (empty($email)) {
                    $errors[] = 'Email is required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email address.';
                }

                // Check if username already exists
                $existing = $this->db->fetchOne(
                    "SELECT id FROM operators WHERE username = ?",
                    [$username]
                );

                if ($existing) {
                    $errors[] = 'Username already exists.';
                }

                if (empty($errors)) {
                    // Create operator
                    $createusers = ($role === 'superadmin' || $role === 'netadmin') ? 1 : 0;

                    $operatorId = $this->db->insert('operators', [
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email1' => $email,
                        'department' => $department,
                        'company' => $company,
                        'createusers' => $createusers
                    ]);

                    if ($operatorId) {
                        Utils::flash('success', 'Operator created successfully.');
                        Utils::redirect('index.php?page=users');
                    } else {
                        $errors[] = 'Failed to create operator.';
                    }
                }
            }
        }

        require APP_PATH . '/views/users/create.php';
    }

    public function editAction()
    {
        $pageTitle = 'Edit Operator';
        $errors = [];
        $success = false;

        $operatorId = (int)Utils::get('id');

        if (!$operatorId) {
            Utils::redirect('index.php?page=users');
        }

        // Get operator
        $operator = $this->db->fetchOne(
            "SELECT * FROM operators WHERE id = ?",
            [$operatorId]
        );

        if (!$operator) {
            Utils::redirect('index.php?page=users');
        }

        $currentUser = Auth::user();

        // Cannot edit yourself as last superadmin
        if ($operator['username'] === $currentUser['username']) {
            $superadminCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM operators WHERE createusers = 1"
            );

            if ($superadminCount <= 1) {
                $errors[] = 'You cannot modify the last superadmin account.';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
            // Verify CSRF token
            if (!Auth::verifyCsrfToken(Utils::post('csrf_token'))) {
                $errors[] = 'Invalid security token. Please try again.';
            } else {
                $firstname = Utils::post('firstname');
                $lastname = Utils::post('lastname');
                $email = Utils::post('email');
                $department = Utils::post('department');
                $company = Utils::post('company');
                $role = Utils::post('role');
                $newPassword = Utils::post('new_password');
                $confirmPassword = Utils::post('confirm_password');

                if (empty($email)) {
                    $errors[] = 'Email is required.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email address.';
                }

                // Validate password if provided
                if (!empty($newPassword)) {
                    if ($newPassword !== $confirmPassword) {
                        $errors[] = 'Passwords do not match.';
                    } elseif (strlen($newPassword) < 8) {
                        $errors[] = 'Password must be at least 8 characters.';
                    }
                }

                if (empty($errors)) {
                    $createusers = ($role === 'superadmin' || $role === 'netadmin') ? 1 : 0;

                    $updateData = [
                        'firstname' => $firstname,
                        'lastname' => $lastname,
                        'email1' => $email,
                        'department' => $department,
                        'company' => $company,
                        'createusers' => $createusers
                    ];

                    // Update password if provided
                    if (!empty($newPassword)) {
                        $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    }

                    $updated = $this->db->update('operators', $updateData, 'id = ?', [$operatorId]);

                    if ($updated !== false) {
                        Utils::flash('success', 'Operator updated successfully.');
                        Utils::redirect('index.php?page=users');
                    } else {
                        $errors[] = 'Failed to update operator.';
                    }
                }
            }
        }

        require APP_PATH . '/views/users/edit.php';
    }

    public function deleteAction()
    {
        $operatorId = (int)Utils::post('id');
        $csrfToken = Utils::post('csrf_token');

        if (!Auth::verifyCsrfToken($csrfToken)) {
            Utils::flash('error', 'Invalid security token.');
            Utils::redirect('index.php?page=users');
        }

        if (!$operatorId) {
            Utils::redirect('index.php?page=users');
        }

        $operator = $this->db->fetchOne("SELECT * FROM operators WHERE id = ?", [$operatorId]);

        if (!$operator) {
            Utils::flash('error', 'Operator not found.');
            Utils::redirect('index.php?page=users');
        }

        $currentUser = Auth::user();

        // Cannot delete yourself
        if ($operator['username'] === $currentUser['username']) {
            Utils::flash('error', 'You cannot delete your own account.');
            Utils::redirect('index.php?page=users');
        }

        // Cannot delete last superadmin
        if ($operator['createusers'] == 1) {
            $superadminCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM operators WHERE createusers = 1"
            );

            if ($superadminCount <= 1) {
                Utils::flash('error', 'Cannot delete the last superadmin account.');
                Utils::redirect('index.php?page=users');
            }
        }

        // Delete operator
        $deleted = $this->db->delete('operators', 'id = ?', [$operatorId]);

        if ($deleted) {
            Utils::flash('success', 'Operator deleted successfully.');
        } else {
            Utils::flash('error', 'Failed to delete operator.');
        }

        Utils::redirect('index.php?page=users');
    }

    private function getUserRole($operator)
    {
        $appConfig = require APP_PATH . '/config/app.php';
        $roleMapping = $appConfig['role_mapping'];

        if (isset($roleMapping[$operator['username']])) {
            return $roleMapping[$operator['username']];
        }

        if ($operator['createusers'] == 1) {
            return 'netadmin';
        }

        return 'helpdesk';
    }

    /**
     * Change Password Action - For current logged-in user
     */
    public function changePasswordAction()
    {
        $pageTitle = 'Change Password';
        $errors = [];

        // Check if user is logged in
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
            header('Location: index.php?page=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = Utils::post('current_password', '');
            $newPassword = Utils::post('new_password', '');
            $confirmPassword = Utils::post('confirm_password', '');

            // Get current user
            $user = $this->db->fetchOne(
                'SELECT * FROM operators WHERE id = ?',
                [$_SESSION['user_id']]
            );

            if (!$user) {
                $errors[] = 'User not found';
            }

            // Verify current password
            if (empty($errors)) {
                // Check MD5 hash (password column is varchar(32))
                $currentHashMd5 = md5($currentPassword);

                if ($currentHashMd5 !== $user['password']) {
                    $errors[] = 'Current password is incorrect';
                }
            }

            // Validate new password
            if (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match';
            }

            // Update password if no errors
            if (empty($errors)) {
                // Use MD5 hash (password column is varchar(32))
                $newHash = md5($newPassword);

                // Use update method from Database class
                $result = $this->db->update(
                    'operators',
                    [
                        'password' => $newHash,
                        'must_change_password' => 0,
                        'password_changed_at' => date('Y-m-d H:i:s')
                    ],
                    'id = ?',
                    [$_SESSION['user_id']]
                );

                if ($result !== false) {
                    $_SESSION['success_message'] = 'Password changed successfully';
                    unset($_SESSION['must_change_password']);
                    header('Location: index.php?page=dashboard');
                    exit;
                } else {
                    $errors[] = 'Failed to update password';
                }
            }
        }

        require APP_PATH . '/views/users/change-password.php';
    }
}
