<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$inspectionId = isset($_GET['inspection_id']) ? (int)$_GET['inspection_id'] : 0;

if (!$inspectionId) {
    die('Invalid inspection protocol association.');
}

try {
    $db = Database::getConnection();
    
    // Get inspection details - Fix table join (establishments use establishment_id)
    $stmt = $db->prepare("
        SELECT i.*, e.name AS establishment_name
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        WHERE i.inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        die('Institutional inspection record not found.');
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = trim($_POST['description'] ?? '');
        $violationType = trim($_POST['violation_type'] ?? '');
        $severity = $_POST['severity'] ?? 'minor';
        $correctiveAction = trim($_POST['corrective_action'] ?? '');
        $deadline = $_POST['deadline'] ?? null;
        $latitude = $_POST['gps_latitude'] ?? null;
        $longitude = $_POST['gps_longitude'] ?? null;
        
        if (empty($description)) {
            $error = 'Anomaly description is required for registry entry.';
        } else {
            // Insert violation
            $stmt = $db->prepare("
                INSERT INTO violations 
                (inspection_id, establishment_id, description, violation_type, severity, corrective_action, corrective_action_deadline, gps_latitude, gps_longitude, status, reported_by, reported_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, NOW())
            ");
            $stmt->execute([
                $inspectionId,
                $inspection['establishment_id'] ?? 0,
                $description,
                $violationType,
                $severity,
                $correctiveAction,
                $deadline ?: null,
                $latitude,
                $longitude,
                $_SESSION['user_id']
            ]);
            
            $violationId = $db->lastInsertId();
            
            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/violations/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'vlog_' . $violationId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                    // Update violation with photo path or add to documents
                    $stmt = $db->prepare("
                        INSERT INTO documents 
                        (entity_type, entity_id, title, file_path, file_type, uploaded_by, created_at)
                        VALUES ('violation', ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $violationId,
                        'Evidence Dossier - ' . substr($description, 0, 32),
                        'uploads/violations/' . $filename,
                        $extension,
                        $_SESSION['user_id']
                    ]);
                }
            }
            
            $success = true;
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Registry error: Protocol commit failed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anomaly Declaration :: #<?= $inspectionId ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-100; }
            .form-input { @apply w-full px-4 py-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all shadow-sm; }
            .form-label { @apply block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1 italic; }
            .mono { font-family: 'JetBrains Mono', monospace; }
        }
    </style>
</head>
<body class="p-4 md:p-8 flex items-center justify-center min-h-screen text-base">
    
    <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden relative border border-slate-200">
        
        <!-- Header Section -->
        <div class="bg-blue-700 px-10 py-8 flex items-center justify-between">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20">
                    <i class="fas fa-file-signature text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xs font-black text-white uppercase tracking-[0.2em]">Institutional Anomaly Declaration</h1>
                    <div class="mono text-[9px] text-blue-100 font-bold tracking-widest opacity-80 uppercase mt-0.5">
                        Audit Associated: #<?= str_pad((string)$inspectionId, 5, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>
            </div>
            <button onclick="window.close()" class="w-10 h-10 rounded-full hover:bg-black/10 flex items-center justify-center text-white transition-all">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <div class="p-10 space-y-10">
            <!-- Entity Context -->
            <div class="flex items-center gap-6 p-6 bg-slate-50 rounded-2xl border border-slate-100 italic">
                <div class="w-14 h-14 bg-white rounded-xl border border-slate-200 flex items-center justify-center text-blue-700 text-xl shadow-sm">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Subject Entity Registry</div>
                    <div class="text-xl font-black text-slate-800 uppercase tracking-tighter italic">
                        <?= htmlspecialchars($inspection['establishment_name']) ?>
                    </div>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="bg-emerald-50 border border-emerald-100 rounded-3xl p-10 text-center space-y-6 animate-in fade-in zoom-in duration-500">
                    <div class="w-20 h-20 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto">
                        <i class="fas fa-check-double text-emerald-600 text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 uppercase tracking-tighter italic">Infraction Logged Successfully</h3>
                        <p class="text-slate-500 text-xs mt-2 font-bold uppercase tracking-widest">The anomaly has been persisted to the master compliance registry.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                        <button onclick="window.close()" class="px-10 py-3.5 bg-blue-700 hover:bg-blue-800 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-lg">
                            Terminate Session
                        </button>
                        <button onclick="location.reload()" class="px-10 py-3.5 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl border border-slate-200 text-[10px] font-black uppercase tracking-widest transition-all">
                            New Record Entry
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" class="space-y-8" id="violationForm">
                    <!-- Forensic Metadata Hidden Fields -->
                    <input type="hidden" name="gps_latitude" id="gps_latitude">
                    <input type="hidden" name="gps_longitude" id="gps_longitude">

                    <?php if (isset($error)): ?>
                        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex items-center gap-4 animate-pulse">
                            <i class="fas fa-triangle-exclamation text-rose-500"></i>
                            <div class="text-rose-700 font-black uppercase text-[10px] tracking-widest">Protocol Error: <?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-2">
                        <label class="form-label">Anomaly Narrative <span class="text-rose-500">*</span></label>
                        <textarea name="description" rows="4" required placeholder="Describe the specific compliance breach observed..." 
                                  class="form-input resize-none italic leading-relaxed"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="form-label">Classification Tier</label>
                            <select name="violation_type" class="form-input appearance-none bg-slate-50 cursor-pointer italic font-bold">
                                <option value="">UNCATEGORIZED</option>
                                <option value="food_safety">FOOD SAFETY PROTOCOL</option>
                                <option value="sanitation">SANITATION & HYGIENE</option>
                                <option value="structural">STRUCTURAL INTEGRITY</option>
                                <option value="fire_safety">FIRE SAFETY SYSTEMS</option>
                                <option value="occupational_health">OCC_HEALTH HAZARD</option>
                                <option value="environmental">ENV_COMPLIANCE</option>
                                <option value="documentation">DOCUMENTATION BREACH</option>
                                <option value="other">MISC_ANOMALY</option>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="form-label">Severity Profile <span class="text-rose-500">*</span></label>
                            <select name="severity" required class="form-input appearance-none bg-slate-50 cursor-pointer italic font-bold">
                                <option value="minor">MINOR DEVIATION</option>
                                <option value="major">MAJOR SYSTEM BREACH</option>
                                <option value="critical">CRITICAL OPERATIONAL RISK</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="form-label">Mandatory Remediation Protocol</label>
                        <textarea name="corrective_action" rows="2" placeholder="Detail the specific actions required to clear this breach..." 
                                  class="form-input resize-none italic"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="form-label">Remediation Deadline</label>
                            <input type="date" name="deadline" min="<?= date('Y-m-d') ?>" 
                                   class="form-input bg-slate-50 italic font-bold">
                        </div>

                        <div class="space-y-2">
                            <label class="form-label">Forensic Evidence (Media)</label>
                            <label class="form-input flex items-center justify-between cursor-pointer group bg-slate-50 hover:bg-white transition-all border-dashed">
                                <span id="file-label" class="text-[11px] font-bold text-slate-400 group-hover:text-slate-600 transition-colors italic">Attach Photo Evidence...</span>
                                <i class="fas fa-camera text-slate-300 group-hover:text-blue-700 transition-colors"></i>
                                <input type="file" name="photo" accept="image/*" onchange="previewPhoto(event)" class="hidden">
                            </label>
                        </div>
                    </div>

                    <div id="previewContainer" class="hidden bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <div class="text-[9px] text-blue-700 uppercase tracking-widest font-black mb-3 italic">Evidence Preview Dossier</div>
                        <img id="photoPreview" class="w-full h-48 object-cover rounded-xl border border-slate-200">
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full h-16 bg-blue-700 hover:bg-blue-800 text-white rounded-2xl flex items-center justify-center gap-4 transition-all shadow-xl shadow-blue-900/10 group active:scale-[0.98]">
                            <i class="fas fa-shield-halved text-lg group-hover:rotate-12 transition-transform"></i>
                            <span class="text-[11px] font-black uppercase tracking-[0.2em] italic">Commit to Registry</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function previewPhoto(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('photoPreview');
            const container = document.getElementById('previewContainer');
            const label = document.getElementById('file-label');
            
            if (file) {
                label.textContent = file.name.toUpperCase();
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                container.classList.add('hidden');
                label.textContent = "Attach Photo Evidence...";
            }
        }

        // Get Geo-location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('gps_latitude').value = pos.coords.latitude;
                document.getElementById('gps_longitude').value = pos.coords.longitude;
            });
        }

        <?php if (isset($success)): ?>
        if (window.opener && !window.opener.closed) {
            window.opener.location.reload();
        }
        <?php endif; ?>
    </script>
</body>
</html>
