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
    
    // Get inspection details
    $stmt = $db->prepare("
        SELECT i.*, e.name AS establishment_name, e.type AS establishment_type
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        WHERE i.inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        header('Location: /inspections');
        exit;
    }
    
    // If inspection is pending/scheduled, start it
    if ($inspection['status'] === 'pending' || $inspection['status'] === 'scheduled') {
        $stmt = $db->prepare("
            UPDATE inspections 
            SET status = 'in_progress', actual_start_datetime = NOW() 
            WHERE inspection_id = ?
        ");
        $stmt->execute([$inspectionId]);
        $inspection['status'] = 'in_progress';
    }
    
    // Get checklist items via template
    $stmt = $db->prepare("
        SELECT ci.* FROM checklist_items ci
        JOIN checklist_templates ct ON ci.template_id = ct.template_id
        WHERE ct.inspection_type = ? AND ct.status = 'active'
        ORDER BY ci.category, ci.order_sequence
    ");
    $stmt->execute([$inspection['inspection_type']]);
    $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing responses
    $stmt = $db->prepare("SELECT * FROM inspection_checklist_responses WHERE inspection_id = ?");
    $stmt->execute([$inspectionId]);
    $resMap = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $resMap[$r['checklist_item_id']] = $r; }
    
    // Group checklist by category
    $checklistByCategory = [];
    foreach ($checklistItems as $item) { $checklistByCategory[$item['category'] ?? 'General'][] = $item; }
    
    // Handle submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        foreach ($_POST['responses'] ?? [] as $itemId => $data) {
            $status = $data['status'] ?? 'na';
            $notes = $data['notes'] ?? '';
            
            if (isset($resMap[$itemId])) {
                $st = $db->prepare("UPDATE inspection_checklist_responses SET response = ?, notes = ? WHERE response_id = ?");
                $st->execute([$status, $notes, $resMap[$itemId]['response_id']]);
            } else {
                $st = $db->prepare("INSERT INTO inspection_checklist_responses (inspection_id, checklist_item_id, response, notes, recorded_at) VALUES (?, ?, ?, ?, NOW())");
                $st->execute([$inspectionId, $itemId, $status, $notes]);
            }
        }
        
        if ($action === 'complete') {
            $db->prepare("UPDATE inspections SET status = 'completed', actual_end_datetime = NOW(), completion_date = CURDATE() WHERE inspection_id = ?")->execute([$inspectionId]);
            header('Location: /inspections/view?id=' . $inspectionId . '&success=1');
            exit;
        }
        header('Location: /inspections/conduct?id=' . $inspectionId . '&saved=1');
        exit;
    }
    
} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Institutional Protocol Access Denied");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Field Protocol - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
            .radio-label { @apply flex items-center justify-center p-3 border rounded-lg cursor-pointer transition-all text-[10px] font-black uppercase tracking-widest; }
            .radio-input:checked + .radio-label.pass { @apply bg-emerald-600 border-emerald-600 text-white shadow-emerald-900/10 shadow-lg; }
            .radio-input:checked + .radio-label.fail { @apply bg-rose-600 border-rose-600 text-white shadow-rose-900/10 shadow-lg; }
            .radio-input:checked + .radio-label.na { @apply bg-slate-600 border-slate-600 text-white shadow-slate-900/10 shadow-lg; }
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

        <!-- Protocol Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-base">
            <!-- Professional Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-50 shadow-sm">
                <div class="flex items-center space-x-4">
                    <a href="/inspections" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Audit Protocol Execution</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic"><?= htmlspecialchars($inspection['reference_number']) ?></span>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="flex flex-col items-end">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-[0.2em] mb-0.5">Audit Target</span>
                        <span class="text-[10px] font-bold text-slate-800 italic uppercase"><?= htmlspecialchars($inspection['establishment_name']) ?></span>
                    </div>
                </div>
            </header>

            <!-- Scrollable Investigation Sheet -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50 pb-24">
                <div class="max-w-4xl mx-auto space-y-12">
                    <form method="POST" id="protocolForm">
                        <div class="space-y-12">
                            <?php foreach ($checklistByCategory as $cat => $items): ?>
                                <section class="space-y-6">
                                    <div class="flex items-center space-x-4">
                                        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest flex items-center">
                                            <span class="w-8 h-px bg-slate-300 mr-4"></span>
                                            <?= htmlspecialchars($cat) ?>
                                        </h2>
                                    </div>

                                    <?php foreach ($items as $item): 
                                        $id = $item['item_id'];
                                        $res = $resMap[$id] ?? null;
                                        $st = $res['response'] ?? '';
                                    ?>
                                        <div class="card p-6 md:p-8 hover:border-blue-200 transition-colors group">
                                            <div class="flex flex-col md:flex-row gap-8">
                                                <div class="flex-1">
                                                    <div class="text-[9px] font-black text-slate-300 uppercase tracking-tighter mb-2 font-mono italic">REG_REQ_#<?= $id ?></div>
                                                    <h3 class="text-sm font-bold text-slate-800 leading-relaxed mb-4 group-hover:text-blue-700 transition-colors"><?= htmlspecialchars($item['requirement_text']) ?></h3>
                                                    <textarea name="responses[<?= $id ?>][notes]" placeholder="Inspector observations (optional)..." class="w-full text-[11px] p-4 bg-slate-50 border border-slate-100 rounded-xl focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all resize-none italic"><?= htmlspecialchars($res['notes'] ?? '') ?></textarea>
                                                </div>

                                                <div class="grid grid-cols-3 gap-2 w-full md:w-[240px] h-fit">
                                                    <label class="block">
                                                        <input type="radio" name="responses[<?= $id ?>][status]" value="pass" <?= $st === 'pass' ? 'checked' : '' ?> class="sr-only radio-input">
                                                        <div class="radio-label pass border-slate-100 text-slate-400"><i class="fas fa-check mr-2"></i> Pass</div>
                                                    </label>
                                                    <label class="block">
                                                        <input type="radio" name="responses[<?= $id ?>][status]" value="fail" <?= $st === 'fail' ? 'checked' : '' ?> class="sr-only radio-input">
                                                        <div class="radio-label fail border-slate-100 text-slate-400"><i class="fas fa-times mr-2"></i> Fail</div>
                                                    </label>
                                                    <label class="block">
                                                        <input type="radio" name="responses[<?= $id ?>][status]" value="na" <?= $st === 'na' ? 'checked' : '' ?> class="sr-only radio-input">
                                                        <div class="radio-label na border-slate-100 text-slate-400">N/A</div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </section>
                            <?php endforeach; ?>
                        </div>

                        <!-- Sticky Footer Actions -->
                        <div class="fixed bottom-0 left-0 right-0 md:left-64 bg-white border-t border-slate-200 p-4 px-8 flex items-center justify-between z-50">
                            <div class="text-[10px] font-black text-slate-400 uppercase italic tracking-widest hidden md:block">
                                <i class="fas fa-shield-alt mr-2 text-blue-700"></i> Field Audit Mode Active
                            </div>
                            <div class="flex items-center space-x-4">
                                <button type="submit" name="action" value="save" class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors px-6 py-3">
                                    Save Progress
                                </button>
                                <button type="submit" name="action" value="complete" class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-3 rounded-lg text-xs font-bold uppercase tracking-widest transition-all shadow-md shadow-blue-900/10">
                                    Finalize Audit Protocol
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
