<?php
$pageTitle = 'Add NAS Device';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-plus"></i> Add NAS Device</h1>
            <p class="text-muted mb-0">Configure a new Network Access Server</p>
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

<!-- Create Form -->
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="index.php?page=nas&action=create">
                    <div class="mb-3">
                        <label for="nasname" class="form-label">
                            NAS IP Address or Hostname <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nasname" name="nasname" required
                               placeholder="192.168.1.1 or ap01.example.com">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> The IP address or FQDN that FreeRADIUS will use to identify this NAS
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="shortname" class="form-label">
                            Short Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="shortname" name="shortname" required
                               placeholder="AP-MainBlock-01">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> A friendly name to identify this device (e.g., Building name, Location)
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Device Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="other" selected>Other</option>
                            <option value="cisco">Cisco</option>
                            <option value="aruba">Aruba</option>
                            <option value="mikrotik">MikroTik</option>
                            <option value="ubiquiti">Ubiquiti</option>
                            <option value="ruckus">Ruckus</option>
                            <option value="hp">HP</option>
                        </select>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Select the manufacturer/type of this device
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="secret" class="form-label">
                            RADIUS Shared Secret <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="secret" name="secret" required
                                   placeholder="Enter a strong secret key">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleSecret()">
                                <i class="fas fa-eye" id="secretIcon"></i>
                            </button>
                            <button class="btn btn-outline-primary" type="button" onclick="generateSecret()">
                                <i class="fas fa-random"></i> Generate
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> Must match the secret configured on the NAS device
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Optional description (e.g., Location, Purpose, Contact info)"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i> <strong>Configuration Tip:</strong> After creating this NAS,
                        make sure to configure the same RADIUS secret on your access point/controller.
                        The NAS device should point to this RADIUS server's IP address.
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php?page=nas" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create NAS Device
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

function generateSecret() {
    const length = 32;
    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
    let secret = '';

    for (let i = 0; i < length; i++) {
        secret += charset.charAt(Math.floor(Math.random() * charset.length));
    }

    document.getElementById('secret').value = secret;
    document.getElementById('secret').type = 'text';
    document.getElementById('secretIcon').classList.remove('fa-eye');
    document.getElementById('secretIcon').classList.add('fa-eye-slash');
}
</script>
