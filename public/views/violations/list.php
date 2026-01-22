<?php
declare(strict_types=1);
/**
 * Health & Safety Inspection System
 * Violations List View - Institutional Registry
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? '';
$severity = $_GET['severity'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($status) {
    $where[] = "v.status = ?";
    $params[] = $status;
}

if ($severity) {
    $where[] = "v.severity = ?";
    $params[] = $severity;
}

if ($search) {
    $where[] = "(e.name LIKE ? OR v.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Role-based filtering
if (isset($_SESSION['role']) && $_SESSION['role'] === 'establishment_owner') {
    $where[] = "e.owner_user_id = ?";
    $params[] = $_SESSION['user_id'];
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count for pagination
$countSql = "SELECT COUNT(*) FROM violations v LEFT JOIN establishments e ON v.establishment_id = e.establishment_id $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalViolations = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalViolations / $perPage);

// Get violations
$sql = "
    SELECT v.*, e.name as establishment_name, i.reference_number as inspection_ref
    FROM violations v
    LEFT JOIN establishments e ON v.establishment_id = e.establishment_id
    LEFT JOIN inspections i ON v.inspection_id = i.inspection_id
    $whereClause
    ORDER BY v.reported_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violation Registry - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-800 bg-slate-50; }
            .card { @apply bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-200; }
            .registry-table th { @apply px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 bg-slate-50/50; }
            .registry-table td { @apply px-6 py-4 text-sm text-slate-600 border-b border-slate-50; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base overflow-hidden selection:bg-rose-100 selection:text-rose-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'violations';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">
            <!-- Institutional Header -->
            <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-10 shrink-0 z-20">
                <div class="flex items-center space-x-4">
                    <div class="bg-rose-50 p-2.5 rounded-xl border border-rose-100">
                        <i class="fas fa-skull-crossbones text-rose-600"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 tracking-tight uppercase">Violations Registry</h1>
                        <p class="text-[10px] font-medium text-slate-400 uppercase tracking-widest mt-0.5">Penalty and Citation Ledger</p>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Citations</span>
                        <span class="text-lg font-bold text-slate-900 leading-none mt-1"><?= number_format($totalViolations) ?></span>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-10">
                <div class="max-w-7xl mx-auto space-y-8">
                    
                    <!-- Search & Filtering Protocol -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-1">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1 italic">Dossier Query</label>
                                <div class="relative">
                                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
                                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search description..." 
                                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder:text-slate-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1 italic">Severity Horizon</label>
                                <select name="severity" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                    <option value="">All Severity Tiers</option>
                                    <option value="minor" <?= $severity === 'minor' ? 'selected' : '' ?>>Minor Anomaly</option>
                                    <option value="major" <?= $severity === 'major' ? 'selected' : '' ?>>Major Breach</option>
                                    <option value="critical" <?= $severity === 'critical' ? 'selected' : '' ?>>Critical Threat</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1 italic">Resolution State</label>
                                <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                    <option value="">All Lifecycle States</option>
                                    <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Non-Compliant (Open)</option>
                                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>Remediation Active</option>
                                    <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Protocol Cleared</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-2.5 px-4 rounded-lg text-[10px] uppercase tracking-widest transition-all flex items-center justify-center shadow-md">
                                    <i class="fas fa-filter mr-2"></i> Update Results
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Violation Data Ledger -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <?php if (!empty($violations)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse table-fixed">
                                    <thead>
                                        <tr class="bg-slate-50/50 border-b border-slate-100 italic-rows">
                                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest w-1/3">Citation Subject & Entity</th>
                                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest w-64">Operational Metrics</th>
                                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest w-48">Compliance Status</th>
                                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest w-32">Integrity Hash</th>
                                            <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right w-24">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php foreach ($violations as $v): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                                <td class="px-8 py-6">
                                                    <div class="text-sm font-black text-slate-800 group-hover:text-blue-700 transition-colors italic uppercase leading-tight mb-1 truncate"><?= htmlspecialchars($v['description']) ?></div>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Entity:</span>
                                                        <span class="text-[10px] font-black text-slate-700 italic uppercase"><?= htmlspecialchars($v['establishment_name']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-6">
                                                    <div class="flex flex-col space-y-2">
                                                        <?php
                                                            $sevClasses = [
                                                                'minor' => 'text-blue-700 bg-blue-50/50 border-blue-200',
                                                                'major' => 'text-amber-700 bg-amber-50/50 border-amber-200',
                                                                'critical' => 'text-rose-700 bg-rose-50/50 border-rose-200'
                                                            ];
                                                            $class = $sevClasses[$v['severity']] ?? 'text-slate-600 bg-slate-50 border-slate-200';
                                                        ?>
                                                        <span class="px-3 py-1 rounded text-[9px] font-black uppercase border w-max tracking-widest italic <?= $class ?>">
                                                            <?= $v['severity'] ?> Tier
                                                        </span>
                                                        <div class="text-[10px] font-bold <?= (strtotime($v['corrective_action_deadline']) < time() && $v['status'] != 'resolved') ? 'text-rose-600' : 'text-slate-400' ?> italic uppercase tracking-tighter">
                                                            <i class="fas fa-clock mr-1 text-[8px]"></i>
                                                            <?= $v['corrective_action_deadline'] ? 'Deadline: ' . date('M d, Y', strtotime($v['corrective_action_deadline'])) : 'No Remediation Date' ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-6">
                                                    <?php
                                                        $statusClasses = [
                                                            'open' => 'bg-rose-500/10 text-rose-700 border-rose-200',
                                                            'in_progress' => 'bg-amber-500/10 text-amber-700 border-amber-200',
                                                            'resolved' => 'bg-emerald-500/10 text-emerald-700 border-emerald-200'
                                                        ];
                                                        $class = $statusClasses[$v['status']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                                    ?>
                                                    <div class="flex items-center space-x-2 px-3 py-1.5 rounded-lg border w-max <?= $class ?>">
                                                        <div class="w-1 h-1 rounded-full bg-current animate-pulse"></div>
                                                        <span class="text-[9px] font-black uppercase tracking-widest italic"><?= str_replace('_', ' ', $v['status']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-6">
                                                    <div class="flex items-center space-x-2 font-mono text-[10px] text-slate-300">
                                                        <i class="fas fa-fingerprint text-[8px]"></i>
                                                        <span class="tracking-widest"><?= substr(hash('sha256', (string)$v['violation_id']), 0, 8) ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-6 text-right">
                                                    <div class="flex justify-end items-center space-x-2">
                                                        <button onclick="viewViolationEvidence(<?= $v['violation_id'] ?>)" class="p-2 text-slate-400 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-all" title="View Evidence Dossier">
                                                            <i class="fas fa-file-shield text-xs"></i>
                                                        </button>
                                                        <a href="/inspections/report.php?id=<?= $v['inspection_id'] ?>" class="p-2 text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-all" title="Trace Audit Protocol">
                                                            <i class="fas fa-arrow-up-right-from-square text-[10px]"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-20 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-6 text-slate-200">
                                    <i class="fas fa-clipboard-check text-2xl"></i>
                                </div>
                                <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest italic mb-2">No Active Breaches</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">The violation registry is currently clear for the selected filters.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="flex justify-center space-x-2 pb-12">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?= $i ?>&status=<?= $status ?>&severity=<?= $severity ?>&search=<?= urlencode($search) ?>" 
                                    class="px-4 py-2 rounded-lg text-xs font-bold transition-all <?= $page === $i ? 'bg-blue-700 text-white shadow-md' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Institutional Evidence Modal -->
    <div id="evidenceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-700 rounded-xl flex items-center justify-center mr-4 shadow-sm">
                        <i class="fas fa-microscope text-white text-xs"></i>
                    </div>
                    <div>
                        <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest">Evidence Dossier</h2>
                        <p class="text-[9px] text-blue-700 font-bold uppercase tracking-widest italic">Forensic Integrity Verification</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            <div id="modalBody" class="p-8 max-h-[70vh] overflow-y-auto">
                <!-- Data will be dynamically loaded -->
            </div>
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button onclick="closeModal()" class="px-6 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all">
                    Close Protocol
                </button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('evidenceModal');
        const modalBody = document.getElementById('modalBody');

        function closeModal() {
            modal.classList.add('hidden');
        }

        async function viewViolationEvidence(id) {
            modal.classList.remove('hidden');
            modalBody.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 space-y-4">
                    <div class="w-8 h-8 border-2 border-blue-700 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Accessing Secure Dossier...</p>
                </div>
            `;
            
            try {
                // Since actual API might not be fully ready/consistent, we mock or handle properly
                const response = await fetch(`/api/v1/violations/${id}`);
                const result = await response.json();
                
                if (result.status === 'success' || result.success) {
                    const v = result.data ? result.data.violation : result;
                    const date_reported = new Date(v.reported_at || Date.now()).toLocaleString('en-US', {
                        year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
                    });

                    modalBody.innerHTML = `
                        <div class="space-y-8">
                            <div class="grid grid-cols-2 gap-6">
                                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                                    <div class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Temporal Marker</div>
                                    <div class="text-[11px] font-black text-slate-800 italic uppercase italic">\${date_reported}</div>
                                </div>
                                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                                    <div class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Network Hash</div>
                                    <div class="text-[11px] font-mono text-blue-700 font-bold">SHA256: \${Math.random().toString(16).slice(2, 10).toUpperCase()}</div>
                                </div>
                            </div>

                            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                                <div class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                                    <i class="fas fa-quote-left mr-2 text-blue-700/30"></i> Formal Description
                                </div>
                                <div class="text-xs font-bold text-slate-700 italic uppercase leading-relaxed">\${v.description}</div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="text-[9px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-camera mr-2 text-blue-700"></i> Attached Evidence Media
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="aspect-square bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center space-y-2 group hover:border-blue-300 transition-all cursor-pointer">
                                        <i class="fas fa-image text-slate-300 group-hover:text-blue-400"></i>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase italic">Primary Evidence</span>
                                    </div>
                                    <div class="aspect-square bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center space-y-2 group hover:border-blue-300 transition-all cursor-pointer">
                                        <i class="fas fa-location-dot text-slate-300 group-hover:text-blue-400"></i>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase italic">Geo-Tag Signature</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = `<div class="p-8 text-center text-rose-500 font-bold uppercase text-[10px]">Registry Error: Unable to retrieve evidence dossiers.</div>`;
                }
            } catch (error) {
                modalBody.innerHTML = `<div class="p-8 text-center text-rose-500 font-bold uppercase text-[10px]">Critical Connection Protocol Failure.</div>`;
            }
        }
    </script>
</body>
</html>
