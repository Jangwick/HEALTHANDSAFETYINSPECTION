<?php
/**
 * Health & Safety Inspection System
 * Establishments List View - Modern Tailwind Layout
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $db = Database::getConnection();
    
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 15;
    $offset = ($page - 1) * $perPage;
    
    // Filters
    $type = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? '';
    $barangay = $_GET['barangay'] ?? '';
    $search = trim($_GET['search'] ?? '');
    
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

    // Role-based filtering: Establishment Owners only see their own
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'establishment_owner') {
        $where[] = "e.owner_user_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM establishments e $whereClause");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get establishments
    $sql = "
        SELECT e.*,
               (SELECT COUNT(*) FROM inspections i WHERE i.establishment_id = e.establishment_id) as inspection_count,
               (SELECT MAX(i.scheduled_date) FROM inspections i WHERE i.establishment_id = e.establishment_id) as last_inspection_date,
               (SELECT COUNT(*) FROM certificates c WHERE c.establishment_id = e.establishment_id AND c.status = 'active') as active_certificates
        FROM establishments e
        $whereClause
        ORDER BY e.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $establishments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique barangays for filter
    $barangays = $db->query("SELECT DISTINCT address_barangay FROM establishments WHERE address_barangay IS NOT NULL ORDER BY address_barangay")->fetchAll(PDO::FETCH_COLUMN);
    
    // Get stats
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN compliance_status = 'compliant' THEN 1 ELSE 0 END) as compliant,
            SUM(CASE WHEN compliance_status = 'non_compliant' THEN 1 ELSE 0 END) as non_compliant,
            SUM(CASE WHEN compliance_status = 'pending' THEN 1 ELSE 0 END) as pending
        FROM establishments
    ")->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'compliant' => 0, 'non_compliant' => 0, 'pending' => 0];
    
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
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700; }
            h1, h2, h3, h4, h5, h6 { @apply font-bold tracking-tight text-slate-900; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'establishments';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-10">
                <h1 class="text-sm font-bold text-slate-700 tracking-tight">Establishment Registry</h1>
                <div class="flex items-center space-x-3">
                    <a href="/establishments/create" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center shadow-sm transition-all active:scale-95">
                        <i class="fas fa-plus mr-2 text-[10px]"></i> Register Establishment
                    </a>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50">
                <!-- Administrative Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                        <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Registered</div>
                        <div class="text-2xl font-bold text-slate-900"><?php echo number_format((float)($stats['total'] ?? 0)) ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
                        <div class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Compliant</div>
                        <div class="text-2xl font-bold text-slate-900"><?php echo number_format((float)($stats['compliant'] ?? 0)) ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-rose-500">
                        <div class="text-[10px] font-bold text-rose-600 uppercase tracking-wider mb-1">Non-Compliant</div>
                        <div class="text-2xl font-bold text-slate-900"><?php echo number_format((float)($stats['non_compliant'] ?? 0)) ?></div>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm border-l-4 border-l-amber-500">
                        <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1">Pending Review</div>
                        <div class="text-2xl font-bold text-slate-900"><?php echo number_format((float)($stats['pending'] ?? 0)) ?></div>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Search Registry</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                    <i class="fas fa-search text-xs"></i>
                                </span>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search) ?>" placeholder="Establishment name or owner..." 
                                    class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Type</label>
                            <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition-all cursor-pointer">
                                <option value="">All Types</option>
                                <option value="restaurant" <?php echo $type === 'restaurant' ? 'selected' : '' ?>>Restaurant</option>
                                <option value="school" <?php echo $type === 'school' ? 'selected' : '' ?>>School</option>
                                <option value="hospital" <?php echo $type === 'hospital' ? 'selected' : '' ?>>Hospital</option>
                                <option value="hotel" <?php echo $type === 'hotel' ? 'selected' : '' ?>>Hotel</option>
                                <option value="market" <?php echo $type === 'market' ? 'selected' : '' ?>>Market</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Status</label>
                            <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm text-slate-700 focus:ring-1 focus:ring-blue-500 outline-none transition-all cursor-pointer">
                                <option value="">All Statuses</option>
                                <option value="compliant" <?php echo $status === 'compliant' ? 'selected' : '' ?>>Compliant</option>
                                <option value="non_compliant" <?php echo $status === 'non_compliant' ? 'selected' : '' ?>>Non-Compliant</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-lg text-xs transition-all flex items-center justify-center shadow-sm">
                                <i class="fas fa-filter mr-2 opacity-70"></i> Filter
                            </button>
                        </div>
                        <div>
                            <a href="/establishments" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-lg text-xs transition-all flex items-center justify-center border border-slate-200">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Registry Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <?php if (!empty($establishments)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                        <th class="px-6 py-4">Establishment Name</th>
                                        <th class="px-6 py-4">Type</th>
                                        <th class="px-6 py-4">Owner / Representative</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4">Last Activity</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 italic-rows">
                                    <?php foreach ($establishments as $est): ?>
                                        <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="window.location='/establishments/view?id=<?php echo $est['establishment_id'] ?>'">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($est['name']) ?></div>
                                                <div class="text-[10px] text-slate-500 font-medium mt-0.5">Permit: <?php echo htmlspecialchars($est['business_permit_number'] ?? 'N/A') ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded uppercase"><?php echo htmlspecialchars($est['type']) ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs font-medium text-slate-700"><?php echo htmlspecialchars($est['owner_name']) ?></div>
                                                <div class="text-[10px] text-slate-500 mt-0.5"><?php echo htmlspecialchars($est['owner_phone'] ?? 'No contact') ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $statusStyles = [
                                                        'compliant' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                        'non_compliant' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                        'pending' => 'bg-amber-50 text-amber-700 border-amber-200'
                                                    ];
                                                    $style = $statusStyles[$est['compliance_status']] ?? 'bg-slate-50 text-slate-600 border-slate-200';
                                                ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold border <?php echo $style ?>">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5 opacity-60"></span>
                                                    <?php echo strtoupper(str_replace('_', ' ', $est['compliance_status'])) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs text-slate-700 font-medium">
                                                    <?php echo $est['last_inspection_date'] ? date('M d, Y', strtotime($est['last_inspection_date'])) : 'No inspections' ?>
                                                </div>
                                                <div class="text-[10px] text-slate-500 mt-0.5"><?php echo $est['inspection_count'] ?> recorded deployments</div>
                                            </td>
                                            <td class="px-6 py-4 text-right" onclick="event.stopPropagation()">
                                                <div class="flex justify-end space-x-1">
                                                    <a href="/establishments/view?id=<?php echo $est['establishment_id'] ?>" class="p-2 text-slate-400 hover:text-blue-700 hover:bg-blue-50 rounded transition-all" title="View Details">
                                                        <i class="fas fa-eye text-xs"></i>
                                                    </a>
                                                    <a href="/inspections/create?establishment_id=<?php echo $est['establishment_id'] ?>" class="p-2 text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 rounded transition-all" title="New Inspection">
                                                        <i class="fas fa-calendar-plus text-xs"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Control -->
                        <?php if ($totalPages > 1): ?>
                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                                    Displaying page <?php echo $page ?> of <?php echo $totalPages ?>
                                </div>
                                <div class="flex space-x-1">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1 ?>&search=<?php echo urlencode($search) ?>&type=<?php echo $type ?>&status=<?php echo $status ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded text-[10px] font-bold text-slate-600 hover:bg-slate-50 transition-all">Prev</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo $i ?>&search=<?php echo urlencode($search) ?>&type=<?php echo $type ?>&status=<?php echo $status ?>" 
                                            class="px-3 py-1.5 rounded text-[10px] font-bold border transition-all <?php echo $i == $page ? 'bg-blue-700 border-blue-700 text-white shadow-sm' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                                            <?php echo $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page + 1 ?>&search=<?php echo urlencode($search) ?>&type=<?php echo $type ?>&status=<?php echo $status ?>" class="px-3 py-1.5 bg-white border border-slate-200 rounded text-[10px] font-bold text-slate-600 hover:bg-slate-50 transition-all">Next</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="py-20 text-center">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-building text-2xl text-slate-300"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">No records found</h3>
                            <p class="text-xs text-slate-500 mb-6 max-w-[240px] mx-auto">Specific parameters returned zero results from the registry database.</p>
                            <a href="/establishments/create" class="inline-flex items-center px-4 py-2 bg-blue-700 text-white text-[10px] font-bold rounded-lg hover:bg-blue-800 transition-all shadow-sm">
                                <i class="fas fa-plus mr-2"></i> Register New Establishment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
