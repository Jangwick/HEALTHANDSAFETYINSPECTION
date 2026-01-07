<?php
/**
 * Health & Safety Inspection System
 * Schedule New Inspection
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Get user info
$stmt = $db->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN user_roles ur ON u.user_id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $establishment_id = $_POST['establishment_id'] ?? '';
    $inspection_type = $_POST['inspection_type'] ?? '';
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $inspector_id = $_POST['inspector_id'] ?? $_SESSION['user_id'];
    $priority = $_POST['priority'] ?? 'medium';
    $notes = $_POST['notes'] ?? '';
    
    if ($establishment_id && $inspection_type && $scheduled_date) {
        try {
            // Generate Reference Number (Simplified copy from InspectionService)
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
            header("refresh:1.5;url=/views/inspections/view.php?id=$inspection_id");
        } catch (PDOException $e) {
            $error = "Error scheduling inspection: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
    }
}

// Get establishments
$establishments = $db->query("SELECT establishment_id, name, type, address_street, address_barangay, address_city FROM establishments WHERE compliance_status != 'revoked' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get inspectors
$inspectors = $db->query("
    SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as full_name, i.specializations
    FROM users u
    INNER JOIN user_roles ur ON u.user_id = ur.user_id
    INNER JOIN roles r ON ur.role_id = r.role_id
    LEFT JOIN inspectors i ON u.user_id = i.user_id
    WHERE r.role_name IN ('inspector', 'senior_inspector', 'admin', 'super_admin')
    AND u.status = 'active'
    ORDER BY u.first_name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Inspection - Health & Safety System</title>
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
                <div class="flex items-center">
                    <a href="/inspections/scheduling" class="mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-xl font-bold text-slate-800">Schedule New Inspection</h1>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-3xl mx-auto">
                    <?php if ($error): ?>
                        <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 p-4 mb-6 rounded shadow-sm flex items-center">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-6 rounded shadow-sm flex items-center">
                            <i class="fas fa-check-circle mr-3"></i>
                            <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-8">
                            <form method="POST" action="" class="space-y-6">
                                <!-- Establishment Selection -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Establishment <span class="text-rose-500">*</span></label>
                                    <select name="establishment_id" id="establishment_id" required 
                                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all appearance-none"
                                            onchange="updateEstablishmentPreview(this)">
                                        <option value="">Select an establishment...</option>
                                        <?php foreach ($establishments as $est): ?>
                                            <option value="<?= $est['establishment_id'] ?>" 
                                                    data-type="<?= htmlspecialchars($est['type']) ?>"
                                                    data-addr="<?= htmlspecialchars($est['address_street'] . ', ' . $est['address_barangay']) ?>">
                                                <?= htmlspecialchars($est['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="est-preview" class="mt-3 p-4 bg-blue-50 rounded-xl border border-blue-100 hidden">
                                        <div class="flex items-start">
                                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                            <div>
                                                <p id="preview-type" class="text-xs font-bold text-blue-800 uppercase tracking-wider mb-1"></p>
                                                <p id="preview-addr" class="text-sm text-blue-600 font-medium"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Inspection Type -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Inspection Type <span class="text-rose-500">*</span></label>
                                        <select name="inspection_type" required 
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                            <option value="food_safety">Food Safety</option>
                                            <option value="building_safety">Building Safety</option>
                                            <option value="workplace_safety">Workplace Safety</option>
                                            <option value="fire_safety">Fire Safety</option>
                                            <option value="sanitation">Sanitation</option>
                                        </select>
                                    </div>

                                    <!-- Priority -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Priority Level <span class="text-rose-500">*</span></label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <label class="relative flex items-center justify-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-all has-[:checked]:bg-blue-600 has-[:checked]:text-white has-[:checked]:border-blue-600 group">
                                                <input type="radio" name="priority" value="medium" checked class="sr-only">
                                                <span class="text-sm font-bold">Standard</span>
                                            </label>
                                            <label class="relative flex items-center justify-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition-all has-[:checked]:bg-rose-600 has-[:checked]:text-white has-[:checked]:border-rose-600 group">
                                                <input type="radio" name="priority" value="high" class="sr-only">
                                                <span class="text-sm font-bold">High Priority</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Scheduled Date -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Schedule Date <span class="text-rose-500">*</span></label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                            <input type="date" name="scheduled_date" required min="<?= date('Y-m-d') ?>"
                                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                        </div>
                                    </div>

                                    <!-- Assigned Inspector -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Assigned Inspector</label>
                                        <select name="inspector_id" 
                                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                            <?php foreach ($inspectors as $inspector): ?>
                                                <option value="<?= $inspector['user_id'] ?>" <?= $inspector['user_id'] === $_SESSION['user_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($inspector['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Notes & Special Instructions</label>
                                    <textarea name="notes" rows="4" 
                                              placeholder="Any details the inspector should know before arriving..."
                                              class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none transition-all resize-none"></textarea>
                                </div>

                                <div class="pt-6 flex space-x-4">
                                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg shadow-blue-200 transition-all active:scale-95 flex items-center justify-center">
                                        <i class="fas fa-calendar-check mr-2"></i> Confirm Schedule
                                    </button>
                                    <a href="/inspections/scheduling" class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-4 px-6 rounded-xl transition-all flex items-center justify-center">
                                        Cancel
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
                typeText.textContent = opt.dataset.type.replace('_', ' ');
                addrText.textContent = opt.dataset.addr;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
