<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
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
    
    // Ensure session has first_name and last_name
    if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
        $userStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $_SESSION['first_name'] = $userData['first_name'];
            $_SESSION['last_name'] = $userData['last_name'];
        }
    }
    
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
        header('Location: /views/inspections/list.php');
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
        $inspection['actual_start_datetime'] = date('Y-m-d H:i:s');
    }
    
    // Get checklist items for this inspection type via template
    $stmt = $db->prepare("
        SELECT ci.* FROM checklist_items ci
        JOIN checklist_templates ct ON ci.template_id = ct.template_id
        WHERE ct.inspection_type = ? AND ct.status = 'active'
        ORDER BY ci.category, ci.order_sequence
    ");
    $stmt->execute([$inspection['inspection_type']]);
    $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing responses
    $stmt = $db->prepare("
        SELECT * FROM inspection_checklist_responses
        WHERE inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $existingResponses = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $response) {
        $existingResponses[$response['checklist_item_id']] = $response;
    }
    
    // Group checklist by category
    $checklistByCategory = [];
    foreach ($checklistItems as $item) {
        $category = $item['category'] ?? 'General';
        if (!isset($checklistByCategory[$category])) {
            $checklistByCategory[$category] = [];
        }
        $checklistByCategory[$category][] = $item;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'save_progress' || $action === 'complete') {
            // Save all checklist responses
            foreach ($_POST['responses'] ?? [] as $itemId => $data) {
                $response = $data['status'] ?? 'na';
                $notes = $data['notes'] ?? '';
                
                // Check if response exists
                if (isset($existingResponses[$itemId])) {
                    // Update existing
                    $stmt = $db->prepare("
                        UPDATE inspection_checklist_responses 
                        SET response = ?, notes = ?
                        WHERE response_id = ?
                    ");
                    $stmt->execute([$response, $notes, $existingResponses[$itemId]['response_id']]);
                } else {
                    // Insert new
                    $stmt = $db->prepare("
                        INSERT INTO inspection_checklist_responses 
                        (inspection_id, checklist_item_id, response, notes, recorded_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$inspectionId, $itemId, $response, $notes]);
                }
            }
            
            if ($action === 'complete') {
                // Mark inspection as completed
                $stmt = $db->prepare("
                    UPDATE inspections 
                    SET status = 'completed', actual_end_datetime = NOW()
                    WHERE inspection_id = ?
                ");
                $stmt->execute([$inspectionId]);
                
                $_SESSION['message'] = 'Inspection completed successfully!';
                header('Location: /inspections/view?id=' . $inspectionId);
                exit;
            } else {
                $_SESSION['message'] = 'Progress saved successfully!';
                header('Location: /inspections/conduct?id=' . $inspectionId);
                exit;
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONDUCT_PROTOCOL_#<?= $inspection['reference_number'] ?> | Health & Safety</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root { --glass: rgba(15, 23, 42, 0.85); }
        body { font-family: 'Inter', sans-serif; background: #020617; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .glass { background: var(--glass); backdrop-filter: blur(16px); }
        .scan-line {
            width: 100%; height: 2px;
            background: linear-gradient(90deg, transparent, rgba(56, 189, 248, 0.2), transparent);
            position: absolute; animation: scan 4s linear infinite; pointer-events: none;
        }
        @keyframes scan { 0% { top: -100%; } 100% { top: 100%; } }
        
        .radio-tile input:checked + div {
            border-color: currentColor;
            background: currentColor;
            color: #020617;
        }
    </style>
</head>
<body class="text-slate-300 min-h-screen overflow-x-hidden">
    <!-- Animated Background -->
    <div class="fixed inset-0 z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-sky-900/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-blue-900/5 rounded-full blur-[120px]"></div>
    </div>

    <!-- Sticky Mission Dashboard -->
    <div class="sticky top-0 z-[100] glass border-b border-white/10 px-8 py-5">
        <div class="max-w-[1600px] mx-auto flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="flex items-center gap-6">
                <a href="/inspections/view?id=<?= $inspectionId ?>" class="w-10 h-10 glass rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all border border-white/5">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <div class="flex items-center gap-4 mb-1">
                        <div class="h-px w-8 bg-sky-500"></div>
                        <span class="mono text-[9px] font-black tracking-[0.3em] text-sky-500 uppercase italic">Active_Field_Protocol</span>
                    </div>
                    <h1 class="text-xl font-black text-white italic tracking-tighter uppercase truncate max-w-[400px]">
                        <?= htmlspecialchars($inspection['establishment_name']) ?> <span class="text-sky-500">_#<?= $inspection['reference_number'] ?></span>
                    </h1>
                </div>
            </div>

            <div class="flex-1 max-w-xl px-12 relative">
                <div class="flex items-center justify-between mb-2">
                    <span class="mono text-[8px] text-slate-500 uppercase tracking-widest font-black italic">MISSION_PROGRESS</span>
                    <span class="mono text-[8px] text-sky-400 font-black italic"><span id="progressPercent">0</span>% COMPLETE</span>
                </div>
                <div class="h-1 w-full bg-slate-950/50 rounded-full overflow-hidden border border-white/5 p-[1px]">
                    <div id="progressBar" class="h-full bg-gradient-to-r from-sky-600 to-sky-400 rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>

            <div class="flex items-center gap-6">
                <div class="text-right">
                    <div class="mono text-[8px] text-slate-600 uppercase tracking-widest mb-1 italic">COMPLETION_INDEX</div>
                    <div class="text-xs font-black text-white italic tracking-wider"><span id="completedCount">0</span> / <span id="totalCount"><?= count($checklistItems) ?></span> ITEMS</div>
                </div>
                <div class="h-10 w-px bg-white/5 mx-2"></div>
                <button type="button" onclick="confirmComplete()" class="h-14 px-8 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl flex items-center gap-4 shadow-2xl shadow-emerald-900/40 transition-all mono text-[10px] font-black uppercase tracking-widest italic group active:scale-95">
                    <i class="fas fa-satellite-dish group-hover:animate-pulse"></i> TRANSMIT_REPORT
                </button>
            </div>
        </div>
    </div>

    <!-- Main Mission Grid -->
    <main class="relative z-10 p-8 lg:p-12 max-w-[1400px] mx-auto">
        <?php if (isset($_SESSION['message'])): ?>
            <div id="status-toast" class="fixed top-32 right-8 glass border border-emerald-500/20 rounded-3xl p-6 flex items-center gap-6 animate-in fade-in slide-in-from-right-8 z-[200]">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 border border-emerald-500/20 shrink-0">
                    <i class="fas fa-check"></i>
                </div>
                <div class="mono text-[10px] text-emerald-500 font-black uppercase tracking-widest italic"><?= $_SESSION['message'] ?></div>
            </div>
            <script>setTimeout(() => document.getElementById('status-toast')?.remove(), 3000);</script>
        <?php unset($_SESSION['message']); endif; ?>

        <form method="POST" id="checklistForm" class="space-y-16">
            <?php foreach ($checklistByCategory as $category => $items): ?>
                <div class="space-y-8">
                    <!-- Category Header -->
                    <div class="flex items-center gap-6">
                        <h2 class="text-2xl font-black text-slate-500 italic uppercase tracking-tighter flex items-center gap-4">
                            <span class="w-10 h-px bg-slate-800"></span>
                            <?= htmlspecialchars($category) ?>
                        </h2>
                        <span class="mono text-[9px] text-slate-700 tracking-widest italic"><?= count($items) ?>_NODES_IN_CLUSTER</span>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $itemId = $item['item_id'];
                            $currentResponse = $existingResponses[$itemId] ?? null;
                            $currentStatus = $currentResponse['response'] ?? '';
                            $currentNotes = $currentResponse['notes'] ?? '';
                            ?>
                            <div class="glass rounded-3xl p-8 border border-white/5 relative group hover:bg-white/[0.02] transition-all checklist-item" 
                                 data-item-id="<?= $itemId ?>" 
                                 data-status="<?= $currentStatus ?>">
                                <div class="scan-line opacity-[0.03]"></div>
                                
                                <div class="flex flex-col lg:flex-row gap-10">
                                    <!-- Requirement Info -->
                                    <div class="flex-1 space-y-4">
                                        <div class="flex items-center gap-4">
                                            <span class="mono text-[8px] text-slate-600 italic">#<?= $itemId ?>_REQ</span>
                                            <div class="px-2 py-0.5 bg-slate-900 rounded-md border border-white/5 mono text-[8px] text-slate-500">VAL_<?= $item['points_possible'] ?></div>
                                        </div>
                                        <h3 class="text-lg font-bold text-white italic leading-tight uppercase tracking-tight group-hover:text-sky-400 transition-colors">
                                            <?= htmlspecialchars($item['requirement_text']) ?>
                                        </h3>
                                        <?php if ($item['guidance_notes']): ?>
                                            <p class="text-xs text-slate-500 italic leading-relaxed uppercase tracking-tighter"><?= htmlspecialchars($item['guidance_notes']) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Response Terminal -->
                                    <div class="w-full lg:w-[450px] space-y-6">
                                        <div class="grid grid-cols-3 gap-3">
                                            <!-- Pass -->
                                            <label class="radio-tile relative group cursor-pointer text-emerald-500">
                                                <input type="radio" 
                                                       name="responses[<?= $itemId ?>][status]" 
                                                       value="pass" 
                                                       <?= $currentStatus === 'pass' ? 'checked' : '' ?>
                                                       onchange="updateItemStatus(<?= $itemId ?>, 'pass')"
                                                       class="sr-only">
                                                <div class="py-4 px-2 glass rounded-2xl border border-white/5 flex flex-col items-center gap-2 group-hover:border-emerald-500/30 transition-all">
                                                    <i class="fas fa-check-circle text-xs"></i>
                                                    <span class="mono text-[9px] font-black uppercase tracking-widest italic">PASS</span>
                                                </div>
                                            </label>

                                            <!-- Fail -->
                                            <label class="radio-tile relative group cursor-pointer text-rose-500">
                                                <input type="radio" 
                                                       name="responses[<?= $itemId ?>][status]" 
                                                       value="fail" 
                                                       <?= $currentStatus === 'fail' ? 'checked' : '' ?>
                                                       onchange="updateItemStatus(<?= $itemId ?>, 'fail')"
                                                       class="sr-only">
                                                <div class="py-4 px-2 glass rounded-2xl border border-white/5 flex flex-col items-center gap-2 group-hover:border-rose-500/30 transition-all">
                                                    <i class="fas fa-times-circle text-xs"></i>
                                                    <span class="mono text-[9px] font-black uppercase tracking-widest italic">FAIL</span>
                                                </div>
                                            </label>

                                            <!-- N/A -->
                                            <label class="radio-tile relative group cursor-pointer text-slate-500">
                                                <input type="radio" 
                                                       name="responses[<?= $itemId ?>][status]" 
                                                       value="na" 
                                                       <?= $currentStatus === 'na' || !$currentStatus ? 'checked' : '' ?>
                                                       onchange="updateItemStatus(<?= $itemId ?>, 'na')"
                                                       class="sr-only">
                                                <div class="py-4 px-2 glass rounded-2xl border border-white/5 flex flex-col items-center gap-2 group-hover:border-slate-500/30 transition-all">
                                                    <i class="fas fa-minus-circle text-xs"></i>
                                                    <span class="mono text-[9px] font-black uppercase tracking-widest italic">N/A</span>
                                                </div>
                                            </label>
                                        </div>

                                        <div class="relative group">
                                            <textarea name="responses[<?= $itemId ?>][notes]" rows="2" 
                                                      placeholder="FIELD_OBSERVATIONS_LOG..."
                                                      class="w-full bg-slate-950/50 border border-white/5 rounded-2xl py-4 px-5 text-slate-300 focus:ring-1 focus:ring-sky-500/20 focus:border-sky-500/30 outline-none transition-all resize-none text-[10px] mono tracking-widest italic placeholder:text-slate-800"><?= htmlspecialchars($currentNotes) ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Guard Spacing -->
            <div class="h-32"></div>

            <!-- Global Action Bar -->
            <div class="fixed bottom-12 left-1/2 -translate-x-1/2 z-[101] glass border border-white/10 p-4 rounded-3xl shadow-2xl flex items-center gap-4">
                <button type="button" onclick="addViolation()" class="h-16 px-10 glass border border-rose-500/20 hover:bg-rose-500/10 text-rose-500 rounded-2xl flex items-center gap-4 transition-all mono text-[10px] font-black uppercase tracking-widest italic group">
                    <i class="fas fa-exclamation-triangle group-hover:animate-bounce"></i> LOG_ANOMALY
                </button>
                <div class="w-px h-8 bg-white/10 mx-2"></div>
                <button type="submit" name="action" value="save_progress" class="h-16 px-10 glass border border-sky-500/20 hover:bg-sky-500/10 text-sky-400 rounded-2xl flex items-center gap-4 transition-all mono text-[10px] font-black uppercase tracking-widest italic group active:scale-95">
                    <i class="fas fa-save group-hover:translate-y-[-2px] transition-transform"></i> CACHE_LOCAL_STATE
                </button>
            </div>
        </form>
    </main>

    <script>
        function updateItemStatus(itemId, status) {
            const item = document.querySelector(`[data-item-id="${itemId}"]`);
            item.setAttribute('data-status', status);
            updateProgress();
        }

        function updateProgress() {
            const total = document.querySelectorAll('.checklist-item').length;
            const completed = Array.from(document.querySelectorAll('.checklist-item input[type="radio"]:checked')).filter(input => input.value !== 'na').length;
            // Count NA as completed too for metric? Let's say all checked ones are "completed"
            const allChecked = document.querySelectorAll('.checklist-item input[type="radio"]:checked').length;
            
            const percent = Math.round((allChecked / total) * 100);
            
            document.getElementById('completedCount').textContent = allChecked;
            document.getElementById('totalCount').textContent = total;
            document.getElementById('progressPercent').textContent = percent;
            document.getElementById('progressBar').style.width = percent + '%';
        }

        function confirmComplete() {
            const total = document.querySelectorAll('.checklist-item').length;
            const checked = document.querySelectorAll('.checklist-item input[type="radio"]:checked').length;
            
            if (checked < total) {
                if (!confirm(`Warning: ${total - checked} nodes unvalidated. Continue transmission anyway?`)) {
                    return;
                }
            }
            
            if (confirm('Finalize mission report and transmit to HQ?')) {
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'complete';
                document.getElementById('checklistForm').appendChild(actionInput);
                document.getElementById('checklistForm').submit();
            }
        }

        function addViolation() {
            window.open('/violations/add?inspection_id=<?= $inspectionId ?>', 'Add Violation', 'width=1000,height=800');
        }

        // Auto-save every 2 minutes
        setInterval(() => {
            const formData = new FormData(document.getElementById('checklistForm'));
            formData.append('action', 'save_progress');
            fetch(window.location.href, { method: 'POST', body: formData });
        }, 120000);

        updateProgress();

        // Prevent accidental navigation
        window.addEventListener('beforeunload', (e) => {
            if (document.querySelectorAll('.checklist-item input[type="radio"]:checked').length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
