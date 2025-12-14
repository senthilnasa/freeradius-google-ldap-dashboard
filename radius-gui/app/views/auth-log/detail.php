<?php
$pageTitle = 'Authentication Log Details';
$requestLog = $log['request_log_parsed'] ?? [];
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-info-circle"></i> Authentication Log Details</h1>
            <p class="text-muted mb-0">Complete authentication request information</p>
        </div>
        <div>
            <a href="index.php?page=auth-log" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Log
            </a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row">
    <!-- Left Column: Basic Info & Assignment -->
    <div class="col-md-6">
        <!-- Basic Information Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Basic Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">Username:</th>
                        <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Date & Time (Local):</th>
                        <td><?= htmlspecialchars($log['authdate']) ?></td>
                    </tr>
                    <?php if ($log['authdate_utc']): ?>
                    <tr>
                        <th>Date & Time (UTC):</th>
                        <td><?= htmlspecialchars($log['authdate_utc']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Result:</th>
                        <td>
                            <?php if ($log['reply'] === 'Access-Accept'): ?>
                                <span class="badge bg-success fs-6">
                                    <i class="fas fa-check-circle"></i> Access-Accept
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger fs-6">
                                    <i class="fas fa-times-circle"></i> <?= htmlspecialchars($log['reply']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($log['reply_message']): ?>
                    <tr>
                        <th>Reply Message:</th>
                        <td><?= htmlspecialchars($log['reply_message']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($log['error_type']): ?>
                    <tr>
                        <th>Error Type:</th>
                        <td>
                            <span class="badge bg-warning text-dark">
                                <?= htmlspecialchars($log['error_type']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Assignment Information Card -->
        <?php if ($log['reply'] === 'Access-Accept'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-network-wired"></i> Assignment & Authorization</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <?php if ($log['vlan']): ?>
                    <tr>
                        <th width="40%">VLAN Assignment:</th>
                        <td>
                            <span class="badge bg-info fs-6">VLAN <?= htmlspecialchars($log['vlan']) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($log['user_type']): ?>
                    <tr>
                        <th>User Type:</th>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($log['user_type']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Service Type:</th>
                        <td><?= htmlspecialchars($requestLog['Service-Type'] ?? '-') ?></td>
                    </tr>
                    <?php if (!empty($requestLog['Framed-MTU'])): ?>
                    <tr>
                        <th>Framed MTU:</th>
                        <td><?= htmlspecialchars($requestLog['Framed-MTU']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Network & Location Info -->
    <div class="col-md-6">
        <!-- Network Information Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-wifi"></i> Network Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">NAS IP Address:</th>
                        <td><code><?= htmlspecialchars($requestLog['NAS-IP-Address'] ?? '-') ?></code></td>
                    </tr>
                    <tr>
                        <th>NAS Identifier:</th>
                        <td><?= htmlspecialchars($requestLog['NAS-Identifier'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>NAS Port:</th>
                        <td><?= htmlspecialchars($requestLog['NAS-Port'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>NAS Port Type:</th>
                        <td>
                            <?php if (!empty($requestLog['NAS-Port-Type'])): ?>
                                <span class="badge bg-secondary">
                                    <?= htmlspecialchars($requestLog['NAS-Port-Type']) ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Client MAC Address:</th>
                        <td><code><?= htmlspecialchars($requestLog['Calling-Station-Id'] ?? '-') ?></code></td>
                    </tr>
                    <tr>
                        <th>AP MAC Address:</th>
                        <td><code><?= htmlspecialchars($requestLog['Called-Station-Id'] ?? '-') ?></code></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Aruba-Specific Information Card -->
        <?php if (!empty($requestLog['Aruba-Essid-Name']) || !empty($requestLog['Aruba-Location-Id']) || !empty($requestLog['Aruba-AP-Group'])): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Access Point Details (Aruba)</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <?php if (!empty($requestLog['Aruba-Essid-Name'])): ?>
                    <tr>
                        <th width="40%">SSID:</th>
                        <td>
                            <span class="badge bg-primary">
                                <i class="fas fa-broadcast-tower"></i>
                                <?= htmlspecialchars($requestLog['Aruba-Essid-Name']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($requestLog['Aruba-Location-Id'])): ?>
                    <tr>
                        <th>Location:</th>
                        <td>
                            <i class="fas fa-building"></i>
                            <?= htmlspecialchars($requestLog['Aruba-Location-Id']) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($requestLog['Aruba-AP-Group'])): ?>
                    <tr>
                        <th>AP Group:</th>
                        <td>
                            <i class="fas fa-layer-group"></i>
                            <?= htmlspecialchars($requestLog['Aruba-AP-Group']) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Complete Request Attributes Card -->
<?php if (!empty($requestLog)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-dark text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-code"></i> Complete RADIUS Request Attributes (JSON)</h5>
            <button class="btn btn-sm btn-light" onclick="copyJSON()">
                <i class="fas fa-copy"></i> Copy JSON
            </button>
        </div>
    </div>
    <div class="card-body">
        <pre id="jsonContent" class="bg-light p-3 mb-0" style="max-height: 400px; overflow-y: auto; border-radius: 4px;"><code><?= json_encode($requestLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></code></pre>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>No request attributes logged.</strong>
    The request_log field is empty for this authentication attempt.
    This may be because FreeRADIUS is not configured to log request attributes yet.
</div>
<?php endif; ?>

<!-- Additional Attributes (if any) -->
<?php
// Find any other attributes not already displayed
$displayedKeys = [
    'User-Name', 'NAS-IP-Address', 'NAS-Identifier', 'NAS-Port', 'NAS-Port-Type',
    'Calling-Station-Id', 'Called-Station-Id', 'Service-Type', 'Framed-MTU',
    'Aruba-Essid-Name', 'Aruba-Location-Id', 'Aruba-AP-Group'
];
$otherAttributes = array_diff_key($requestLog, array_flip($displayedKeys));
?>

<?php if (!empty($otherAttributes)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-list"></i> Additional RADIUS Attributes</h5>
    </div>
    <div class="card-body">
        <table class="table table-sm table-striped">
            <thead>
                <tr>
                    <th>Attribute Name</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($otherAttributes as $key => $value): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($key) ?></code></td>
                        <td><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function copyJSON() {
    const jsonContent = document.getElementById('jsonContent').textContent;
    navigator.clipboard.writeText(jsonContent).then(() => {
        // Show success message
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        btn.classList.remove('btn-light');
        btn.classList.add('btn-success');

        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-light');
        }, 2000);
    }).catch(err => {
        alert('Failed to copy: ' + err);
    });
}
</script>
