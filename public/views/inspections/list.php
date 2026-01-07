<?php
/**
 * Health & Safety Inspection System
 * Inspections List View
 */

declare(strict_types=1);

// PSR-4 Autoloading and Bootstrap already handled by public/index.php if routed correctly,
// but for direct access we need to ensure environment is set up.
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Get user info for roles/permissions
$stmt = $db->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN user_roles ur ON u.user_id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build SQL Query
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

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM inspections i LEFT JOIN establishments e ON i.establishment_id = e.establishment_id $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$totalInspections = (int)$stmt->fetchColumn();
$totalPages = ceil($totalInspections / $perPage);

// Get paginated inspections with relations
$sql = "
    SELECT i.*, 
           e.name as establishment_name, 
           e.type as establishment_type,
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
    <title>Inspections - Health & Safety Inspection System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'inspections';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-xl font-bold text-slate-800">Inspections</h1>
                <div class="flex items-center space-x-4">
                    <a href="/views/inspections/create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center shadow-sm transition-all active:scale-95">
                        <i class="fas fa-plus mr-2"></i> New Inspection
                    </a>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <!-- Filters Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Establishment name..." 
                                    class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                            <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Type</label>
                            <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Types</option>
                                <option value="food_safety" <?= $type === 'food_safety' ? 'selected' : '' ?>>Food Safety</option>
                                <option value="building_safety" <?= $type === 'building_safety' ? 'selected' : '' ?>>Building Safety</option>
                                <option value="workplace_safety" <?= $type === 'workplace_safety' ? 'selected' : '' ?>>Workplace Safety</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors flex items-center justify-center">
                                <i class="fas fa-filter mr-2"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Results Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <?php if (count($inspections) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200">
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Establishment / ID</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Inspector</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($inspections as $inspection): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-slate-900">#<?= $inspection['inspection_id'] ?></div>
                                                <div class="text-sm text-slate-500"><?= htmlspecialchars($inspection['establishment_name']) ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-slate-600"><?= ucwords(str_replace('_', ' ', $inspection['inspection_type'])) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-slate-600"><?= htmlspecialchars($inspection['inspector_name'] ?? 'Unassigned') ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $statusClasses = [
                                                        'pending' => 'bg-amber-100 text-amber-700',
                                                        'in_progress' => 'bg-blue-100 text-blue-700',
                                                        'completed' => 'bg-emerald-100 text-emerald-700',
                                                        'failed' => 'bg-rose-100 text-rose-700'
                                                    ];
                                                    $class = $statusClasses[$inspection['status']] ?? 'bg-slate-100 text-slate-700';
                                                ?>
                                                <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider <?= $class ?>">
                                                    <?= str_replace('_', ' ', $inspection['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="/views/inspections/view.php?id=<?= $inspection['inspection_id'] ?>" 
                                                       class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($inspection['status'] === 'pending'): ?>
                                                        <a href="/views/inspections/start.php?id=<?= $inspection['inspection_id'] ?>" 
                                                           class="p-2 text-slate-400 hover:text-amber-600 transition-colors" title="Start Inspection">
                                                            <i class="fas fa-play"></i>
                                                        </a>
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
                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <div class="text-sm text-slate-500 italic">
                                    Showing <?= count($inspections) ?> of <?= $totalInspections ?> inspections
                                </div>
                                <div class="flex space-x-1">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?= $i ?>&status=<?= $status ?>&type=<?= $type ?>&search=<?= urlencode($search) ?>" 
                                           class="px-3 py-1 rounded border <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?> text-sm font-semibold transition-colors">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="p-12 text-center">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-clipboard-list text-2xl text-slate-400"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">No inspections found</h3>
                            <p class="text-slate-500 mb-6">Try adjusting your filters or create a new inspection record.</p>
                            <a href="/views/inspections/create.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all">
                                <i class="fas fa-plus mr-2"></i> Create First Inspection
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
