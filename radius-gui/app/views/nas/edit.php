<?php
$pageTitle = 'Edit NAS Device';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-edit"></i> Edit NAS Device</h1>
            <p class="text-muted mb-0">Modify NAS configuration: <?= htmlspecialchars($nas['nasname']) ?></p>
        </div>
        <div>
            <a href="index.php?page=nas" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<!-- Flash Messages -->
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Edit Form -->
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-lock"></i> <strong>Note:</strong> The NAS IP Address cannot be changed.
                    If you need to change it, please delete and recreate the NAS device.
                </div>

                <form method="POST" action="index.php?page=nas&action=edit&id=<?= $nas['id'] ?>">
                    <div class="mb-3">
                        <label for="nasname" class="form-label">
                            NAS IP Address or Hostname
                        </label>
                        <input type="text" class="form-control" id="nasname" name="nasname" disabled
                               value="<?= htmlspecialchars($nas['nasname']) ?>">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> This field cannot be modified
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="shortname" class="form-label">
                            Short Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="shortname" name="shortname" required
                               value="<?= htmlspecialchars($nas['shortname']) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Device Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="other" <?= $nas['type'] === 'other' ? 'selected' : '' ?>>Other</option>
                            <option value="cisco" <?= $nas['type'] === 'cisco' ? 'selected' : '' ?>>Cisco</option>
                            <option value="aruba" <?= $nas['type'] === 'aruba' ? 'selected' : '' ?>>Aruba</option>
                            <option value="mikrotik" <?= $nas['type'] === 'mikrotik' ? 'selected' : '' ?>>MikroTik</option>
                            <option value="ubiquiti" <?= $nas['type'] === 'ubiquiti' ? 'selected' : '' ?>>Ubiquiti</option>
                            <option value="ruckus" <?= $nas['type'] === 'ruckus' ? 'selected' : '' ?>>Ruckus</option>
                            <option value="hp" <?= $nas['type'] === 'hp' ? 'selected' : '' ?>>HP</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="secret" class="form-label">
                            RADIUS Shared Secret <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="secret" name="secret" required
                                   value="<?= htmlspecialchars($nas['secret']) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSecret()">
                                <i class="fas fa-eye" id="secretIcon"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Changing this will break authentication until you update the NAS device configuration
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($nas['description'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php?page=nas" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update NAS Device
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSecret() {
    const secretInput = document.getElementById('secret');
    const secretIcon = document.getElementById('secretIcon');

    if (secretInput.type === 'password') {
        secretInput.type = 'text';
        secretIcon.classList.remove('fa-eye');
        secretIcon.classList.add('fa-eye-slash');
    } else {
        secretInput.type = 'password';
        secretIcon.classList.remove('fa-eye-slash');
        secretIcon.classList.add('fa-eye');
    }
}
</script>
