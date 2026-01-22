<?php
declare(strict_types=1);
/**
 * Health & Safety Inspection System
 * Inspections List View
 */

// PSR-4 Autoloading and Bootstrap already handled by public/index.php if routed correctly,
// but for direct access we need to ensure environment is set up.
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
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
            $activePage = 'inspections';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 z-10 shrink-0">
                <h1 class="text-sm font-bold text-slate-700 tracking-tight">Inspection Management</h1>
                <div class="flex items-center space-x-3">
                    <button id="aiPrioritizeBtn"
                       class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg text-xs font-bold flex items-center border border-indigo-200 transition-all active:scale-95 group">
                        <i class="fas fa-microchip mr-2"></i> AI Insights
                    </button>
                    <a href="/inspections/create" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-xs font-bold flex items-center shadow-sm transition-all active:scale-95">
                        <i class="fas fa-plus mr-2"></i> Register Inspection
                    </a>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50">
                <!-- Filters Section -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Search Registry</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                    <i class="fas fa-search text-xs"></i>
                                </span>
                                <input type="text" name="search" value="<?php echo  htmlspecialchars($search) ?>" placeholder="Ref ID or Establishment..." 
                                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Operational Status</label>
                            <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2.5 px-3 text-sm text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition-all appearance-none cursor-pointer">
                                <option value="">All Logs</option>
                                <option value="pending" <?php echo  $status === 'pending' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="in_progress" <?php echo  $status === 'in_progress' ? 'selected' : '' ?>>In Field</option>
                                <option value="completed" <?php echo  $status === 'completed' ? 'selected' : '' ?>>Finalized</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Primary Category</label>
                            <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2.5 px-3 text-sm text-slate-600 focus:ring-1 focus:ring-blue-500 outline-none transition-all appearance-none cursor-pointer">
                                <option value="">All Categories</option>
                                <option value="food_safety" <?php echo  $type === 'food_safety' ? 'selected' : '' ?>>Food Services</option>
                                <option value="building_safety" <?php echo  $type === 'building_safety' ? 'selected' : '' ?>>Structural Registry</option>
                                <option value="fire_safety" <?php echo  $type === 'fire_safety' ? 'selected' : '' ?>>Fire Mitigation</option>
                                <option value="sanitation" <?php echo  $type === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white py-2.5 rounded-lg text-xs font-bold transition-all shadow-sm">
                                Apply Filter
                            </button>
                            <a href="/inspections" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs font-bold transition-all border border-slate-200 shadow-sm flex items-center justify-center">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Inspections Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <?php if ($inspections): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 border-b border-slate-100 text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4">Ref. ID</th>
                                        <th class="px-6 py-4">Establishment</th>
                                        <th class="px-6 py-4">Inspector</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Administrative</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($inspections as $inspection): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer" onclick="window.location='/inspections/view?id=<?php echo  $inspection['inspection_id'] ?>'">
                                            <td class="px-6 py-4 text-xs font-bold text-blue-700">HSI-<?php echo  str_pad((string)$inspection['inspection_id'], 5, '0', STR_PAD_LEFT) ?></td>
                                            <td class="px-6 py-4">
                                                <p class="text-sm font-bold text-slate-900 leading-tight"><?php echo  htmlspecialchars($inspection['establishment_name']) ?></p>
                                                <p class="text-[11px] text-slate-500 font-medium uppercase mt-1 tracking-wider"><?php echo  ucwords(str_replace('_', ' ', (string)$inspection['inspection_type'])) ?></p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <span class="text-sm font-medium text-slate-600"><?php echo  htmlspecialchars($inspection['inspector_name'] ?? 'Unassigned') ?></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $statusStyles = [
                                                        'pending' => 'bg-slate-100 text-slate-600 border-slate-200',
                                                        'in_progress' => 'bg-blue-50 text-blue-700 border-blue-100',
                                                        'completed' => 'bg-green-50 text-green-700 border-green-100',
                                                        'failed' => 'bg-rose-50 text-rose-700 border-rose-100',
                                                        'cancelled' => 'bg-slate-100 text-slate-400 border-slate-200'
                                                    ];
                                                    $label = [
                                                        'pending' => 'Scheduled',
                                                        'in_progress' => 'In Field',
                                                        'completed' => 'Finalized',
                                                        'failed' => 'Non-Compliant',
                                                        'cancelled' => 'Cancelled'
                                                    ];
                                                    $style = $statusStyles[$inspection['status']] ?? 'bg-slate-50 text-slate-500 border-slate-200';
                                                    $statusLabel = $label[$inspection['status']] ?? ucfirst((string)$inspection['status']);
                                                ?>
                                                <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider border <?php echo  $style ?>">
                                                    <?php echo  $statusLabel ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="/inspections/view?id=<?php echo  $inspection['inspection_id'] ?>" class="p-2 text-slate-400 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-all" title="View Records">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                    <button onclick="showActionDetails(<?php echo $inspection['inspection_id'] ?>)"
                                                       class="p-2 text-slate-400 hover:text-purple-700 hover:bg-purple-50 rounded-lg transition-all" title="AI Action Details">
                                                        <i class="fas fa-robot"></i>
                                                    </button>
                                                    <?php if ($inspection['status'] === 'pending'): ?>
                                                        <a href="/inspections/conduct?id=<?php echo  $inspection['inspection_id'] ?>" class="p-2 text-slate-400 hover:text-green-700 hover:bg-green-50 rounded-lg transition-all" title="Execute Inspection">
                                                            <i class="fas fa-clipboard-check"></i>
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
                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    Displaying <?php echo  count($inspections) ?> of <?php echo  $totalInspections ?> records
                                </div>
                                <div class="flex space-x-1">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo  $i ?>&status=<?php echo  $status ?>&type=<?php echo  $type ?>&search=<?php echo  urlencode($search) ?>" 
                                           class="px-3 py-1 rounded text-[10px] font-bold transition-all <?php echo  $i == $page ? 'bg-blue-700 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                                            <?php echo  $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="p-20 text-center">
                            <div class="w-20 h-20 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-6 border border-slate-100">
                                <i class="fas fa-clipboard-list text-2xl text-slate-300"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 mb-1">No inspections found</h3>
                            <p class="text-xs text-slate-400 mb-6 max-w-xs mx-auto">Access the registry or adjust filters to view operational logs.</p>
                            <a href="/inspections/create" class="inline-flex items-center px-6 py-2 bg-blue-700 text-white text-xs font-bold rounded-lg hover:bg-blue-800 transition-all shadow-sm">
                                <i class="fas fa-plus mr-2"></i> Register New Inspection
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- AI Prioritization Modal -->
    <div id="aiModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black/80 backdrop-blur-sm" aria-hidden="true" onclick="closeAIModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-[#15181e] rounded-3xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-8 border border-white/10">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-purple-600/20 rounded-2xl flex items-center justify-center border border-purple-500/30">
                            <i class="fas fa-robot text-purple-400 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white tracking-tight">Safety AI Optimization</h3>
                            <p class="text-xs font-black text-slate-500 uppercase tracking-widest">Predictive Risk Scheduling</p>
                        </div>
                    </div>
                    <button onclick="closeAIModal()" class="text-slate-500 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div id="aiContent" class="space-y-4">
                    <!-- Dynamic Content -->
                    <div class="flex flex-col items-center justify-center py-12 space-y-4">
                        <div class="w-16 h-16 border-4 border-purple-500/20 border-t-purple-500 rounded-full animate-spin"></div>
                        <p class="text-slate-400 font-medium">Gemini AI is analyzing establishment risk factors...</p>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button onclick="closeAIModal()" class="px-6 py-2.5 bg-white/5 hover:bg-white/10 text-white rounded-xl text-sm font-bold transition-all border border-white/10">
                        Dismiss
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- AI Action Details Modal -->
        <div id="actionDetailsModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
            <div class="bg-[#0f1115] border border-white/10 w-full max-w-2xl rounded-3xl overflow-hidden shadow-2xl">
                <div class="p-6 border-b border-white/5 flex justify-between items-center bg-gradient-to-r from-purple-900/20 to-transparent">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-600/20 rounded-xl flex items-center justify-center mr-4 border border-purple-500/30">
                            <i class="fas fa-brain text-purple-400"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-white tracking-tight">AI Action Details</h2>
                            <p class="text-[10px] text-purple-400 font-bold uppercase tracking-widest">Intelligent Inspection Analysis</p>
                        </div>
                    </div>
                    <button onclick="closeActionModal()" class="text-slate-500 hover:text-white transition-colors p-2 hover:bg-white/5 rounded-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="actionModalContent" class="p-8 max-h-[70vh] overflow-y-auto">
                    <!-- Dynamic Content -->
                </div>
                <div class="p-6 bg-white/5 border-t border-white/5 flex justify-end">
                    <button onclick="closeActionModal()" class="px-6 py-2.5 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white rounded-xl text-sm font-bold transition-all">
                        Dismiss
                    </button>
                </div>
            </div>
        </div>

    <script>
        const btn = document.getElementById('aiPrioritizeBtn');
        const modal = document.getElementById('aiModal');
        const content = document.getElementById('aiContent');

        function openAIModal() {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeAIModal() {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        const actionModal = document.getElementById('actionDetailsModal');
        const actionContent = document.getElementById('actionModalContent');

        function closeActionModal() {
            actionModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        async function showActionDetails(id) {
            actionModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            actionContent.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 space-y-4">
                    <div class="w-16 h-16 border-4 border-purple-500/20 border-t-purple-500 rounded-full animate-spin"></div>
                    <p class="text-slate-400 font-medium italic">Gemini AI is distilling historical data and inspection notes...</p>
                </div>
            `;

            try {
                // Fetch action details
                const res = await fetch(\`/api/v1/ai/action-details/\${id}\`);
                const result = await res.json();

                // Build HTML
                if (result.status === 'success') {
                    const data = result.data;
                    const ai = data.risk_assessment;
                    const recommendations = data.strategic_recommendations;
                    const audit = data.note_audit;

                    let html = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="bg-white/5 border border-white/5 p-5 rounded-2xl">
                                <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block mb-2">Predictive Risk Score</span>
                                <div class="flex items-end space-x-2">
                                    <span class="text-4xl font-black text-white">\${(ai.risk_score * 100).toFixed(1)}%</span>
                                    <span class="text-xs text-slate-500 font-bold mb-1 italic">Probability</span>
                                </div>
                                <div class="w-full bg-white/5 h-1.5 rounded-full mt-4 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500" style="width: \${ai.risk_score * 100}%"></div>
                                </div>
                            </div>
                            <div class="bg-white/5 border border-white/5 p-5 rounded-2xl">
                                <span class="text-[10px] text-slate-500 font-black uppercase tracking-widest block mb-1">Status Sentiment</span>
                                <div class="text-lg font-bold text-white mb-2">\${audit.summary_sentiment.toUpperCase()}</div>
                                <div class="px-3 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-lg inline-block text-[10px] font-black italic">
                                    \${(audit.compliance_confidence * 100).toFixed(0)}% Integrity Confidence
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <h3 class="text-sm font-black text-white uppercase tracking-widest mb-4 flex items-center">
                                    <i class="fas fa-magic text-purple-400 mr-2"></i> Strategic Recommendations
                                </h3>
                                <div class="space-y-3">
                                    \${recommendations.map(rec => \`
                                        <div class="bg-purple-600/5 border border-purple-500/10 p-4 rounded-xl flex items-start space-x-3">
                                            <i class="fas fa-arrow-right text-purple-500 mt-1 text-xs"></i>
                                            <p class="text-sm text-slate-300 italic">\${rec}</p>
                                        </div>
                                    \`).join('')}
                                </div>
                            </div>

                            <div class="bg-blue-600/5 border border-blue-500/10 p-6 rounded-2xl">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-sm font-black text-white uppercase tracking-widest">Forensic Chain of Custody</h3>
                                    <i class="fas fa-fingerprint text-blue-400"></i>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-4">
                                    <span class="px-2 py-1 bg-blue-500/10 border border-blue-500/20 text-[9px] text-blue-300 rounded font-bold uppercase tracking-tighter">GPS: LOCKED</span>
                                    <span class="px-2 py-1 bg-blue-500/10 border border-blue-500/20 text-[9px] text-blue-300 rounded font-bold uppercase tracking-tighter">ID: VERIFIED</span>
                                    <span class="px-2 py-1 bg-blue-500/10 border border-blue-500/20 text-[9px] text-blue-300 rounded font-bold uppercase tracking-tighter">AUTH: SECURE</span>
                                </div>
                            </div>
                        </div>
                    \`;
                    actionContent.innerHTML = html;
                } else {
                    actionContent.innerHTML = \`<div class="p-8 text-center text-rose-400">Error: \${result.message}</div>\`;
                }
            } catch (err) {
                actionContent.innerHTML = \`<div class="p-8 text-center text-rose-400">Connection Error mapping AI Logic: \${err.message}</div>\`;
            }
        }

        btn.addEventListener('click', async () => {
            openAIModal();
            content.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 space-y-4">
                    <div class="w-16 h-16 border-4 border-purple-500/20 border-t-purple-500 rounded-full animate-spin"></div>
                    <p class="text-slate-400 font-medium">Analyzing real-time health data and risk indicators...</p>
                </div>
            `;

            try {
                const today = new Date().toISOString().split('T')[0];
                const response = await fetch(\`/api/v1/inspections?prioritize=true&date=\${today}\`);
                const result = await response.json();

                if (result.status === 'success') {
                    const data = result.data.data;
                    
                    if (data.length === 0) {
                        content.innerHTML = \`
                            <div class="bg-blue-900/20 border border-blue-500/30 p-6 rounded-2xl text-center">
                                <i class="fas fa-info-circle text-blue-400 text-3xl mb-3"></i>
                                <p class="text-slate-300">No pending inspections found for today's AI prioritization queue.</p>
                            </div>
                        \`;
                        return;
                    }

                    let html = \`
                        <div class="bg-indigo-950/30 border border-indigo-500/20 p-4 rounded-2xl mb-6">
                            <p class="text-sm text-indigo-300 leading-relaxed">
                                <i class="fas fa-sparkles mr-1"></i> AI has ranked today's queue based on <strong>Predictive Risk Scoring</strong>. Establishments with critical histories or high crowd density indicators are prioritized first.
                            </p>
                        </div>
                        <div class="space-y-3">
                    \`;

                    data.forEach((item, index) => {
                        const riskColor = item.risk_category === 'high' ? 'red' : (item.risk_category === 'medium' ? 'yellow' : 'green');
                        html += \`
                            <div class="bg-white/5 border border-white/5 p-4 rounded-2xl flex items-center justify-between hover:bg-white/10 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="text-lg font-black text-slate-700 w-6">\${index + 1}</div>
                                    <div>
                                        <div class="text-sm font-bold text-white">\${item.establishment_name || 'Establishment #' + item.establishment_id}</div>
                                        <div class="flex items-center space-x-2 mt-1">
                                            <span class="text-[10px] uppercase font-black px-2 py-0.5 rounded bg-\${riskColor}-500/20 text-\${riskColor}-400 border border-\${riskColor}-500/30">
                                                \${item.risk_category.toUpperCase()} RISK
                                            </span>
                                            <span class="text-[10px] text-slate-500 font-bold">\${item.inspection_type.replace('_', ' ').toUpperCase()}</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="/inspections/conduct.php?id=\${item.inspection_id}" class="text-blue-400 hover:text-blue-300 transition-colors">
                                    <i class="fas fa-chevron-right text-sm"></i>
                                </a>
                            </div>
                        \`;
                    });

                    html += '</div>';
                    content.innerHTML = html;
                } else {
                    throw new Error(result.message || 'AI engine failed to respond');
                }
            } catch (error) {
                console.error(error);
                content.innerHTML = \`
                    <div class="bg-red-900/20 border border-red-500/30 p-6 rounded-2xl text-center">
                        <i class="fas fa-exclamation-triangle text-red-400 text-3xl mb-3"></i>
                        <p class="text-slate-300">AI Optimization Engine Error: \${error.message}</p>
                    </div>
                \`;
            }
        });
    </script>
</body>
</html>
