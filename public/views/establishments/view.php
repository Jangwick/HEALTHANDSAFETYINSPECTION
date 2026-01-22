<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$establishmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']);

if (!$establishmentId) {
    header('Location: /views/establishments/list.php');
    exit;
}

try {
    $db = Database::getConnection();
    
    // Get establishment details
    $stmt = $db->prepare("
        SELECT e.*,
               CONCAT(u.first_name, ' ', u.last_name) as created_by_name
        FROM establishments e
        LEFT JOIN users u ON e.created_by = u.user_id
        WHERE e.establishment_id = ?
    ");
    $stmt->execute([$establishmentId]);
    $establishment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$establishment) {
        header('Location: /views/establishments/list.php');
        exit;
    }

    // Role-based access control
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'establishment_owner') {
        if ($establishment['owner_user_id'] != $_SESSION['user_id']) {
            header('Location: /views/establishments/list.php');
            exit;
        }
    }
    
    // Get inspection history
    $inspections = $db->prepare("
        SELECT i.*,
               CONCAT(u.first_name, ' ', u.last_name) as inspector_name
        FROM inspections i
        LEFT JOIN inspectors ins ON i.inspector_id = ins.inspector_id
        LEFT JOIN users u ON ins.user_id = u.user_id
        WHERE i.establishment_id = ?
        ORDER BY i.scheduled_date DESC
        LIMIT 10
    ");
    $inspections->execute([$establishmentId]);
    $inspectionHistory = $inspections->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active certificates
    $certificates = $db->prepare("
        SELECT c.*,
               CONCAT(u.first_name, ' ', u.last_name) as issued_by_name
        FROM certificates c
        LEFT JOIN users u ON c.issued_by = u.user_id
        WHERE c.establishment_id = ?
        ORDER BY c.issue_date DESC
    ");
    $certificates->execute([$establishmentId]);
    $certificateList = $certificates->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent violations
    $violations = $db->prepare("
        SELECT v.*, i.reference_number as inspection_reference
        FROM violations v
        LEFT JOIN inspections i ON v.inspection_id = i.inspection_id
        WHERE i.establishment_id = ?
        ORDER BY v.identified_date DESC
        LIMIT 5
    ");
    $violations->execute([$establishmentId]);
    $violationList = $violations->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($establishment['name']) ?> - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            h1, h2, h3 { @apply font-bold tracking-tight text-slate-900; }
            .italic-rows tbody tr { @apply transition-colors hover:bg-slate-50; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'establishments';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-base">
            <!-- Institutional Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-10">
                <div class="flex items-center space-x-4">
                    <a href="/establishments" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Establishment Dossier</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic"><?= htmlspecialchars($establishment['name']) ?></span>
                </div>
                <div class="flex items-center space-x-3">
                    <?php if ($_SESSION['role'] !== 'establishment_owner'): ?>
                    <a href="/inspections/create?establishment_id=<?= $establishment['establishment_id'] ?>" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-xs font-bold transition-all shadow-sm">
                        <i class="fas fa-plus mr-2"></i> New Inspection
                    </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50">
                <?php if ($success): ?>
                <div class="mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center text-emerald-700 text-xs font-bold uppercase tracking-wider">
                    <i class="fas fa-check-circle mr-3"></i> Establishment status record updated successfully.
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Column 1 & 2: Main Details -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Entity Status Card -->
                        <div class="card p-8 bg-white relative">
                            <div class="absolute top-0 left-0 w-full h-1 bg-blue-700"></div>
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <div>
                                    <div class="flex items-center space-x-3 mb-4">
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[9px] font-bold uppercase tracking-widest">
                                            <?= htmlspecialchars(ucwords($establishment['type'])) ?>
                                        </span>
                                        <?php
                                            $statusStyles = [
                                                'compliant' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                'non_compliant' => 'bg-rose-50 text-rose-700 border-rose-100',
                                                'pending' => 'bg-amber-50 text-amber-700 border-amber-100',
                                                'suspended' => 'bg-slate-100 text-slate-700 border-slate-200 text-slate-600'
                                            ];
                                            $style = $statusStyles[$establishment['compliance_status']] ?? 'bg-slate-50 text-slate-500 border-slate-100';
                                        ?>
                                        <span class="px-2 py-1 rounded text-[9px] font-bold uppercase tracking-widest border <?= $style ?>">
                                            <i class="fas fa-shield-alt mr-1"></i> <?= str_replace('_', ' ', $establishment['compliance_status']) ?>
                                        </span>
                                    </div>
                                    <h2 class="text-2xl font-black text-slate-900 mb-2"><?= htmlspecialchars($establishment['name']) ?></h2>
                                    <div class="flex items-center text-slate-500 text-[11px] font-medium italic">
                                        <i class="fas fa-map-marker-alt mr-2 text-blue-700"></i>
                                        <?= htmlspecialchars($establishment['address_street']) ?>, <?= htmlspecialchars($establishment['address_barangay']) ?>, <?= htmlspecialchars($establishment['address_city']) ?>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($_SESSION['role'] !== 'establishment_owner'): ?>
                                    <button class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                                        <i class="fas fa-edit mr-2 opacity-50"></i> Edit Details
                                    </button>
                                    <?php endif; ?>
                                    <button class="px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-slate-900 transition-colors shadow-sm">
                                        <i class="fas fa-download mr-2 opacity-70"></i> Export Dossier
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Operational Details Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Registration Parameters -->
                            <div class="card">
                                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
                                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center">
                                        <i class="fas fa-file-contract mr-2 text-blue-700"></i> Registry Parameters
                                    </h3>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Business Permit No.</p>
                                        <p class="text-sm font-mono font-bold text-slate-800"><?= htmlspecialchars($establishment['business_permit_number'] ?? 'N/A') ?></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Max Capacity</p>
                                            <p class="text-sm font-bold text-slate-800"><?= number_format((float)($establishment['capacity'] ?? 0)) ?> Souls</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Operating Window</p>
                                            <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($establishment['operating_hours'] ?? 'N/A') ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Operational Scope</p>
                                        <p class="text-[11px] leading-relaxed text-slate-600 italic"><?= htmlspecialchars($establishment['description'] ?? 'No formal description logged in registry.') ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Ownership & Contacts -->
                            <div class="card">
                                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
                                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center">
                                        <i class="fas fa-user-circle mr-2 text-emerald-600"></i> Ownership Profile
                                    </h3>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Registered Owner</p>
                                        <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($establishment['owner_name']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Primary Contact</p>
                                        <p class="text-sm font-mono font-bold text-blue-700"><?= htmlspecialchars($establishment['owner_phone']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-1">Official Email Address</p>
                                        <p class="text-sm font-medium text-slate-700 underline decoration-slate-200"><?= htmlspecialchars($establishment['owner_email'] ?? 'N/A') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formal Inspection Log -->
                        <div class="card">
                            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-clipboard-list mr-2 text-blue-700"></i> Formal Inspection Log
                                </h3>
                                <span class="text-[10px] font-bold text-slate-400 italic"><?= count($inspectionHistory) ?> Entries Total</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left italic-rows">
                                    <thead class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase">
                                        <tr>
                                            <th class="px-6 py-4">Reference</th>
                                            <th class="px-6 py-4">Type / Inspector</th>
                                            <th class="px-6 py-4">Date</th>
                                            <th class="px-6 py-4">Result</th>
                                            <th class="px-6 py-4 text-right">Dossier</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php if (!empty($inspectionHistory)): ?>
                                            <?php foreach ($inspectionHistory as $insp): ?>
                                                <tr class="group cursor-pointer hover:bg-slate-50 transition-colors" onclick="window.location='/inspections/view?id=<?= $insp['inspection_id'] ?>'">
                                                    <td class="px-6 py-4 font-mono font-bold text-xs text-slate-800">#<?= htmlspecialchars($insp['reference_number']) ?></td>
                                                    <td class="px-6 py-4">
                                                        <div class="text-xs font-bold text-slate-700"><?= ucwords(str_replace('_', ' ', $insp['inspection_type'])) ?></div>
                                                        <div class="text-[9px] text-slate-400 uppercase font-bold"><?= htmlspecialchars($insp['inspector_name'] ?? 'Personnel Unassigned') ?></div>
                                                    </td>
                                                    <td class="px-6 py-4 text-xs font-medium text-slate-600"><?= date('M d, Y', strtotime($insp['scheduled_date'])) ?></td>
                                                    <td class="px-6 py-4">
                                                        <?php
                                                        $statusColors = [
                                                            'scheduled' => 'text-blue-600',
                                                            'in_progress' => 'text-amber-600',
                                                            'completed' => 'text-emerald-600',
                                                            'cancelled' => 'text-rose-600'
                                                        ];
                                                        $txtColor = $statusColors[$insp['status']] ?? 'text-slate-500';
                                                        ?>
                                                        <span class="text-[10px] font-black uppercase tracking-tighter <?= $txtColor ?> italic">
                                                            <?= $insp['status'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-right">
                                                        <i class="fas fa-chevron-right text-slate-300 group-hover:text-blue-700 transition-colors text-[10px]"></i>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="px-6 py-12 text-center text-[11px] text-slate-400 italic">No historical data available in this dossier.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Sidebar Portfolio -->
                    <div class="space-y-8">
                        <!-- Key Performance Portfolio -->
                        <div class="card bg-blue-700 p-8 shadow-blue-200/50 shadow-lg text-white">
                           <h3 class="text-[10px] font-bold text-blue-200 uppercase tracking-widest mb-6">Credential Portfolio</h3>
                           <div class="space-y-6">
                               <div class="flex items-center justify-between">
                                   <div class="flex items-center space-x-3">
                                       <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                                           <i class="fas fa-tasks text-blue-200 text-xs"></i>
                                       </div>
                                       <span class="text-xs font-bold text-blue-100">Audit Volume</span>
                                   </div>
                                   <span class="text-xl font-black"><?= count($inspectionHistory) ?></span>
                               </div>
                               <div class="flex items-center justify-between">
                                   <div class="flex items-center space-x-3">
                                       <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                                           <i class="fas fa-certificate text-blue-200 text-xs"></i>
                                       </div>
                                       <span class="text-xs font-bold text-blue-100">Valid Credentials</span>
                                   </div>
                                   <span class="text-xl font-black"><?= count(array_filter($certificateList, fn($c) => $c['status'] === 'active')) ?></span>
                               </div>
                               <div class="flex items-center justify-between">
                                   <div class="flex items-center space-x-3">
                                       <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                                           <i class="fas fa-bug text-blue-200 text-xs"></i>
                                       </div>
                                       <span class="text-xs font-bold text-blue-100">Active Non-Compliance</span>
                                   </div>
                                   <span class="text-xl font-black text-rose-300"><?= count($violationList) ?></span>
                               </div>
                           </div>
                        </div>

                        <!-- Issued Credentials Ledger -->
                        <div class="card">
                            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-stamp mr-2 text-indigo-600"></i> Issued Ledger
                                </h3>
                                <button class="text-[8px] font-bold text-blue-700 hover:underline uppercase tracking-widest">Formal Issuance</button>
                            </div>
                            <div class="p-4 space-y-3">
                                <?php if (!empty($certificateList)): ?>
                                    <?php foreach ($certificateList as $cert): ?>
                                        <a href="/certificates/view?id=<?= $cert['certificate_id'] ?>" class="block p-4 border border-slate-100 rounded-xl hover:border-blue-200 transition-all hover:bg-slate-50 group">
                                            <div class="flex justify-between items-start mb-2">
                                                <div>
                                                    <div class="text-[11px] font-bold text-slate-800 group-hover:text-blue-700 transition-colors uppercase"><?= htmlspecialchars($cert['certificate_number']) ?></div>
                                                    <div class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter"><?= str_replace('_', ' ', $cert['certificate_type']) ?></div>
                                                </div>
                                                <?php
                                                    $certColors = [
                                                        'active' => 'bg-emerald-100 text-emerald-700',
                                                        'expired' => 'bg-rose-100 text-rose-700',
                                                        'revoked' => 'bg-slate-100 text-slate-500'
                                                    ];
                                                ?>
                                                <span class="px-1.5 py-0.5 rounded text-[8px] font-black uppercase <?= $certColors[$cert['status']] ?? 'bg-slate-100 text-slate-500' ?>">
                                                    <?= $cert['status'] ?>
                                                </span>
                                            </div>
                                            <div class="text-[9px] font-bold text-slate-400 flex items-center italic">
                                                <i class="far fa-clock mr-1"></i> Expires <?= date('M d, Y', strtotime($cert['expiry_date'])) ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="py-8 text-center text-[10px] text-slate-400 italic uppercase">No credentials issued for this entity.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Dossier Audit Metadata -->
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
                            <h4 class="text-[9px] font-black text-blue-700 uppercase tracking-widest mb-2 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i> Audit Metadata
                            </h4>
                            <p class="text-[10px] text-blue-600 leading-relaxed italic">
                                Documentation finalized by <span class="font-bold underline decoration-blue-200"><?= htmlspecialchars($establishment['created_by_name']) ?></span> 
                                on <?= date('F d, Y', strtotime($establishment['created_at'])) ?>. 
                                Registry compliance reviewed on every inspection cycle.
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
