<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-user-plus"></i> Create Operator</h4>
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

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text"
                                       class="form-control"
                                       id="username"
                                       name="username"
                                       value="<?= Utils::e(Utils::post('username', '')) ?>"
                                       required
                                       autocomplete="username">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email"
                                       class="form-control"
                                       id="email"
                                       name="email"
                                       value="<?= Utils::e(Utils::post('email', '')) ?>"
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
                                       value="<?= Utils::e(Utils::post('firstname', '')) ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text"
                                       class="form-control"
                                       id="lastname"
                                       name="lastname"
                                       value="<?= Utils::e(Utils::post('lastname', '')) ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text"
                                       class="form-control"
                                       id="department"
                                       name="department"
                                       value="<?= Utils::e(Utils::post('department', '')) ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="company" class="form-label">Company</label>
                                <input type="text"
                                       class="form-control"
                                       id="company"
                                       name="company"
                                       value="<?= Utils::e(Utils::post('company', '')) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                <option value="superadmin" <?= Utils::post('role') === 'superadmin' ? 'selected' : '' ?>>Superadmin (Full Access)</option>
                                <option value="netadmin" <?= Utils::post('role') === 'netadmin' ? 'selected' : '' ?>>Netadmin (User Management)</option>
                                <option value="helpdesk" <?= Utils::post('role') === 'helpdesk' ? 'selected' : '' ?>>Helpdesk (View Only)</option>
                            </select>
                            <small class="form-text text-muted">
                                Superadmin: Full system access | Netadmin: User management + reports | Helpdesk: Read-only access
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password"
                                       class="form-control"
                                       id="password"
                                       name="password"
                                       required
                                       minlength="8"
                                       autocomplete="new-password">
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password"
                                       class="form-control"
                                       id="confirm_password"
                                       name="confirm_password"
                                       required
                                       minlength="8"
                                       autocomplete="new-password">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Operator
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
// Validate passwords match
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});
</script>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
