<?php
declare(strict_types=1);
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$inspectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$download = isset($_GET['download']) ? true : false;

if (!$inspectionId) {
    die('Invalid inspection ID');
}

try {
    $db = Database::getConnection();
    
    // Get inspection details with all related data
    $stmt = $db->prepare("
        SELECT 
            i.*,
            e.name AS establishment_name,
            e.type AS establishment_type,
            e.address_street,
            e.address_barangay,
            e.address_city,
            e.owner_name,
            e.owner_contact,
            u.first_name AS inspector_first_name,
            u.last_name AS inspector_last_name,
            u.email AS inspector_email
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        LEFT JOIN users u ON i.inspector_id = u.user_id
        WHERE i.inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        die('Inspection not found');
    }
    
    // Get checklist responses
    $stmt = $db->prepare("
        SELECT 
            cr.*,
            ci.requirement_text,
            ci.category,
            ci.points_possible,
            ci.mandatory
        FROM inspection_checklist_responses cr
        JOIN checklist_items ci ON cr.checklist_item_id = ci.item_id
        WHERE cr.inspection_id = ?
        ORDER BY ci.category, ci.order_sequence
    ");
    $stmt->execute([$inspectionId]);
    $checklistResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by category
    $checklistByCategory = [];
    foreach ($checklistResponses as $response) {
        $category = $response['category'] ?? 'General';
        if (!isset($checklistByCategory[$category])) {
            $checklistByCategory[$category] = [];
        }
        $checklistByCategory[$category][] = $response;
    }
    
    // Get violations
    $stmt = $db->prepare("
        SELECT * FROM violations
        WHERE inspection_id = ?
        ORDER BY severity DESC, created_at DESC
    ");
    $stmt->execute([$inspectionId]);
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate score
    $totalPoints = 0;
    $earnedPoints = 0;
    $passCount = 0;
    $failCount = 0;
    
    foreach ($checklistResponses as $response) {
        $totalPoints += (int)$response['points_possible'];
        if ($response['response'] === 'pass') {
            $earnedPoints += (int)$response['points_possible'];
            $passCount++;
        } elseif ($response['response'] === 'fail') {
            $failCount++;
        }
    }
    
    $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
    
    // Generate compliance rating
    if ($score >= 90) {
        $rating = 'EXCELLENT';
        $ratingColor = 'text-emerald-700';
        $ratingBg = 'bg-emerald-50';
    } elseif ($score >= 75) {
        $rating = 'GOOD';
        $ratingColor = 'text-blue-700';
        $ratingBg = 'bg-blue-50';
    } elseif ($score >= 60) {
        $rating = 'FAIR';
        $ratingColor = 'text-amber-600';
        $ratingBg = 'bg-amber-50';
    } else {
        $rating = 'NEEDS IMPROVEMENT';
        $ratingColor = 'text-rose-700';
        $ratingBg = 'bg-rose-50';
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while generating the report.");
}

$isPrint = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VALIDATION_REPORT_#<?= $inspection['reference_number'] ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
            .table-header { @apply px-6 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-50 border-b border-slate-100; }
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .card { border: none !important; shadow: none !important; border-bottom: 2px solid #f1f5f9 !important; border-radius: 0 !important; }
            .bg-slate-50 { background: white !important; }
        }
    </style>
    <?php if ($isPrint): ?>
    <script>window.onload = function() { window.print(); };</script>
    <?php endif; ?>
</head>
<body class="font-sans antialiased text-base">
    
    <!-- Actions Toolbar -->
    <div class="no-print sticky top-0 bg-white/80 backdrop-blur-md border-b border-slate-200 z-50 h-16 flex items-center justify-between px-8">
        <div class="flex items-center space-x-4">
            <a href="/inspections/view?id=<?= $inspectionId ?>" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-xs font-black text-slate-800 uppercase tracking-widest italic">Validation Report #<?= $inspection['reference_number'] ?></h1>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="window.print()" class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center shadow-md transition-all">
                <i class="fas fa-print mr-2"></i> Print Report
            </button>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-5xl mx-auto p-8 md:p-12 space-y-10">
        
        <!-- Institutional Letterhead -->
        <header class="flex flex-col items-center text-center space-y-4 pb-10 border-b-2 border-slate-900/5">
            <div class="bg-blue-700 text-white w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-xl shadow-blue-700/20 mb-2">
                <i class="fas fa-building-shield"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.4em] italic">Republic of the Philippines</p>
                <h1 class="text-3xl font-black text-slate-800 tracking-tighter uppercase mb-1 italic">Health & Safety <span class="text-blue-700">Insight</span></h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest italic">Official Validation & Compliance Registry</p>
            </div>
            <div class="mt-4 flex flex-col items-center">
                <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Protocol Type: <?= strtoupper(str_replace('_', ' ', $inspection['inspection_type'])) ?></div>
                <div class="mono text-[11px] font-black bg-slate-900 text-white px-4 py-1.5 rounded uppercase tracking-widest italic">Ref_#<?= $inspection['reference_number'] ?></div>
            </div>
        </header>

        <!-- Summary Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-6">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic ml-1 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-blue-700"></i> Subject Entity Details
                </h3>
                <div class="card p-6 bg-white">
                    <div class="mb-4">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic">Full Legal Name</label>
                        <p class="text-lg font-black text-slate-800 italic uppercase"><?= htmlspecialchars($inspection['establishment_name']) ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic">Registry Address</label>
                        <p class="text-sm font-bold text-slate-600 uppercase tracking-tight">
                            <?= htmlspecialchars($inspection['address_street']) ?>, <?= htmlspecialchars($inspection['address_barangay']) ?><br>
                            <?= htmlspecialchars($inspection['address_city']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6 text-right md:text-left">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic ml-1 flex items-center justify-end md:justify-start">
                    <i class="fas fa-user-shield mr-2 text-blue-700"></i> Audit Operational Meta
                </h3>
                <div class="card p-6 bg-white">
                    <div class="mb-4">
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic">Official Validation Date</label>
                        <p class="text-lg font-black text-slate-800 italic uppercase"><?= date('F d, Y', strtotime($inspection['scheduled_date'])) ?></p>
                    </div>
                    <div>
                        <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest italic">Lead Compliance Inspector</label>
                        <p class="text-sm font-bold text-slate-600 uppercase italic"><?= htmlspecialchars($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Pulse -->
        <div class="card p-12 text-center relative overflow-hidden bg-white">
            <div class="absolute top-0 left-0 w-full h-1 <?= str_replace('text', 'bg', $ratingColor) ?>"></div>
            <div class="relative z-10 flex flex-col items-center">
                <div class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] italic mb-6">Automated Compliance Scoring</div>
                
                <div class="flex flex-col md:flex-row items-center justify-center space-y-8 md:space-y-0 md:space-x-16">
                    <div>
                        <div class="text-7xl font-black italic <?= $ratingColor ?>"><?= round($score) ?><span class="text-2xl font-black italic opacity-50 ml-1">%</span></div>
                        <div class="mt-2 inline-flex px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest italic <?= $ratingColor ?> <?= $ratingBg ?>">
                            <?= $rating ?>
                        </div>
                    </div>
                    
                    <div class="h-16 w-px bg-slate-100 hidden md:block"></div>
                    
                    <div class="grid grid-cols-2 gap-x-12 gap-y-4 text-left">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-check-circle text-emerald-600 text-[10px]"></i>
                            <div>
                                <p class="text-[9px] font-black text-slate-300 uppercase italic">Passed Items</p>
                                <p class="text-sm font-black text-slate-800 italic mono"><?= $passCount ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-times-circle text-rose-600 text-[10px]"></i>
                            <div>
                                <p class="text-[9px] font-black text-slate-300 uppercase italic">Failed Items</p>
                                <p class="text-sm font-black text-slate-800 italic mono"><?= $failCount ?></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3 col-span-2">
                            <i class="fas fa-chart-line text-blue-700 text-[10px]"></i>
                            <div>
                                <p class="text-[9px] font-black text-slate-300 uppercase italic">Points Accrued</p>
                                <p class="text-sm font-black text-slate-800 italic mono"><?= $earnedPoints ?> / <?= $totalPoints ?> Possible</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Registry Tables -->
        <div class="space-y-12">
            <h2 class="text-xl font-black text-slate-800 italic tracking-tighter uppercase flex items-center space-x-4">
                <span class="w-8 h-px bg-blue-700"></span>
                <span>Audit Protocol Results</span>
            </h2>

            <?php foreach ($checklistByCategory as $category => $responses): ?>
                <div class="space-y-4">
                    <h3 class="text-[10px] font-black text-blue-700 uppercase tracking-[0.2em] italic ml-4"><?= htmlspecialchars($category) ?></h3>
                    
                    <div class="card overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="table-header w-1/2">Requirement Descriptor</th>
                                    <th class="table-header text-center w-32">Status</th>
                                    <th class="table-header text-center">Score</th>
                                    <th class="table-header">Officer Certification Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 italic-rows">
                                <?php foreach ($responses as $response): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 text-xs font-bold text-slate-800 uppercase italic tracking-tight leading-relaxed">
                                            <?= htmlspecialchars($response['requirement_text']) ?>
                                            <?php if ($response['mandatory']): ?>
                                                <span class="ml-2 text-[8px] px-1.5 py-0.5 bg-rose-50 text-rose-600 rounded mono uppercase font-black">Mandatory</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 rounded text-[8px] font-black uppercase italic tracking-widest border <?= $response['response'] === 'pass' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : ($response['response'] === 'fail' ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-slate-100 text-slate-500 border-slate-200') ?>">
                                                <?= strtoupper($response['response']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center mono text-[10px] font-black text-slate-400 italic">
                                            <?= $response['response'] === 'pass' ? $response['points_possible'] : 0 ?> / <?= $response['points_possible'] ?>
                                        </td>
                                        <td class="px-6 py-4 text-[10px] text-slate-400 font-medium italic">
                                            <?= htmlspecialchars($response['notes'] ?: 'No annotation') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Anomaly Report Section -->
        <?php if (!empty($violations)): ?>
            <div class="space-y-6 pt-8">
                <h2 class="text-xl font-black text-rose-600 italic tracking-tighter uppercase flex items-center space-x-4">
                    <span class="w-8 h-px bg-rose-600"></span>
                    <span>Regulatory Exceptions & Violations</span>
                </h2>
                <div class="card border-rose-100 bg-rose-50/10">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-rose-100/50">
                                <?php foreach ($violations as $violation): ?>
                                    <tr class="hover:bg-rose-50/30 transition-colors">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center space-x-4 mb-2">
                                                <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase italic tracking-widest border <?= $violation['severity'] === 'critical' ? 'bg-rose-600 text-white border-rose-600' : 'bg-amber-100 text-amber-700 border-amber-200' ?>">
                                                    <?= strtoupper($violation['severity']) ?>_ALERT
                                                </span>
                                                <span class="text-[10px] font-black text-rose-700 uppercase italic tracking-widest"><?= htmlspecialchars($violation['violation_type']) ?></span>
                                            </div>
                                            <p class="text-sm font-bold text-slate-800 italic uppercase italic leading-relaxed mb-4"><?= htmlspecialchars($violation['description']) ?></p>
                                            <div class="flex items-center space-x-6">
                                                <div class="flex items-center space-x-2">
                                                    <i class="fas fa-gavel text-slate-300 text-[10px]"></i>
                                                    <span class="text-[9px] font-black text-slate-400 uppercase italic">Registry Record: <span class="text-slate-600">#<?= str_pad($violation['violation_id'], 6, '0', STR_PAD_LEFT) ?></span></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <i class="fas fa-calendar-alt text-slate-300 text-[10px]"></i>
                                                    <span class="text-[9px] font-black text-slate-400 uppercase italic">Detected: <span class="text-slate-600"><?= date('M d, Y', strtotime($violation['created_at'])) ?></span></span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Official Sign-off and Certification -->
        <footer class="pt-20 pb-12">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-20">
                <div class="flex flex-col items-center border-t-2 border-slate-900/5 pt-8">
                    <p class="text-sm font-black text-slate-800 italic uppercase mb-1"><?= htmlspecialchars($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']) ?></p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] italic">Authorized Compliance Inspector</p>
                    <div class="mt-4 text-[8px] text-slate-300 mono italic uppercase tracking-tighter">Certified on <?= date('Y-m-d H:i:s') ?></div>
                </div>
                <div class="flex flex-col items-center border-t-2 border-slate-900/5 pt-8">
                    <p class="text-sm font-black text-slate-800 italic uppercase mb-1"><?= htmlspecialchars($inspection['owner_name'] ?: 'Proprietor/Representative') ?></p>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] italic">Acknowledged Receipt of Audit</p>
                    <div class="mt-4 text-[8px] text-slate-300 italic uppercase tracking-tighter">Official Ledger Entry v2.0</div>
                </div>
            </div>

            <div class="mt-20 text-center flex flex-col items-center border-t border-slate-100 pt-8">
                <div class="flex items-center justify-center space-x-8 mb-4">
                    <div class="text-[10px] font-black text-slate-300 flex items-center">
                        <i class="fas fa-shield-halved mr-2"></i> DATA_INTEGRITY_VERIFIED
                    </div>
                    <div class="text-[10px] font-black text-slate-300 flex items-center">
                        <i class="fas fa-fingerprint mr-2"></i> SYSTEM_GENERATED_DOCUMENT
                    </div>
                </div>
                <p class="text-[8px] text-slate-300 italic uppercase tracking-widest max-w-sm">
                    This document is a formal registry record of inspection. Alteration of this document is a severe regulatory violation. Verified by LGU Health and Safety Protocol.
                </p>
            </div>
        </footer>
    </div>

</body>
</html>
