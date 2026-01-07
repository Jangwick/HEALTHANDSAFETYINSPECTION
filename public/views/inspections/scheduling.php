<?php
/**
 * Health & Safety Inspection System
 * Inspection Scheduling Dashboard
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
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
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'scheduling';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-xl font-bold text-slate-800">Inspection Scheduling</h1>
                <div class="flex items-center space-x-4">
                    <a href="/views/inspections/create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center shadow-sm transition-all active:scale-95">
                        <i class="fas fa-calendar-plus mr-2"></i> Schedule Inspection
                    </a>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-hover hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                                <i class="fas fa-calendar-day text-xl"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-slate-900"><?= $stats['today'] ?></div>
                        <div class="text-sm text-slate-500 font-medium">Scheduled Today</div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-hover hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600">
                                <i class="fas fa-calendar-week text-xl"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-slate-900"><?= $stats['this_week'] ?></div>
                        <div class="text-sm text-slate-500 font-medium">Coming Up (7 Days)</div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-hover hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-2 bg-rose-50 rounded-lg text-rose-600">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-slate-900"><?= $stats['overdue'] ?></div>
                        <div class="text-sm text-slate-500 font-medium text-rose-600">Overdue Inspections</div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-hover hover:shadow-md">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
                                <i class="fas fa-hourglass-half text-xl"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-slate-900"><?= $stats['total_pending'] ?></div>
                        <div class="text-sm text-slate-500 font-medium">Total Pending</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Upcoming List -->
                    <div class="lg:col-span-2 space-y-8">
                        <section>
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-bold text-slate-800 flex items-center">
                                    <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                    Upcoming Inspections
                                </h2>
                            </div>
                            
                            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                                <?php if (empty($upcomingInspections)): ?>
                                    <div class="p-8 text-center text-slate-500 italic">No upcoming inspections scheduled.</div>
                                <?php else: ?>
                                    <table class="w-full text-left border-collapse text-sm">
                                        <thead>
                                            <tr class="bg-slate-50 border-b border-slate-200">
                                                <th class="px-6 py-4 font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                                <th class="px-6 py-4 font-bold text-slate-500 uppercase tracking-wider">Establishment</th>
                                                <th class="px-6 py-4 font-bold text-slate-500 uppercase tracking-wider">Inspector</th>
                                                <th class="px-6 py-4 font-bold text-slate-500 uppercase tracking-wider">Priority</th>
                                                <th class="px-6 py-4 font-bold text-slate-500 uppercase tracking-wider text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <?php foreach ($upcomingInspections as $insp): ?>
                                                <?php 
                                                    $dateColor = $insp['scheduled_date'] == $today ? 'text-blue-600 font-bold' : 'text-slate-600';
                                                    $priorityClass = [
                                                        'urgent' => 'text-rose-600 bg-rose-50',
                                                        'high' => 'text-amber-600 bg-amber-50',
                                                        'medium' => 'text-blue-600 bg-blue-50',
                                                        'low' => 'text-slate-600 bg-slate-50'
                                                    ][$insp['priority']] ?? 'text-slate-600 bg-slate-50';
                                                ?>
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <td class="px-6 py-4 <?= $dateColor ?>">
                                                        <?= date('M d, Y', strtotime($insp['scheduled_date'])) ?>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars($insp['establishment_name']) ?></div>
                                                        <div class="text-xs text-slate-400"><?= ucwords(str_replace('_', ' ', $insp['inspection_type'])) ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 text-slate-600">
                                                        <?= htmlspecialchars($insp['inspector_name'] ?: 'Unassigned') ?>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $priorityClass ?>">
                                                            <?= $insp['priority'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <a href="/views/inspections/view.php?id=<?= $insp['inspection_id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <!-- Side Panel: Overdue & Quick Tasks -->
                    <div class="space-y-8">
                        <section>
                            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                                <i class="fas fa-exclamation-circle mr-2 text-rose-500"></i>
                                Overdue
                            </h2>
                            <div class="space-y-3">
                                <?php if (empty($overdueInspections)): ?>
                                    <div class="p-4 bg-emerald-50 text-emerald-700 rounded-lg text-sm border border-emerald-100 italic">
                                        Great! You have no overdue inspections.
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($overdueInspections, 0, 5) as $insp): ?>
                                        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden group">
                                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-rose-500"></div>
                                            <div class="flex justify-between items-start mb-2">
                                                <span class="text-xs font-bold text-rose-500 uppercase"><?= date('M d', strtotime($insp['scheduled_date'])) ?></span>
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-rose-50 text-rose-600 border border-rose-100">LATE</span>
                                            </div>
                                            <h3 class="font-bold text-slate-900 truncate"><?= htmlspecialchars($insp['establishment_name']) ?></h3>
                                            <p class="text-xs text-slate-500 mb-3 line-clamp-1"><?= ucwords(str_replace('_', ' ', $insp['inspection_type'])) ?></p>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-slate-400 italic">Assignee: <?= htmlspecialchars($insp['inspector_name'] ?: 'None') ?></span>
                                                <a href="/views/inspections/view.php?id=<?= $insp['inspection_id'] ?>" class="text-xs font-bold text-blue-600 hover:underline">Re-schedule</a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($overdueInspections) > 5): ?>
                                        <button class="w-full py-2 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">
                                            View all <?= count($overdueInspections) ?> overdue
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </section>

                        <div class="bg-slate-900 rounded-2xl p-6 text-white shadow-xl relative overflow-hidden">
                            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-blue-600/20 rounded-full blur-3xl"></div>
                            <h3 class="text-lg font-bold mb-2">Need Help?</h3>
                            <p class="text-slate-400 text-sm mb-4">To automatically generate an inspection schedule based on risk factors, use the AI Assistant.</p>
                            <button class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition-all flex items-center justify-center">
                                <i class="fas fa-robot mr-2"></i> Launch Smart Scheduler
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
