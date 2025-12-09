<?php require APP_PATH . '/views/layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-custom">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Reports Hub
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Daily Authentication Report -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-primary">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-calendar-day text-primary"></i> Daily Authentication Summary
                                    </h5>
                                    <p class="card-text text-muted">View authentication attempts, success rates, and user activity for a specific day.</p>
                                    <form method="GET" action="">
                                        <input type="hidden" name="page" value="reports">
                                        <input type="hidden" name="action" value="daily-auth">
                                        <div class="input-group">
                                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-arrow-right"></i> View Report
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Usage Report -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-calendar-alt text-success"></i> Monthly Usage Summary
                                    </h5>
                                    <p class="card-text text-muted">View total sessions, data usage, and unique users for a specific month.</p>
                                    <form method="GET" action="">
                                        <input type="hidden" name="page" value="reports">
                                        <input type="hidden" name="action" value="monthly-usage">
                                        <div class="input-group">
                                            <input type="month" name="month" class="form-control" value="<?= date('Y-m') ?>" required>
                                            <button class="btn btn-success" type="submit">
                                                <i class="fas fa-arrow-right"></i> View Report
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Logins Report -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-danger">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-exclamation-triangle text-danger"></i> Failed Logins Report
                                    </h5>
                                    <p class="card-text text-muted">Identify users with multiple failed login attempts and error patterns.</p>
                                    <form method="GET" action="">
                                        <input type="hidden" name="page" value="reports">
                                        <input type="hidden" name="action" value="failed-logins">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="date" name="from_date" class="form-control" value="<?= date('Y-m-d', strtotime('-7 days')) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" name="to_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                            </div>
                                            <div class="col-md-12">
                                                <button class="btn btn-danger w-100" type="submit">
                                                    <i class="fas fa-arrow-right"></i> View Report
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- System Health Report -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-warning">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-heartbeat text-warning"></i> System Health Report
                                    </h5>
                                    <p class="card-text text-muted">View system status, database performance, and resource usage metrics.</p>
                                    <form method="GET" action="">
                                        <input type="hidden" name="page" value="reports">
                                        <input type="hidden" name="action" value="system-health">
                                        <button class="btn btn-warning w-100" type="submit">
                                            <i class="fas fa-arrow-right"></i> View Report
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- User Type Distribution Report -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-info">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-user-tag text-info"></i> User Type Distribution Report
                                    </h5>
                                    <p class="card-text text-muted">Analyze authentication patterns and user distribution by type (Student-MBA, Staff, etc.).</p>
                                    <form method="GET" action="">
                                        <input type="hidden" name="page" value="reports">
                                        <input type="hidden" name="action" value="user-type-distribution">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="date" name="from_date" class="form-control" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="date" name="to_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                            </div>
                                            <div class="col-md-12">
                                                <button class="btn btn-info w-100" type="submit">
                                                    <i class="fas fa-arrow-right"></i> View Report
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . '/views/layouts/footer.php'; ?>
