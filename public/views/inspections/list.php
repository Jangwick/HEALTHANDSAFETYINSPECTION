<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Get user info
$stmt = $db->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN user_roles ur ON u.user_id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($status) {
    $where[] = "i.status = ?";
    $params[] = $status;
}

if ($type) {
    $where[] = "i.inspection_type = ?";
    $params[] = $type;
}

if ($search) {
    $where[] = "(e.name LIKE ? OR e.owner_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) FROM inspections i LEFT JOIN establishments e ON i.establishment_id = e.establishment_id $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalInspections = $stmt->fetchColumn();
$totalPages = ceil($totalInspections / $perPage);

// Get inspections
$sql = "
    SELECT i.*, 
           e.name as establishment_name, 
           e.type as establishment_type, 
           e.address_street,
           e.address_barangay,
           e.address_city,
           CONCAT(u.first_name, ' ', u.last_name) as inspector_name
    FROM inspections i
    LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
    LEFT JOIN users u ON i.inspector_id = u.user_id
    $whereClause
    ORDER BY i.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspections List - Health & Safety Inspection System</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 2rem; }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; }
        
        .nav { background: white; border-bottom: 1px solid #e5e7eb; padding: 0 2rem; }
        .nav-content { max-width: 1400px; margin: 0 auto; display: flex; gap: 2rem; }
        .nav-link { padding: 1rem 0; color: #6b7280; text-decoration: none; border-bottom: 2px solid transparent; }
        .nav-link:hover, .nav-link.active { color: #667eea; border-bottom-color: #667eea; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-title { font-size: 2rem; color: #1f2937; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; border: none; cursor: pointer; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        
        .filters { background: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-label { display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem; }
        .filter-input, .filter-select { width: 100%; padding: 0.625rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
        .filter-input:focus, .filter-select:focus { outline: none; border-color: #667eea; }
        .btn-filter { padding: 0.625rem 1.25rem; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; align-self: flex-end; }
        
        .inspections-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 1rem; border-bottom: 2px solid #e5e7eb; background: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.875rem; }
        td { padding: 1rem; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #e5e7eb; color: #374151; }
        
        .action-btns { display: flex; gap: 0.5rem; }
        .btn-view { padding: 0.5rem 1rem; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-size: 0.875rem; }
        .btn-view:hover { background: #5568d3; }
        .btn-edit { padding: 0.5rem 1rem; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-size: 0.875rem; }
        .btn-start { padding: 0.5rem 1rem; background: #f59e0b; color: white; text-decoration: none; border-radius: 6px; font-size: 0.875rem; }
        
        .pagination { display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem; }
        .page-btn { padding: 0.5rem 1rem; background: white; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; }
        .page-btn:hover, .page-btn.active { background: #667eea; color: white; border-color: #667eea; }
        
        .empty-state { text-align: center; padding: 4rem 2rem; color: #6b7280; }
        .empty-state-icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">🏥 Health & Safety Inspection System</div>
            <div class="user-info">
                <div><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                <a href="/views/auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="nav">
        <div class="nav-content">
            <a href="/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/views/inspections/list.php" class="nav-link active">Inspections</a>
            <a href="/views/establishments/list.php" class="nav-link">Establishments</a>
            <a href="/views/violations/list.php" class="nav-link">Violations</a>
            <a href="/views/certificates/list.php" class="nav-link">Certificates</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Inspections</h1>
            <a href="/views/inspections/create.php" class="btn-primary">
                <span>➕</span>
                <span>New Inspection</span>
            </a>
        </div>
        
        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label class="filter-label">Search</label>
                <input type="text" name="search" class="filter-input" placeholder="Establishment name..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">Type</label>
                <select name="type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="food_safety" <?php echo $type === 'food_safety' ? 'selected' : ''; ?>>Food Safety</option>
                    <option value="building_safety" <?php echo $type === 'building_safety' ? 'selected' : ''; ?>>Building Safety</option>
                    <option value="workplace_safety" <?php echo $type === 'workplace_safety' ? 'selected' : ''; ?>>Workplace Safety</option>
                    <option value="fire_safety" <?php echo $type === 'fire_safety' ? 'selected' : ''; ?>>Fire Safety</option>
                    <option value="sanitation" <?php echo $type === 'sanitation' ? 'selected' : ''; ?>>Sanitation</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filter</button>
        </form>
        
        <!-- Inspections Table -->
        <div class="inspections-table">
            <?php if (count($inspections) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Establishment</th>
                            <th>Type</th>
                            <th>Inspector</th>
                            <th>Scheduled Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inspections as $inspection): ?>
                            <tr>
                                <td><strong>#<?php echo $inspection['inspection_id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($inspection['establishment_name']); ?></strong><br>
                                    <small style="color: #6b7280;"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $inspection['establishment_type']))); ?></small>
                                </td>
                                <td><?php echo ucwords(str_replace('_', ' ', $inspection['inspection_type'])); ?></td>
                                <td><?php echo htmlspecialchars($inspection['inspector_name'] ?? 'Not assigned'); ?></td>
                                <td><?php echo $inspection['scheduled_date'] ? date('M d, Y', strtotime($inspection['scheduled_date'])) : '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $inspection['status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $inspection['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="/views/inspections/view.php?id=<?php echo $inspection['inspection_id']; ?>" class="btn-view">View</a>
                                        <?php if ($inspection['status'] === 'pending'): ?>
                                            <a href="/views/inspections/start.php?id=<?php echo $inspection['inspection_id']; ?>" class="btn-start">Start</a>
                                        <?php elseif ($inspection['status'] === 'in_progress'): ?>
                                            <a href="/views/inspections/conduct.php?id=<?php echo $inspection['inspection_id']; ?>" class="btn-edit">Continue</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">← Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <span class="page-btn">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&type=<?php echo $type; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">Next →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No inspections found</h3>
                    <p>Start by creating your first inspection</p>
                    <br>
                    <a href="/views/inspections/create.php" class="btn-primary">Create Inspection</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
