<?php
/**
 * Health & Safety Inspection System
 * Schedule New Inspection
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            $success = "Inspection #$reference_number scheduled successfully!";
            
            // Redirect after 1.5 seconds
            header("refresh:1.5;url=/inspections/view?id=$inspection_id");
        } catch (PDOException $e) {
            $error = "Error scheduling inspection: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
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
        .highlight-none { -webkit-tap-highlight-color: transparent; }
        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
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
                    <a href="/inspections" class="h-10 w-10 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all mr-5">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Schedule New Inspection</h1>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-[#0b0c10]">
                <div class="max-w-4xl mx-auto">
                    <?php if ($error): ?>
                        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-5 mb-8 rounded-2xl flex items-center animate-pulse">
                            <i class="fas fa-exclamation-triangle mr-4 text-xl"></i>
                            <p class="font-bold"><?php echo  htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-5 mb-8 rounded-2xl flex items-center">
                            <i class="fas fa-check-circle mr-4 text-xl"></i>
                            <p class="font-bold"><?php echo  htmlspecialchars($success) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="bg-[#15181e] rounded-3xl shadow-2xl border border-white/5 overflow-hidden border-t-4 border-t-blue-600">
                        <div class="p-10">
                            <form method="POST" action="" class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Establishment Selection -->
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Establishment <span class="text-rose-500">*</span></label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-building"></i>
                                            </span>
                                            <select name="establishment_id" id="establishment_id" required 
                                                    class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer"
                                                    onchange="updateEstablishmentPreview(this)">
                                                <option value="">Select an establishment...</option>
                                                <?php foreach ($establishments as $est): ?>
                                                    <option value="<?php echo  $est['establishment_id'] ?>" 
                                                            data-type="<?php echo  htmlspecialchars($est['type']) ?>"
                                                            data-addr="<?php echo  htmlspecialchars($est['address_street'] . ', ' . $est['address_barangay']) ?>">
                                                        <?php echo  htmlspecialchars($est['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                                <i class="fas fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>
                                        
                                        <div id="est-preview" class="mt-4 p-5 bg-blue-500/5 rounded-2xl border border-blue-500/10 hidden">
                                            <div class="flex items-start">
                                                <div class="h-10 w-10 bg-blue-500/10 rounded-xl flex items-center justify-center mr-4 shrink-0 border border-blue-500/20">
                                                    <i class="fas fa-info-circle text-blue-500"></i>
                                                </div>
                                                <div>
                                                    <p id="preview-type" class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] mb-1"></p>
                                                    <p id="preview-addr" class="text-sm text-slate-400 font-medium leading-relaxed"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Inspection Type -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Inspection Type <span class="text-rose-500">*</span></label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-clipboard-list"></i>
                                            </span>
                                            <select name="inspection_type" required 
                                                    class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer">
                                                <option value="food_safety">Food Safety</option>
                                                <option value="building_safety">Building Safety</option>
                                                <option value="workplace_safety">Workplace Safety</option>
                                                <option value="fire_safety">Fire Safety</option>
                                                <option value="sanitation">Sanitation</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                                <i class="fas fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Priority -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Priority Level <span class="text-rose-500">*</span></label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <label class="relative flex items-center justify-center p-4 rounded-2xl border border-white/10 cursor-pointer hover:bg-white/[0.02] transition-all has-[:checked]:bg-blue-600 has-[:checked]:text-white has-[:checked]:border-blue-600 group shrink-0">
                                                <input type="radio" name="priority" value="medium" checked class="sr-only">
                                                <span class="text-xs font-black uppercase tracking-widest">Standard</span>
                                            </label>
                                            <label class="relative flex items-center justify-center p-4 rounded-2xl border border-white/10 cursor-pointer hover:bg-white/[0.02] transition-all has-[:checked]:bg-rose-600 has-[:checked]:text-white has-[:checked]:border-rose-600 group shrink-0">
                                                <input type="radio" name="priority" value="high" class="sr-only">
                                                <span class="text-xs font-black uppercase tracking-widest">High</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Scheduled Date -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Schedule Date <span class="text-rose-500">*</span></label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                            <input type="date" name="scheduled_date" required min="<?php echo  date('Y-m-d') ?>"
                                                   class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all">
                                        </div>
                                    </div>

                                    <!-- Assigned Inspector -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Assigned Inspector</label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-user-shield"></i>
                                            </span>
                                            <select name="inspector_id" 
                                                    class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer">
                                                <option value="">Choose Inspector...</option>
                                                <?php foreach ($inspectors as $inspector): ?>
                                                    <option value="<?php echo  $inspector['inspector_id'] ?>" <?php echo  $inspector['inspector_id'] == $currentUserInspectorId ? 'selected' : '' ?>>
                                                        <?php echo  htmlspecialchars($inspector['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                                <i class="fas fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Notes & Special Instructions</label>
                                        <textarea name="notes" rows="4" 
                                                  placeholder="Any details the inspector should know before arriving..."
                                                  class="w-full bg-[#0b0c10] border border-white/10 rounded-2xl py-4 px-5 text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all resize-none placeholder:text-slate-600"></textarea>
                                    </div>
                                </div>

                                <div class="pt-8 flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-[0.2em] text-xs py-5 px-8 rounded-2xl shadow-xl shadow-blue-900/30 transition-all active:scale-95 flex items-center justify-center group">
                                        <i class="fas fa-calendar-check mr-3 group-hover:scale-110 transition-transform"></i> Confirm Schedule
                                    </button>
                                    <a href="/inspections" class="flex-1 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white font-black uppercase tracking-[0.2em] text-xs py-5 px-8 rounded-2xl transition-all flex items-center justify-center border border-white/5">
                                        Discard Changes
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function updateEstablishmentPreview(select) {
            const preview = document.getElementById('est-preview');
            const typeText = document.getElementById('preview-type');
            const addrText = document.getElementById('preview-addr');
            
            if (select.value) {
                const opt = select.options[select.selectedIndex];
                typeText.textContent = opt.dataset.type.split('_').join(' ');
                addrText.textContent = opt.dataset.addr;
                preview.classList.remove('hidden');
                preview.classList.add('animate-in', 'fade-in', 'slide-in-from-top-2');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <div class="flex items-center">
                    <a href="/inspections" class="h-10 w-10 bg-white/5 border border-white/10 rounded-xl flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all mr-5">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Schedule New Inspection</h1>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-[#0b0c10]">
                <div class="max-w-4xl mx-auto">
                    <?php if ($error): ?>
                        <div class="bg-rose-500/10 border border-rose-500/20 text-rose-400 p-5 mb-8 rounded-2xl flex items-center animate-pulse">
                            <i class="fas fa-exclamation-triangle mr-4 text-xl"></i>
                            <p class="font-bold"><?php echo  htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-5 mb-8 rounded-2xl flex items-center">
                            <i class="fas fa-check-circle mr-4 text-xl"></i>
                            <p class="font-bold"><?php echo  htmlspecialchars($success) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="bg-[#15181e] rounded-3xl shadow-2xl border border-white/5 overflow-hidden border-t-4 border-t-blue-600">
                        <div class="p-10">
                            <form method="POST" action="" class="space-y-8">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Establishment Selection -->
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Establishment <span class="text-rose-500">*</span></label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-building"></i>
                                            </span>
                                            <select name="establishment_id" id="establishment_id" required 
                                                    class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer"
                                                    onchange="updateEstablishmentPreview(this)">
                                                <option value="">Select an establishment...</option>
                                                <?php foreach ($establishments as $est): ?>
                                                    <option value="<?php echo  $est['establishment_id'] ?>" 
                                                            data-type="<?php echo  htmlspecialchars($est['type']) ?>"
                                                            data-addr="<?php echo  htmlspecialchars($est['address_street'] . ', ' . $est['address_barangay']) ?>">
                                                        <?php echo  htmlspecialchars($est['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                                <i class="fas fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>
                                        
                                        <div id="est-preview" class="mt-4 p-5 bg-blue-500/5 rounded-2xl border border-blue-500/10 hidden">
                                            <div class="flex items-start">
                                                <div class="h-10 w-10 bg-blue-500/10 rounded-xl flex items-center justify-center mr-4 shrink-0 border border-blue-500/20">
                                                    <i class="fas fa-info-circle text-blue-500"></i>
                                                </div>
                                                <div>
                                                    <p id="preview-type" class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] mb-1"></p>
                                                    <p id="preview-addr" class="text-sm text-slate-400 font-medium leading-relaxed"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Inspection Type -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Inspection Type <span class="text-rose-500">*</span></label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-clipboard-list"></i>
                                            </span>
                                            <select name="inspection_type" required 
                                                    class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer">
                                                <option value="food_safety">Food Safety</option>
                                                <option value="building_safety">Building Safety</option>
                                                <option value="workplace_safety">Workplace Safety</option>
                                                <option value="fire_safety">Fire Safety</option>
                                                <option value="sanitation">Sanitation</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                                <i class="fas fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Priority -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Priority Level <span class="text-rose-500">*</span></label>
                                        <div class="grid grid-cols-2 gap-4">
                                            <label class="relative flex items-center justify-center p-4 rounded-2xl border border-white/10 cursor-pointer hover:bg-white/[0.02] transition-all has-[:checked]:bg-blue-600 has-[:checked]:text-white has-[:checked]:border-blue-600 group shrink-0">
                                                <input type="radio" name="priority" value="medium" checked class="sr-only">
                                                <span class="text-xs font-black uppercase tracking-widest">Standard</span>
                                            </label>
                                            <label class="relative flex items-center justify-center p-4 rounded-2xl border border-white/10 cursor-pointer hover:bg-white/[0.02] transition-all has-[:checked]:bg-rose-600 has-[:checked]:text-white has-[:checked]:border-rose-600 group shrink-0">
                                                <input type="radio" name="priority" value="high" class="sr-only">
                                                <span class="text-xs font-black uppercase tracking-widest">High</span>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Scheduled Date -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Schedule Date <span class="text-rose-500">*</span></label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                            <input type="date" name="scheduled_date" required min="<?php echo  date('Y-m-d') ?>"
                                                   class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all">
                                        </div>
                                    </div>

                                    <!-- Assigned Inspector -->
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Assigned Inspector</label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-500 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-user-shield"></i>
                                            </span>
                                            <select name="inspector_id" 
                                                    class="w-full pl-11 pr-4 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all appearance-none cursor-pointer">
                                                <option value="">Choose Inspector...</option>
                                                <?php foreach ($inspectors as $inspector): ?>
                                                    <option value="<?php echo  $inspector['inspector_id'] ?>" <?php echo  $inspector['inspector_id'] == $currentUserInspectorId ? 'selected' : '' ?>>
                                                        <?php echo  htmlspecialchars($inspector['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500">
                                                <i class="fas fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3">Notes & Special Instructions</label>
                                        <textarea name="notes" rows="4" 
                                                  placeholder="Any details the inspector should know before arriving..."
                                                  class="w-full bg-[#0b0c10] border border-white/10 rounded-2xl py-4 px-5 text-slate-200 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all resize-none placeholder:text-slate-600"></textarea>
                                    </div>
                                </div>

                                <div class="pt-8 flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-black uppercase tracking-[0.2em] text-xs py-5 px-8 rounded-2xl shadow-xl shadow-blue-900/30 transition-all active:scale-95 flex items-center justify-center group">
                                        <i class="fas fa-calendar-check mr-3 group-hover:scale-110 transition-transform"></i> Confirm Schedule
                                    </button>
                                    <a href="/inspections" class="flex-1 bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white font-black uppercase tracking-[0.2em] text-xs py-5 px-8 rounded-2xl transition-all flex items-center justify-center border border-white/5">
                                        Discard Changes
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function updateEstablishmentPreview(select) {
            const preview = document.getElementById('est-preview');
            const typeText = document.getElementById('preview-type');
            const addrText = document.getElementById('preview-addr');
            
            if (select.value) {
                const opt = select.options[select.selectedIndex];
                typeText.textContent = opt.dataset.type.split('_').join(' ');
                addrText.textContent = opt.dataset.addr;
                preview.classList.remove('hidden');
                preview.classList.add('animate-in', 'fade-in', 'slide-in-from-top-2');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
