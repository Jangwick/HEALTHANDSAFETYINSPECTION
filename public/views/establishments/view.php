<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$establishmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']);

if (!$establishmentId) {
    header('Location: /views/establishments/list.php');
    exit;
}

try {
    $db = Database::getConnection();
    
    // Get establishment details
    $stmt = $db->prepare("
        SELECT e.*,
               CONCAT(u.first_name, ' ', u.last_name) as created_by_name
        FROM establishments e
        LEFT JOIN users u ON e.created_by = u.user_id
        WHERE e.establishment_id = ?
    ");
    $stmt->execute([$establishmentId]);
    $establishment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$establishment) {
        header('Location: /views/establishments/list.php');
        exit;
    }
    
    // Get inspection history
    $inspections = $db->prepare("
        SELECT i.*,
               CONCAT(u.first_name, ' ', u.last_name) as inspector_name
        FROM inspections i
        LEFT JOIN users u ON i.assigned_to = u.user_id
        WHERE i.establishment_id = ?
        ORDER BY i.scheduled_date DESC
        LIMIT 10
    ");
    $inspections->execute([$establishmentId]);
    $inspectionHistory = $inspections->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active certificates
    $certificates = $db->prepare("
        SELECT c.*,
               CONCAT(u.first_name, ' ', u.last_name) as issued_by_name
        FROM certificates c
        LEFT JOIN users u ON c.issued_by = u.user_id
        WHERE c.establishment_id = ?
        ORDER BY c.issue_date DESC
    ");
    $certificates->execute([$establishmentId]);
    $certificateList = $certificates->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent violations
    $violations = $db->prepare("
        SELECT v.*, i.reference_number as inspection_reference
        FROM violations v
        LEFT JOIN inspections i ON v.inspection_id = i.inspection_id
        WHERE i.establishment_id = ?
        ORDER BY v.identified_date DESC
        LIMIT 5
    ");
    $violations->execute([$establishmentId]);
    $violationList = $violations->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($establishment['name']) ?> - Health & Safety Inspection System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .status-badge { padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; font-size: 0.95rem; }
        .status-compliant { background-color: #d1fae5; color: #065f46; }
        .status-non_compliant { background-color: #fee2e2; color: #991b1b; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-suspended { background-color: #dbeafe; color: #1e40af; }
        .info-row { padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #6b7280; }
        .establishment-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem; border-radius: 8px 8px 0 0; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-shield-check"></i> Health & Safety Inspection
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['first_name'] ?? '') ?> <?= htmlspecialchars($_SESSION['last_name'] ?? '') ?>
                </span>
                <a class="nav-link text-white" href="/views/auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> Establishment created successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Establishment Header -->
        <div class="card">
            <div class="establishment-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="mb-2"><i class="bi bi-building"></i> <?= htmlspecialchars($establishment['name']) ?></h2>
                        <p class="mb-3">
                            <span class="badge bg-light text-dark me-2">
                                <i class="bi bi-tag"></i> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $establishment['type']))) ?>
                            </span>
                            <span class="status-badge status-<?= htmlspecialchars($establishment['compliance_status']) ?>">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $establishment['compliance_status']))) ?>
                            </span>
                        </p>
                        <p class="mb-0">
                            <i class="bi bi-geo-alt-fill"></i> 
                            <?= htmlspecialchars($establishment['address_street']) ?>, 
                            <?= htmlspecialchars($establishment['address_barangay']) ?>, 
                            <?= htmlspecialchars($establishment['address_city']) ?>
                        </p>
                    </div>
                    <div>
                        <a href="/views/establishments/list.php" class="btn btn-light me-2">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <a href="/views/establishments/edit.php?id=<?= $establishment['establishment_id'] ?>" class="btn btn-light me-2">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <a href="/views/inspections/create.php?establishment_id=<?= $establishment['establishment_id'] ?>" class="btn btn-success">
                            <i class="bi bi-clipboard-check"></i> New Inspection
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <!-- Establishment Details -->
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="bi bi-info-circle"></i> Establishment Details</h5>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Business Permit:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['business_permit_number'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                        
                        <?php if ($establishment['permit_issue_date']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Permit Issued:</div>
                                <div class="col-8"><?= date('F d, Y', strtotime($establishment['permit_issue_date'])) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($establishment['permit_expiry_date']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Permit Expires:</div>
                                <div class="col-8">
                                    <?= date('F d, Y', strtotime($establishment['permit_expiry_date'])) ?>
                                    <?php 
                                    $expiry = new DateTime($establishment['permit_expiry_date']);
                                    $now = new DateTime();
                                    $daysUntilExpiry = $now->diff($expiry)->days;
                                    if ($expiry < $now): ?>
                                    <span class="badge bg-danger">Expired</span>
                                    <?php elseif ($daysUntilExpiry <= 30): ?>
                                    <span class="badge bg-warning">Expires in <?= $daysUntilExpiry ?> days</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($establishment['capacity']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Capacity:</div>
                                <div class="col-8"><?= number_format($establishment['capacity']) ?> persons</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($establishment['operating_hours']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Operating Hours:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['operating_hours']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($establishment['description']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Description:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['description']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Registered By:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['created_by_name']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Registered On:</div>
                                <div class="col-8"><?= date('F d, Y g:i A', strtotime($establishment['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Owner Details -->
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="bi bi-person"></i> Owner Information</h5>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Name:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['owner_name']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Phone:</div>
                                <div class="col-8">
                                    <a href="tel:<?= htmlspecialchars($establishment['owner_phone']) ?>">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($establishment['owner_phone']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($establishment['owner_email']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Email:</div>
                                <div class="col-8">
                                    <a href="mailto:<?= htmlspecialchars($establishment['owner_email']) ?>">
                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($establishment['owner_email']) ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <h5 class="mb-3 mt-4"><i class="bi bi-geo-alt"></i> Complete Address</h5>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Street:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['address_street']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Barangay:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['address_barangay']) ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">City/Municipality:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['address_city']) ?></div>
                            </div>
                        </div>
                        
                        <?php if ($establishment['address_province']): ?>
                        <div class="info-row">
                            <div class="row">
                                <div class="col-4 info-label">Province:</div>
                                <div class="col-8"><?= htmlspecialchars($establishment['address_province']) ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Inspection History -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-clipboard-data"></i> Inspection History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($inspectionHistory)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No inspections recorded yet.
                            <a href="/views/inspections/create.php?establishment_id=<?= $establishment['establishment_id'] ?>">Schedule an inspection</a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Reference</th>
                                        <th>Type</th>
                                        <th>Scheduled Date</th>
                                        <th>Inspector</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inspectionHistory as $insp): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($insp['reference_number']) ?></strong></td>
                                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $insp['inspection_type']))) ?></td>
                                        <td><?= date('M d, Y', strtotime($insp['scheduled_date'])) ?></td>
                                        <td><?= htmlspecialchars($insp['inspector_name'] ?? 'Unassigned') ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'scheduled' => 'primary',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'secondary'
                                            ];
                                            $color = $statusColors[$insp['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>">
                                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $insp['status']))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="/views/inspections/view.php?id=<?= $insp['inspection_id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Violations -->
                <?php if (!empty($violationList)): ?>
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Recent Violations</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($violationList as $violation): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($violation['violation_type']) ?></h6>
                                        <p class="mb-1 text-muted small"><?= htmlspecialchars($violation['description']) ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?= date('M d, Y', strtotime($violation['identified_date'])) ?>
                                            | Inspection: <?= htmlspecialchars($violation['inspection_reference']) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?= $violation['severity'] === 'critical' ? 'danger' : ($violation['severity'] === 'major' ? 'warning' : 'info') ?>">
                                        <?= htmlspecialchars(ucfirst($violation['severity'])) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Certificates -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-award"></i> Certificates</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($certificateList)): ?>
                        <p class="text-muted mb-0"><i class="bi bi-info-circle"></i> No certificates issued yet.</p>
                        <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($certificateList as $cert): ?>
                            <a href="/views/certificates/view.php?id=<?= $cert['certificate_id'] ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($cert['certificate_number']) ?></h6>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['certificate_type']))) ?>
                                        </small>
                                    </div>
                                    <?php
                                    $certStatusColors = [
                                        'active' => 'success',
                                        'expired' => 'danger',
                                        'revoked' => 'dark',
                                        'suspended' => 'warning'
                                    ];
                                    $certColor = $certStatusColors[$cert['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $certColor ?>"><?= ucfirst($cert['status']) ?></span>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Valid until: <?= date('M d, Y', strtotime($cert['expiry_date'])) ?>
                                </small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Total Inspections:</span>
                            <strong><?= count($inspectionHistory) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Active Certificates:</span>
                            <strong><?= count(array_filter($certificateList, fn($c) => $c['status'] === 'active')) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-0">
                            <span class="text-muted">Violations:</span>
                            <strong class="text-danger"><?= count($violationList) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
