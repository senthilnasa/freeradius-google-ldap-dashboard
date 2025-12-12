<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="card card-custom">
    <div class="card-header">
        <i class="fas fa-list-alt"></i> Authentication Log
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="" class="row g-3 mb-3">
            <input type="hidden" name="page" value="auth-log">

            <div class="col-md-2">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" class="form-control" id="from_date" name="from_date"
                       value="<?= Utils::e($fromDate) ?>">
            </div>

            <div class="col-md-2">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" class="form-control" id="to_date" name="to_date"
                       value="<?= Utils::e($toDate) ?>">
            </div>

            <div class="col-md-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= Utils::e($username) ?>" placeholder="Search username...">
            </div>

            <div class="col-md-2">
                <label for="result" class="form-label">Result</label>
                <select class="form-select" id="result" name="result">
                    <option value="">All Results</option>
                    <option value="success" <?= $result === 'success' ? 'selected' : '' ?>>Success</option>
                    <option value="failed" <?= $result === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="error_type" class="form-label">Error Type</label>
                <select class="form-select" id="error_type" name="error_type">
                    <option value="">All Types</option>
                    <?php foreach ($errorTypes as $et): ?>
                        <option value="<?= Utils::e($et['error_type']) ?>"
                                <?= $errorType === $et['error_type'] ? 'selected' : '' ?>>
                            <?= Utils::e(ucwords(str_replace('_', ' ', $et['error_type']))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        <div class="mb-3">
            <a href="?page=auth-log&export=csv&from_date=<?= $fromDate ?>&to_date=<?= $toDate ?>&username=<?= urlencode($username) ?>&result=<?= $result ?>&error_type=<?= urlencode($errorType) ?>"
               class="btn btn-success">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        </div>

        <!-- Results -->
        <div class="table-responsive">
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No authentication logs found for the selected criteria.
                </div>
            <?php else: ?>
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time (IST)</th>
                            <th>UTC Time</th>
                            <th>Username</th>
                            <th>Result</th>
                            <th>VLAN</th>
                            <th>User Type</th>
                            <th>Error Type</th>
                            <th>Message</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= Utils::formatDate($log['authdate']) ?></td>
                                <td class="text-muted">
                                    <small><?= Utils::formatDate($log['authdate_utc'] ?? '-') ?></small>
                                </td>
                                <td><?= Utils::e($log['username']) ?></td>
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
                                    <?php if (!empty($log['vlan']) && $log['reply'] === 'Access-Accept'): ?>
                                        <span class="badge bg-info">
                                            <i class="fas fa-network-wired"></i> <?= Utils::e($log['vlan']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['user_type']) && $log['reply'] === 'Access-Accept'): ?>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-user-tag"></i> <?= Utils::e($log['user_type']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['reply'] === 'Access-Accept'): ?>
                                        <span class="text-muted">-</span>
                                    <?php elseif (!empty($log['error_type'])): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= Utils::e(ucwords(str_replace('_', ' ', $log['error_type']))) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Unknown Error</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small title="<?= Utils::e($log['reply_message'] ?? '') ?>">
                                        <?= Utils::e(substr($log['reply_message'] ?? '-', 0, 80)) ?>
                                        <?php if (strlen($log['reply_message'] ?? '') > 80): ?>...<?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if (!empty($log['request_log'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#requestLogModal"
                                                data-log-id="<?= $log['id'] ?>"
                                                data-username="<?= Utils::e($log['username']) ?>"
                                                data-request-log='<?= Utils::e($log['request_log']) ?>'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalRecords) ?>
                        of <?= number_format($totalRecords) ?> records
                    </div>
                    <div>
                        <?php
                        $baseUrl = 'index.php?page=auth-log&from_date=' . $fromDate . '&to_date=' . $toDate .
                                   '&username=' . urlencode($username) . '&result=' . $result .
                                   '&error_type=' . urlencode($errorType);
                        echo Utils::paginationLinks($pagination, $baseUrl);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Request Log Details Modal -->
<div class="modal fade" id="requestLogModal" tabindex="-1" aria-labelledby="requestLogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requestLogModalLabel">
                    <i class="fas fa-info-circle"></i> Request Details - <span id="modalUsername"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="requestLogTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="parsed-tab" data-bs-toggle="tab" data-bs-target="#parsed" type="button" role="tab">
                            <i class="fas fa-table"></i> Parsed View
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="json-tab" data-bs-toggle="tab" data-bs-target="#json" type="button" role="tab">
                            <i class="fas fa-code"></i> JSON View
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-3" id="requestLogTabContent">
                    <!-- Parsed View Tab -->
                    <div class="tab-pane fade show active" id="parsed" role="tabpanel">
                        <table class="table table-sm table-bordered">
                            <tbody id="requestLogDetails">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- JSON View Tab -->
                    <div class="tab-pane fade" id="json" role="tabpanel">
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-success" id="downloadJsonBtn">
                                <i class="fas fa-download"></i> Download JSON
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="copyJsonBtn">
                                <i class="fas fa-copy"></i> Copy to Clipboard
                            </button>
                        </div>
                        <pre id="requestLogJson" style="background: #f5f5f5; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Populate modal with request log details
document.addEventListener('DOMContentLoaded', function() {
    var requestLogModal = document.getElementById('requestLogModal');
    var currentRequestLogJson = ''; // Store current JSON for download/copy

    if (requestLogModal) {
        requestLogModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var username = button.getAttribute('data-username');
            var requestLogJson = button.getAttribute('data-request-log');

            currentRequestLogJson = requestLogJson; // Store for download

            // Update modal title
            document.getElementById('modalUsername').textContent = username;

            // Parse and display JSON data in Parsed View
            try {
                var requestLog = JSON.parse(requestLogJson);
                var detailsHtml = '';

                // Parse the pipe-delimited request log format
                // Format: "key1:value1|key2:value2|..."
                var detailsHtml = '';
                
                try {
                    // First try to parse as JSON (for backward compatibility)
                    if (requestLogJson.trim().startsWith('{')) {
                        var requestLog = JSON.parse(requestLogJson);
                        for (var key in requestLog) {
                            if (requestLog.hasOwnProperty(key) && requestLog[key]) {
                                var label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                var value = requestLog[key];
                                
                                // Format based on field type
                                if (key.toLowerCase().includes('mac') || key.toLowerCase().includes('station')) {
                                    value = '<code>' + value + '</code>';
                                } else if (key.toLowerCase().includes('ip') || key.toLowerCase().includes('address')) {
                                    value = '<code>' + value + '</code>';
                                } else if (key.toLowerCase().includes('port')) {
                                    value = '<code>' + value + '</code>';
                                } else if (key.toLowerCase().includes('ssid')) {
                                    value = '<strong>' + value + '</strong>';
                                }
                                
                                detailsHtml += '<tr><td class="fw-bold" style="width: 40%;">' + label + '</td><td>' + value + '</td></tr>';
                            }
                        }
                    } else {
                        // Parse pipe-delimited format: "key1:value1|key2:value2|..."
                        var pairs = requestLogJson.split('|');
                        for (var i = 0; i < pairs.length; i++) {
                            var pair = pairs[i].split(':');
                            if (pair.length === 2) {
                                var key = pair[0].trim();
                                var value = pair[1].trim();
                                
                                // Skip empty values
                                if (!value) continue;
                                
                                // Create friendly label from key
                                var label = key.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                
                                // Format based on field type
                                if (key.toLowerCase().includes('mac') || key.toLowerCase().includes('station')) {
                                    value = '<code>' + value + '</code>';
                                } else if (key.toLowerCase().includes('ip') && !key.toLowerCase().includes('octets')) {
                                    value = '<code>' + value + '</code>';
                                } else if (key.toLowerCase().includes('port') && !key.toLowerCase().includes('type') && !key.toLowerCase().includes('packets')) {
                                    value = '<code>' + value + '</code>';
                                } else if (key.toLowerCase().includes('ssid') || key.toLowerCase().includes('essid')) {
                                    value = '<strong>' + value + '</strong>';
                                }
                                
                                detailsHtml += '<tr><td class="fw-bold" style="width: 40%;">' + label + '</td><td>' + value + '</td></tr>';
                            }
                        }
                    }
                } catch (e) {
                    // If parsing fails, show raw data
                    detailsHtml = '<tr><td colspan="2" class="text-warning"><i class="fas fa-exclamation-circle"></i> Raw request data (parsing error):</td></tr>';
                    detailsHtml += '<tr><td colspan="2"><pre style="font-size: 0.85em; max-height: 200px; overflow-y: auto;">' + requestLogJson + '</pre></td></tr>';
                }
                
                document.getElementById('requestLogDetails').innerHTML = detailsHtml;
                
                // Display raw data in JSON View tab
                document.getElementById('requestLogJson').textContent = requestLogJson;

                document.getElementById('requestLogDetails').innerHTML = detailsHtml;

                // Display formatted JSON in JSON View tab
                document.getElementById('requestLogJson').textContent = JSON.stringify(requestLog, null, 2);

            } catch (e) {
                document.getElementById('requestLogDetails').innerHTML =
                    '<tr><td colspan="2" class="text-danger"><i class="fas fa-exclamation-triangle"></i> Error parsing request log data</td></tr>';
                document.getElementById('requestLogJson').textContent = 'Error parsing JSON data';
            }
        });
    }

    // Download JSON button handler
    document.getElementById('downloadJsonBtn')?.addEventListener('click', function() {
        try {
            var username = document.getElementById('modalUsername').textContent;
            var requestLog = JSON.parse(currentRequestLogJson);
            var jsonStr = JSON.stringify(requestLog, null, 2);

            // Create blob and download
            var blob = new Blob([jsonStr], { type: 'application/json' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'request_log_' + username.replace(/[^a-z0-9]/gi, '_') + '_' + Date.now() + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (e) {
            alert('Error downloading JSON: ' + e.message);
        }
    });

    // Copy to clipboard button handler
    document.getElementById('copyJsonBtn')?.addEventListener('click', function() {
        try {
            var jsonText = document.getElementById('requestLogJson').textContent;
            navigator.clipboard.writeText(jsonText).then(function() {
                // Show success feedback
                var btn = document.getElementById('copyJsonBtn');
                var originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.classList.remove('btn-secondary');
                btn.classList.add('btn-success');

                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-secondary');
                }, 2000);
            }).catch(function(err) {
                alert('Error copying to clipboard: ' + err);
            });
        } catch (e) {
            alert('Error: ' + e.message);
        }
    });
});
</script>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
