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
                                    <?php if (!empty($log['error_type'])): ?>
                                        <span class="badge bg-warning text-dark">
                                            <?= Utils::e(ucwords(str_replace('_', ' ', $log['error_type']))) ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small title="<?= Utils::e($log['reply_message'] ?? '') ?>">
                                        <?= Utils::e(substr($log['reply_message'] ?? '-', 0, 80)) ?>
                                        <?php if (strlen($log['reply_message'] ?? '') > 80): ?>...<?php endif; ?>
                                    </small>
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

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
