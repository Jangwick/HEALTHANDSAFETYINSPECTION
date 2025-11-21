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
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Build query
    $where = [];
    $params = [];
    
    if ($type) {
        $where[] = "e.type = ?";
        $params[] = $type;
    }
    
    if ($status) {
        $where[] = "e.compliance_status = ?";
        $params[] = $status;
    }
    
    if ($barangay) {
        $where[] = "e.address_barangay = ?";
        $params[] = $barangay;
    }
    
    if ($search) {
        $where[] = "(e.name LIKE ? OR e.owner_name LIKE ? OR e.business_permit_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM establishments e $whereClause");
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get establishments
    $stmt = $db->prepare("
        SELECT e.*,
               (SELECT COUNT(*) FROM inspections i WHERE i.establishment_id = e.establishment_id) as inspection_count,
               (SELECT MAX(i.scheduled_date) FROM inspections i WHERE i.establishment_id = e.establishment_id) as last_inspection_date,
               (SELECT COUNT(*) FROM certificates c WHERE c.establishment_id = e.establishment_id AND c.status = 'active') as active_certificates
        FROM establishments e
        $whereClause
        ORDER BY e.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $perPage;
    $params[] = $offset;
    $stmt->execute($params);
    $establishments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique barangays for filter
    $barangays = $db->query("SELECT DISTINCT address_barangay FROM establishments WHERE address_barangay IS NOT NULL ORDER BY address_barangay")->fetchAll(PDO::FETCH_COLUMN);
    
    // Get current user info
    $userStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while fetching establishments.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establishments - Health & Safety Inspection System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        .status-badge { padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .status-compliant { background-color: #d1fae5; color: #065f46; }
        .status-non_compliant { background-color: #fee2e2; color: #991b1b; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-suspended { background-color: #dbeafe; color: #1e40af; }
        .filter-section { background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .table-hover tbody tr:hover { background-color: #f1f5f9; cursor: pointer; }
        .type-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .type-restaurant { background: #fef3c7; color: #92400e; }
        .type-school { background: #dbeafe; color: #1e40af; }
        .type-hospital { background: #fce7f3; color: #9f1239; }
        .type-hotel { background: #e0e7ff; color: #3730a3; }
        .type-market { background: #d1fae5; color: #065f46; }
        .type-other { background: #e5e7eb; color: #374151; }
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
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-building"></i> Establishments</h2>
                <p class="text-muted mb-0">Manage registered establishments and their compliance status</p>
            </div>
            <a href="/views/establishments/create.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Establishment
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php
            $stats = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN compliance_status = 'compliant' THEN 1 ELSE 0 END) as compliant,
                    SUM(CASE WHEN compliance_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant,
                    SUM(CASE WHEN compliance_status = 'pending' THEN 1 ELSE 0 END) as pending
                FROM establishments
            ")->fetch(PDO::FETCH_ASSOC);
            ?>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Establishments</h6>
                                <h3 class="mb-0"><?= number_format($stats['total']) ?></h3>
                            </div>
                            <div class="type-icon type-other">
                                <i class="bi bi-building"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Compliant</h6>
                                <h3 class="mb-0 text-success"><?= number_format($stats['compliant']) ?></h3>
                            </div>
                            <div class="type-icon type-market">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Non-Compliant</h6>
                                <h3 class="mb-0 text-danger"><?= number_format($stats['non_compliant']) ?></h3>
                            </div>
                            <div class="type-icon type-restaurant">
                                <i class="bi bi-x-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Pending</h6>
                                <h3 class="mb-0 text-warning"><?= number_format($stats['pending']) ?></h3>
                            </div>
                            <div class="type-icon type-restaurant">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" placeholder="Name, owner, or permit..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="restaurant" <?= $type === 'restaurant' ? 'selected' : '' ?>>Restaurant</option>
                        <option value="school" <?= $type === 'school' ? 'selected' : '' ?>>School</option>
                        <option value="hospital" <?= $type === 'hospital' ? 'selected' : '' ?>>Hospital</option>
                        <option value="hotel" <?= $type === 'hotel' ? 'selected' : '' ?>>Hotel</option>
                        <option value="market" <?= $type === 'market' ? 'selected' : '' ?>>Market</option>
                        <option value="factory" <?= $type === 'factory' ? 'selected' : '' ?>>Factory</option>
                        <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="compliant" <?= $status === 'compliant' ? 'selected' : '' ?>>Compliant</option>
                        <option value="non_compliant" <?= $status === 'non_compliant' ? 'selected' : '' ?>>Non-Compliant</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Barangay</label>
                    <select class="form-select" name="barangay">
                        <option value="">All Barangays</option>
                        <?php foreach ($barangays as $brgy): ?>
                        <option value="<?= htmlspecialchars($brgy) ?>" <?= $barangay === $brgy ? 'selected' : '' ?>>
                            <?= htmlspecialchars($brgy) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> Establishments List 
                        <span class="badge bg-secondary"><?= number_format($totalRecords) ?> total</span>
                    </h5>
                </div>

                <?php if (empty($establishments)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No establishments found. 
                    <a href="/views/establishments/create.php">Add your first establishment</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Establishment Name</th>
                                <th>Type</th>
                                <th>Owner</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Inspections</th>
                                <th>Certificates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($establishments as $est): ?>
                            <tr onclick="window.location='/views/establishments/view.php?id=<?= $est['establishment_id'] ?>'">
                                <td>
                                    <div class="type-icon type-<?= htmlspecialchars($est['type']) ?>">
                                        <?php
                                        $icons = [
                                            'restaurant' => 'bi-shop',
                                            'school' => 'bi-book',
                                            'hospital' => 'bi-heart-pulse',
                                            'hotel' => 'bi-building',
                                            'market' => 'bi-cart',
                                            'factory' => 'bi-gear',
                                            'other' => 'bi-building'
                                        ];
                                        ?>
                                        <i class="bi <?= $icons[$est['type']] ?? 'bi-building' ?>"></i>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($est['name']) ?></strong><br>
                                    <small class="text-muted">
                                        <i class="bi bi-file-text"></i> <?= htmlspecialchars($est['business_permit_number'] ?? 'N/A') ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $est['type']))) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($est['owner_name']) ?><br>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone"></i> <?= htmlspecialchars($est['owner_phone'] ?? 'N/A') ?>
                                    </small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($est['address_barangay']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($est['address_city']) ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($est['compliance_status']) ?>">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $est['compliance_status']))) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $est['inspection_count'] ?> total</span><br>
                                    <?php if ($est['last_inspection_date']): ?>
                                    <small class="text-muted">Last: <?= date('M d, Y', strtotime($est['last_inspection_date'])) ?></small>
                                    <?php else: ?>
                                    <small class="text-muted">Never inspected</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($est['active_certificates'] > 0): ?>
                                    <span class="badge bg-success"><?= $est['active_certificates'] ?> active</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">None</span>
                                    <?php endif; ?>
                                </td>
                                <td onclick="event.stopPropagation();">
                                    <div class="btn-group btn-group-sm">
                                        <a href="/views/establishments/view.php?id=<?= $est['establishment_id'] ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="/views/establishments/edit.php?id=<?= $est['establishment_id'] ?>" 
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="/views/inspections/create.php?establishment_id=<?= $est['establishment_id'] ?>" 
                                           class="btn btn-outline-success" title="New Inspection">
                                            <i class="bi bi-clipboard-check"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&barangay=<?= urlencode($barangay) ?>&search=<?= urlencode($search) ?>">Previous</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&barangay=<?= urlencode($barangay) ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&type=<?= urlencode($type) ?>&status=<?= urlencode($status) ?>&barangay=<?= urlencode($barangay) ?>&search=<?= urlencode($search) ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
