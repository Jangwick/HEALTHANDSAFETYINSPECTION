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
    $badges = [
        'active' => '<span class="inline-flex items-center px-3 py-1 bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase tracking-widest rounded-full border border-emerald-500/20 italic"><i class="fas fa-check-circle mr-2"></i>Active</span>',
        'expired' => '<span class="inline-flex items-center px-3 py-1 bg-rose-500/10 text-rose-500 text-[10px] font-black uppercase tracking-widest rounded-full border border-rose-500/20 italic"><i class="fas fa-clock mr-2"></i>Expired</span>',
        'revoked' => '<span class="inline-flex items-center px-3 py-1 bg-slate-500/10 text-slate-500 text-[10px] font-black uppercase tracking-widest rounded-full border border-slate-500/20 italic"><i class="fas fa-ban mr-2"></i>Revoked</span>',
        'suspended' => '<span class="inline-flex items-center px-3 py-1 bg-amber-500/10 text-amber-500 text-[10px] font-black uppercase tracking-widest rounded-full border border-amber-500/20 italic"><i class="fas fa-pause mr-2"></i>Suspended</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VALIDATION_DOSSIER: <?= htmlspecialchars($certificate['certificate_number']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root { --font-sans: 'Inter', sans-serif; --font-mono: 'JetBrains Mono', monospace; }
        body { font-family: var(--font-sans); background-color: #06070a; }
        .mono { font-family: var(--font-mono); }
        .glass { background: rgba(15, 17, 26, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }
        .scan-line { height: 2px; background: linear-gradient(to right, transparent, rgba(59, 130, 246, 0.5), transparent); position: absolute; width: 100%; z-index: 20; animation: scan 3s linear infinite; }
    </style>
</head>
<body class="bg-[#06070a] text-slate-300 antialiased overflow-hidden">
    <div class="flex h-screen relative">
        <!-- Background Hazards -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none opacity-20">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-900/10 blur-[120px] rounded-full"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-indigo-900/10 blur-[120px] rounded-full"></div>
        </div>

        <?php 
            $activePage = 'certificates';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <div class="flex-1 flex flex-col relative z-10 min-w-0">
            <!-- Terminal Header -->
            <header class="h-24 glass border-b border-white/5 flex items-center justify-between px-12 shrink-0">
                <div class="flex items-center gap-8">
                    <a href="/certificates" class="w-12 h-12 glass rounded-2xl flex items-center justify-center hover:bg-white/5 transition-all group border-blue-500/20">
                        <i class="fas fa-chevron-left text-slate-400 group-hover:text-blue-400 transition-colors"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <span class="px-2 py-0.5 bg-blue-600/20 text-blue-400 rounded-md mono text-[8px] tracking-[0.2em] font-bold uppercase">SECURE_DOSSIER_ACCESS</span>
                            <span class="h-px w-6 bg-blue-600/30"></span>
                            <span class="mono text-[8px] text-slate-500 uppercase tracking-widest italic">ID: <?= $certificate['certificate_id'] ?></span>
                        </div>
                        <h1 class="text-2xl font-black text-white italic tracking-tighter uppercase italic">VALIDATION_PROTOCOL</h1>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden md:flex flex-col items-end mr-6">
                        <span class="mono text-[9px] text-slate-500 uppercase tracking-widest">SYSTEM_TIME_SYNC</span>
                        <span class="text-xs font-bold text-slate-400 italic"><?= date('H:i:s T') ?></span>
                    </div>
                    
                    <div class="h-10 w-px bg-white/5 mx-4"></div>

                    <div class="flex items-center gap-3">
                        <a href="/certificates/certificate?id=<?= $certificate['certificate_id'] ?>" target="_blank" 
                           class="h-12 px-6 glass rounded-2xl flex items-center gap-3 hover:bg-white/5 transition-all text-slate-400 hover:text-white border-blue-500/10">
                            <i class="fas fa-print text-xs"></i>
                            <span class="mono text-[9px] font-bold uppercase tracking-widest">HARD_COPY</span>
                        </a>
                        <a href="/certificates/certificate?id=<?= $certificate['certificate_id'] ?>&download=1" 
                           class="h-12 px-6 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl flex items-center gap-3 transition-all shadow-xl shadow-blue-900/40 border border-blue-400/20">
                            <i class="fas fa-file-export text-xs"></i>
                            <span class="mono text-[9px] font-black uppercase tracking-widest">EXPORT_ISO</span>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-12 custom-scrollbar relative z-10">
                <div class="max-w-7xl mx-auto space-y-12">
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="glass p-6 rounded-3xl border border-emerald-500/20 bg-emerald-500/5 text-emerald-400 flex items-center gap-5 italic font-bold mono text-xs uppercase tracking-widest animate-in fade-in slide-in-from-top-4">
                            <i class="fas fa-check-double text-lg animate-pulse"></i>
                            SYTEM_CONFIRMATION: <?php echo  htmlspecialchars($_SESSION['success']) ?>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <!-- Protocol Overview -->
                    <div class="grid grid-cols-1 xl:grid-cols-4 gap-10">
                        <!-- ID Card & QR -->
                        <div class="xl:col-span-3 space-y-10">
                            <!-- High Tech Header Card -->
                            <div class="glass rounded-[3rem] overflow-hidden relative border-blue-500/10 group shadow-2xl">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-900/20 via-blue-800/5 to-transparent"></div>
                                <div class="scan-line opacity-10"></div>
                                
                                <div class="relative z-10 p-12 flex flex-col md:flex-row justify-between items-start md:items-center gap-10">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-4 mb-4">
                                            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-900/40 transform -rotate-6">
                                                <i class="fas fa-fingerprint text-white text-xl"></i>
                                            </div>
                                            <span class="mono text-[10px] text-blue-400 font-bold uppercase tracking-[0.4em] italic">REGISTRY_NUMBER:</span>
                                        </div>
                                        <h2 class="text-6xl font-black text-white italic tracking-tighter uppercase leading-none mb-6">
                                            <?= htmlspecialchars($certificate['certificate_number']) ?>
                                        </h2>
                                        <div class="flex flex-wrap items-center gap-4">
                                            <?= getStatusBadge($certificate['status']) ?>
                                            <?php if ($isExpiringSoon): ?>
                                                <span class="px-4 py-1.5 bg-amber-500/10 text-amber-500 rounded-full mono text-[9px] uppercase tracking-widest font-black border border-amber-500/20 animate-pulse">
                                                    <i class="fas fa-radiation mr-2"></i>EXP_CRITICAL
                                                </span>
                                            <?php endif; ?>
                                            <span class="h-1 w-1 bg-slate-700 rounded-full"></span>
                                            <span class="mono text-[9px] text-slate-500 uppercase tracking-widest italic tracking-[0.2em]">EMITTED: <?= date('d.m.Y', strtotime($certificate['issue_date'])) ?></span>
                                        </div>
                                    </div>

                                    <div class="relative group/qr">
                                        <div class="absolute -inset-4 bg-blue-500/20 blur-2xl rounded-full opacity-0 group-hover/qr:opacity-100 transition-opacity"></div>
                                        <div class="w-48 h-48 glass rounded-[2.5rem] p-4 relative z-10 border-white/10 group-hover:scale-105 transition-transform duration-500 shadow-2xl">
                                            <div class="bg-white rounded-[2rem] p-4 w-full h-full flex items-center justify-center">
                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode('https://safety-inspection.gov/verify/' . $certificate['certificate_number']) ?>" 
                                                     alt="Verification Token" class="w-full h-full object-contain mix-blend-multiply opacity-80">
                                            </div>
                                        </div>
                                        <div class="mt-4 text-center">
                                            <span class="mono text-[8px] text-slate-500 uppercase tracking-[0.3em] font-bold">BLOCKCHAIN_TOKEN_VERIFIED</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Deep Data Strip -->
                                <div class="relative z-10 bg-white/[0.02] border-t border-white/5 p-8 grid grid-cols-2 md:grid-cols-4 gap-8">
                                    <div class="space-y-1">
                                        <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black">PROTOCOL_TYPE</div>
                                        <div class="text-sm font-bold text-white italic truncate uppercase"><?= str_replace('_', ' ', $certificate['certificate_type']) ?></div>
                                    </div>
                                    <div class="space-y-1 text-center md:text-left">
                                        <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black">EXPIRATION_GATEWAY</div>
                                        <div class="text-sm font-bold text-rose-500 italic uppercase"><?= date('M d, Y', strtotime($certificate['expiry_date'])) ?></div>
                                    </div>
                                    <div class="space-y-1 text-center md:text-left">
                                        <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black">EMITTING_OFFICER</div>
                                        <div class="text-sm font-bold text-slate-300 italic uppercase truncate"><?= htmlspecialchars($certificate['issued_by_name']) ?></div>
                                    </div>
                                    <div class="space-y-1 text-right">
                                        <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black">SYSTEM_AUTH</div>
                                        <div class="text-sm font-black text-emerald-500 italic uppercase">VERIFIED_HASH</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dual Detail Panels -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                                <!-- Entity Information -->
                                <div class="glass rounded-[2.5rem] p-10 relative overflow-hidden group border-l-4 border-emerald-600">
                                    <div class="absolute -right-8 -bottom-8 opacity-5 group-hover:scale-110 transition-transform duration-700">
                                        <i class="fas fa-building-circle-check text-[140px]"></i>
                                    </div>
                                    <div class="flex items-center gap-4 mb-8">
                                        <div class="w-10 h-10 glass rounded-xl flex items-center justify-center text-emerald-500 border-emerald-500/20">
                                            <i class="fas fa-shield-halved text-sm"></i>
                                        </div>
                                        <h3 class="text-lg font-black italic text-white uppercase tracking-tight">PROTECTED_ENTITY</h3>
                                    </div>
                                    <div class="space-y-8 relative z-10">
                                        <div>
                                            <div class="mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold mb-2 italic">ESTABLISHMENT_NAME</div>
                                            <div class="text-xl font-black text-white italic uppercase tracking-tighter leading-tight"><?= htmlspecialchars($certificate['establishment_name']) ?></div>
                                            <div class="mt-2 flex items-center gap-2">
                                                <span class="px-2 py-0.5 bg-slate-800 rounded text-[9px] text-slate-400 mono uppercase font-bold tracking-widest"><?= str_replace('_', ' ', $certificate['establishment_type']) ?></span>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-4 p-4 bg-white/[0.02] rounded-2xl border border-white/5">
                                            <i class="fas fa-map-location-dot text-slate-600 mt-1"></i>
                                            <div>
                                                <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black mb-1">ZONE_COORDINATES</div>
                                                <div class="text-[11px] text-slate-400 leading-relaxed italic uppercase font-bold">
                                                    <?= htmlspecialchars($certificate['address_street']) ?><br>
                                                    <?= htmlspecialchars($certificate['address_barangay']) ?>, <?= htmlspecialchars($certificate['address_city']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inspection Traceability -->
                                <div class="glass rounded-[2.5rem] p-10 relative overflow-hidden group border-l-4 border-blue-600">
                                    <div class="absolute -right-2 -bottom-2 opacity-5 rotate-12 group-hover:rotate-0 transition-transform duration-700">
                                        <i class="fas fa-microchip text-[160px]"></i>
                                    </div>
                                    <div class="flex items-center gap-4 mb-8">
                                        <div class="w-10 h-10 glass rounded-xl flex items-center justify-center text-blue-500 border-blue-500/20">
                                            <i class="fas fa-barcode text-sm"></i>
                                        </div>
                                        <h3 class="text-lg font-black italic text-white uppercase tracking-tight">SOURCE_AUDIT_LOG</h3>
                                    </div>
                                    <div class="space-y-6 relative z-10">
                                        <div>
                                            <div class="mono text-[9px] text-slate-500 uppercase tracking-[0.3em] font-bold mb-3 italic">INSPECTION_REFERENCE</div>
                                            <a href="/inspections/view?id=<?= $certificate['inspection_id'] ?>" 
                                               class="flex items-center justify-between p-5 bg-blue-600/5 hover:bg-blue-600/10 border border-blue-500/10 rounded-3xl transition-all group/link">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-10 h-10 glass rounded-2xl flex items-center justify-center text-blue-400 group-hover/link:text-white transition-colors">
                                                        <i class="fas fa-file-contract"></i>
                                                    </div>
                                                    <span class="text-base font-black text-blue-400 group-hover/link:text-white italic tracking-tighter uppercase"><?= htmlspecialchars($certificate['inspection_reference']) ?></span>
                                                </div>
                                                <i class="fas fa-chevron-right text-[10px] text-blue-700 group-hover/link:translate-x-1 transition-transform"></i>
                                            </a>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="p-4 bg-white/[0.02] rounded-2xl border border-white/5">
                                                <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black mb-1">AUDIT_STAMP</div>
                                                <div class="text-[10px] text-slate-400 font-bold italic uppercase"><?= date('d M Y', strtotime($certificate['actual_end_datetime'])) ?></div>
                                            </div>
                                            <div class="p-4 bg-white/[0.02] rounded-2xl border border-white/5">
                                                <div class="mono text-[8px] text-slate-600 uppercase tracking-widest font-black mb-1">RESULT_CLASS</div>
                                                <div class="text-[10px] text-emerald-500 font-black italic uppercase tracking-widest animate-pulse">PASSED_VERIFIED</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Remarks Annex -->
                            <?php if ($certificate['remarks']): ?>
                                <div class="glass rounded-[2.5rem] p-10 border border-white/5 relative">
                                    <div class="flex items-center gap-4 mb-6">
                                        <i class="fas fa-quote-left text-blue-500/30 text-3xl"></i>
                                        <h3 class="mono text-[10px] font-black text-slate-500 uppercase tracking-[0.4em] italic leading-none">OFFICIAL_OFFICER_REMARKS</h3>
                                    </div>
                                    <div class="pl-12">
                                        <p class="text-slate-400 italic font-medium leading-[1.8] text-[13px] border-l-2 border-slate-800 pl-8">
                                            <?= nl2br(htmlspecialchars($certificate['remarks'])) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sidebar Analytics & Forecast -->
                        <div class="space-y-8">
                            <!-- Gauge -->
                            <div class="glass rounded-[2.5rem] p-10 border border-white/5 shadow-2xl relative overflow-hidden group">
                                <div class="absolute inset-0 bg-blue-600/[0.03] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <div class="relative z-10 flex flex-col items-center">
                                    <div class="w-full flex justify-between items-center mb-10">
                                        <span class="mono text-[9px] text-slate-500 uppercase tracking-widest font-black italic">PROTOCOL_LIFE_CYCLE</span>
                                        <i class="fas fa-circle-nodes text-blue-600 animate-pulse text-xs"></i>
                                    </div>
                                    
                                    <?php
                                        $totalDays = (strtotime($certificate['expiry_date']) - strtotime($certificate['issue_date'])) / (60 * 60 * 24);
                                        $daysElapsed = (time() - strtotime($certificate['issue_date'])) / (60 * 60 * 24);
                                        $percentage = min(100, max(0, ($daysElapsed / $totalDays) * 100));
                                        $remaining = 100 - $percentage;
                                        $progressColor = $remaining < 15 ? '#f43f5e' : ($remaining < 35 ? '#f59e0b' : '#10b981');
                                    ?>

                                    <div class="relative w-56 h-56 mb-10 group-hover:scale-105 transition-transform duration-700">
                                        <!-- Gauge Background -->
                                        <svg class="w-full h-full transform -rotate-90">
                                            <circle cx="112" cy="112" r="100" stroke="currentColor" stroke-width="12" fill="transparent" class="text-white/5" />
                                            <circle cx="112" cy="112" r="100" stroke="<?= $progressColor ?>" stroke-width="16" fill="transparent" 
                                                stroke-dasharray="<?= 2 * pi() * 100 ?>" 
                                                stroke-dashoffset="<?= (2 * pi() * 100) * (1 - ($remaining / 100)) ?>"
                                                class="transition-all duration-1000 ease-out" stroke-linecap="round" />
                                        </svg>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1 italic">STABILITY</div>
                                            <span class="text-5xl font-black text-white italic mono"><?= round($remaining) ?>%</span>
                                        </div>
                                    </div>

                                    <div class="w-full space-y-4">
                                        <?php if ($isExpired): ?>
                                            <div class="bg-rose-500/10 border border-rose-500/20 rounded-[1.5rem] p-5 text-center">
                                                <div class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-2 italic tracking-[0.3em]">CRITICAL: EXPIRED</div>
                                                <div class="text-base font-black text-white italic uppercase tracking-tighter"><?= abs(round($daysUntilExpiry)) ?> DAYS OVERDUE</div>
                                            </div>
                                        <?php elseif ($isExpiringSoon): ?>
                                            <div class="bg-amber-500/10 border border-amber-500/20 rounded-[1.5rem] p-5 text-center animate-pulse">
                                                <div class="text-[9px] font-black text-amber-500 uppercase tracking-widest mb-2 italic tracking-[0.3em]">RENEWAL_URGENT</div>
                                                <div class="text-base font-black text-white italic uppercase tracking-tighter"><?= round($daysUntilExpiry) ?> DAYS REMAINING</div>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-emerald-500/5 border border-emerald-500/10 rounded-[1.5rem] p-5 text-center text-emerald-500">
                                                <div class="text-[9px] font-black uppercase tracking-widest mb-2 italic tracking-[0.3em]">PROTOCOL_HEALTHY</div>
                                                <div class="text-base font-black text-white italic uppercase tracking-tighter"><?= round($daysUntilExpiry) ?> DAYS ACTIVE</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Meta Intel Block -->
                            <div class="glass rounded-[2.5rem] p-8 border border-white/5 space-y-6">
                                <h3 class="mono text-[10px] font-black text-slate-600 uppercase tracking-widest italic flex items-center gap-3">
                                    <span class="w-1.5 h-1.5 bg-blue-600 rounded-full animate-ping"></span>
                                    CORE_LOG_PARAMETERS
                                </h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-3 bg-white/[0.02] rounded-xl border border-white/5">
                                        <span class="text-[9px] text-slate-500 font-bold uppercase italic tracking-widest">ENCRYPTED_ID</span>
                                        <span class="mono text-[9px] text-slate-300 font-bold"><?= strtoupper(substr(md5($certificate['certificate_id']), 0, 12)) ?></span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-white/[0.02] rounded-xl border border-white/5">
                                        <span class="text-[9px] text-slate-500 font-bold uppercase italic tracking-widest">NETWORK_STATUS</span>
                                        <span class="text-[10px] text-emerald-500 font-black italic uppercase">SYNCHRONIZED</span>
                                    </div>
                                    <div class="flex items-center justify-between p-3 bg-white/[0.02] rounded-xl border border-white/5">
                                        <span class="text-[9px] text-slate-500 font-bold uppercase italic tracking-widest">BLOCK_HEIGHT</span>
                                        <span class="mono text-[10px] text-slate-400">#74,22<?= $certificate['certificate_id'] ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Hub -->
                            <div class="space-y-4 pt-4">
                                <?php if ($certificate['status'] === 'active' && $isExpiringSoon): ?>
                                    <button class="w-full h-16 bg-blue-600 hover:bg-blue-500 text-white rounded-[1.5rem] flex items-center justify-center gap-4 transition-all shadow-xl shadow-blue-900/40 border border-blue-400/20 group">
                                        <i class="fas fa-rotate text-sm group-hover:rotate-180 transition-transform duration-700"></i>
                                        <span class="mono text-[10px] font-black uppercase tracking-[0.2em]">INITIALIZE_RENEWAL</span>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($certificate['status'] === 'active'): ?>
                                    <button onclick="revokeCertificate()" class="w-full h-16 glass hover:bg-rose-500/10 text-slate-400 hover:text-rose-500 rounded-[1.5rem] flex items-center justify-center gap-4 transition-all border border-rose-500/0 hover:border-rose-500/30 group">
                                        <i class="fas fa-radiation text-sm group-hover:rotate-45 transition-transform"></i>
                                        <span class="mono text-[10px] font-black uppercase tracking-[0.2em]">EMERGENCY_REVOCATION</span>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function revokeCertificate() {
            const reason = prompt('ENTER_REVOCATION_CLEARANCE_REASON:');
            if (reason && confirm('CONFIRM_PROTOCOL_TERMINAL_TERMINATION? This action will permanently nullify health certificate <?= $certificate['certificate_number'] ?>.')) {
                fetch('/api/certificates/revoke', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        certificate_id: <?= $certificate['certificate_id'] ?>,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' || data.success) {
                        alert('PROTOCOL_NULLIFIED: Database synchronization successful.');
                        location.reload();
                    } else {
                        alert('TERMINAL_ERROR: ' + (data.message || 'Operation failed'));
                    }
                })
                .catch(error => {
                    alert('CRITICAL_EXCEPTION: Connection lost during sync.');
                });
            }
        }
    </script>
</body>
</html>
