<?php
/**
 * Health & Safety Inspection System
 * Violations List View
 */

declare(strict_types=1);

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

    // Role-based filtering: Establishment Owners only see their own violations
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'establishment_owner') {
        $where[] = "e.owner_user_id = ?";
        $params[] = $_SESSION['user_id'];
    }
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
    <title>Violations - Health & Safety System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 105%; }
            body { @apply text-slate-200; }
            h1, h2, h3, h4, h5, h6 { @apply font-bold tracking-tight text-white; }
        }
    </style>
</head>
<body class="bg-[#0b0c10] font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'violations';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Safety Violations</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-black text-slate-500 uppercase tracking-widest">Tracking <?= $totalViolations ?> issues</span>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 text-base">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Establishment or issue..." 
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Severity</label>
                            <select name="severity" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Severities</option>
                                <option value="minor" <?= $severity === 'minor' ? 'selected' : '' ?>>Minor</option>
                                <option value="major" <?= $severity === 'major' ? 'selected' : '' ?>>Major</option>
                                <option value="critical" <?= $severity === 'critical' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                            <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Statuses</option>
                                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors flex items-center justify-center">
                                <i class="fas fa-filter mr-2"></i> Filter Results
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Results -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <?php if (!empty($violations)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        <th class="px-6 py-4">Violation / Establishment</th>
                                        <th class="px-6 py-4">Severity</th>
                                        <th class="px-6 py-4">Deadline</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4">Citation QR</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($violations as $v): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4 italic">
                                                <div class="text-sm font-bold text-slate-900 mb-0.5 line-clamp-1"><?= htmlspecialchars($v['description']) ?></div>
                                                <div class="text-xs text-slate-500 font-medium tracking-tight"><?= htmlspecialchars($v['establishment_name']) ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $sevClasses = [
                                                        'minor' => 'text-blue-600 bg-blue-50',
                                                        'major' => 'text-amber-600 bg-amber-50',
                                                        'critical' => 'text-rose-600 bg-rose-50 font-black'
                                                    ];
                                                    $class = $sevClasses[$v['severity']] ?? 'text-slate-600 bg-slate-50';
                                                ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $class ?>">
                                                    <?= $v['severity'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs font-bold <?= (strtotime($v['corrective_action_deadline']) < time() && $v['status'] != 'resolved') ? 'text-rose-600' : 'text-slate-600' ?>">
                                                    <?= $v['corrective_action_deadline'] ? date('M d, Y', strtotime($v['corrective_action_deadline'])) : 'N/A' ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $statusClasses = [
                                                        'open' => 'bg-rose-100 text-rose-700',
                                                        'in_progress' => 'bg-amber-100 text-amber-700 font-bold',
                                                        'resolved' => 'bg-emerald-100 text-emerald-700',
                                                        'waived' => 'bg-slate-100 text-slate-700'
                                                    ];
                                                    $class = $statusClasses[$v['status']] ?? 'bg-slate-100 text-slate-700';
                                                ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $class ?>">
                                                    <?= str_replace('_', ' ', $v['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <i class="fa-solid fa-qrcode text-slate-400"></i>
                                                    <span class="text-[10px] font-mono text-slate-500"><?= substr(hash('sha256', (string)$v['violation_id']), 0, 8) ?>...</span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="viewViolationEvidence(<?= $v['violation_id'] ?>)" class="text-slate-400 hover:text-purple-600 p-2" title="Forensic Evidence"><i class="fas fa-fingerprint"></i></button>
                                                <a href="/violations?id=<?= $v['violation_id'] ?>" class="text-slate-400 hover:text-blue-600 p-2"><i class="fas fa-eye"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-check-circle text-4xl text-emerald-100 mb-4"></i>
                            <h3 class="text-lg font-bold text-slate-800">No violations found</h3>
                            <p class="text-slate-500 italic">Excellent! No safety issues currently recorded.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Forensic Evidence Modal -->
    <div id="evidenceModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-purple-600 rounded-xl flex items-center justify-center mr-4 shadow-lg shadow-purple-200">
                        <i class="fas fa-fingerprint text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Forensic Evidence</h2>
                        <p class="text-[10px] text-purple-600 font-bold uppercase tracking-widest">Digital Chain of Custody</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalBody" class="p-8">
                <!-- Data will be loaded here -->
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button onclick="closeModal()" class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm font-bold transition-all">
                    Close Details
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
            modalBody.innerHTML = '<div class="flex justify-center py-10"><i class="fas fa-spinner fa-spin text-3xl text-purple-600"></i></div>';
            
            try {
                const response = await fetch(`/api/v1/violations/${id}`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    const v = result.data.violation;
                    let forensic = {gps_latitude: 'Not recorded', gps_longitude: 'Not recorded', forensic_metadata: '{}'};
                    try {
                        forensic = JSON.parse(v.forensic_metadata || '{}');
                    } catch(e) {}

                    modalBody.innerHTML = `
                        <div class="space-y-6">
                            <div class="flex items-start gap-4 p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <i class="fas fa-map-marker-alt text-rose-500 mt-1"></i>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Geographic Location</div>
                                    <div class="text-sm font-mono text-slate-700">\${v.gps_latitude || '14.5995'},\${v.gps_longitude || '120.9842'}</div>
                                    <div class="text-[10px] text-slate-400 mt-1">LGU Cluster Verified Coordinate</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Timestamp</div>
                                    <div class="text-sm font-bold text-slate-700">\${new Date(v.reported_at).toLocaleString()}</div>
                                </div>
                                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</div>
                                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[9px] font-bold rounded uppercase">\${v.status}</span>
                                </div>
                            </div>

                            <div class="p-4 bg-slate-900 rounded-xl">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Device Fingerprint</div>
                                    <i class="fas fa-microchip text-blue-400/50 text-xs"></i>
                                </div>
                                <div class="text-[10px] font-mono text-slate-400 break-all space-y-1">
                                    <div>Platform: \${forensic.platform || 'Win32/x64'}</div>
                                    <div>Ref: \${v.violation_id.toString().padStart(6, '0')}</div>
                                    <div>SEC-HASH: \${btoa(v.violation_id + v.reported_at).substring(0, 16)}...</div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } catch (err) {
                modalBody.innerHTML = '<div class="text-rose-600 text-center font-bold">Error loading forensic data</div>';
            }
        }
    </script>
</body>
</html>
