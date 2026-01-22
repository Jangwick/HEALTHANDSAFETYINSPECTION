<?php
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
        $ratingColor = '#28a745';
    } elseif ($score >= 75) {
        $rating = 'GOOD';
        $ratingColor = '#17a2b8';
    } elseif ($score >= 60) {
        $rating = 'FAIR';
        $ratingColor = '#ffc107';
    } else {
        $rating = 'NEEDS IMPROVEMENT';
        $ratingColor = '#dc3545';
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while generating the report.");
}

// If download is requested, generate PDF (we'll use HTML to PDF approach)
if ($download) {
    // For now, we'll use a simple HTML approach that can be printed to PDF by browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Inspection_Report_' . $inspectionId . '.pdf"');
    // In production, you'd use a proper PDF library here
    // For now, redirect to print view
    header('Location: /views/inspections/report.php?id=' . $inspectionId . '&print=1');
    exit;
}

$isPrint = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VALIDATION_DOSSIER_#<?= $inspection['reference_number'] ?> | Health & Safety</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root { --glass: rgba(15, 23, 42, 0.85); }
        body { font-family: 'Inter', sans-serif; background: #020617; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .glass { background: var(--glass); backdrop-filter: blur(16px); }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .glass { background: white !important; backdrop-filter: none !important; border: 1px solid #ddd !important; }
            .text-white { color: black !important; }
            .text-slate-200, .text-slate-300, .text-slate-400, .text-slate-500 { color: #333 !important; }
            .bg-slate-900, .bg-slate-950, .bg-black { background: white !important; border: 1px solid #eee !important; }
            .border-white\/5, .border-white\/10 { border-color: #eee !important; }
            .mono { font-family: 'Courier New', monospace !important; }
            .print-shadow { box-shadow: none !important; }
            .print-no-blur { backdrop-filter: none !important; }
        }

        .scan-line {
            width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.2), transparent);
            position: absolute; animation: scan 4s linear infinite; pointer-events: none;
        }
        @keyframes scan { 0% { top: -100%; } 100% { top: 100%; } }
    </style>
    <?php if ($isPrint): ?>
    <script>window.onload = function() { window.print(); };</script>
    <?php endif; ?>
</head>
<body class="text-slate-300 min-h-screen">
    <!-- Background Decor (Digital View Only) -->
    <div class="fixed inset-0 z-0 no-print">
        <div class="absolute top-0 right-0 w-[50%] h-[50%] bg-sky-950/10 blur-[120px]"></div>
        <div class="absolute bottom-0 left-0 w-[50%] h-[50%] bg-blue-950/5 blur-[120px]"></div>
    </div>

    <!-- Interface Content -->
    <div class="relative z-10 p-8 lg:p-16 max-w-[1200px] mx-auto">
        
        <!-- Dashboard Actions -->
        <div class="no-print flex items-center justify-between mb-16">
            <a href="/views/inspections/view.php?id=<?= $inspectionId ?>" class="h-12 px-6 glass rounded-2xl flex items-center gap-4 text-slate-400 hover:text-white border border-white/5 transition-all mono text-[10px] font-black uppercase tracking-widest italic translate-y-[-1px] active:translate-y-0">
                <i class="fas fa-arrow-left"></i> RETURN_TO_VIEW
            </a>
            <div class="flex items-center gap-4">
                <button onclick="window.print()" class="h-12 px-8 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl flex items-center gap-4 shadow-2xl shadow-sky-900/40 transition-all mono text-[10px] font-black uppercase tracking-widest italic active:scale-95">
                    <i class="fas fa-print"></i> INITIALIZE_PRINT_SEQUENCE
                </button>
            </div>
        </div>

        <!-- Official Header -->
        <header class="text-center mb-20 space-y-6">
            <div class="inline-flex flex-col items-center">
                <div class="mono text-[10px] text-sky-500 font-black tracking-[0.5em] uppercase italic mb-4">REPUBLIC_OF_THE_PHILIPPINES</div>
                <h1 class="text-4xl font-black text-white italic tracking-tighter uppercase mb-2">HEALTH_&_SAFETY<span class="text-sky-500">_INSPECTION_SYSTEM</span></h1>
                <div class="h-px w-64 bg-gradient-to-r from-transparent via-sky-500/50 to-transparent"></div>
                <p class="mono text-[9px] text-slate-500 uppercase tracking-widest mt-4">OFFICIAL_VALIDATION_REPORT_v2.0_SIGMA</p>
            </div>
        </header>

        <!-- Mission Meta Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <div class="glass p-8 rounded-[2rem] border border-white/5 space-y-4">
                <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black italic">REPORT_IDENTIFIER</div>
                <div class="text-lg font-black text-white italic"><?= htmlspecialchars($inspection['reference_number']) ?></div>
                <div class="h-px bg-white/5"></div>
                <div class="mono text-[9px] text-slate-500 italic uppercase"><?= htmlspecialchars(str_replace('_', ' ', $inspection['inspection_type'])) ?></div>
            </div>

            <div class="glass p-8 rounded-[2rem] border border-white/5 space-y-4 md:col-span-2">
                <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black italic">TARGET_ENTITY</div>
                <div class="text-xl font-black text-white italic uppercase"><?= htmlspecialchars($inspection['establishment_name']) ?></div>
                <div class="text-xs text-slate-400 font-bold italic uppercase tracking-wider">
                    <?= htmlspecialchars($inspection['address_street']) ?>, <?= htmlspecialchars($inspection['address_barangay']) ?>, <?= htmlspecialchars($inspection['address_city']) ?>
                </div>
            </div>
        </div>

        <!-- Metric Pulse -->
        <div class="glass p-12 rounded-[3.5rem] border border-white/5 relative bg-white/[0.01] mb-16 overflow-hidden text-center">
            <div class="scan-line opacity-10"></div>
            <div class="relative z-10 flex flex-col items-center">
                <div class="mono text-[10px] text-slate-600 uppercase tracking-[0.4em] font-black italic mb-8">COMPLIANCE_ALGORITHM_RESULT</div>
                
                <div class="flex items-center justify-center gap-12 mb-8">
                    <div class="text-center">
                        <div class="text-6xl font-black italic" style="color: <?= $ratingColor ?>"><?= $score ?>%</div>
                        <div class="mono text-[10px] font-bold mt-2 tracking-widest" style="color: <?= $ratingColor ?>"><?= $rating ?></div>
                    </div>
                    <div class="h-20 w-px bg-white/10"></div>
                    <div class="text-left space-y-3">
                        <div class="flex items-center gap-4">
                            <i class="fas fa-check-circle text-emerald-500 text-xs"></i>
                            <span class="mono text-[9px] text-slate-400 uppercase tracking-widest">PASSED: <?= $passCount ?></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <i class="fas fa-times-circle text-rose-500 text-xs"></i>
                            <span class="mono text-[9px] text-slate-400 uppercase tracking-widest">FAILED: <?= $failCount ?></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <i class="fas fa-database text-slate-500 text-xs"></i>
                            <span class="mono text-[9px] text-slate-400 uppercase tracking-widest">TOTAL_POINTS: <?= $earnedPoints ?> / <?= $totalPoints ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Clusters -->
        <div class="space-y-16 mb-20">
            <h2 class="text-2xl font-black text-white italic tracking-tighter uppercase flex items-center gap-4 mb-8">
                <span class="w-10 h-px bg-sky-500"></span>
                VALIDATION_CLUSTERS
            </h2>

            <?php foreach ($checklistByCategory as $category => $responses): ?>
                <div class="space-y-6">
                    <h3 class="mono text-[10px] font-black text-sky-500/80 tracking-[0.3em] uppercase italic ml-4"><?= htmlspecialchars($category) ?></h3>
                    
                    <div class="glass rounded-3xl overflow-hidden border border-white/5">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-950/50 border-b border-white/5">
                                    <th class="px-8 py-5 mono text-[8px] text-slate-500 uppercase tracking-widest italic">REQUIREMENT_DESCRIPTOR</th>
                                    <th class="px-8 py-5 mono text-[8px] text-slate-500 uppercase tracking-widest italic text-center">STATUS</th>
                                    <th class="px-8 py-5 mono text-[8px] text-slate-500 uppercase tracking-widest italic text-center">YIELD</th>
                                    <th class="px-8 py-5 mono text-[8px] text-slate-500 uppercase tracking-widest italic">NOTE_LOG</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($responses as $response): ?>
                                    <tr class="hover:bg-white/[0.01] transition-colors">
                                        <td class="px-8 py-6 text-xs font-bold text-slate-200 italic uppercase tracking-tight"><?= htmlspecialchars($response['requirement_text']) ?></td>
                                        <td class="px-8 py-6 text-center">
                                            <span class="px-3 py-1 rounded-full mono text-[7px] font-black uppercase tracking-widest italic border <?= $response['response'] === 'pass' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : ($response['response'] === 'fail' ? 'bg-rose-500/10 text-rose-500 border-rose-500/20' : 'bg-slate-500/10 text-slate-500 border-slate-500/20') ?>">
                                                <?= strtoupper($response['response']) ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-6 text-center mono text-[9px] font-bold text-slate-400 italic">
                                            <?= $response['response'] === 'pass' ? $response['points_possible'] : 0 ?> / <?= $response['points_possible'] ?>
                                        </td>
                                        <td class="px-8 py-6 text-[10px] text-slate-500 italic">
                                            <?= htmlspecialchars($response['notes'] ?? '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Anomaly Inventory -->
        <?php if (!empty($violations)): ?>
            <div class="mb-20">
                <h2 class="text-2xl font-black text-rose-500 italic tracking-tighter uppercase flex items-center gap-4 mb-8">
                    <span class="w-10 h-px bg-rose-500"></span>
                    ANOMALY_INVENTORY (<?= count($violations) ?>)
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($violations as $v): ?>
                        <div class="glass p-8 rounded-[2rem] border border-rose-500/20 bg-rose-500/[0.01]">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-3 py-1 bg-rose-500/10 text-rose-500 rounded-full mono text-[7px] font-black uppercase tracking-widest italic border border-rose-500/20">
                                    <?= strtoupper($v['severity']) ?>_RISK
                                </span>
                                <span class="mono text-[8px] text-slate-600 uppercase italic">STATUS: <?= strtoupper($v['status']) ?></span>
                            </div>
                            <h4 class="text-lg font-black text-white italic uppercase mb-2"><?= htmlspecialchars($v['violation_type'] ?? 'GENERAL_ANOMALY') ?></h4>
                            <p class="text-xs text-slate-400 italic leading-relaxed mb-6 uppercase tracking-tighter"><?= htmlspecialchars($v['description']) ?></p>
                            
                            <div class="p-4 bg-slate-950/50 rounded-2xl border border-white/5">
                                <div class="mono text-[8px] text-slate-600 uppercase mb-1 font-black italic italic">REMEDIATION_PROTOCOL</div>
                                <div class="text-[10px] text-rose-400 font-bold italic uppercase"><?= htmlspecialchars($v['corrective_action_required'] ?: 'IMMEDIATE_RESOLUTION_REQUIRED') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Operational Summary -->
        <section class="mb-20">
            <h2 class="text-2xl font-black text-white italic tracking-tighter uppercase flex items-center gap-4 mb-8">
                <span class="w-10 h-px bg-sky-500"></span>
                FINAL_PROTOCOL_SUMMARY
            </h2>
            <div class="glass p-10 rounded-[3rem] border border-white/5 bg-slate-900/20">
                 <p class="text-sm text-slate-300 italic leading-relaxed uppercase tracking-tight mb-8">
                    <?php if ($score >= 90): ?>
                        TARGET_ENTITY_EXHIBITS_SUPERIOR_COMPLIANCE_PARAMETERS. CONTINUED_OPERATIONS_AUTHORIZED_WITH_MINIMAL_OVERSIGHT.
                    <?php elseif ($score >= 75): ?>
                        TARGET_ENTITY_MEETS_OPERATIONAL_THRESHOLD. IDENTIFIED_ANOMALIES_REQUIRING_MINOR_RECALIBRATION_PER_PROTOCOL.
                    <?php elseif ($score >= 60): ?>
                        TARGET_ENTITY_FAILURE_POINTS_DETECTED. IMMEDIATE_INTERVENTION_REQUIRED. FOLLOW-UP_SCAN_SCHEDULED_T-30_DAYS.
                    <?php else: ?>
                        CRITICAL_PROTOCOL_FAILURES. OPERATIONAL_SAFETY_COMPROMISED. RE-VALIDATION_REQUIRED_T-14_DAYS. SUSPENSION_PENDING_REVIEW.
                    <?php endif; ?>
                 </p>
                 
                 <?php if (!empty($violations)): ?>
                    <div class="space-y-4">
                        <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black italic italic">MANDATORY_FIELD_ACTIONS</div>
                        <ul class="space-y-2">
                            <?php foreach ($violations as $v): ?>
                                <li class="flex items-center gap-4 text-[10px] text-slate-400 italic">
                                    <div class="w-1 h-1 rounded-full bg-rose-500"></div>
                                    <?= strtoupper(htmlspecialchars($v['corrective_action_required'] ?: $v['description'])) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                 <?php endif; ?>
            </div>
        </section>

        <!-- Validation Signatures -->
        <footer class="mt-32">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-20">
                <div class="text-center">
                    <div class="h-24 flex items-end justify-center mb-4">
                         <!-- Empty space for signature -->
                    </div>
                    <div class="mono text-[10px] font-black text-white italic mb-1 uppercase underline decoration-sky-500 underline-offset-8"><?= htmlspecialchars($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']) ?></div>
                    <div class="mono text-[8px] text-slate-600 uppercase tracking-[0.3em] font-black italic italic">AUTHORIZING_FIELD_AGENT</div>
                    <div class="text-[9px] text-slate-500 mt-2 italic"><?= date('F d, Y') ?></div>
                </div>

                <div class="text-center">
                    <div class="h-24 flex items-end justify-center mb-4">
                        <!-- Empty space for signature -->
                    </div>
                    <div class="mono text-[10px] font-black text-white italic mb-1 uppercase underline decoration-slate-700 underline-offset-8"><?= htmlspecialchars($inspection['owner_name']) ?></div>
                    <div class="mono text-[8px] text-slate-600 uppercase tracking-[0.3em] font-black italic italic">ENTITY_REPRESENTATIVE_ACKNOWLEDGMENT</div>
                    <div class="text-[9px] text-slate-500 mt-2 italic">TIMESTAMP_PENDING</div>
                </div>
            </div>

            <!-- Report Footer -->
            <div class="mt-32 pt-8 border-t border-white/5 text-center">
                <div class="mono text-[8px] text-slate-700 uppercase tracking-widest italic italic">
                    DIGITALLY_ENCRYPTED_DOCUMENT | REFID: <?= $inspection['reference_number'] ?> | GENERATED: <?= date('Y-m-d H:i:s') ?>
                </div>
                <div class="mt-4 flex justify-center gap-8 text-slate-800 text-xs">
                    <i class="fas fa-microchip"></i>
                    <i class="fas fa-fingerprint"></i>
                    <i class="fas fa-barcode"></i>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
                    This is an official document generated by the Health & Safety Inspection System<br>
                    Report Generated: <?php echo  date('F d, Y h:i A') ?><br>
                    Reference: <?php echo  htmlspecialchars($inspection['reference_number']) ?>
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
