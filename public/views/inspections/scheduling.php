<?php
declare(strict_types=1);
/**
 * Health & Safety Inspection System
 * Inspection Scheduling Registry
 */

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Get summary stats
$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));

// Statistics
$stmtStats = $db->prepare("SELECT COUNT(*) FROM inspections WHERE scheduled_date = ? AND status = 'pending'");
$stmtStats->execute([$today]);
$todayCount = $stmtStats->fetchColumn();

$stmtWeek = $db->prepare("SELECT COUNT(*) FROM inspections WHERE scheduled_date BETWEEN ? AND ? AND status = 'pending'");
$stmtWeek->execute([$today, $nextWeek]);
$weekCount = $stmtWeek->fetchColumn();

$stmtOverdue = $db->prepare("SELECT COUNT(*) FROM inspections WHERE scheduled_date < ? AND status = 'pending'");
$stmtOverdue->execute([$today]);
$overdueCount = $stmtOverdue->fetchColumn();

$totalPending = $db->query("SELECT COUNT(*) FROM inspections WHERE status = 'pending'")->fetchColumn();

$stats = [
    'today' => $todayCount,
    'this_week' => $weekCount,
    'overdue' => $overdueCount,
    'total_pending' => $totalPending
];

// Get upcoming inspections
$upcomingSql = "
    SELECT i.*, 
           e.name as establishment_name, 
           CONCAT(u.first_name, ' ', u.last_name) as inspector_full_name
    FROM inspections i
    LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
    LEFT JOIN users u ON i.inspector_id = u.user_id
    WHERE i.scheduled_date >= ? AND i.status = 'pending'
    ORDER BY i.scheduled_date ASC
    LIMIT 20
";
$stmt = $db->prepare($upcomingSql);
$stmt->execute([$today]);
$upcomingInspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overdue inspections
$overdueSql = "
    SELECT i.*, 
           e.name as establishment_name, 
           CONCAT(u.first_name, ' ', u.last_name) as inspector_full_name
    FROM inspections i
    LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
    LEFT JOIN users u ON i.inspector_id = u.user_id
    WHERE i.scheduled_date < ? AND i.status = 'pending'
    ORDER BY i.scheduled_date DESC
