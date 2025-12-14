<?php
$pageTitle = 'NAS Management';
?>

<!-- Flash Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-network-wired"></i> NAS Management</h1>
            <p class="text-muted mb-0">Manage Network Access Servers (Access Points, WiFi Controllers)</p>
        </div>
        <div>
            <a href="index.php?page=nas&action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add NAS Device
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total NAS Devices</h6>
                        <h3 class="mb-0"><?= number_format($totalCount) ?></h3>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-network-wired fa-3x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active NAS (7 days)</h6>
                        <h3 class="mb-0">
                            <?php
                            $activeCount = 0;
                            foreach ($nasDevices as $nas) {
                                if ($nas['stats'] && $nas['stats']['total_sessions'] > 0) {
                                    $activeCount++;
                                }
                            }
                            echo $activeCount;
                            ?>
                        </h3>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-3x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Sessions (7 days)</h6>
                        <h3 class="mb-0">
                            <?php
                            $totalSessions = 0;
                            foreach ($nasDevices as $nas) {
                                if ($nas['stats']) {
                                    $totalSessions += $nas['stats']['total_sessions'] ?? 0;
                                }
                            }
                            echo number_format($totalSessions);
                            ?>
                        </h3>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-users fa-3x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- NAS Devices Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="nasTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>NAS Name / IP</th>
                        <th>Short Name</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>7-Day Activity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nasDevices as $nas): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($nas['nasname']) ?></strong>
                                <?php if ($nas['stats'] && $nas['stats']['total_sessions'] > 0): ?>
                                    <span class="badge bg-success ms-2">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-2">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($nas['shortname']) ?></td>
                            <td>
                                <?php
                                $typeIcons = [
                                    'cisco' => 'fa-server',
                                    'aruba' => 'fa-wifi',
                                    'mikrotik' => 'fa-router',
                                    'other' => 'fa-network-wired'
                                ];
                                $icon = $typeIcons[$nas['type']] ?? 'fa-network-wired';
                                ?>
                                <i class="fas <?= $icon ?>"></i> <?= ucfirst(htmlspecialchars($nas['type'])) ?>
                            </td>
                            <td><?= htmlspecialchars($nas['description'] ?? 'N/A') ?></td>
                            <td>
                                <?php if ($nas['stats'] && $nas['stats']['total_sessions'] > 0): ?>
                                    <small>
                                        <i class="fas fa-users text-primary"></i> <?= number_format($nas['stats']['total_sessions']) ?> sessions<br>
                                        <i class="fas fa-user text-success"></i> <?= number_format($nas['stats']['unique_users']) ?> users<br>
                                        <i class="fas fa-database text-info"></i> <?= Utils::formatBytes($nas['stats']['total_data'] ?? 0) ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted">No activity</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?page=nas&action=view&id=<?= $nas['id'] ?>"
                                       class="btn btn-outline-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="index.php?page=nas&action=edit&id=<?= $nas['id'] ?>"
                                       class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?page=nas&action=delete&id=<?= $nas['id'] ?>"
                                       class="btn btn-outline-danger" title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this NAS device?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Initialize DataTable with export buttons
$(document).ready(function() {
    $('#nasTable').DataTable({
        responsive: true,
        order: [[1, 'asc']], // Sort by Short Name
        pageLength: 25,
        language: {
            search: "Search NAS:",
            lengthMenu: "Show _MENU_ devices per page"
        }
    });
});
</script>
