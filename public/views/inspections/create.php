<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Get establishments
$establishments = $db->query("SELECT establishment_id, name, type, address_street, address_barangay, address_city FROM establishments WHERE compliance_status != 'revoked' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get inspectors
$inspectors = $db->query("
    SELECT i.inspector_id, CONCAT(u.first_name, ' ', u.last_name) as full_name
    FROM users u
    INNER JOIN inspectors i ON u.user_id = i.user_id
    WHERE u.status = 'active'
    ORDER BY u.first_name
")->fetchAll(PDO::FETCH_ASSOC);

// Current user inspector check
$stmt = $db->prepare("SELECT inspector_id FROM inspectors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUserInspectorId = $stmt->fetchColumn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estId = $_POST['establishment_id'] ?? '';
    $type = $_POST['inspection_type'] ?? '';
    $date = $_POST['scheduled_date'] ?? '';
    $insId = $_POST['inspector_id'] ?: null;
    $priority = $_POST['priority'] ?? 'medium';
    $notes = $_POST['notes'] ?? '';
    
    if ($estId && $type && $date) {
        try {
            $year = date('Y'); $month = date('m');
            $pattern = "HSI-$year-$month%";
            $st = $db->prepare("SELECT reference_number FROM inspections WHERE reference_number LIKE ? ORDER BY inspection_id DESC LIMIT 1");
            $st->execute([$pattern]);
            $lastRef = $st->fetchColumn();
            $newNum = $lastRef ? (int)substr($lastRef, -4) + 1 : 1;
            $refNum = "HSI-$year-$month" . str_pad((string)$newNum, 4, '0', STR_PAD_LEFT);

            $st = $db->prepare("INSERT INTO inspections (reference_number, establishment_id, inspection_type, inspector_id, scheduled_date, priority, status, inspector_notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())");
            $st->execute([$refNum, $estId, $type, $insId, $date, $priority, $notes, $_SESSION['user_id']]);
            header('Location: /inspections/view?id=' . $db->lastInsertId() . '&success=1');
            exit;
        } catch (PDOException $e) { $error = "Protocol registry failure: " . $e->getMessage(); }
    } else { $error = "Mandatory registry fields missing."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Audit - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
            .form-input { @apply w-full px-4 py-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all shadow-sm; }
            .form-label { @apply block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1; }
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
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Audit Assignment</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic">New Field Schedule</span>
                </div>
            </header>

            <!-- Scrollable Form -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50">
                <div class="max-w-3xl mx-auto">
                    <?php if ($error): ?>
                        <div class="mb-8 p-4 bg-rose-50 border border-rose-100 rounded-xl flex items-center text-rose-700 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-exclamation-triangle mr-3"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8 pb-12">
                        <div class="card relative p-8">
                            <div class="absolute top-0 left-0 w-full h-1 bg-blue-700"></div>
                            
                            <div class="mb-8 flex justify-between items-center border-b border-slate-50 pb-4">
                                <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-calendar-check mr-2 text-blue-700"></i> Deployment Parameters
                                </h2>
                                <span class="text-[9px] font-bold text-slate-300 italic">Registry Record Entry</span>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="form-label">Target Establishment <span class="text-rose-500">*</span></label>
                                    <select name="establishment_id" required class="form-input appearance-none">
                                        <option value="">Select Inspected Entity</option>
                                        <?php foreach ($establishments as $est): ?>
                                            <option value="<?= $est['establishment_id'] ?>">
                                                <?= htmlspecialchars($est['name']) ?> 
                                                (<?= htmlspecialchars($est['address_barangay']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="form-label">Protocol Type <span class="text-rose-500">*</span></label>
                                        <select name="inspection_type" required class="form-input appearance-none">
                                            <option value="food_safety">Food Safety Protocol</option>
                                            <option value="fire_safety">Fire Safety Protocol</option>
                                            <option value="sanitation">General Sanitation</option>
                                            <option value="building_safety">Structural Compliance</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Deployment Date <span class="text-rose-500">*</span></label>
                                        <input type="date" name="scheduled_date" required min="<?= date('Y-m-d') ?>" class="form-input">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="form-label">Audit Priority</label>
                                        <div class="flex space-x-2">
                                            <label class="flex-1">
                                                <input type="radio" name="priority" value="medium" checked class="sr-only peer">
                                                <div class="text-[10px] font-black text-center py-3 border border-slate-200 rounded-lg cursor-pointer peer-checked:bg-blue-700 peer-checked:text-white peer-checked:border-blue-700 transition-all uppercase italic">Standard</div>
                                            </label>
                                            <label class="flex-1">
                                                <input type="radio" name="priority" value="high" class="sr-only peer">
                                                <div class="text-[10px] font-black text-center py-3 border border-slate-200 rounded-lg cursor-pointer peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600 transition-all uppercase italic">Critical</div>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Assigned Personnel</label>
                                        <select name="inspector_id" class="form-input appearance-none">
                                            <option value="">Auto-Assign Personnel</option>
                                            <?php foreach ($inspectors as $inspector): ?>
                                                <option value="<?= $inspector['inspector_id'] ?>" <?= $inspector['inspector_id'] == $currentUserInspectorId ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($inspector['full_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="form-label">Deployment Instructions</label>
                                    <textarea name="notes" rows="3" placeholder="Additional audit specific instructions..." class="form-input resize-none"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
                            <div class="text-[9px] font-bold text-slate-400 italic">
                                Deployment records are linked to individual personnel performance metrics.
                            </div>
                            <div class="flex items-center space-x-4">
                                <a href="/inspections" class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors">Abort</a>
                                <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-3 rounded-lg text-xs font-bold uppercase tracking-widest transition-all shadow-md">
                                    <i class="fas fa-check-circle mr-2"></i> Commit to Schedule
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
