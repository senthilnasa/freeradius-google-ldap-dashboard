<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>
                <i class="fas fa-users"></i> User Management
            </h2>
            <p class="text-muted">Manage dashboard operators and their permissions</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list"></i> Operators</h5>
            <a href="index.php?page=users&action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Operator
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($operators)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No operators found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operators as $operator): ?>
                                <tr>
                                    <td>
                                        <strong><?= Utils::e($operator['username']) ?></strong>
                                    </td>
                                    <td>
                                        <?= Utils::e(trim($operator['firstname'] . ' ' . $operator['lastname'])) ?>
                                    </td>
                                    <td>
                                        <?= Utils::e($operator['email']) ?>
                                    </td>
                                    <td>
                                        <?= Utils::e($operator['department']) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $roleBadgeClass = match($operator['role']) {
                                            'superadmin' => 'bg-danger',
                                            'netadmin' => 'bg-warning text-dark',
                                            'helpdesk' => 'bg-info',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $roleBadgeClass ?>">
                                            <?= Utils::e(ucfirst($operator['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= isset($operator['creationdate']) ? date('Y-m-d', strtotime($operator['creationdate'])) : '-' ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="index.php?page=users&action=edit&id=<?= $operator['id'] ?>"
                                               class="btn btn-outline-primary"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($operator['username'] !== Auth::user()['username']): ?>
                                                <button type="button"
                                                        class="btn btn-outline-danger"
                                                        onclick="confirmDelete(<?= $operator['id'] ?>, '<?= Utils::e($operator['username']) ?>')"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Role Permissions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6><span class="badge bg-danger">Superadmin</span></h6>
                    <ul>
                        <li>Full system access</li>
                        <li>User management</li>
                        <li>All reports and logs</li>
                        <li>System configuration</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6><span class="badge bg-warning text-dark">Netadmin</span></h6>
                    <ul>
                        <li>User management</li>
                        <li>View all reports</li>
                        <li>Authentication logs</li>
                        <li>Limited system config</li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6><span class="badge bg-info">Helpdesk</span></h6>
                    <ul>
                        <li>View reports</li>
                        <li>Search authentication logs</li>
                        <li>Basic troubleshooting</li>
                        <li>Read-only access</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" action="index.php?page=users&action=delete" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
    <input type="hidden" name="id" id="deleteOperatorId">
</form>

<script>
function confirmDelete(id, username) {
    if (confirm('Are you sure you want to delete operator "' + username + '"?\n\nThis action cannot be undone.')) {
        document.getElementById('deleteOperatorId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
