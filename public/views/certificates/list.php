<?php
/**
 * Health & Safety Inspection System
 * Certificates Institutional Registry
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
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

    // Role-based filtering: Establishment Owners only see their own certificates
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'establishment_owner') {
        $where[] = "e.owner_user_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM certificates c LEFT JOIN establishments e ON c.establishment_id = e.establishment_id $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalCertificates = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($totalCertificates / $perPage));
    
    // Get certificates
    $sql = "
        SELECT c.*, 
               e.name as establishment_name,
               e.type as establishment_type,
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
    <title>Certification Registry - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
            .registry-table th { @apply px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 bg-slate-50/50; }
            .registry-table td { @apply px-6 py-4 text-sm border-b border-slate-50; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'certificates';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-base">
            <!-- Institutional Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-10">
                <div class="flex items-center space-x-4">
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Certification Registry</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic">Institutional Ledger</span>
                </div>
                <div class="flex items-center space-x-3 text-base">
                    <a href="/certificates/issue" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center shadow-md transition-all active:scale-95">
                        <i class="fas fa-plus mr-2 text-[10px]"></i> Issue Certificate
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto space-y-8">
                    
                    <!-- Search & Filter Parameters -->
                    <div class="card p-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-1">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1 italic">Registry Search</label>
                                <div class="relative">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Ref # or Subject..." 
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all placeholder:text-slate-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1 italic">Classification</label>
                                <select name="certificate_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none appearance-none font-medium">
                                    <option value="">All Protocol Types</option>
                                    <option value="food_safety" <?= $certificateType === 'food_safety' ? 'selected' : '' ?>>Food Safety</option>
                                    <option value="building_safety" <?= $certificateType === 'building_safety' ? 'selected' : '' ?>>Building Safety</option>
                                    <option value="fire_safety" <?= $certificateType === 'fire_safety' ? 'selected' : '' ?>>Fire Safety</option>
                                    <option value="sanitation" <?= $certificateType === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1 italic">Status</label>
                                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none appearance-none font-medium">
                                    <option value="">Full Status Range</option>
                                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Registry</option>
                                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired Registry</option>
                                    <option value="revoked" <?= $status === 'revoked' ? 'selected' : '' ?>>Revoked Registry</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-slate-900 hover:bg-black text-white px-6 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest transition-all italic flex items-center justify-center">
                                    <i class="fas fa-filter mr-2 text-[10px]"></i> Refresh Ledger
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Certification Master Ledger -->
                    <div class="card">
                        <?php if (!empty($certificates)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left registry-table border-collapse">
                                    <thead>
                                        <tr>
                                            <th>Identification Number</th>
                                            <th>Subject Entity</th>
                                            <th>Protocol Type</th>
                                            <th>Validity Horizon</th>
                                            <th>Current Status</th>
                                            <th class="text-right">Operation</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 italic-rows">
                                        <?php foreach ($certificates as $cert): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                                <td>
                                                    <div class="font-black text-slate-800 text-xs italic"><?= htmlspecialchars($cert['certificate_number']) ?></div>
                                                    <div class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter mt-0.5 italic">Internal_Ref: #<?= $cert['certificate_id'] ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-xs font-black text-slate-800 group-hover:text-blue-700 transition-colors uppercase italic"><?= htmlspecialchars($cert['establishment_name']) ?></div>
                                                    <div class="text-[9px] text-slate-400 font-bold uppercase tracking-widest italic"><?= str_replace('_', ' ', $cert['establishment_type']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="inline-block px-2 py-1 bg-slate-100 text-[9px] font-black text-slate-500 rounded uppercase tracking-tighter">
                                                        <?= str_replace('_', ' ', $cert['certificate_type']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-[10px] text-slate-400 font-bold italic">Exp: <span class="text-slate-800"><?= date('M d, Y', strtotime($cert['expiry_date'])) ?></span></div>
                                                    <div class="text-[9px] text-slate-300 font-bold uppercase tracking-tighter mt-0.5">Issued: <?= date('M d, Y', strtotime($cert['issue_date'])) ?></div>
                                                </td>
                                                <td>
                                                    <?php
                                                        $statusClasses = [
                                                            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                            'expired' => 'bg-rose-50 text-rose-700 border-rose-100',
                                                            'revoked' => 'bg-slate-100 text-slate-400 border-slate-200',
                                                            'suspended' => 'bg-amber-50 text-amber-700 border-amber-100'
                                                        ];
                                                        $class = $statusClasses[$cert['status']] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                                                    ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black border uppercase tracking-widest italic <?= $class ?>">
                                                        <?= $cert['status'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-right">
                                                    <div class="flex justify-end space-x-2">
                                                        <a href="/views/certificates/view.php?id=<?= $cert['certificate_id'] ?>" class="text-[10px] font-black text-slate-300 hover:text-blue-700 uppercase tracking-widest italic transition-colors p-2">
                                                            View
                                                        </a>
                                                        <a href="/views/certificates/certificate.php?id=<?= $cert['certificate_id'] ?>" target="_blank" class="text-[10px] font-black text-slate-300 hover:text-emerald-700 uppercase tracking-widest italic transition-all p-2 bg-slate-50 rounded">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paging Controls -->
                            <div class="px-8 py-5 border-t border-slate-50 flex items-center justify-between bg-white text-base">
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                                    Registry Ledger Segment <?= $page ?> / <?= $totalPages ?>
                                </div>
                                <div class="flex space-x-1">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?= $i ?>&status=<?= $status ?>&type=<?= $certificateType ?>&search=<?= urlencode($search) ?>" 
                                            class="w-8 h-8 flex items-center justify-center rounded text-[10px] font-black transition-all <?= $i == $page ? 'bg-blue-700 text-white shadow-md' : 'bg-slate-50 text-slate-400 hover:bg-slate-100' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="py-24 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-200 shadow-inner">
                                    <i class="fas fa-certificate text-2xl"></i>
                                </div>
                                <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-widest italic">Ledger Segment Null</h3>
                                <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">No certification records found for current parameters.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
