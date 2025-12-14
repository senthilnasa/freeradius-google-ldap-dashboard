<?php
$pageTitle = 'NAS Device Details';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-network-wired"></i> NAS Device Details</h1>
            <p class="text-muted mb-0">View Network Access Server information</p>
        </div>
        <div>
            <a href="index.php?page=nas" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="index.php?page=nas&action=edit&id=<?= htmlspecialchars($nas['id']) ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>
</div>

<!-- NAS Details -->
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Device Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">NAS Name / IP:</th>
                        <td><code class="fs-5"><?= htmlspecialchars($nas['nasname']) ?></code></td>
                    </tr>
                    <tr>
                        <th>Short Name:</th>
                        <td><strong><?= htmlspecialchars($nas['shortname']) ?></strong></td>
                    </tr>
                    <tr>
                        <th>Device Type:</th>
                        <td>
                            <?php
                            $typeIcons = [
                                'cisco' => 'fa-network-wired',
                                'aruba' => 'fa-wifi',
                                'mikrotik' => 'fa-router',
                                'ubiquiti' => 'fa-broadcast-tower',
                                'ruckus' => 'fa-signal',
                                'hp' => 'fa-server',
                                'other' => 'fa-question-circle'
                            ];
                            $icon = $typeIcons[$nas['type']] ?? 'fa-question-circle';
                            ?>
                            <i class="fas <?= $icon ?>"></i> <?= htmlspecialchars(ucfirst($nas['type'])) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>RADIUS Secret:</th>
                        <td>
                            <span class="badge bg-secondary">
                                <i class="fas fa-key"></i> Configured (hidden for security)
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Description:</th>
                        <td><?= $nas['description'] ? htmlspecialchars($nas['description']) : '<em class="text-muted">No description</em>' ?></td>
                    </tr>
                    <?php if (isset($nas['server'])): ?>
                    <tr>
                        <th>Server:</th>
                        <td><?= htmlspecialchars($nas['server']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> 7-Day Activity</h5>
            </div>
            <div class="card-body">
                <?php if ($stats): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Total Authentications:</span>
                            <strong class="fs-4"><?= number_format($stats['total']) ?></strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Successful:</span>
                            <strong class="text-success"><?= number_format($stats['successful']) ?></strong>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: <?= $stats['total'] > 0 ? round(($stats['successful'] / $stats['total']) * 100, 2) : 0 ?>%">
                                <?= $stats['total'] > 0 ? round(($stats['successful'] / $stats['total']) * 100, 1) : 0 ?>%
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>Failed:</span>
                            <strong class="text-danger"><?= number_format($stats['failed']) ?></strong>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-danger" role="progressbar"
                                 style="width: <?= $stats['total'] > 0 ? round(($stats['failed'] / $stats['total']) * 100, 2) : 0 ?>%">
                                <?= $stats['total'] > 0 ? round(($stats['failed'] / $stats['total']) * 100, 1) : 0 ?>%
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Unique Users:</span>
                            <strong><?= number_format($stats['unique_users']) ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle"></i> No activity recorded in the last 7 days
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="index.php?page=auth-log&action=filtered&nas_ip=<?= urlencode($nas['nasname']) ?>"
                       class="btn btn-outline-primary">
                        <i class="fas fa-list"></i> View Authentication Logs
                    </a>
                    <a href="index.php?page=nas&action=edit&id=<?= htmlspecialchars($nas['id']) ?>"
                       class="btn btn-outline-warning">
                        <i class="fas fa-edit"></i> Edit Configuration
                    </a>
                    <button type="button" class="btn btn-outline-danger"
                            onclick="if(confirm('Are you sure you want to delete this NAS device?')) { window.location.href='index.php?page=nas&action=delete&id=<?= htmlspecialchars($nas['id']) ?>'; }">
                        <i class="fas fa-trash"></i> Delete Device
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Authentication Attempts -->
<?php if ($recentAuths && count($recentAuths) > 0): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Authentication Attempts (Last 24 Hours)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Username</th>
                        <th>Result</th>
                        <th>VLAN</th>
                        <th>Error Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAuths as $auth): ?>
                    <tr>
                        <td><?= htmlspecialchars($auth['authdate']) ?></td>
                        <td><?= htmlspecialchars($auth['username']) ?></td>
                        <td>
                            <?php if ($auth['reply'] === 'Access-Accept'): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Success
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="fas fa-times-circle"></i> Failed
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($auth['reply'] === 'Access-Accept' && $auth['vlan']): ?>
                                <span class="badge bg-info">VLAN <?= htmlspecialchars($auth['vlan']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($auth['error_type']): ?>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($auth['error_type']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="index.php?page=auth-log&action=detail&id=<?= $auth['id'] ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-center">
        <a href="index.php?page=auth-log&action=filtered&nas_ip=<?= urlencode($nas['nasname']) ?>"
           class="btn btn-sm btn-primary">
            <i class="fas fa-list"></i> View All Authentication Logs for this NAS
        </a>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle"></i> No authentication attempts recorded in the last 24 hours for this NAS device.
</div>
<?php endif; ?>
