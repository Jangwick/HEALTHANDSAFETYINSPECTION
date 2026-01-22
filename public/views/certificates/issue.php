<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

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
    
    // Get completed inspections without certificates
    $inspections = $db->query("
        SELECT i.*, 
               e.name as establishment_name,
               e.type as establishment_type
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        LEFT JOIN certificates c ON i.inspection_id = c.inspection_id
        WHERE i.status = 'completed' 
        AND c.certificate_id IS NULL
        ORDER BY i.actual_end_datetime DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inspectionId = (int)$_POST['inspection_id'];
        $certificateType = $_POST['certificate_type'];
        $validityMonths = (int)$_POST['validity_months'];
        $remarks = trim($_POST['remarks'] ?? '');
        
        // Get inspection details
        $stmt = $db->prepare("
            SELECT i.*, e.establishment_id 
            FROM inspections i
            JOIN establishments e ON i.establishment_id = e.establishment_id
            WHERE i.inspection_id = ?
        ");
        $stmt->execute([$inspectionId]);
        $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inspection) {
            // Generate certificate number
            $year = date('Y');
            $stmt = $db->query("SELECT COUNT(*) FROM certificates WHERE YEAR(issue_date) = $year");
            $count = $stmt->fetchColumn() + 1;
            $certificateNumber = 'CERT-' . $year . '-' . str_pad((string)$count, 5, '0', STR_PAD_LEFT);
            
            // Calculate dates
            $issueDate = date('Y-m-d');
            $expiryDate = date('Y-m-d', strtotime("+{$validityMonths} months"));
            
            // Insert certificate
            $stmt = $db->prepare("
                INSERT INTO certificates 
                (certificate_number, establishment_id, inspection_id, certificate_type, 
                 issue_date, expiry_date, status, issued_by, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, NOW())
            ");
            
            $stmt->execute([
                $certificateNumber,
                $inspection['establishment_id'],
                $inspectionId,
                $certificateType,
                $issueDate,
                $expiryDate,
                $_SESSION['user_id'],
                $remarks
            ]);
            
            $certificateId = $db->lastInsertId();
            
            // Update establishment compliance status
            $db->prepare("UPDATE establishments SET compliance_status = 'compliant' WHERE establishment_id = ?")->execute([$inspection['establishment_id']]);
            
            $_SESSION['success'] = 'Registry updated: Certificate issued for ' . $certificateNumber;
            header('Location: /views/certificates/view.php?id=' . $certificateId);
            exit;
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Registry error: Protocol cannot be committed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Certificate - Health & Safety Insight</title>
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
            $activePage = 'certificates';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-base">
            <!-- Institutional Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-10">
                <div class="flex items-center space-x-4">
                    <a href="/certificates" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Certification Issuance</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic">Protocol Registry</span>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50 text-base">
                <div class="max-w-4xl mx-auto">
                    
                    <?php if (isset($error)): ?>
                        <div class="mb-8 p-4 bg-rose-50 border border-rose-100 rounded-xl flex items-center text-rose-700 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-exclamation-triangle mr-3"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($inspections)): ?>
                        <div class="card p-12 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-6 text-slate-200">
                                <i class="fas fa-clipboard-check text-2xl"></i>
                            </div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest italic mb-2">Registry Segment Null</h3>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-8">No completed audits await certification at this time.</p>
                            <a href="/inspections" class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-3 rounded-lg text-xs font-bold uppercase tracking-widest transition-all shadow-md inline-flex items-center">
                                <i class="fas fa-search-dollar mr-2 text-[10px]"></i> Review Master Audit Registry
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="space-y-8 pb-12">
                            <div class="card relative p-10">
                                <div class="absolute top-0 left-0 w-full h-1 bg-blue-700"></div>
                                
                                <div class="mb-10 border-b border-slate-50 pb-6">
                                    <h2 class="text-xs font-black text-slate-800 uppercase tracking-widest flex items-center">
                                        <i class="fas fa-award mr-3 text-blue-700"></i> Protocol Parameters
                                    </h2>
                                    <p class="text-[9px] text-slate-300 font-bold uppercase tracking-widest italic mt-1">Institutional Deployment 2.0</p>
                                </div>

                                <div class="space-y-8">
                                    <!-- Source Audit -->
                                    <div>
                                        <label class="form-label">Subject Audit Protocol <span class="text-rose-500">*</span></label>
                                        <select name="inspection_id" id="inspection_id" class="form-input appearance-none bg-slate-50/50" required onchange="updateInspectionInfo()">
                                            <option value="">Select Completed Audit Record</option>
                                            <?php foreach ($inspections as $insp): ?>
                                                <option value="<?= $insp['inspection_id'] ?>" 
                                                        data-type="<?= $insp['inspection_type'] ?>"
                                                        data-establishment="<?= htmlspecialchars($insp['establishment_name']) ?>"
                                                        data-date="<?= date('M d, Y', strtotime($insp['actual_end_datetime'])) ?>"
                                                        data-reference="<?= htmlspecialchars($insp['reference_number']) ?>">
                                                    <?= htmlspecialchars($insp['reference_number']) ?> - <?= htmlspecialchars($insp['establishment_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Selected Audit Meta (Dynamic) -->
                                    <div id="inspection-info" class="hidden grid grid-cols-2 md:grid-cols-4 gap-6 p-6 bg-slate-50 rounded-2xl border border-slate-100 animate-in fade-in duration-500">
                                        <div class="col-span-2">
                                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Subject Entity</p>
                                            <p id="info-establishment" class="text-xs font-black text-slate-700 italic uppercase"></p>
                                        </div>
                                        <div>
                                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Audit Type</p>
                                            <p id="info-type" class="text-xs font-black text-slate-700 italic uppercase"></p>
                                        </div>
                                        <div>
                                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Closed Date</p>
                                            <p id="info-date" class="text-xs font-black text-slate-700 italic uppercase"></p>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div>
                                            <label class="form-label">Certification Classification <span class="text-rose-500">*</span></label>
                                            <select name="certificate_type" id="certificate_type" class="form-input" required>
                                                <option value="food_safety">Food Safety Protocol</option>
                                                <option value="building_safety">Building Safety Protocol</option>
                                                <option value="fire_safety">Fire Safety Protocol</option>
                                                <option value="sanitation">Sanitation Protocol</option>
                                                <option value="occupational_health">Occupational Health</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Validity Horizon <span class="text-rose-500">*</span></label>
                                            <select name="validity_months" class="form-input" required id="validity_selector">
                                                <option value="6">6 Months Segment</option>
                                                <option value="12" selected>12 Months Segment (Default)</option>
                                                <option value="24">24 Months Segment</option>
                                                <option value="36">36 Months Segment</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="form-label">Administrative Annotation</label>
                                        <textarea name="remarks" class="form-input resize-none" rows="3" placeholder="Reference additional regulatory notes or specific conditions..."></textarea>
                                    </div>

                                    <!-- Pulse Preview -->
                                    <div class="p-6 border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/30">
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-[10px] font-black text-slate-800 uppercase tracking-[0.2em] italic">Commitment Preview</h4>
                                            <div class="flex items-center space-x-2">
                                                <div class="w-1.5 h-1.5 rounded-full bg-blue-700 animate-pulse"></div>
                                                <span class="text-[8px] font-bold text-blue-700 uppercase tracking-widest">Real-Time Ledger Forecast</span>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-8">
                                            <div>
                                                <p class="text-[8px] font-bold text-slate-400 uppercase italic">Entry Sequence</p>
                                                <p class="text-[11px] font-black text-slate-800 italic uppercase"><?= date('F d, Y') ?></p>
                                            </div>
                                            <div>
                                                <p class="text-[8px] font-bold text-slate-400 uppercase italic">Scheduled De-Listing</p>
                                                <p id="expiry-preview" class="text-[11px] font-black text-slate-800 italic uppercase"><?= date('F d, Y', strtotime('+12 months')) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-6">
                                <div class="text-[9px] font-bold text-slate-400 italic uppercase tracking-widest">
                                    Certificates are legally binding registry entries. Verify all parameters.
                                </div>
                                <div class="flex items-center space-x-4">
                                    <a href="/certificates" class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors">Abort Registry</a>
                                    <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-10 py-3 rounded-lg text-xs font-bold uppercase tracking-widest transition-all shadow-md">
                                        <i class="fas fa-check-circle mr-2"></i> Commit to Ledger
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        function updateInspectionInfo() {
            const select = document.getElementById('inspection_id');
            const selectedOption = select.options[select.selectedIndex];
            const infoDiv = document.getElementById('inspection-info');
            
            if (selectedOption.value) {
                document.getElementById('info-establishment').textContent = selectedOption.dataset.establishment;
                document.getElementById('info-type').textContent = selectedOption.dataset.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                document.getElementById('info-date').textContent = selectedOption.dataset.date;
                infoDiv.classList.remove('hidden');
                
                const inspectionType = selectedOption.dataset.type;
                const certTypeSelect = document.getElementById('certificate_type');
                if (inspectionType && certTypeSelect) {
                    certTypeSelect.value = inspectionType;
                }
            } else {
                infoDiv.classList.add('hidden');
            }
        }
        
        document.getElementById('validity_selector').addEventListener('change', function() {
            const months = parseInt(this.value);
            const expiryDate = new Date();
            expiryDate.setMonth(expiryDate.getMonth() + months);
            
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('expiry-preview').textContent = expiryDate.toLocaleDateString('en-US', options).toUpperCase();
        });
    </script>
</body>
</html>
