<?php
/**
 * Health & Safety Inspection System
 * Inspection Scheduling Dashboard
 */

declare(strict_types=1);

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
$stats = [
    'today' => $db->query("SELECT COUNT(*) FROM inspections WHERE scheduled_date = '$today' AND status = 'pending'")->fetchColumn(),
    'this_week' => $db->query("SELECT COUNT(*) FROM inspections WHERE scheduled_date BETWEEN '$today' AND '$nextWeek' AND status = 'pending'")->fetchColumn(),
    'overdue' => $db->query("SELECT COUNT(*) FROM inspections WHERE scheduled_date < '$today' AND status = 'pending'")->fetchColumn(),
    'total_pending' => $db->query("SELECT COUNT(*) FROM inspections WHERE status = 'pending'")->fetchColumn()
];

// Get upcoming inspections
$upcomingSql = "
    SELECT i.*, 
           e.name as establishment_name, 
           CONCAT(u.first_name, ' ', u.last_name) as inspector_name
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
           CONCAT(u.first_name, ' ', u.last_name) as inspector_name
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
    <title>Scheduling - Health & Safety System</title>
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
            $activePage = 'scheduling';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <div class="flex flex-col">
                    <h1 class="text-xl font-bold text-white tracking-tight">Inspection Scheduling</h1>
                    <p class="text-xs text-slate-400">Manage and monitor field assignments</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/inspections/create" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-blue-900/20 transition-all active:scale-95 group">
                        <i class="fas fa-calendar-plus mr-2 group-hover:rotate-12 transition-transform"></i> Schedule Inspection
                    </a>
                </div>
            </header>

            <!-- Scrollable Content Area -->
                        <!-- Main Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8 text-base">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-sm transition-hover">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
                                <i class="fas fa-calendar-day text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-white"><?php echo  $stats['today'] ?></div>
                        <div class="text-sm text-slate-400 font-medium tracking-wide">Scheduled Today</div>
                    </div>
                    
                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-sm transition-hover">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-500">
                                <i class="fas fa-calendar-week text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-white"><?php echo  $stats['this_week'] ?></div>
                        <div class="text-sm text-slate-400 font-medium tracking-wide">Coming Up (7 Days)</div>
                    </div>

                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-sm transition-hover">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-rose-500/10 rounded-xl text-rose-500">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-white"><?php echo  $stats['overdue'] ?></div>
                        <div class="text-sm text-rose-400 font-medium tracking-wide">Overdue Inspections</div>
                    </div>

                    <div class="bg-[#15181e] p-6 rounded-2xl border border-white/5 shadow-sm transition-hover">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-amber-500/10 rounded-xl text-amber-600">
                                <i class="fas fa-hourglass-half text-xl"></i>
                            </div>
                        </div>
                        <div class="text-3xl font-black text-white"><?php echo  $stats['total_pending'] ?></div>
                        <div class="text-sm text-slate-400 font-medium tracking-wide">Total Pending</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8">
                    <!-- Upcoming List -->
                    <div class="space-y-8">
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-sm font-bold text-slate-400 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                    Upcoming Inspections
                                </h2>
                            </div>
                            
                            <div class="bg-[#15181e] rounded-2xl border border-white/5 shadow-xl overflow-hidden">
                                <?php if (empty($upcomingInspections)): ?>
                                    <div class="p-12 text-center text-slate-500 italic">No upcoming inspections scheduled.</div>
                                <?php else: ?>
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-black/20 border-b border-white/5">
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Date</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Establishment</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Inspector</th>
                                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-white/5">
                                            <?php foreach ($upcomingInspections as $insp): ?>
                                                <?php 
                                                    $isToday = $insp['scheduled_date'] == $today;
                                                    $priorityClass = [
                                                        'urgent' => 'bg-rose-500/10 text-rose-500',
                                                        'high' => 'bg-amber-500/10 text-amber-500',
                                                        'medium' => 'bg-blue-500/10 text-blue-500',
                                                        'low' => 'bg-slate-500/10 text-slate-500'
                                                    ][$insp['priority']] ?? 'bg-slate-500/10 text-slate-500';
                                                ?>
                                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                                    <td class="px-6 py-4">
                                                        <div class="text-sm <?= $isToday ? 'text-blue-400 font-bold' : 'text-slate-300' ?>">
                                                            <?= date('M d', strtotime($insp['scheduled_date'])) ?>
                                                        </div>
                                                        <div class="text-[10px] text-slate-500 uppercase"><?= date('D', strtotime($insp['scheduled_date'])) ?></div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="font-bold text-white group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($insp['establishment_name']) ?></div>
                                                        <div class="flex items-center space-x-2 mt-1">
                                                            <span class="text-[10px] px-1.5 py-0.5 rounded <?= $priorityClass ?> font-bold uppercase tracking-tighter">
                                                                <?= $insp['priority'] ?>
                                                            </span>
                                                            <span class="text-[10px] text-slate-500"><?= ucwords(str_replace('_', ' ', $insp['inspection_type'])) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center">
                                                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mr-3 text-[10px] font-black text-blue-400 uppercase">
                                                                <?= substr($insp['inspector_name'] ?: 'UN', 0, 2) ?>
                                                            </div>
                                                            <div class="text-sm text-slate-300"><?= htmlspecialchars($insp['inspector_name'] ?: 'Unassigned') ?></div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <a href="/inspections/view?id=<?= $insp['inspection_id'] ?>" class="p-2 hover:bg-blue-500/20 text-blue-400 rounded-lg transition-colors inline-block">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); }
    </style>
</body>
</html>
