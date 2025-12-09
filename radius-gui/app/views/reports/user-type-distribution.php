<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>
                <i class="fas fa-user-tag"></i> User Type Distribution Report
            </h2>
            <p class="text-muted">Analyze authentication patterns and user distribution by type</p>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="page" value="reports">
                <input type="hidden" name="action" value="user-type-distribution">

                <div class="col-md-4">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="from_date" name="from_date"
                           value="<?= Utils::e($fromDate) ?>" required>
                </div>

                <div class="col-md-4">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="to_date" name="to_date"
                           value="<?= Utils::e($toDate) ?>" required>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Generate Report
                    </button>
                    <!-- PDF Export - Coming Soon -->
                    <!-- <a href="?page=reports&action=user-type-distribution&from_date=<?= urlencode($fromDate) ?>&to_date=<?= urlencode($toDate) ?>&export=pdf"
                       class="btn btn-secondary ms-2">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a> -->
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($userTypeStats)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            No user type data available for the selected date range. User type logging captures data from successful authentications only.
        </div>
    <?php else: ?>
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total User Types</h6>
                        <h2><?= count($userTypeStats) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Authentications</h6>
                        <h2><?= number_format($totalAuths) ?></h2>
                        <small><?= Utils::formatDate($fromDate) ?> - <?= Utils::formatDate($toDate) ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Unique Users</h6>
                        <h2><?= number_format($totalUsers) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Avg Auths/User</h6>
                        <h2><?= $totalUsers > 0 ? number_format($totalAuths / $totalUsers, 1) : '0' ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Type Distribution Table -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> User Type Distribution</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User Type</th>
                                <th class="text-end">Authentications</th>
                                <th class="text-end">Unique Users</th>
                                <th class="text-end">Active Days</th>
                                <th class="text-end">Avg Auths/User</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userTypeStats as $stat): ?>
                                <?php
                                $percentage = $totalAuths > 0 ? ($stat['auth_count'] / $totalAuths * 100) : 0;
                                $avgAuthsPerUser = $stat['unique_users'] > 0 ? ($stat['auth_count'] / $stat['unique_users']) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">
                                            <i class="fas fa-user-tag"></i> <?= Utils::e($stat['user_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong><?= number_format($stat['auth_count']) ?></strong>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($stat['unique_users']) ?>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($stat['active_days']) ?>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($avgAuthsPerUser, 1) ?>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 25px;">
                                            <div class="progress-bar bg-primary" role="progressbar"
                                                 style="width: <?= $percentage ?>%"
                                                 aria-valuenow="<?= $percentage ?>"
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?= number_format($percentage, 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end"><?= number_format($totalAuths) ?></th>
                                <th class="text-end"><?= number_format($totalUsers) ?></th>
                                <th class="text-end">-</th>
                                <th class="text-end">-</th>
                                <th>100%</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Type & VLAN Correlation -->
        <?php if (!empty($userTypeVlan)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-network-wired"></i> User Type & VLAN Correlation</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>User Type</th>
                                <th>VLAN</th>
                                <th class="text-end">Authentications</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $currentType = '';
                            foreach ($userTypeVlan as $item):
                                $isNewType = $item['user_type'] !== $currentType;
                                $currentType = $item['user_type'];
                            ?>
                                <tr <?= $isNewType ? 'class="table-active"' : '' ?>>
                                    <td>
                                        <?php if ($isNewType): ?>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-user-tag"></i> <?= Utils::e($item['user_type']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-network-wired"></i> <?= Utils::e($item['vlan']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($item['count']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Failed Authentications by User Type -->
        <?php if (!empty($failedByType)): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Failed Authentications by User Type (Inferred)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    <i class="fas fa-info-circle"></i> User types for failed authentications are inferred from username patterns since failed auths don't receive type assignments.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Inferred User Type</th>
                                <th>Error Type</th>
                                <th class="text-end">Failure Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($failedByType as $item): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user-tag"></i> <?= Utils::e($item['inferred_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($item['error_type'])): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?= Utils::e(ucwords(str_replace('_', ' ', $item['error_type']))) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-danger"><?= number_format($item['failure_count']) ?></strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Daily Breakdown -->
        <?php if (!empty($dailyBreakdown)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Daily Breakdown by User Type</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User Type</th>
                                <th class="text-end">Authentications</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $currentDate = '';
                            foreach ($dailyBreakdown as $item):
                                $isNewDate = $item['date'] !== $currentDate;
                                $currentDate = $item['date'];
                            ?>
                                <tr <?= $isNewDate ? 'class="table-active"' : '' ?>>
                                    <td>
                                        <?php if ($isNewDate): ?>
                                            <strong><?= Utils::formatDate($item['date']) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= Utils::e($item['user_type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($item['auth_count']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
