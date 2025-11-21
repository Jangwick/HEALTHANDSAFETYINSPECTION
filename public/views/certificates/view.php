<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$certificateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$certificateId) {
    header('Location: /views/certificates/list.php');
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get certificate details
    $stmt = $db->prepare("
        SELECT c.*,
               e.name as establishment_name,
               e.type as establishment_type,
               e.address_street,
               e.address_barangay,
               e.address_city,
               e.owner_name,
               e.owner_contact,
               e.owner_email,
               i.reference_number as inspection_reference,
               i.inspection_type,
               i.actual_start_datetime,
               i.actual_end_datetime,
               CONCAT(u.first_name, ' ', u.last_name) as issued_by_name,
               u.email as issued_by_email
        FROM certificates c
        LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
        LEFT JOIN inspections i ON c.inspection_id = i.inspection_id
        LEFT JOIN users u ON c.issued_by = u.user_id
        WHERE c.certificate_id = ?
    ");
    $stmt->execute([$certificateId]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$certificate) {
        header('Location: /views/certificates/list.php');
        exit;
    }
    
    // Calculate days until expiry
    $daysUntilExpiry = (strtotime($certificate['expiry_date']) - time()) / (60 * 60 * 24);
    $isExpiringSoon = $daysUntilExpiry <= 30 && $daysUntilExpiry > 0;
    $isExpired = $daysUntilExpiry <= 0;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while loading the certificate.");
}

function getStatusBadge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'expired' => '<span class="badge bg-danger">Expired</span>',
        'revoked' => '<span class="badge bg-secondary">Revoked</span>',
        'suspended' => '<span class="badge bg-warning">Suspended</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate <?= htmlspecialchars($certificate['certificate_number']) ?> - Health & Safety Inspection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .certificate-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 8px 8px 0 0;
        }
        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
        }
        .qr-code-box {
            border: 2px solid #dee2e6;
            padding: 1rem;
            text-align: center;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-shield-check"></i> Health & Safety Inspection
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/views/inspections/list.php">Inspections</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/views/certificates/list.php">Certificates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/views/auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <!-- Action Buttons -->
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="/views/certificates/list.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>
            <div class="col-md-6 text-end">
                <a href="/views/certificates/certificate.php?id=<?= $certificate['certificate_id'] ?>" class="btn btn-success" target="_blank">
                    <i class="bi bi-file-earmark-text"></i> View Certificate
                </a>
                <a href="/views/certificates/certificate.php?id=<?= $certificate['certificate_id'] ?>&download=1" class="btn btn-primary">
                    <i class="bi bi-download"></i> Download PDF
                </a>
                <?php if ($certificate['status'] === 'active'): ?>
                <button onclick="revokeCertificate()" class="btn btn-danger">
                    <i class="bi bi-x-circle"></i> Revoke
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Main Info -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="certificate-header">
                        <h3 class="mb-2">
                            <i class="bi bi-award"></i> 
                            <?= htmlspecialchars($certificate['certificate_number']) ?>
                        </h3>
                        <p class="mb-0">
                            <?= getStatusBadge($certificate['status']) ?>
                            <?php if ($isExpiringSoon): ?>
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="bi bi-exclamation-triangle"></i> Expiring Soon
                                </span>
                            <?php endif; ?>
                            <?php if ($isExpired): ?>
                                <span class="badge bg-danger ms-2">
                                    <i class="bi bi-exclamation-octagon"></i> Expired
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <h5>Certificate Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Type:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $certificate['certificate_type']))) ?></p>
                                <p><strong>Issue Date:</strong> <?= date('F d, Y', strtotime($certificate['issue_date'])) ?></p>
                                <p><strong>Expiry Date:</strong> <?= date('F d, Y', strtotime($certificate['expiry_date'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Issued By:</strong> <?= htmlspecialchars($certificate['issued_by_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($certificate['issued_by_email']) ?></p>
                                <p><strong>Created:</strong> <?= date('M d, Y h:i A', strtotime($certificate['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php if ($certificate['remarks']): ?>
                        <hr>
                        <p><strong>Remarks:</strong></p>
                        <p><?= nl2br(htmlspecialchars($certificate['remarks'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Establishment Details -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-building"></i> Establishment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?= htmlspecialchars($certificate['establishment_name']) ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $certificate['establishment_type']))) ?></p>
                                <p><strong>Owner:</strong> <?= htmlspecialchars($certificate['owner_name']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Address:</strong> 
                                    <?= htmlspecialchars($certificate['address_street']) ?>, 
                                    <?= htmlspecialchars($certificate['address_barangay']) ?>, 
                                    <?= htmlspecialchars($certificate['address_city']) ?>
                                </p>
                                <p><strong>Contact:</strong> <?= htmlspecialchars($certificate['owner_contact']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($certificate['owner_email']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inspection Reference -->
                <?php if ($certificate['inspection_id']): ?>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Based on Inspection</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Reference Number:</strong> <?= htmlspecialchars($certificate['inspection_reference']) ?></p>
                        <p><strong>Inspection Type:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $certificate['inspection_type']))) ?></p>
                        <p><strong>Conducted:</strong> 
                            <?= date('M d, Y', strtotime($certificate['actual_start_datetime'])) ?> - 
                            <?= date('M d, Y', strtotime($certificate['actual_end_datetime'])) ?>
                        </p>
                        <a href="/views/inspections/view.php?id=<?= $certificate['inspection_id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View Inspection Details
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- QR Code -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-qr-code"></i> QR Code</h5>
                    </div>
                    <div class="card-body">
                        <div class="qr-code-box">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode('CERT:' . $certificate['certificate_number']) ?>" 
                                 alt="QR Code" 
                                 class="img-fluid">
                            <p class="text-muted small mt-2 mb-0">Scan to verify</p>
                        </div>
                    </div>
                </div>

                <!-- Validity Status -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Validity</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($isExpired): ?>
                            <div class="alert alert-danger">
                                <strong>This certificate has expired!</strong><br>
                                Expired <?= abs(round($daysUntilExpiry)) ?> days ago
                            </div>
                        <?php elseif ($isExpiringSoon): ?>
                            <div class="alert alert-warning">
                                <strong>Expiring soon!</strong><br>
                                <?= round($daysUntilExpiry) ?> days remaining
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <strong>Valid</strong><br>
                                <?= round($daysUntilExpiry) ?> days remaining
                            </div>
                        <?php endif; ?>

                        <div class="progress" style="height: 25px;">
                            <?php
                            $totalDays = (strtotime($certificate['expiry_date']) - strtotime($certificate['issue_date'])) / (60 * 60 * 24);
                            $daysElapsed = (time() - strtotime($certificate['issue_date'])) / (60 * 60 * 24);
                            $percentage = min(100, max(0, ($daysElapsed / $totalDays) * 100));
                            $progressClass = $percentage > 75 ? 'danger' : ($percentage > 50 ? 'warning' : 'success');
                            ?>
                            <div class="progress-bar bg-<?= $progressClass ?>" 
                                 role="progressbar" 
                                 style="width: <?= $percentage ?>%"
                                 aria-valuenow="<?= $percentage ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?= round($percentage) ?>%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-gear"></i> Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/views/certificates/verify.php?number=<?= urlencode($certificate['certificate_number']) ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="bi bi-search"></i> Verify Certificate
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-secondary">
                                <i class="bi bi-printer"></i> Print Details
                            </button>
                            <?php if ($certificate['status'] === 'active' && $isExpiringSoon): ?>
                            <a href="/views/certificates/renew.php?id=<?= $certificate['certificate_id'] ?>" 
                               class="btn btn-outline-success">
                                <i class="bi bi-arrow-clockwise"></i> Renew Certificate
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function revokeCertificate() {
            const reason = prompt('Please enter the reason for revocation:');
            if (reason && confirm('Are you sure you want to revoke this certificate? This action cannot be undone.')) {
                fetch('/views/certificates/revoke.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        certificate_id: <?= $certificate['certificate_id'] ?>,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Certificate revoked successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>
