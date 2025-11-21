<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $db = Database::getConnection();
    
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Filters
    $status = $_GET['status'] ?? '';
    $certificateType = $_GET['certificate_type'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "c.status = ?";
        $params[] = $status;
    }
    
    if ($certificateType) {
        $where[] = "c.certificate_type = ?";
        $params[] = $certificateType;
    }
    
    if ($search) {
        $where[] = "(e.name LIKE ? OR c.certificate_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM certificates c LEFT JOIN establishments e ON c.establishment_id = e.establishment_id $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalCertificates = $stmt->fetchColumn();
    $totalPages = ceil($totalCertificates / $perPage);
    
    // Get certificates
    $sql = "
        SELECT c.*, 
               e.name as establishment_name,
               e.type as establishment_type,
               e.address_street,
               e.address_barangay,
               e.address_city,
               i.reference_number as inspection_reference,
               CONCAT(u.first_name, ' ', u.last_name) as issued_by_name
        FROM certificates c
        LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
        LEFT JOIN inspections i ON c.inspection_id = i.inspection_id
        LEFT JOIN users u ON c.issued_by = u.user_id
        $whereClause
        ORDER BY c.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while loading certificates.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates - Health & Safety Inspection System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-expired { background: #f8d7da; color: #721c24; }
        .status-revoked { background: #e2e3e5; color: #383d41; }
        .status-suspended { background: #fff3cd; color: #856404; }
        
        .certificate-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .certificate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-shield-check"></i> Health & Safety Inspection
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
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
                        <span class="nav-link"><?= htmlspecialchars($_SESSION['first_name']) ?> <?= htmlspecialchars($_SESSION['last_name']) ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/views/auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="bi bi-award"></i> Certificates</h2>
                <p class="text-muted">Manage health and safety compliance certificates</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="/views/certificates/issue.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Issue New Certificate
                </a>
                <a href="/views/certificates/verify.php" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Verify Certificate
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                            <option value="revoked" <?= $status === 'revoked' ? 'selected' : '' ?>>Revoked</option>
                            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Certificate Type</label>
                        <select name="certificate_type" class="form-select" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="food_safety" <?= $certificateType === 'food_safety' ? 'selected' : '' ?>>Food Safety</option>
                            <option value="building_safety" <?= $certificateType === 'building_safety' ? 'selected' : '' ?>>Building Safety</option>
                            <option value="fire_safety" <?= $certificateType === 'fire_safety' ? 'selected' : '' ?>>Fire Safety</option>
                            <option value="sanitation" <?= $certificateType === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                            <option value="occupational_health" <?= $certificateType === 'occupational_health' ? 'selected' : '' ?>>Occupational Health</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Certificate number or establishment name..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Certificates Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($certificates)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-award" style="font-size: 4rem; color: #dee2e6;"></i>
                        <p class="text-muted mt-3">No certificates found</p>
                        <a href="/views/certificates/issue.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Issue First Certificate
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Certificate #</th>
                                    <th>Establishment</th>
                                    <th>Type</th>
                                    <th>Issue Date</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $cert): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($cert['certificate_number']) ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($cert['establishment_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['establishment_type']))) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['certificate_type']))) ?></td>
                                    <td><?= date('M d, Y', strtotime($cert['issue_date'])) ?></td>
                                    <td>
                                        <?= date('M d, Y', strtotime($cert['expiry_date'])) ?>
                                        <?php
                                        $daysUntilExpiry = (strtotime($cert['expiry_date']) - time()) / (60 * 60 * 24);
                                        if ($daysUntilExpiry <= 30 && $daysUntilExpiry > 0):
                                        ?>
                                            <br><small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Expires soon</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $cert['status'] ?>">
                                            <?= ucfirst($cert['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/views/certificates/view.php?id=<?= $cert['certificate_id'] ?>" class="btn btn-outline-primary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/views/certificates/certificate.php?id=<?= $cert['certificate_id'] ?>" class="btn btn-outline-success" target="_blank" title="View Certificate">
                                                <i class="bi bi-file-earmark-text"></i>
                                            </a>
                                            <a href="/views/certificates/certificate.php?id=<?= $cert['certificate_id'] ?>&download=1" class="btn btn-outline-info" title="Download PDF">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            <?php if ($cert['status'] === 'active'): ?>
                                            <button onclick="revokeCertificate(<?= $cert['certificate_id'] ?>)" class="btn btn-outline-danger" title="Revoke">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&certificate_type=<?= urlencode($certificateType) ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function revokeCertificate(certificateId) {
            if (confirm('Are you sure you want to revoke this certificate? This action cannot be undone.')) {
                fetch('/views/certificates/revoke.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        certificate_id: certificateId
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
