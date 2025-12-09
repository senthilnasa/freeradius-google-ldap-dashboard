<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-user-edit"></i> Edit Operator: <?= Utils::e($operator['username']) ?></h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5>Errors:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Username cannot be changed after creation.
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text"
                                       class="form-control"
                                       value="<?= Utils::e($operator['username']) ?>"
                                       disabled>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email"
                                       class="form-control"
                                       id="email"
                                       name="email"
                                       value="<?= Utils::e($_POST['email'] ?? $operator['email1'] ?? $operator['email'] ?? '') ?>"
                                       required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text"
                                       class="form-control"
                                       id="firstname"
                                       name="firstname"
                                       value="<?= Utils::e($_POST['firstname'] ?? $operator['firstname'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text"
                                       class="form-control"
                                       id="lastname"
                                       name="lastname"
                                       value="<?= Utils::e($_POST['lastname'] ?? $operator['lastname'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text"
                                       class="form-control"
                                       id="department"
                                       name="department"
                                       value="<?= Utils::e($_POST['department'] ?? $operator['department'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="company" class="form-label">Company</label>
                                <input type="text"
                                       class="form-control"
                                       id="company"
                                       name="company"
                                       value="<?= Utils::e($_POST['company'] ?? $operator['company'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <?php
                            $currentRole = $_POST['role'] ?? ($operator['createusers'] == 1 ? 'netadmin' : 'helpdesk');
                            ?>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="superadmin" <?= $currentRole === 'superadmin' ? 'selected' : '' ?>>Superadmin (Full Access)</option>
                                <option value="netadmin" <?= $currentRole === 'netadmin' ? 'selected' : '' ?>>Netadmin (User Management)</option>
                                <option value="helpdesk" <?= $currentRole === 'helpdesk' ? 'selected' : '' ?>>Helpdesk (View Only)</option>
                            </select>
                            <small class="form-text text-muted">
                                Superadmin: Full system access | Netadmin: User management + reports | Helpdesk: Read-only access
                            </small>
                        </div>

                        <hr>

                        <h5 class="mb-3">Change Password (Optional)</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Leave password fields empty to keep the current password.
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password"
                                       class="form-control"
                                       id="new_password"
                                       name="new_password"
                                       minlength="8"
                                       autocomplete="new-password">
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password"
                                       class="form-control"
                                       id="confirm_password"
                                       name="confirm_password"
                                       minlength="8"
                                       autocomplete="new-password">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Operator
                            </button>
                            <a href="index.php?page=users" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validate passwords match if provided
document.querySelector('form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    // Only check if at least one password field has a value
    if (newPassword || confirmPassword) {
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('New passwords do not match!');
            return false;
        }
    }
});
</script>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
