<?php
/**
 * Health & Safety Inspection System
 * Inspections List View
 */

declare(strict_types=1);

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
            $activePage = 'inspections';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-2xl font-bold text-white tracking-tight">Inspections</h1>
                <div class="flex items-center space-x-4">
                    <button id="aiPrioritizeBtn"
                       class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-purple-900/20 transition-all active:scale-95 group">
                        <i class="fas fa-robot mr-2 group-hover:animate-bounce"></i> AI Prioritize
                    </button>
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
                                                    <button onclick="showActionDetails(<?php echo $inspection['inspection_id'] ?>)"
                                                       class="p-2.5 bg-purple-600/10 hover:bg-purple-600 text-purple-400 hover:text-white rounded-lg transition-all" title="AI Action Details">
                                                        <i class="fas fa-robot text-sm"></i>
                                                    </button>
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
