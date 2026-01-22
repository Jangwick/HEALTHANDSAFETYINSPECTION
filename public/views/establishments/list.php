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
    <title>Establishments - Health & Safety System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 105%; }
            body { @apply text-white font-medium; }
        }
    </style>
</head>
<body class="bg-[#0b0c10] font-sans antialiased overflow-hidden text-lg">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'establishments';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-2xl font-bold text-white tracking-tight">Establishments</h1>
                <div class="flex items-center space-x-4">
                    <a href="/establishments/create" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-blue-900/20 transition-all active:scale-95 group">
                        <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i> Add Establishment
                    </a>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-[#0b0c10]">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-xl">
                        <div class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Total Registered</div>
                        <div class="text-3xl font-bold text-white tracking-tight"><?php echo  number_format((float)($stats['total'] ?? 0)) ?></div>
                    </div>
                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-xl">
                        <div class="text-xs font-black text-emerald-400 uppercase tracking-[0.2em] mb-2">Compliant</div>
                        <div class="text-3xl font-bold text-white tracking-tight"><?php echo  number_format((float)($stats['compliant'] ?? 0)) ?></div>
                    </div>
                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-xl">
                        <div class="text-xs font-black text-rose-400 uppercase tracking-[0.2em] mb-2">Non-Compliant</div>
                        <div class="text-3xl font-bold text-white tracking-tight"><?php echo  number_format((float)($stats['non_compliant'] ?? 0)) ?></div>
                    </div>
                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-xl">
                        <div class="text-xs font-black text-amber-400 uppercase tracking-[0.2em] mb-2">Pending Review</div>
                        <div class="text-3xl font-bold text-white tracking-tight"><?php echo  number_format((float)($stats['pending'] ?? 0)) ?></div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="bg-[#15181e] rounded-2xl shadow-xl border border-white/5 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-6 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2.5">Search</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" name="search" value="<?php echo  htmlspecialchars($search) ?>" placeholder="Name, owner, or permit..." 
                                    class="w-full pl-11 pr-4 py-3 bg-[#0b0c10] border border-white/10 rounded-xl text-base text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2.5">Type</label>
                            <select name="type" class="w-full bg-[#0b0c10] border border-white/10 rounded-xl py-3 px-4 text-base text-slate-300 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer font-bold">
                                <option value="">All Types</option>
                                <option value="restaurant" <?php echo  $type === 'restaurant' ? 'selected' : '' ?>>Restaurant</option>
                                <option value="school" <?php echo  $type === 'school' ? 'selected' : '' ?>>School</option>
                                <option value="hospital" <?php echo  $type === 'hospital' ? 'selected' : '' ?>>Hospital</option>
                                <option value="hotel" <?php echo  $type === 'hotel' ? 'selected' : '' ?>>Hotel</option>
                                <option value="market" <?php echo  $type === 'market' ? 'selected' : '' ?>>Market</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-2.5">Status</label>
                            <select name="status" class="w-full bg-[#0b0c10] border border-white/10 rounded-xl py-3 px-4 text-base text-slate-300 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer font-bold">
                                <option value="">All Statuses</option>
                                <option value="compliant" <?php echo  $status === 'compliant' ? 'selected' : '' ?>>Compliant</option>
                                <option value="non_compliant" <?php echo  $status === 'non_compliant' ? 'selected' : '' ?>>Non-Compliant</option>
                                <option value="pending" <?php echo  $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-[#1e232b] hover:bg-[#252b35] text-white font-bold py-3 px-6 rounded-xl text-sm transition-all flex items-center justify-center border border-white/10 shadow-lg">
                            <i class="fas fa-filter mr-2 text-blue-500"></i> Apply
                        </button>
                    </form>
                </div>

                <!-- Results Table -->
                <div class="bg-[#15181e] rounded-2xl shadow-2xl border border-white/5 overflow-hidden">
                    <?php if (!empty($establishments)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-[#1a1d23] border-b border-white/5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                                        <th class="px-8 py-5">Establishment</th>
                                        <th class="px-8 py-5">Type</th>
                                        <th class="px-8 py-5">Owner / Contact</th>
                                        <th class="px-8 py-5">Status</th>
                                        <th class="px-8 py-5">Last Inspection</th>
                                        <th class="px-8 py-5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($establishments as $est): ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors cursor-pointer group" onclick="window.location='/establishments/view?id=<?php echo  $est['establishment_id'] ?>'">
                                            <td class="px-8 py-6">
                                                <div class="font-bold text-white group-hover:text-blue-400 transition-colors"><?php echo  htmlspecialchars($est['name']) ?></div>
                                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo  htmlspecialchars($est['business_permit_number'] ?? 'No Permit') ?></div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <span class="text-xs font-semibold text-slate-400 italic font-mono uppercase tracking-tighter"><?php echo  ucwords($est['type']) ?></span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="text-sm font-medium text-slate-300"><?php echo  htmlspecialchars($est['owner_name']) ?></div>
                                                <div class="text-xs text-slate-500"><?php echo  htmlspecialchars($est['owner_phone'] ?? '') ?></div>
                                            </td>
                                            <td class="px-8 py-6">
                                                <?php
                                                    $statusStyles = [
                                                        'compliant' => 'bg-emerald-500/5 text-emerald-500 border-emerald-500/20',
                                                        'non_compliant' => 'bg-rose-500/5 text-rose-500 border-rose-500/20',
                                                        'pending' => 'bg-amber-500/5 text-amber-500 border-amber-500/20'
                                                    ];
                                                    $style = $statusStyles[$est['compliance_status']] ?? 'bg-slate-500/5 text-slate-500 border-white/5';
                                                ?>
                                                <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-[0.15em] border <?php echo  $style ?>">
                                                    <?php echo  str_replace('_', ' ', $est['compliance_status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-8 py-6">
                                                <div class="text-sm font-medium text-slate-300">
                                                    <?php echo  $est['last_inspection_date'] ? date('M d, Y', strtotime($est['last_inspection_date'])) : 'Never' ?>
                                                </div>
                                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo  $est['inspection_count'] ?> total</div>
                                            </td>
                                            <td class="px-8 py-6 text-right" onclick="event.stopPropagation()">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="/establishments/view?id=<?php echo  $est['establishment_id'] ?>" class="p-2.5 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white rounded-lg transition-all" title="Full Report">
                                                        <i class="fas fa-eye text-sm"></i>
                                                    </a>
                                                    <a href="/inspections/create?establishment_id=<?php echo  $est['establishment_id'] ?>" class="p-2.5 bg-emerald-600/10 hover:bg-emerald-600 text-emerald-500 hover:text-white rounded-lg transition-all" title="Schedule Inspection">
                                                        <i class="fas fa-calendar-plus text-sm"></i>
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
                            <div class="mt-8 flex items-center justify-between bg-[#1a1d23] p-6 border-t border-white/5">
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] italic">
                                    Page <?php echo  $page ?> of <?php echo  $totalPages ?>
                                </div>
                                <div class="flex space-x-1">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo  $i ?>&search=<?php echo  urlencode($search) ?>&type=<?php echo  $type ?>&status=<?php echo  $status ?>" 
                                            class="px-4 py-2 rounded-lg text-xs font-black transition-all <?php echo  $i == $page ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'bg-[#0b0c10] text-slate-400 hover:bg-white/5 border border-white/5' ?>">
                                            <?php echo  $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="p-20 text-center">
                            <div class="w-20 h-20 bg-white/5 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-white/5">
                                <i class="fas fa-building text-3xl text-slate-600"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">No establishments found</h3>
                            <p class="text-slate-500 mb-8 max-w-xs mx-auto">Try adjusting your search or filters to find what you're looking for.</p>
                            <a href="/establishments/create" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-900/20 active:scale-95">
                                <i class="fas fa-plus mr-2 text-xs"></i> Register Establishment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
