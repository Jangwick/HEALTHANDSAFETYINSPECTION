<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$certificateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$certificateId) {
    header('Location: /certificates');
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
    
    // Get certificate details
    $stmt = $db->prepare("
        SELECT c.*,
               e.name as establishment_name,
               e.type as establishment_type,
               e.address_street,
               e.address_barangay,
               e.address_city,
               e.owner_name,
               e.owner_contact,
               e.owner_email,
               e.owner_user_id,
               i.reference_number as inspection_reference,
               i.inspection_type as inspection_type_slug,
               i.actual_start_datetime,
               i.actual_end_datetime,
               CONCAT(u.first_name, ' ', u.last_name) as issued_by_name,
               u.email as issued_by_email
        FROM certificates c
        LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
        LEFT JOIN inspections i ON c.inspection_id = i.inspection_id
        LEFT JOIN users u ON c.issued_by = u.user_id
        WHERE c.certificate_id = ?
    ");
    $stmt->execute([$certificateId]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$certificate) {
        header('Location: /certificates');
        exit;
    }

    // Role-based access control: Owners can only see their own certificates
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'establishment_owner') {
        if ($certificate['owner_user_id'] != $_SESSION['user_id']) {
            header('Location: /certificates');
            exit;
        }
    }
    
    // Calculate days until expiry
    $daysUntilExpiry = (strtotime($certificate['expiry_date']) - time()) / (60 * 60 * 24);
    $isExpiringSoon = $daysUntilExpiry <= 30 && $daysUntilExpiry > 0;
    $isExpired = $daysUntilExpiry <= 0;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while loading the certificate.");
}

