<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$inspectionId = isset($_GET['inspection_id']) ? (int)$_GET['inspection_id'] : 0;

if (!$inspectionId) {
    die('Invalid inspection ID');
}

try {
    $db = Database::getConnection();
    
    // Get inspection details
    $stmt = $db->prepare("
        SELECT i.*, e.name AS establishment_name
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.id
        WHERE i.id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        die('Inspection not found');
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
        $forensicMetadata = $_POST['forensic_metadata'] ?? null;
        
        if (empty($description)) {
            $error = 'Description is required';
        } else {
            // Insert violation
            $stmt = $db->prepare("
                INSERT INTO violations 
                (inspection_id, establishment_id, description, violation_type, severity, corrective_action, deadline, gps_latitude, gps_longitude, forensic_metadata, status, reported_by, reported_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, NOW())
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
                $forensicMetadata,
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
                $filename = 'violation_' . $violationId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                    // Save document reference
                    $stmt = $db->prepare("
                        INSERT INTO documents 
                        (entity_type, entity_id, title, file_path, file_type, uploaded_by, created_at)
                        VALUES ('violation', ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $violationId,
                        'Violation Photo - ' . $description,
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
    $error = "An error occurred while saving the violation.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INFRACTION_REPORT :: #<?= $inspectionId ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.7.2/css/all.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @import "https://cdn.jsdelivr.net/npm/tailwindcss@4.0.0-alpha.25/dist/tailwind.min.css";
        :root { --glass: rgba(15, 23, 42, 0.8); }
        body { font-family: 'Inter', sans-serif; background: #020617; color: #f8fafc; overflow-x: hidden; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .glass { background: var(--glass); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.05); }
        .scan-line {
            width: 100%; height: 2px; background: rgba(244, 63, 94, 0.1);
            position: absolute; top: 0; left: 0; animation: scan 4s linear infinite; pointer-events: none;
        }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(244, 63, 94, 0.2); border-radius: 10px; }
    </style>
</head>
<body class="p-4 md:p-8 flex items-center justify-center min-h-screen">
    <div class="fixed inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 pointer-events-none"></div>
    
    <div class="glass w-full max-w-3xl rounded-[2.5rem] overflow-hidden relative border border-rose-500/20 shadow-2xl shadow-rose-900/20">
        <div class="scan-line"></div>
        
        <!-- Header Terminal -->
        <div class="bg-rose-600 px-8 py-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md">
                    <i class="fas fa-biohazard text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white italic tracking-tighter uppercase italic">REPORT_INFRACTION</h1>
                    <div class="mono text-[10px] text-rose-100 font-bold tracking-widest opacity-80 uppercase">
                        INSPECTION_LINK: #<?= str_pad((string)$inspectionId, 5, '0', STR_PAD_LEFT) ?>
                    </div>
                </div>
            </div>
            <button onclick="window.close()" class="w-10 h-10 rounded-full hover:bg-black/10 flex items-center justify-center text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-8 md:p-12 space-y-8">
            <!-- Entity Context -->
            <div class="flex items-center gap-6 p-6 bg-white/[0.02] rounded-3xl border border-white/5">
                <div class="w-16 h-16 glass rounded-2xl flex items-center justify-center text-rose-500 text-2xl">
                    <i class="fas fa-building-circle-exclamation"></i>
                </div>
                <div>
                    <div class="mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-black italic mb-1">TARGET_ENTITY</div>
                    <div class="text-2xl font-black text-white italic uppercase tracking-tighter italic">
                        <?= htmlspecialchars($inspection['establishment_name']) ?>
                    </div>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-[2rem] p-8 text-center space-y-6">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto">
                        <i class="fas fa-check-circle text-emerald-500 text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white italic uppercase tracking-tighter">INFRACTION_LOGGED</h3>
                        <p class="text-slate-400 mono text-xs mt-2 italic">Data has been successfully persisted to the registry.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button onclick="window.close()" class="px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl mono text-[10px] font-black uppercase tracking-widest transition-all shadow-lg shadow-emerald-900/40">
                            TERMINATE_SESSION
                        </button>
                        <button onclick="location.reload()" class="px-8 py-4 bg-white/5 hover:bg-white/10 text-white rounded-2xl border border-white/5 mono text-[10px] font-black uppercase tracking-widest transition-all">
                            NEW_RECORD_ENTRY
                        </button>
                    </div>
                </div>
            <?php elseif (isset($error)): ?>
                <div class="bg-rose-500/10 border border-rose-500/30 rounded-3xl p-6 flex items-center gap-4">
                    <i class="fas fa-triangle-exclamation text-rose-500 text-xl"></i>
                    <div class="text-rose-200 mono text-xs uppercase font-bold tracking-widest">ERROR: <?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!isset($success)): ?>
                <form method="POST" enctype="multipart/form-data" class="space-y-8" id="violationForm">
                    <!-- Forensic Metadata Hidden Fields -->
                    <input type="hidden" name="gps_latitude" id="gps_latitude">
                    <input type="hidden" name="gps_longitude" id="gps_longitude">
                    <input type="hidden" name="forensic_metadata" id="forensic_metadata">

                    <div class="space-y-3">
                        <label class="block mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold italic ml-2">VIOLATION_DESCRIPTION <span class="text-rose-500">*</span></label>
                        <textarea name="description" rows="3" required placeholder="NARRATIVE_OF_NON_COMPLIANCE..." 
                                  class="w-full bg-slate-950/80 border border-slate-800 rounded-3xl py-6 px-8 text-white text-sm mono uppercase tracking-widest focus:outline-none focus:border-rose-500/50 transition-all italic leading-relaxed"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="block mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold italic ml-2">INFRACTION_CATEGORY</label>
                            <div class="relative group">
                                <select name="violation_type" class="w-full bg-slate-950/80 border border-slate-800 rounded-2xl py-4 px-6 text-white text-[11px] mono uppercase tracking-widest appearance-none focus:outline-none focus:border-rose-500/50 cursor-pointer transition-all italic">
                                    <option value="">UNCATEGORIZED</option>
                                    <option value="food_safety">FOOD_SAFETY</option>
                                    <option value="sanitation">SANITATION_PROTOCOL</option>
                                    <option value="structural">STRUCTURAL_INTEGRITY</option>
                                    <option value="fire_safety">THERMAL_SAFETY</option>
                                    <option value="occupational_health">OCC_HEALTH</option>
                                    <option value="environmental">ENV_COMPLIANCE</option>
                                    <option value="documentation">DATA_INTEGRITY</option>
                                    <option value="other">OTHER_ANOMALY</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-700 pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold italic ml-2">SEVERITY_PROTOCOL <span class="text-rose-500">*</span></label>
                            <div class="relative group">
                                <select name="severity" required class="w-full bg-slate-950/80 border border-slate-800 rounded-2xl py-4 px-6 text-white text-[11px] mono uppercase tracking-widest appearance-none focus:outline-none focus:border-rose-500/50 cursor-pointer transition-all italic">
                                    <option value="minor">MINOR_NON_COMPLIANCE</option>
                                    <option value="major">MAJOR_PROTOCOL_BREAK</option>
                                    <option value="critical">CRITICAL_SYSTEM_RISK</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-6 top-1/2 -translate-y-1/2 text-slate-700 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold italic ml-2">CORRECTIVE_PROTOCOL_REQUIRED</label>
                        <textarea name="corrective_action" rows="2" placeholder="REMEDIATION_REQUIREMENTS..." 
                                  class="w-full bg-slate-950/80 border border-slate-800 rounded-3xl py-6 px-8 text-white text-sm mono uppercase tracking-widest focus:outline-none focus:border-rose-500/50 transition-all italic leading-relaxed"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-3">
                            <label class="block mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold italic ml-2">RESOLUTION_DEADLINE</label>
                            <input type="date" name="deadline" min="<?= date('Y-m-d') ?>" 
                                   class="w-full bg-slate-950/80 border border-slate-800 rounded-2xl py-4 px-6 text-white text-[11px] mono uppercase tracking-widest focus:outline-none focus:border-rose-500/50 transition-all italic">
                        </div>

                        <div class="space-y-3">
                            <label class="block mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold italic ml-2">VISUAL_EVIDENCE</label>
                            <label class="w-full bg-slate-950/80 border border-slate-800 rounded-2xl py-4 px-6 flex items-center justify-between cursor-pointer group hover:border-white/20 transition-all overflow-hidden">
                                <span class="text-[11px] mono text-slate-500 group-hover:text-slate-300 transition-colors italic truncate">INITIALIZE_CAPTURE...</span>
                                <i class="fas fa-camera text-slate-700 group-hover:text-rose-500 transition-colors"></i>
                                <input type="file" name="photo" accept="image/*" onchange="previewPhoto(event)" class="hidden">
                            </label>
                        </div>
                    </div>

                    <div id="previewContainer" class="hidden glass rounded-3xl p-4 border border-rose-500/20">
                        <div class="mono text-[8px] text-rose-500 uppercase tracking-widest font-black mb-3 italic">EVIDENCE_PREVIEW</div>
                        <img id="photoPreview" class="w-full h-48 object-cover rounded-2xl border border-white/5 shadow-2xl">
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full h-16 bg-rose-600 hover:bg-rose-500 text-white rounded-3xl flex items-center justify-center gap-4 transition-all shadow-xl shadow-rose-900/40 group active:scale-[0.98]">
                            <i class="fas fa-biohazard text-xl group-hover:scale-125 transition-transform"></i>
                            <span class="mono text-xs font-black uppercase tracking-[0.3em]">COMMIT_INFRACTION_DATA</span>
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
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    container.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                container.classList.add('hidden');
            }
        }

        <?php if (isset($success)): ?>
        if (window.opener && !window.opener.closed) {
            window.opener.location.reload();
        }
        <?php endif; ?>

        // Automatic Forensic Data Collection (LGU 4 AI Enhancement)
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Capture Geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => {
                    document.getElementById('gps_latitude').value = position.coords.latitude;
                    document.getElementById('gps_longitude').value = position.coords.longitude;
                    console.log('GPS captured:', position.coords.latitude, position.coords.longitude);
                }, (error) => {
                    console.error('GPS Capture Error:', error.message);
                }, { enableHighAccuracy: true });
            }

            // 2. Capture Device/Session Metadata
            const metadata = {
                browser: navigator.userAgent,
                platform: navigator.platform,
                screen_resolution: `${window.screen.width}x${window.screen.height}`,
                timestamp: new Date().toISOString(),
                language: navigator.language,
                time_on_site: performance.now(),
                memory: navigator.deviceMemory || 'unknown'
            };
            document.getElementById('forensic_metadata').value = JSON.stringify(metadata);
        });
    </script>
</body>
</html>
