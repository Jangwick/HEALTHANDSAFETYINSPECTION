<?php
/**
 * Health & Safety Inspection System
 * Schedule New Inspection
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Get establishments
$establishments = $db->query("SELECT establishment_id, name, type, address_street, address_barangay, address_city FROM establishments WHERE compliance_status != 'revoked' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get inspectors
$inspectors = $db->query("
    SELECT i.inspector_id, CONCAT(u.first_name, ' ', u.last_name) as full_name, i.specializations
    FROM users u
    INNER JOIN inspectors i ON u.user_id = i.user_id
    WHERE u.status = 'active'
    ORDER BY u.first_name
")->fetchAll(PDO::FETCH_ASSOC);

// Check if current user is an inspector
$stmt = $db->prepare("SELECT inspector_id FROM inspectors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUserInspectorId = $stmt->fetchColumn();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $establishment_id = $_POST['establishment_id'] ?? '';
    $inspection_type = $_POST['inspection_type'] ?? '';
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $inspector_id = $_POST['inspector_id'] ?: null;
    $priority = $_POST['priority'] ?? 'medium';
    $notes = $_POST['notes'] ?? '';
    
    if ($establishment_id && $inspection_type && $scheduled_date) {
        try {
            // Generate Reference Number
            $year = date('Y');
            $month = date('m');
            $pattern = "HSI-$year-$month%";
            
            $stmt = $db->prepare("SELECT reference_number FROM inspections WHERE reference_number LIKE ? ORDER BY inspection_id DESC LIMIT 1");
            $stmt->execute([$pattern]);
            $lastRef = $stmt->fetchColumn();
            
            if ($lastRef) {
                $lastNumber = (int)substr($lastRef, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            $reference_number = "HSI-$year-$month" . str_pad((string)$newNumber, 4, '0', STR_PAD_LEFT);

            $stmt = $db->prepare("
                INSERT INTO inspections (
                    reference_number, establishment_id, inspection_type, 
                    inspector_id, scheduled_date, priority, status, 
                    inspector_notes, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
            ");
            
            $stmt->execute([
                $reference_number,
                $establishment_id,
                $inspection_type,
                $inspector_id,
                $scheduled_date,
                $priority,
                $notes,
                $_SESSION['user_id']
            ]);
            
            $inspection_id = $db->lastInsertId();
            $success = "Inspection #$reference_number scheduled!";
            
            // Success response for toast if needed, but we follow redirect pattern
            header("refresh:1.5;url=/inspections/view?id=$inspection_id");
        } catch (PDOException $e) {
            $error = "Execution Error: " . $e->getMessage();
        }
    } else {
        $error = "Required fields missing.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Inspection - Health & Safety System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        select { background-image: none !important; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.5; cursor: pointer; }
    </style>
</head>
<body class="bg-[#0b0c10] font-sans antialiased text-slate-200 overflow-hidden">
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
                <div class="flex items-center">
                    <a href="/scheduling" class="h-10 w-10 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all mr-5">
                        <i class="fas fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-white tracking-tight">Schedule Inspection</h1>
                        <p class="text-[10px] text-slate-500 uppercase font-black tracking-widest mt-0.5">New Field Assignment</p>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                <div class="max-w-3xl mx-auto">
                    <?php if ($error): ?>
                        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-4 mb-6 rounded-2xl flex items-center animate-pulse">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <p class="text-sm font-bold"><?= $error ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 mb-6 rounded-2xl flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            <p class="text-sm font-bold"><?= $success ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="bg-[#15181e] rounded-3xl shadow-2xl border border-white/5 overflow-hidden border-t-4 border-t-blue-600">
                        <div class="p-8">
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Establishment <span class="text-rose-500">*</span></label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-600 group-focus-within:text-blue-500 transition-colors">
                                            <i class="fas fa-building text-sm"></i>
                                        </span>
                                        <select name="establishment_id" required 
                                                class="w-full pl-11 pr-10 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none"
                                                onchange="updatePreview(this)">
                                            <option value="">Select Target Establishment</option>
                                            <?php foreach ($establishments as $est): ?>
                                                <option value="<?= $est['establishment_id'] ?>" 
                                                        data-type="<?= htmlspecialchars($est['type']) ?>"
                                                        data-addr="<?= htmlspecialchars($est['address_street'] . ', ' . $est['address_barangay']) ?>">
                                                    <?= htmlspecialchars($est['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-600">
                                            <i class="fas fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    
                                    <div id="est-preview" class="mt-4 p-4 bg-blue-500/5 rounded-2xl border border-blue-500/10 hidden">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 rounded-full bg-blue-500 mr-3 animate-pulse"></div>
                                            <div>
                                                <span id="preview-type" class="text-[9px] font-black text-blue-500 uppercase tracking-widest block"></span>
                                                <span id="preview-addr" class="text-xs text-slate-400"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Type <span class="text-rose-500">*</span></label>
                                        <select name="inspection_type" required class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer">
                                            <option value="food_safety">Food Safety</option>
                                            <option value="fire_safety">Fire Safety</option>
                                            <option value="sanitation">Sanitation</option>
                                            <option value="building_safety">Building Safety</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Date <span class="text-rose-500">*</span></label>
                                        <input type="date" name="scheduled_date" required min="<?= date('Y-m-d') ?>"
                                               class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Priority</label>
                                        <div class="flex space-x-3">
                                            <?php foreach(['medium' => 'Standard', 'high' => 'High'] as $val => $lbl): ?>
                                                <label class="flex-1 relative group cursor-pointer">
                                                    <input type="radio" name="priority" value="<?= $val ?>" <?= $val === 'medium' ? 'checked' : '' ?> class="peer sr-only">
                                                    <div class="py-3 text-center rounded-xl border border-white/5 bg-[#0b0c10] text-slate-500 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 transition-all font-bold text-[10px] uppercase tracking-widest">
                                                        <?= $lbl ?>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Assign Inspector</label>
                                        <select name="inspector_id" class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none">
                                            <option value="">Auto-Assign (Based on availability)</option>
                                            <?php foreach ($inspectors as $inspector): ?>
                                                <option value="<?= $inspector['inspector_id'] ?>" <?= $inspector['inspector_id'] == $currentUserInspectorId ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($inspector['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-3">Notes</label>
                                    <textarea name="notes" rows="3" placeholder="Additional instructions..."
                                              class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white focus:ring-2 focus:ring-blue-500/50 outline-none transition-all resize-none placeholder:text-slate-700"></textarea>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-widest text-xs py-5 rounded-2xl shadow-xl shadow-blue-900/20 transition-all active:scale-[0.98] flex items-center justify-center">
                                        <i class="fas fa-calendar-plus mr-3"></i> Schedule Inspection
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function updatePreview(select) {
            const preview = document.getElementById('est-preview');
            if (select.value) {
                const opt = select.options[select.selectedIndex];
                document.getElementById('preview-type').textContent = opt.dataset.type.replace('_', ' ');
                document.getElementById('preview-addr').textContent = opt.dataset.addr;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