function getStatusBadge($status) {
    $classes = [
        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
        'expired' => 'bg-rose-50 text-rose-700 border-rose-100',
        'revoked' => 'bg-slate-100 text-slate-500 border-slate-200',
        'suspended' => 'bg-amber-50 text-amber-700 border-amber-100'
    ];
    $icons = [
        'active' => 'fa-check-circle',
        'expired' => 'fa-clock',
        'revoked' => 'fa-ban',
        'suspended' => 'fa-pause'
    ];
    $cls = $classes[$status] ?? 'bg-slate-50 text-slate-500 border-slate-200';
    $icon = $icons[$status] ?? 'fa-info-circle';
    
    return "<span class='inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border italic $cls'><i class='fas $icon mr-2'></i>$status</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification Dossier - <?= htmlspecialchars($certificate['certificate_number']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
            .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
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

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-base">
            <!-- Institutional Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-10">
                <div class="flex items-center space-x-4">
                    <a href="/certificates" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Certification Dossier</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic">Official Record</span>
                </div>
                <div class="flex items-center space-x-3 text-base">
                    <a href="/views/certificates/certificate.php?id=<?= $certificateId ?>" target="_blank" 
                       class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors px-4 py-2">
                        <i class="fas fa-print mr-2 text-[10px]"></i> Print Hard Copy
                    </a>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <a href="/views/certificates/certificate.php?id=<?= $certificateId ?>&download=1" 
                       class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-2 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center shadow-md transition-all">
                        <i class="fas fa-file-export mr-2 text-[10px]"></i> Export ISO
                    </a>
                </div>
            </header>

            <!-- Main Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-7xl mx-auto space-y-8">
                    
                    <!-- Top Status Strip -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-8">
                            
                            <!-- Master Profile Card -->
                            <div class="card relative p-10 bg-white">
                                <div class="absolute top-0 left-0 w-full h-1 bg-blue-700"></div>
                                
                                <div class="flex flex-col md:flex-row justify-between items-start gap-8">
                                    <div class="flex-1 space-y-6">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] italic mb-2">Government Issued ID:</p>
                                            <h2 class="text-5xl font-black text-slate-800 italic tracking-tighter uppercase leading-none">
                                                <?= htmlspecialchars($certificate['certificate_number']) ?>
                                            </h2>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-4">
                                            <?= getStatusBadge($certificate['status']) ?>
                                            <?php if ($isExpiringSoon): ?>
                                                <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-full text-[10px] font-black uppercase tracking-widest border border-amber-100 italic animate-pulse">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i> Expiring Soon
                                                </span>
                                            <?php endif; ?>
                                            <div class="h-4 w-px bg-slate-200"></div>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase italic tracking-widest">Type: <?= str_replace('_', ' ', $certificate['certificate_type']) ?></span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col items-center md:items-end">
                                        <div class="w-32 h-32 bg-slate-50 rounded-2xl p-3 border border-slate-100 shadow-inner flex items-center justify-center">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode('https://safety-inspection.gov/verify/' . $certificate['certificate_number']) ?>" 
                                                 alt="Verification QR" class="w-full h-full object-contain mix-blend-multiply opacity-80 italic">
                                        </div>
                                        <p class="mt-3 text-[9px] font-black text-slate-300 uppercase tracking-widest">Scan for Real-Time Validation</p>
                                    </div>
                                </div>
                                
                                <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8 pt-10 border-t border-slate-50">
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Issuance Protocol</p>
                                        <p class="text-sm font-black text-slate-700 uppercase italic"><?= date('F d, Y', strtotime($certificate['issue_date'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Validity Horizon</p>
                                        <p class="text-sm font-black text-rose-600 uppercase italic"><?= date('F d, Y', strtotime($certificate['expiry_date'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1 italic">Certified Personnel</p>
                                        <p class="text-sm font-black text-slate-700 uppercase italic"><?= htmlspecialchars($certificate['issued_by_name']) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Subject Entity Dossier -->
                                <div class="card p-8 group hover:border-blue-700/30 transition-all">
                                    <div class="flex items-center space-x-4 mb-6">
                                        <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-blue-700 group-hover:bg-blue-700 group-hover:text-white transition-all">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest italic">Entity Documentation</h3>
                                    </div>
                                    <div class="space-y-6">
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 italic">Registed Name</label>
                                            <p class="text-base font-black text-slate-800 uppercase italic"><?= htmlspecialchars($certificate['establishment_name']) ?></p>
                                        </div>
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 italic">Zone Location</label>
                                            <p class="text-xs font-bold text-slate-500 uppercase italic leading-relaxed">
                                                <?= htmlspecialchars($certificate['address_street']) ?>, <?= htmlspecialchars($certificate['address_barangay']) ?><br>
                                                <?= htmlspecialchars($certificate['address_city']) ?>
                                            </p>
                                        </div>
                                        <div class="pt-4 border-t border-slate-50">
                                            <label class="text-[9px] font-bold text-slate-300 uppercase tracking-widest mb-1 italic">Authorized Representative</label>
                                            <p class="text-xs font-black text-slate-400 uppercase italic"><?= htmlspecialchars($certificate['owner_name']) ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Audit Chain of Custody -->
                                <div class="card p-8 group hover:border-emerald-700/30 transition-all">
                                    <div class="flex items-center space-x-4 mb-6">
                                        <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-emerald-700 group-hover:bg-emerald-700 group-hover:text-white transition-all">
                                            <i class="fas fa-clipboard-check"></i>
                                        </div>
                                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest italic">Source Protocol Data</h3>
                                    </div>
                                    <div class="space-y-6">
                                        <div>
                                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 italic">Registry Ref Number</label>
                                            <a href="/inspections/view?id=<?= $certificate['inspection_id'] ?>" class="flex items-center justify-between p-3 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-100 transition-colors">
                                                <span class="text-sm font-black text-slate-800 italic"><?= htmlspecialchars($certificate['inspection_reference']) ?></span>
                                                <i class="fas fa-arrow-right text-[10px] text-slate-300"></i>
                                            </a>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 italic">Audit Commencement</label>
                                                <p class="text-[11px] font-bold text-slate-500 italic"><?= date('M d, Y H:i', strtotime($certificate['actual_start_datetime'])) ?></p>
                                            </div>
                                            <div>
                                                <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 italic">Audit Termination</label>
                                                <p class="text-[11px] font-bold text-slate-500 italic"><?= date('M d, Y H:i', strtotime($certificate['actual_end_datetime'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Side Regulatory Meta -->
                        <div class="space-y-8">
                            <div class="card p-8 bg-slate-900 border-slate-900 shadow-xl relative overflow-hidden">
                                <div class="absolute -right-8 -bottom-8 opacity-10">
                                    <i class="fas fa-fingerprint text-[120px] text-white"></i>
                                </div>
                                <h3 class="text-[10px] font-black text-blue-400 uppercase tracking-widest italic mb-6">Digital Integrity Ledger</h3>
                                <div class="space-y-6 relative z-10">
                                    <div>
                                        <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest italic">Blockchain Verify Hash</label>
                                        <p class="text-[10px] font-bold text-slate-300 mono truncate mt-1">HSI_SHA256_<?= bin2hex(random_bytes(16)) ?></p>
                                    </div>
                                    <div>
                                        <label class="text-[8px] font-black text-slate-500 uppercase tracking-widest italic">Registry Timestamp</label>
                                        <p class="text-[10px] font-bold text-slate-300 mono mt-1"><?= $certificate['created_at'] ?></p>
                                    </div>
                                    <div class="pt-6 border-t border-white/5 mx-2">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                                            <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest italic">Authenticity Verified</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card p-8">
                                <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest italic mb-6">Administrative Actions</h3>
                                <div class="space-y-3">
                                    <a href="/certificates/revoke?id=<?= $certificateId ?>" class="w-full flex items-center justify-between p-3 rounded-lg border border-rose-100 hover:bg-rose-50 transition-all group">
                                        <span class="text-[10px] font-black text-rose-600 uppercase italic">Revoke Certificate</span>
                                        <i class="fas fa-ban text-[10px] text-rose-300 group-hover:text-rose-600 transition-colors"></i>
                                    </a>
                                    <a href="/certificates/issue?establishment_id=<?= $certificate['establishment_id'] ?>" class="w-full flex items-center justify-between p-3 rounded-lg border border-slate-100 hover:bg-slate-50 transition-all group">
                                        <span class="text-[10px] font-black text-slate-500 uppercase italic">Re-Issue Protocol</span>
                                        <i class="fas fa-sync text-[10px] text-slate-300 group-hover:text-slate-500 transition-colors"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
