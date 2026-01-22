<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$inspectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$inspectionId) {
    header('Location: /inspections');
    exit;
}

try {
    $db = Database::getConnection();
    
    // Get inspection details with establishment and inspector info
    $stmt = $db->prepare("
        SELECT 
            i.*,
            e.name AS establishment_name,
            e.type AS establishment_type,
            e.address_street, e.address_barangay, e.address_city,
            e.owner_name, e.owner_phone, e.owner_email,
            u.first_name AS inspector_first_name,
            u.last_name AS inspector_last_name
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        LEFT JOIN users u ON i.inspector_id = u.user_id
        WHERE i.inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        header('Location: /views/inspections/list.php');
        exit;
    }
    
    // Get checklist responses
    $stmt = $db->prepare("
        SELECT cr.*, ci.requirement_text, ci.category, ci.points_possible
        FROM inspection_checklist_responses cr
        JOIN checklist_items ci ON cr.checklist_item_id = ci.item_id
        WHERE cr.inspection_id = ?
        ORDER BY ci.category, ci.order_sequence
    ");
    $stmt->execute([$inspectionId]);
    $checklistResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $checklistByCategory = [];
    foreach ($checklistResponses as $response) {
        $checklistByCategory[$response['category'] ?? 'General'][] = $response;
    }
    
    // Get violations
    $stmt = $db->prepare("SELECT * FROM violations WHERE inspection_id = ? ORDER BY severity DESC");
    $stmt->execute([$inspectionId]);
    $violationsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Score calculation
    $totalPoints = 0; $earnedPoints = 0;
    foreach ($checklistResponses as $res) {
        $totalPoints += (int)$res['points_possible'];
        if ($res['response'] === 'pass') $earnedPoints += (int)$res['points_possible'];
    }
    $complianceScore = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Institutional Record Retrieval Failure");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Dossier #<?= $inspection['inspection_id'] ?> - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            h1, h2, h3 { @apply font-bold tracking-tight text-slate-900; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
            .status-tag { @apply px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest border; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'inspections';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-base">
            <!-- Institutional Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-10">
                <div class="flex items-center space-x-4">
                    <a href="/inspections" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Audit Dossier</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic">Reference #<?= htmlspecialchars($inspection['reference_number']) ?></span>
                </div>
                <div class="flex items-center space-x-3">
                    <?php if ($inspection['status'] === 'completed'): ?>
                        <button class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-xs font-bold transition-all shadow-sm">
                            <i class="fas fa-print mr-2 opacity-70"></i> Official Report
                        </button>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Column 1: Core Audit Parameters -->
                    <div class="lg:col-span-3 space-y-8">
                        <!-- Audit Status Banner -->
                        <div class="card p-8 bg-white relative">
                            <div class="absolute top-0 left-0 w-full h-1 bg-blue-700"></div>
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <div>
                                    <div class="flex items-center space-x-3 mb-4">
                                        <?php
                                            $stateStyles = [
                                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                'in_progress' => 'bg-amber-50 text-amber-700 border-amber-100',
                                                'scheduled' => 'bg-blue-50 text-blue-700 border-blue-100',
                                                'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200'
                                            ];
                                            $s = $stateStyles[$inspection['status']] ?? 'bg-slate-50 text-slate-400 border-slate-100';
                                        ?>
                                        <span class="status-tag <?= $s ?> italic">
                                            <i class="fas fa-circle mr-1 text-[6px]"></i> <?= str_replace('_', ' ', $inspection['status']) ?>
                                        </span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest font-mono">
                                            <?= htmlspecialchars($inspection['reference_number']) ?>
                                        </span>
                                    </div>
                                    <h2 class="text-2xl font-black text-slate-900 mb-2 uppercase tracking-tight">
                                        <?= htmlspecialchars($inspection['establishment_name']) ?>
                                    </h2>
                                    <div class="flex items-center space-x-4 text-[11px] font-medium text-slate-500 italic">
                                        <span><i class="far fa-calendar-alt mr-2 text-blue-700"></i> Scheduled: <?= date('M d, Y', strtotime($inspection['scheduled_date'])) ?></span>
                                        <?php if($inspection['completion_date']): ?>
                                            <span><i class="far fa-check-circle mr-2 text-emerald-600"></i> Finalized: <?= date('M d, Y', strtotime($inspection['completion_date'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="bg-slate-50 border border-slate-100 rounded-2xl p-6 flex flex-col items-center justify-center min-w-[140px]">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Compliance Score</span>
                                    <span class="text-3xl font-black <?= $complianceScore >= 80 ? 'text-emerald-600' : ($complianceScore >= 60 ? 'text-amber-500' : 'text-rose-600') ?>">
                                        <?= $complianceScore ?>%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist Dossier -->
                        <div class="space-y-6">
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] flex items-center px-2">
                                <i class="fas fa-tasks mr-3 text-blue-700"></i> Operational Audit Checklist
                            </h3>
                            
                            <?php foreach ($checklistByCategory as $cat => $items): ?>
                                <div class="card overflow-hidden">
                                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                                        <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-wider italic"><?= htmlspecialchars($cat) ?></h4>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase"><?= count($items) ?> Parameters</span>
                                    </div>
                                    <div class="divide-y divide-slate-50">
                                        <?php foreach ($items as $item): ?>
                                            <div class="p-6 flex items-start space-x-4 hover:bg-slate-50/50 transition-colors">
                                                <div class="shrink-0 mt-1">
                                                    <?php if ($item['response'] === 'pass'): ?>
                                                        <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                                                    <?php elseif ($item['response'] === 'fail'): ?>
                                                        <i class="fas fa-times-circle text-rose-500 text-lg"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-minus-circle text-slate-300 text-lg"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="text-[13px] font-bold text-slate-800 leading-relaxed mb-1"><?= htmlspecialchars($item['requirement_text']) ?></p>
                                                    <?php if ($item['comments']): ?>
                                                        <p class="text-[11px] text-slate-500 italic bg-blue-50/50 p-2 rounded-lg border-l-2 border-blue-200 mt-2">
                                                            "<?= htmlspecialchars($item['comments']) ?>"
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="shrink-0 text-right">
                                                    <span class="text-[10px] font-mono font-bold text-slate-400"><?= $item['response'] === 'pass' ? $item['points_possible'] : 0 ?>/<?= $item['points_possible'] ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Column 2: Audit Personnel & Metadata -->
                    <div class="space-y-8">
                        <!-- Audit Personnel -->
                        <div class="card p-6 bg-slate-800 text-white shadow-lg shadow-slate-200/50">
                            <h3 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Personnel Assigned</h3>
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center font-black text-xs">
                                    <?= substr($inspection['inspector_first_name'], 0, 1) ?><?= substr($inspection['inspector_last_name'], 0, 1) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-black italic"><?= htmlspecialchars($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']) ?></p>
                                    <p class="text-[9px] text-slate-400 uppercase tracking-tighter">Authorized Inspector</p>
                                </div>
                            </div>
                        </div>

                        <!-- Non-Compliance Ledger -->
                        <div class="card">
                            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Found Violations</h3>
                                <span class="px-2 py-0.5 bg-rose-50 text-rose-600 border border-rose-100 rounded text-[9px] font-black italic"><?= count($violationsList) ?></span>
                            </div>
                            <div class="p-4 space-y-3">
                                <?php if (!empty($violationsList)): ?>
                                    <?php foreach ($violationsList as $v): ?>
                                        <div class="p-4 border border-rose-100 bg-rose-50/20 rounded-xl relative overflow-hidden group">
                                            <div class="absolute top-0 right-0 p-2 opacity-10 group-hover:opacity-30 transition-opacity">
                                                <i class="fas fa-exclamation-triangle text-rose-500"></i>
                                            </div>
                                            <div class="text-[10px] font-black text-rose-700 uppercase tracking-tighter mb-1"><?= $v['severity'] ?> Severity</div>
                                            <p class="text-[11px] font-bold text-slate-800 leading-tight mb-2 italic">"<?= htmlspecialchars($v['description']) ?>"</p>
                                            <div class="flex items-center justify-between text-[9px] font-bold text-rose-400 uppercase">
                                                <span>Status: <?= $v['status'] ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-center py-6 text-[10px] text-slate-400 italic uppercase">Zero violations logged.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Institutional Reference Card -->
                        <div class="card p-6 border-l-4 border-l-blue-700">
                            <h3 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Entity Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Registered Principal</p>
                                    <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($inspection['owner_name']) ?></p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Official Hotlines</p>
                                    <p class="text-xs font-mono font-bold text-blue-700"><?= htmlspecialchars($inspection['owner_phone']) ?></p>
                                </div>
                                <div>
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1">Jurisdiction</p>
                                    <p class="text-[10px] font-medium text-slate-600 italic">
                                        <?= htmlspecialchars($inspection['address_barangay']) ?>, <?= htmlspecialchars($inspection['address_city']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Formal Audit Metadata -->
                        <div class="p-6 bg-blue-50 rounded-xl border border-blue-100">
                            <h4 class="text-[9px] font-black text-blue-700 uppercase tracking-widest mb-2">Audit Traceability</h4>
                            <p class="text-[10px] text-blue-600 italic leading-relaxed">
                                This dossier represents a formal regulatory audit. Digital signatures and timestamps are hard-linked to the departmental database.
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
