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
<body class="bg-[#0b0c10] font-sans antialiased text-slate-200 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'inspections';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-2xl font-bold text-white tracking-tight">Inspections</h1>
                <div class="flex items-center space-x-4">
                    <a href="/inspections/create" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-blue-900/20 transition-all active:scale-95 group">
                        <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i> New Inspection
                    </a>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-[#0b0c10]">
                <!-- Filters Section -->
                <div class="bg-[#15181e] rounded-2xl shadow-xl border border-white/5 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5">Search</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" name="search" value="<?php echo  htmlspecialchars($search) ?>" placeholder="Establishment name..." 
                                    class="w-full pl-11 pr-4 py-3 bg-[#0b0c10] border border-white/10 rounded-xl text-sm text-slate-200 focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 highlight-none outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5">Status</label>
                            <select name="status" class="w-full bg-[#0b0c10] border border-white/10 rounded-xl py-3 px-4 text-sm text-slate-300 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo  $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?php echo  $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?php echo  $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5">Type</label>
                            <select name="type" class="w-full bg-[#0b0c10] border border-white/10 rounded-xl py-3 px-4 text-sm text-slate-300 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer">
                                <option value="">All Types</option>
                                <option value="food_safety" <?php echo  $type === 'food_safety' ? 'selected' : '' ?>>Food Safety</option>
                                <option value="building_safety" <?php echo  $type === 'building_safety' ? 'selected' : '' ?>>Building Safety</option>
                                <option value="fire_safety" <?php echo  $type === 'fire_safety' ? 'selected' : '' ?>>Fire Safety</option>
                                <option value="sanitation" <?php echo  $type === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-[#1e232b] hover:bg-[#252b35] text-white font-bold py-3 px-6 rounded-xl text-sm transition-all flex items-center justify-center border border-white/10 shadow-lg">
                            <i class="fas fa-filter mr-2 text-blue-500"></i> Apply Filters
                        </button>
                    </form>
                </div>

                <!-- Results Table -->
                <div class="bg-[#15181e] rounded-2xl shadow-2xl border border-white/5 overflow-hidden">
                    <?php if (count($inspections) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-[#1a1d23] border-b border-white/5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                                        <th class="px-8 py-5">Establishment / ID</th>
                                        <th class="px-8 py-5">Type</th>
                                        <th class="px-8 py-5">Inspector</th>
                                        <th class="px-8 py-5">Status</th>
                                        <th class="px-8 py-5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($inspections as $inspection): ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors cursor-pointer group" onclick="window.location='/inspections/view?id=<?php echo  $inspection['inspection_id'] ?>'">
                                            <td class="px-8 py-6">
                                                <div class="flex flex-col">
                                                    <span class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-1">#<?php echo  $inspection['inspection_id'] ?></span>
                                                    <span class="text-sm font-bold text-white group-hover:text-blue-400 transition-colors"><?php echo  htmlspecialchars($inspection['establishment_name']) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <span class="text-xs font-semibold text-slate-400 italic font-mono uppercase tracking-tighter"><?php echo  ucwords(str_replace('_', ' ', $inspection['inspection_type'])) ?></span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="flex items-center">
                                                    <div class="h-7 w-7 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mr-3">
                                                        <i class="fas fa-user-shield text-[10px] text-blue-400"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-slate-300"><?php echo  htmlspecialchars($inspection['inspector_name'] ?? 'Unassigned') ?></span>
                                                </div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <?php
                                                    $statusStyles = [
                                                        'pending' => 'bg-amber-500/5 text-amber-500 border-amber-500/20',
                                                        'in_progress' => 'bg-blue-500/5 text-blue-500 border-blue-500/20',
                                                        'completed' => 'bg-emerald-500/5 text-emerald-500 border-emerald-500/20',
                                                        'failed' => 'bg-rose-500/5 text-rose-500 border-rose-500/20',
                                                        'cancelled' => 'bg-slate-500/5 text-slate-500 border-slate-500/20'
                                                    ];
                                                    $style = $statusStyles[$inspection['status']] ?? $statusStyles['pending'];
                                                ?>
                                                <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-[0.15em] border <?php echo  $style ?>">
                                                    <?php echo  str_replace('_', ' ', $inspection['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-8 py-6 text-right" onclick="event.stopPropagation()">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="/inspections/view?id=<?php echo  $inspection['inspection_id'] ?>" 
                                                       class="p-2.5 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white rounded-lg transition-all" title="View Details">
                                                        <i class="fas fa-eye text-sm"></i>
                                                    </a>
                                                    <?php if ($inspection['status'] === 'pending'): ?>
                                                        <a href="/inspections/conduct?id=<?php echo  $inspection['inspection_id'] ?>" 
                                                           class="p-2.5 bg-blue-600/10 hover:bg-blue-600 text-blue-500 hover:text-white rounded-lg transition-all" title="Start Inspection">
                                                            <i class="fas fa-play text-sm"></i>
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
                            <div class="mt-8 flex items-center justify-between bg-[#1a1d23] p-6 border-t border-white/5">
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                    Showing <?php echo  count($inspections) ?> of <?php echo  $totalInspections ?> inspections
                                </div>
                                <div class="flex space-x-1">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo  $i ?>&status=<?php echo  $status ?>&type=<?php echo  $type ?>&search=<?php echo  urlencode($search) ?>" 
                                           class="px-4 py-2 rounded-lg text-xs font-black transition-all <?php echo  $i == $page ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'bg-[#0b0c10] text-slate-400 hover:bg-white/5 border border-white/5' ?>">
                                            <?php echo  $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="p-20 text-center">
                            <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-white/5 rotation-slow">
                                <i class="fas fa-clipboard-list text-3xl text-slate-600"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">No inspections found</h3>
                            <p class="text-slate-500 mb-8 max-w-xs mx-auto">Try adjusting your filters or record a new inspection to get started.</p>
                            <a href="/views/inspections/create.php" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-900/20 active:scale-95">
                                <i class="fas fa-plus mr-2 text-xs"></i> Create First Inspection
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