";
$stmt = $db->prepare($overdueSql);
$stmt->execute([$today]);
$overdueInspections = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Protocol Scheduling - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            .registry-table th { @apply px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 bg-slate-50/50; }
            .registry-table td { @apply px-6 py-4 text-sm border-b border-slate-50; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'scheduling';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">
            <!-- Institutional Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0 z-20">
                <div class="flex items-center space-x-4">
                    <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-800">Deployment & Scheduling</h1>
                    <div class="px-3 py-1 bg-blue-50 border border-blue-100 rounded-full">
                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest leading-none">Resource Allocation</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/inspections/create" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-lg shadow-blue-500/20 flex items-center">
                        <i class="fas fa-calendar-plus mr-2 text-[10px]"></i>
                        Create Assignment
                    </a>
                </div>
            </header>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-y-auto bg-[#f8fafc] p-10">
                <div class="max-w-7xl mx-auto space-y-8">
                    
                    <!-- Page Intro -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Scheduling Matrix</h2>
                            <p class="text-slate-500 mt-2 font-medium italic">Real-time inspection assignments and protocol distribution metrics.</p>
                        </div>
                    </div>
                    
                    <!-- Performance & Assignment Matrix -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="card relative p-6">
                            <div class="absolute top-0 left-0 w-1 h-full bg-blue-700"></div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Active Today</p>
                            <p class="text-3xl font-black text-slate-800 italic"><?= $stats['today'] ?></p>
                            <p class="text-[10px] text-slate-400 mt-2 uppercase font-medium">Pending Protocol Deployments</p>
                        </div>
                        <div class="card p-6">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Weekly Forecast</p>
                            <p class="text-3xl font-black text-slate-800 italic"><?= $stats['this_week'] ?></p>
                            <p class="text-[10px] text-slate-400 mt-2 uppercase font-medium">Scheduled next 7 days</p>
                        </div>
                        <div class="card p-6 border-rose-100">
                            <p class="text-xs font-bold text-rose-400 uppercase tracking-widest mb-1">Protocol Delays</p>
                            <p class="text-3xl font-black text-rose-600 italic"><?= $stats['overdue'] ?></p>
                            <p class="text-[10px] text-rose-400 mt-2 uppercase font-medium italic">Action Required Immediate</p>
                        </div>
                        <div class="card p-6">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Master Ledger</p>
                            <p class="text-3xl font-black text-slate-800 italic"><?= $stats['total_pending'] ?></p>
                            <p class="text-[10px] text-slate-400 mt-2 uppercase font-medium">Total Pending Assignments</p>
                        </div>
                    </div>

                    <!-- Critical Alert Section (Overdue) -->
                    <?php if (!empty($overdueInspections)): ?>
                        <div class="card border-rose-200">
                            <div class="px-6 py-4 bg-rose-50/50 border-b border-rose-100 flex items-center justify-between">
                                <h2 class="text-xs font-black text-rose-700 uppercase tracking-widest flex items-center italic">
                                    <i class="fas fa-clock mr-2"></i> Outstanding Audit Protocol Delays
                                </h2>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left registry-table">
                                    <tbody>
                                        <?php foreach ($overdueInspections as $insp): ?>
                                            <tr class="hover:bg-rose-50/30 transition-colors">
                                                <td class="w-1/4">
                                                    <div class="text-[11px] font-black text-rose-600 uppercase tracking-tighter italic">Delayed Since <?= date('M d, Y', strtotime($insp['scheduled_date'])) ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-xs font-bold text-slate-800 uppercase"><?= htmlspecialchars($insp['establishment_name']) ?></div>
                                                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Assigned: <?= htmlspecialchars($insp['inspector_full_name'] ?: 'Unassigned') ?></div>
                                                </td>
                                                <td class="text-right">
                                                    <a href="/inspections/conduct?id=<?= $insp['inspection_id'] ?>" class="bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-bold px-4 py-2 rounded shadow-sm uppercase tracking-widest italic transition-all">
                                                        Deploy Personnel
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Primary Scheduling Ledger -->
                    <div class="card">
                        <div class="px-8 py-5 border-b border-slate-100 flex items-center justify-between bg-white">
                            <h2 class="text-[10px] font-black text-slate-800 uppercase tracking-widest flex items-center italic">
                                <i class="fas fa-list-check mr-3 text-blue-700"></i> Deployment Registry Forecast
                            </h2>
                            <span class="text-[9px] font-bold text-slate-300 uppercase italic">Limited to next 20 entries</span>
                        </div>
                        
                        <?php if (empty($upcomingInspections)): ?>
                            <div class="p-16 text-center">
                                <div class="bg-slate-50 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-200">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No Protocol Assignments Found</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left registry-table">
                                    <thead>
                                        <tr>
                                            <th>Deployment Date</th>
                                            <th>Subject Entity</th>
                                            <th>Operation Type</th>
                                            <th>Personnel</th>
                                            <th class="text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php foreach ($upcomingInspections as $insp): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                                <td>
                                                    <div class="text-xs font-black text-slate-800"><?= date('M d, Y', strtotime($insp['scheduled_date'])) ?></div>
                                                    <div class="text-[9px] text-slate-400 font-bold uppercase italic"><?= date('l', strtotime($insp['scheduled_date'])) ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-xs font-bold text-slate-800 group-hover:text-blue-700 transition-colors uppercase"><?= htmlspecialchars($insp['establishment_name']) ?></div>
                                                    <div class="text-[10px] text-slate-400 font-medium italic">Ref: <?= $insp['reference_number'] ?></div>
                                                </td>
                                                <td>
                                                    <span class="inline-block px-2 py-1 bg-slate-100 text-[10px] font-black text-slate-500 rounded uppercase tracking-tighter">
                                                        <?= str_replace('_', ' ', $insp['inspection_type']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="flex items-center space-x-2">
                                                        <div class="w-6 h-6 rounded bg-blue-50 flex items-center justify-center text-[10px] text-blue-700 font-bold">
                                                            <?= strtoupper(substr($insp['inspector_full_name'] ?: 'U', 0, 1)) ?>
                                                        </div>
                                                        <span class="text-xs font-semibold text-slate-600"><?= htmlspecialchars($insp['inspector_full_name'] ?: 'Not Assigned') ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <a href="/inspections/view?id=<?= $insp['inspection_id'] ?>" class="text-[10px] font-black text-slate-300 hover:text-blue-700 uppercase tracking-widest transition-colors italic">View Protocol</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
