<?php
$pageTitle = 'Authentication Log - Advanced Filters';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="fas fa-filter"></i> Authentication Log - Advanced Filters</h1>
            <p class="text-muted mb-0">Filter authentication logs by multiple criteria</p>
        </div>
        <div>
            <a href="index.php?page=auth-log" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Simple View
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

<!-- Advanced Filter Panel -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Apply Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="index.php" id="filterForm">
            <input type="hidden" name="page" value="auth-log">
            <input type="hidden" name="action" value="filtered">

            <!-- Row 1: Date Range and Username -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-calendar"></i> From Date</label>
                    <input type="date" name="date_from" class="form-control"
                           value="<?= htmlspecialchars($_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'))) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-calendar"></i> To Date</label>
                    <input type="date" name="date_to" class="form-control"
                           value="<?= htmlspecialchars($_GET['date_to'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control"
                           placeholder="Search username (partial match)"
                           value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
                </div>
            </div>

            <!-- Row 2: Result, Error Type, User Type, VLAN -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-check-circle"></i> Result</label>
                    <select name="reply" class="form-select">
                        <option value="">All Results</option>
                        <option value="Access-Accept" <?= ($_GET['reply'] ?? '') === 'Access-Accept' ? 'selected' : '' ?>>
                            ✅ Success
                        </option>
                        <option value="Access-Reject" <?= ($_GET['reply'] ?? '') === 'Access-Reject' ? 'selected' : '' ?>>
                            ❌ Failed
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-exclamation-triangle"></i> Error Type</label>
                    <select name="error_type" class="form-select">
                        <option value="">All Errors</option>
                        <?php foreach ($filterOptions['error_types'] as $et): ?>
                            <option value="<?= htmlspecialchars($et['error_type']) ?>"
                                    <?= ($_GET['error_type'] ?? '') === $et['error_type'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($et['error_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-user-tag"></i> User Type</label>
                    <select name="user_type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($filterOptions['user_types'] as $type): ?>
                            <option value="<?= htmlspecialchars($type['user_type']) ?>"
                                    <?= ($_GET['user_type'] ?? '') === $type['user_type'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['user_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-network-wired"></i> VLAN</label>
                    <select name="vlan" class="form-select">
                        <option value="">All VLANs</option>
                        <?php foreach ($filterOptions['vlans'] as $vlan): ?>
                            <option value="<?= htmlspecialchars($vlan['vlan']) ?>"
                                    <?= ($_GET['vlan'] ?? '') === $vlan['vlan'] ? 'selected' : '' ?>>
                                VLAN <?= htmlspecialchars($vlan['vlan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Row 3: Access Point Filters (from JSON) -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-wifi"></i> Access Point (NAS IP)</label>
                    <select name="nas_ip" class="form-select">
                        <option value="">All Access Points</option>
                        <?php foreach ($filterOptions['nas_ips'] as $nas): ?>
                            <?php if (!empty($nas['nas_ip'])): ?>
                                <option value="<?= htmlspecialchars($nas['nas_ip']) ?>"
                                        <?= ($_GET['nas_ip'] ?? '') === $nas['nas_ip'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nas['nas_ip']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-map-marker-alt"></i> Location</label>
                    <select name="location" class="form-select">
                        <option value="">All Locations</option>
                        <?php foreach ($filterOptions['locations'] as $loc): ?>
                            <?php if (!empty($loc['location'])): ?>
                                <option value="<?= htmlspecialchars($loc['location']) ?>"
                                        <?= ($_GET['location'] ?? '') === $loc['location'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['location']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-layer-group"></i> AP Group</label>
                    <select name="ap_group" class="form-select">
                        <option value="">All AP Groups</option>
                        <?php foreach ($filterOptions['ap_groups'] as $group): ?>
                            <?php if (!empty($group['ap_group'])): ?>
                                <option value="<?= htmlspecialchars($group['ap_group']) ?>"
                                        <?= ($_GET['ap_group'] ?? '') === $group['ap_group'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group['ap_group']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-broadcast-tower"></i> SSID</label>
                    <select name="ssid" class="form-select">
                        <option value="">All SSIDs</option>
                        <?php foreach ($filterOptions['ssids'] as $ssid): ?>
                            <?php if (!empty($ssid['ssid'])): ?>
                                <option value="<?= htmlspecialchars($ssid['ssid']) ?>"
                                        <?= ($_GET['ssid'] ?? '') === $ssid['ssid'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ssid['ssid']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="index.php?page=auth-log&action=filtered" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset Filters
                    </a>
                    <button type="button" class="btn btn-success" onclick="exportFiltered()">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </button>
                    <span class="text-muted ms-3">
                        <i class="fas fa-info-circle"></i>
                        Showing <?= number_format(count($logs)) ?> of <?= number_format($totalRecords) ?> records
                    </span>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0">
            <i class="fas fa-list"></i> Authentication Logs
            <span class="badge bg-primary ms-2"><?= number_format($totalRecords) ?> records</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No authentication logs found matching your filters.
                Try adjusting your filter criteria.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm" id="authLogTable">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Time</th>
                            <th>Username</th>
                            <th>Result</th>
                            <th>Error Type</th>
                            <th>VLAN</th>
                            <th>User Type</th>
                            <th>Access Point</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <small><?= date('Y-m-d H:i:s', strtotime($log['authdate'])) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($log['username']) ?></strong>
                                </td>
                                <td>
                                    <?php if ($log['reply'] === 'Access-Accept'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Success
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times"></i> Failed
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['error_type'])): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= htmlspecialchars($log['error_type']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['vlan'])): ?>
                                        <span class="badge bg-info">VLAN <?= htmlspecialchars($log['vlan']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($log['user_type'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <?php
                                    $nasIp = $log['request_log_parsed']['NAS-IP-Address'] ?? '-';
                                    ?>
                                    <small><?= htmlspecialchars($nasIp) ?></small>
                                </td>
                                <td>
                                    <?php
                                    $location = $log['request_log_parsed']['Aruba-Location-Id'] ?? '-';
                                    ?>
                                    <small><?= htmlspecialchars($location) ?></small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="showDetails(<?= $log['id'] ?>)">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $pagination['first_page_url'] ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= $pagination['prev_page_url'] ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= str_replace('page_num=1', 'page_num=' . $i, $_SERVER['REQUEST_URI']) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $pagination['next_page_url'] ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="<?= $pagination['last_page_url'] ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Authentication Log Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(id) {
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    const modalContent = document.getElementById('modalContent');

    // Show loading
    modalContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    modal.show();

    // Fetch details via AJAX
    fetch('index.php?page=auth-log&action=detail&id=' + id + '&ajax=1')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Check if the response contains an error
            if (data.error) {
                modalContent.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> ${data.error}
                        ${data.json_error ? '<br><small>' + data.json_error + '</small>' : ''}
                    </div>
                `;
            } else {
                modalContent.innerHTML = formatDetails(data);
            }
        })
        .catch(error => {
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error loading details: ${error.message}
                </div>
            `;
        });
}

function formatDetails(log) {
    const requestLog = log.request_log_parsed || {};

    let html = `
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user"></i> Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th>Username:</th><td>${escapeHtml(log.username)}</td></tr>
                            <tr><th>Date & Time:</th><td>${log.authdate}</td></tr>
                            <tr><th>Result:</th><td>
                                ${log.reply === 'Access-Accept'
                                    ? '<span class="badge bg-success">Success</span>'
                                    : '<span class="badge bg-danger">Failed</span>'}
                            </td></tr>
                            <tr><th>Reply Message:</th><td>${escapeHtml(log.reply_message || '-')}</td></tr>
                            <tr><th>Error Type:</th><td>${escapeHtml(log.error_type || '-')}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-network-wired"></i> Assignment</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th>VLAN:</th><td>${log.vlan ? 'VLAN ' + escapeHtml(log.vlan) : '-'}</td></tr>
                            <tr><th>User Type:</th><td>${escapeHtml(log.user_type || '-')}</td></tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-wifi"></i> Network Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th>NAS IP:</th><td>${escapeHtml(requestLog['NAS-IP-Address'] || '-')}</td></tr>
                            <tr><th>NAS Identifier:</th><td>${escapeHtml(requestLog['NAS-Identifier'] || '-')}</td></tr>
                            <tr><th>NAS Port:</th><td>${escapeHtml(requestLog['NAS-Port'] || '-')}</td></tr>
                            <tr><th>NAS Port Type:</th><td>${escapeHtml(requestLog['NAS-Port-Type'] || '-')}</td></tr>
                            <tr><th>Client MAC:</th><td>${escapeHtml(requestLog['Calling-Station-Id'] || '-')}</td></tr>
                            <tr><th>AP MAC:</th><td>${escapeHtml(requestLog['Called-Station-Id'] || '-')}</td></tr>
                        </table>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-map-marker-alt"></i> Aruba-Specific</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr><th>SSID:</th><td>${escapeHtml(requestLog['Aruba-Essid-Name'] || '-')}</td></tr>
                            <tr><th>Location:</th><td>${escapeHtml(requestLog['Aruba-Location-Id'] || '-')}</td></tr>
                            <tr><th>AP Group:</th><td>${escapeHtml(requestLog['Aruba-AP-Group'] || '-')}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-code"></i> Complete Request Attributes (JSON)</h6>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;">${JSON.stringify(requestLog, null, 2)}</pre>
            </div>
        </div>
    `;

    return html;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
}

function exportFiltered() {
    const form = document.getElementById('filterForm');
    const url = new URL(form.action, window.location.href);
    const formData = new FormData(form);

    formData.forEach((value, key) => {
        url.searchParams.append(key, value);
    });

    url.searchParams.append('export', 'csv');

    window.location.href = url.toString();
}

// Initialize DataTables
$(document).ready(function() {
    $('#authLogTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        order: [[0, 'desc']]
    });
});
</script>
