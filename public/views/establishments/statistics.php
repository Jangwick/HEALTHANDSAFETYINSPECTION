<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $db = Database::getConnection();
    
    // Overall statistics
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_establishments,
            SUM(CASE WHEN compliance_status = 'compliant' THEN 1 ELSE 0 END) as compliant,
            SUM(CASE WHEN compliance_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant,
            SUM(CASE WHEN compliance_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN compliance_status = 'suspended' THEN 1 ELSE 0 END) as suspended
        FROM establishments
    ")->fetch(PDO::FETCH_ASSOC) ?: [
        'total_establishments' => 0,
        'compliant' => 0,
        'non_compliant' => 0,
        'pending' => 0,
        'suspended' => 0
    ];
    
    // Establishments by type
    $byType = $db->query("
        SELECT type, COUNT(*) as count
        FROM establishments
        GROUP BY type
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Establishments by barangay (top 10)
    $byBarangay = $db->query("
        SELECT address_barangay, COUNT(*) as count
        FROM establishments
        WHERE address_barangay IS NOT NULL
        GROUP BY address_barangay
        ORDER BY count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent establishments (last 30 days)
    $recentCount = $db->query("
        SELECT COUNT(*) 
        FROM establishments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn();
    
    // Expiring permits (next 30 days)
    $expiringPermits = $db->query("
        SELECT e.*, 
               DATEDIFF(permit_expiry_date, NOW()) as days_until_expiry
        FROM establishments e
        WHERE permit_expiry_date IS NOT NULL
        AND permit_expiry_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
        ORDER BY permit_expiry_date ASC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Recently inspected
    $recentlyInspected = $db->query("
        SELECT e.establishment_id, e.name, e.type, e.compliance_status,
               i.scheduled_date, i.status as inspection_status,
               CONCAT(u.first_name, ' ', u.last_name) as inspector_name
        FROM establishments e
        INNER JOIN inspections i ON e.establishment_id = i.establishment_id
        LEFT JOIN users u ON i.assigned_to = u.user_id
        WHERE i.scheduled_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY i.scheduled_date DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Never inspected
    $neverInspected = $db->query("
        SELECT e.*
        FROM establishments e
        LEFT JOIN inspections i ON e.establishment_id = i.establishment_id
        WHERE i.inspection_id IS NULL
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Active certificates
    $activeCertificates = $db->query("
        SELECT COUNT(*) 
        FROM certificates 
        WHERE status = 'active'
    ")->fetchColumn();
    
    // Compliance trend (last 6 months)
    $complianceTrend = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN compliance_status = 'compliant' THEN 1 ELSE 0 END) as compliant,
            SUM(CASE WHEN compliance_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant
        FROM establishments
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching statistics.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establishment Statistics - Health & Safety Inspection System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px; }
        .stat-number { font-size: 2.5rem; font-weight: bold; margin: 0; }
        .stat-label { font-size: 0.9rem; opacity: 0.9; }
        .chart-container { position: relative; height: 300px; }
        .progress-bar-custom { height: 25px; font-weight: 600; }
        .alert-warning-custom { background-color: #fef3c7; border-left: 4px solid #f59e0b; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-slate-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php 
            $activePage = 'statistics';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden text-sm">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 z-10">
                <div class="px-6 h-16 flex items-center justify-between">
                    <div class="flex items-center">
                        <button class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h2 class="text-xl font-bold text-slate-800 ml-2 md:ml-0">Compliance Analytics</h2>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <span class="text-sm font-medium text-slate-600">
                            <i class="fas fa-calendar-alt mr-2"></i> <?php echo  date('F d, Y') ?>
                        </span>
                        <div class="h-8 w-px bg-slate-200 mx-2"></div>
                        <div class="flex items-center">
                             <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                <?php echo  strtoupper($_SESSION['role'] ?? 'OFFICER') ?>
                             </span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-6 md:p-8">
                <div class="container-fluid">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h3 mb-1"><i class="bi bi-graph-up"></i> Statistics & Performance</h2>
                            <p class="text-muted mb-0">Live monitoring of establishment compliance</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/dashboard" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i> Home
                            </a>
                            <a href="/establishments" class="btn btn-primary">
                                <i class="bi bi-list"></i> View All
                            </a>
                        </div>
                    </div>

        <!-- Main Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Total Establishments</div>
                    <div class="stat-number"><?php echo  number_format((float)($stats['total_establishments'] ?? 0)) ?></div>
                    <small><i class="bi bi-plus-circle"></i> <?php echo  $recentCount ?> added this month</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                    <div class="card-body">
                        <div class="stat-label">Compliant</div>
                        <div class="stat-number"><?php echo  number_format((float)($stats['compliant'] ?? 0)) ?></div>
                        <small><?php echo  $stats['total_establishments'] > 0 ? round($stats['compliant']/$stats['total_establishments']*100, 1) : 0 ?>% of total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none;">
                    <div class="card-body">
                        <div class="stat-label">Non-Compliant</div>
                        <div class="stat-number"><?php echo  number_format((float)($stats['non_compliant'] ?? 0)) ?></div>
                        <small><?php echo  $stats['total_establishments'] > 0 ? round($stats['non_compliant']/$stats['total_establishments']*100, 1) : 0 ?>% of total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none;">
                    <div class="card-body">
                        <div class="stat-label">Pending Review</div>
                        <div class="stat-number"><?php echo  number_format((float)($stats['pending'] ?? 0)) ?></div>
                        <small><?php echo  $stats['suspended'] ?> suspended</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Establishments by Type -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Establishments by Type</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="typeChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Distribution -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Compliance Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-success">Compliant</span>
                                <strong><?php echo  $stats['compliant'] ?></strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success progress-bar-custom" 
                                     style="width: <?php echo  $stats['total_establishments'] > 0 ? ($stats['compliant']/$stats['total_establishments']*100) : 0 ?>%">
                                    <?php echo  $stats['total_establishments'] > 0 ? round($stats['compliant']/$stats['total_establishments']*100, 1) : 0 ?>%
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-danger">Non-Compliant</span>
                                <strong><?php echo  $stats['non_compliant'] ?></strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger progress-bar-custom" 
                                     style="width: <?php echo  $stats['total_establishments'] > 0 ? ($stats['non_compliant']/$stats['total_establishments']*100) : 0 ?>%">
                                    <?php echo  $stats['total_establishments'] > 0 ? round($stats['non_compliant']/$stats['total_establishments']*100, 1) : 0 ?>%
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-warning">Pending</span>
                                <strong><?php echo  $stats['pending'] ?></strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning progress-bar-custom" 
                                     style="width: <?php echo  $stats['total_establishments'] > 0 ? ($stats['pending']/$stats['total_establishments']*100) : 0 ?>%">
                                    <?php echo  $stats['total_establishments'] > 0 ? round($stats['pending']/$stats['total_establishments']*100, 1) : 0 ?>%
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-secondary">Suspended</span>
                                <strong><?php echo  $stats['suspended'] ?></strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-secondary progress-bar-custom" 
                                     style="width: <?php echo  $stats['total_establishments'] > 0 ? ($stats['suspended']/$stats['total_establishments']*100) : 0 ?>%">
                                    <?php echo  $stats['total_establishments'] > 0 ? round($stats['suspended']/$stats['total_establishments']*100, 1) : 0 ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between">
                                <span>Active Certificates:</span>
                                <strong class="text-success"><?php echo  number_format((float)($activeCertificates ?? 0)) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Top Barangays -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Top 10 Barangays</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Barangay</th>
                                        <th class="text-end">Establishments</th>
                                        <th class="text-end">% of Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($byBarangay as $brgy): ?>
                                    <tr>
                                        <td><?php echo  htmlspecialchars($brgy['address_barangay']) ?></td>
                                        <td class="text-end"><strong><?php echo  $brgy['count'] ?></strong></td>
                                        <td class="text-end">
                                            <span class="badge bg-primary">
                                                <?php echo  round($brgy['count']/$stats['total_establishments']*100, 1) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expiring Permits -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> Expiring Permits (30 Days)</h5>
                        <span class="badge bg-warning"><?php echo  count($expiringPermits) ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($expiringPermits)): ?>
                        <p class="text-muted mb-0"><i class="bi bi-check-circle text-success"></i> No permits expiring in the next 30 days</p>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($expiringPermits as $est): ?>
                            <a href="/views/establishments/view.php?id=<?php echo  $est['establishment_id'] ?>" 
                               class="list-group-item list-group-item-action alert-warning-custom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo  htmlspecialchars($est['name']) ?></h6>
                                        <small class="text-muted">
                                            Permit: <?php echo  htmlspecialchars($est['business_permit_number']) ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-warning"><?php echo  $est['days_until_expiry'] ?> days</span><br>
                                        <small class="text-muted"><?php echo  date('M d, Y', strtotime($est['permit_expiry_date'])) ?></small>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recently Inspected -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recently Inspected (30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentlyInspected)): ?>
                        <p class="text-muted mb-0">No inspections in the last 30 days</p>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentlyInspected as $est): ?>
                            <a href="/views/establishments/view.php?id=<?php echo  $est['establishment_id'] ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo  htmlspecialchars($est['name']) ?></h6>
                                        <small class="text-muted">
                                            By: <?php echo  htmlspecialchars($est['inspector_name'] ?? 'Unassigned') ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted"><?php echo  date('M d, Y', strtotime($est['scheduled_date'])) ?></small><br>
                                        <span class="badge bg-info"><?php echo  ucfirst($est['inspection_status']) ?></span>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Never Inspected -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-exclamation-circle text-danger"></i> Never Inspected</h5>
                        <span class="badge bg-danger"><?php echo  count($neverInspected) ?>+</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($neverInspected)): ?>
                        <p class="text-muted mb-0"><i class="bi bi-check-circle text-success"></i> All establishments have been inspected</p>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($neverInspected as $est): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo  htmlspecialchars($est['name']) ?></h6>
                                    <small class="text-muted">
                                        <?php echo  htmlspecialchars(ucwords(str_replace('_', ' ', $est['type']))) ?> - 
                                        <?php echo  htmlspecialchars($est['address_barangay']) ?>
                                    </small>
                                </div>
                                <a href="/views/inspections/create.php?establishment_id=<?php echo  $est['establishment_id'] ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus"></i> Schedule
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    async function handleLogout() {
        if (confirm('Are you sure you want to sign out?')) {
            window.location.href = '/views/auth/logout.php';
        }
    }
    
    // Original chart scripts
        // Type Distribution Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(fn($t) => '"'.ucfirst($t['type']).'"', $byType)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(fn($t) => $t['count'], $byType)); ?>],
                    backgroundColor: [
                        '#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444',
                        '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
</body>
</html>
