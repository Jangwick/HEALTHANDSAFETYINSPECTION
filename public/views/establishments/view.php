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

    // Role-based access control: Owners can only see their own establishment
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
    <title><?php echo  htmlspecialchars($establishment['name']) ?> - Health & Safety System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#0b0c10] font-sans antialiased text-slate-200 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'establishments';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <div class="flex items-center space-x-4">
                    <a href="/establishments" class="text-slate-500 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-white tracking-tight"><?php echo  htmlspecialchars($establishment['name']) ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($_SESSION['role'] !== 'establishment_owner'): ?>
                    <a href="/inspections/create?establishment_id=<?php echo  $establishment['establishment_id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-blue-900/20 transition-all active:scale-95 group">
                        <i class="fas fa-plus mr-2 group-hover:rotate-90 transition-transform"></i> Schedule Inspection
                    </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-[#0b0c10] text-base">
                <?php if ($success): ?>
                <div class="max-w-7xl mx-auto mb-8 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl flex items-center text-emerald-500">
                    <i class="fas fa-check-circle mr-3"></i>
                    <span class="text-xs font-black uppercase tracking-widest italic">Establishment successfully updated/registered.</span>
                </div>
                <?php endif; ?>

                <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Column: Details & History -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Header Banner -->
                        <div class="bg-[#15181e] rounded-3xl border border-white/5 overflow-hidden shadow-2xl relative">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-600"></div>
                            <div class="p-8">
                                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                    <div>
                                        <div class="flex items-center space-x-3 mb-4">
                                            <span class="px-3 py-1.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-lg text-[10px] font-black uppercase tracking-widest">
                                                <i class="fas fa-tag mr-1.5"></i> <?php echo  htmlspecialchars(ucwords($establishment['type'])) ?>
                                            </span>
                                            <?php
                                                $statusStyles = [
                                                    'compliant' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
                                                    'non_compliant' => 'bg-rose-500/10 text-rose-500 border-rose-500/20',
                                                    'pending' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                                    'suspended' => 'bg-blue-500/10 text-blue-500 border-blue-500/20'
                                                ];
                                                $style = $statusStyles[$establishment['compliance_status']] ?? 'bg-slate-500/10 text-slate-500 border-white/10';
                                            ?>
                                            <span class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest border <?php echo  $style ?>">
                                                <i class="fas fa-shield-alt mr-1.5"></i> <?php echo  str_replace('_', ' ', $establishment['compliance_status']) ?>
                                            </span>
                                        </div>
                                        <div class="flex items-start text-slate-400 text-sm italic font-medium leading-relaxed">
                                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-blue-500"></i>
                                            <span>
                                                <?php echo  htmlspecialchars($establishment['address_street']) ?>, 
                                                <?php echo  htmlspecialchars($establishment['address_barangay']) ?>, 
                                                <?php echo  htmlspecialchars($establishment['address_city']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex space-x-3">
                                        <?php if ($_SESSION['role'] !== 'establishment_owner'): ?>
                                        <button class="px-6 py-3 bg-[#1e232b] hover:bg-[#252b35] text-white rounded-xl text-sm font-bold border border-white/5 transition-all active:scale-95 shadow-lg">
                                            <i class="fas fa-edit mr-2 text-slate-400"></i> Edit
                                        </button>
                                        <?php endif; ?>
                                        <button class="px-6 py-3 bg-white/[0.03] hover:bg-white/[0.08] text-white rounded-xl text-sm font-bold border border-white/10 transition-all active:scale-95">
                                            <i class="fas fa-download mr-2 text-slate-400"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grid Info Blocks -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Business Info -->
                            <div class="bg-[#15181e] rounded-3xl border border-white/5 shadow-xl overflow-hidden">
                                <div class="px-8 py-6 bg-white/[0.02] border-b border-white/5">
                                    <h2 class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] flex items-center">
                                        <i class="fas fa-building mr-2"></i> Business Details
                                    </h2>
                                </div>
                                <div class="p-8 space-y-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Permit Number</label>
                                        <div class="text-white font-mono text-sm"><?php echo  htmlspecialchars($establishment['business_permit_number'] ?? 'N/A') ?></div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Capacity</label>
                                            <div class="text-white font-bold text-sm"><?php echo  number_format((float)($establishment['capacity'] ?? 0)) ?> <span class="text-slate-500 font-normal">Persons</span></div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Operating Hours</label>
                                            <div class="text-white font-bold text-sm"><?php echo  htmlspecialchars($establishment['operating_hours'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Description</label>
                                        <p class="text-slate-400 text-xs leading-relaxed italic"><?php echo  htmlspecialchars($establishment['description'] ?? 'No description provided.') ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Ownership Info -->
                            <div class="bg-[#15181e] rounded-3xl border border-white/5 shadow-xl overflow-hidden">
                                <div class="px-8 py-6 bg-white/[0.02] border-b border-white/5">
                                    <h2 class="text-[10px] font-black text-emerald-500 uppercase tracking-[0.2em] flex items-center">
                                        <i class="fas fa-user-tie mr-2"></i> Contact & Ownership
                                    </h2>
                                </div>
                                <div class="p-8 space-y-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Owner Name</label>
                                        <div class="text-white font-bold text-sm"><?php echo  htmlspecialchars($establishment['owner_name']) ?></div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Phone Contact</label>
                                        <a href="tel:<?php echo  htmlspecialchars($establishment['owner_phone']) ?>" class="text-blue-400 hover:text-blue-300 transition-colors font-mono text-sm flex items-center group">
                                            <i class="fas fa-phone-alt mr-2 text-[10px] group-hover:scale-110"></i> <?php echo  htmlspecialchars($establishment['owner_phone']) ?>
                                        </a>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Email Address</label>
                                        <a href="mailto:<?php echo  htmlspecialchars($establishment['owner_email']) ?>" class="text-slate-300 hover:text-white transition-colors text-sm flex items-center group italic underline decoration-white/10">
                                            <i class="fas fa-envelope mr-2 text-[10px] group-hover:scale-110"></i> <?php echo  htmlspecialchars($establishment['owner_email'] ?? 'N/A') ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inspection History Table -->
                        <div class="bg-[#15181e] rounded-3xl border border-white/5 shadow-2xl overflow-hidden">
                            <div class="px-8 py-6 bg-white/[0.02] border-b border-white/5 flex items-center justify-between">
                                <h2 class="text-[10px] font-black text-amber-500 uppercase tracking-[0.2em] flex items-center">
                                    <i class="fas fa-history mr-2"></i> Inspection History
                                </h2>
                                <span class="text-[9px] font-black text-slate-600 uppercase tracking-widest italic"><?php echo  count($inspectionHistory) ?> total records</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-white/[0.01] border-b border-white/5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                                            <th class="px-8 py-5">Reference</th>
                                            <th class="px-8 py-5">Type / Inspector</th>
                                            <th class="px-8 py-5 text-center">Date</th>
                                            <th class="px-8 py-5">Status</th>
                                            <th class="px-8 py-5 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-white/5 text-sm">
                                        <?php if (!empty($inspectionHistory)): ?>
                                            <?php foreach ($inspectionHistory as $insp): ?>
                                                <tr class="hover:bg-white/[0.02] transition-colors group cursor-pointer" onclick="window.location='/inspections/view?id=<?php echo  $insp['inspection_id'] ?>'">
                                                    <td class="px-8 py-6">
                                                        <div class="font-mono font-bold text-white group-hover:text-amber-400 transition-colors">#<?php echo  htmlspecialchars($insp['reference_number']) ?></div>
                                                    </td>
                                                    <td class="px-8 py-6">
                                                        <div class="text-white font-medium"><?php echo  ucwords(str_replace('_', ' ', $insp['inspection_type'])) ?></div>
                                                        <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-1"><?php echo  htmlspecialchars($insp['inspector_name'] ?? 'Unassigned') ?></div>
                                                    </td>
                                                    <td class="px-8 py-6 text-center">
                                                        <div class="text-white"><?php echo  date('M d, Y', strtotime($insp['scheduled_date'])) ?></div>
                                                    </td>
                                                    <td class="px-8 py-6">
                                                        <?php
                                                        $statusColors = [
                                                            'scheduled' => 'bg-blue-500/10 text-blue-500 border-blue-500/20',
                                                            'in_progress' => 'bg-amber-500/10 text-amber-500 border-amber-500/20',
                                                            'completed' => 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
                                                            'cancelled' => 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                                                        ];
                                                        $color = $statusColors[$insp['status']] ?? 'bg-slate-500/10 text-slate-500 border-white/10';
                                                        ?>
                                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-[0.1em] border <?php echo  $color ?>">
                                                            <?php echo  $insp['status'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-8 py-6 text-right">
                                                        <a href="/inspections/view?id=<?php echo  $insp['inspection_id'] ?>" class="p-2 text-slate-500 hover:text-white transition-colors">
                                                            <i class="fas fa-external-link-alt text-xs"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="px-8 py-12 text-center text-slate-500 italic">No inspection history found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Sidebar Stats & Certificates -->
                    <div class="space-y-8">
                        <!-- Quick Stats -->
                        <div class="bg-gradient-to-br from-[#1e232b] to-[#15181e] p-8 rounded-3xl border border-white/10 shadow-2xl relative overflow-hidden group">
                           <div class="absolute -right-8 -bottom-8 opacity-[0.03] text-9xl group-hover:scale-110 transition-transform duration-700">
                               <i class="fas fa-chart-line"></i>
                           </div>
                           <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mb-8 italic">Operational Overview</h3>
                           <div class="space-y-8">
                               <div class="flex items-center justify-between">
                                   <div class="flex items-center">
                                       <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center mr-4 group-hover:bg-blue-500/20 transition-colors">
                                           <i class="fas fa-clipboard-check text-blue-500 text-sm"></i>
                                       </div>
                                       <span class="text-sm font-bold text-slate-300">Total Inspections</span>
                                   </div>
                                   <div class="text-2xl font-black text-white"><?php echo  count($inspectionHistory) ?></div>
                               </div>
                               <div class="flex items-center justify-between">
                                   <div class="flex items-center">
                                       <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center mr-4 group-hover:bg-emerald-500/20 transition-colors">
                                           <i class="fas fa-certificate text-emerald-500 text-sm"></i>
                                       </div>
                                       <span class="text-sm font-bold text-slate-300">Active Certificates</span>
                                   </div>
                                   <div class="text-2xl font-black text-white"><?php echo  count(array_filter($certificateList, fn($c) => $c['status'] === 'active')) ?></div>
                               </div>
                               <div class="flex items-center justify-between">
                                   <div class="flex items-center">
                                       <div class="w-10 h-10 bg-rose-500/10 rounded-xl flex items-center justify-center mr-4 group-hover:bg-rose-500/20 transition-colors">
                                           <i class="fas fa-exclamation-triangle text-rose-500 text-sm"></i>
                                       </div>
                                       <span class="text-sm font-bold text-slate-300">Active Violations</span>
                                   </div>
                                   <div class="text-2xl font-black text-rose-500"><?php echo  count($violationList) ?></div>
                               </div>
                           </div>
                        </div>

                        <!-- Active Certificates List -->
                        <div class="bg-[#15181e] rounded-3xl border border-white/5 shadow-xl overflow-hidden">
                            <div class="px-8 py-6 bg-white/[0.02] border-b border-white/5 flex items-center justify-between">
                                <h2 class="text-[10px] font-black text-purple-500 uppercase tracking-[0.2em] flex items-center">
                                    <i class="fas fa-award mr-2"></i> Certificates
                                </h2>
                                <button class="text-[9px] font-black text-blue-400 hover:text-blue-300 uppercase tracking-widest transition-colors mb-0.5">Issue New</button>
                            </div>
                            <div class="p-4 space-y-3">
                                <?php if (!empty($certificateList)): ?>
                                    <?php foreach ($certificateList as $cert): ?>
                                        <a href="/certificates/view?id=<?php echo  $cert['certificate_id'] ?>" class="block p-5 bg-[#0b0c10] hover:bg-white/[0.04] border border-white/5 rounded-2xl transition-all group overflow-hidden relative">
                                            <div class="flex justify-between items-start relative z-10">
                                                <div>
                                                    <div class="text-xs font-black text-white group-hover:text-purple-400 transition-colors mb-1"><?php echo  htmlspecialchars($cert['certificate_number']) ?></div>
                                                    <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest"><?php echo  str_replace('_', ' ', $cert['certificate_type']) ?></div>
                                                </div>
                                                <?php
                                                    $certColors = [
                                                        'active' => 'bg-emerald-500 text-white',
                                                        'expired' => 'bg-rose-500 text-white',
                                                        'revoked' => 'bg-slate-700 text-slate-400'
                                                    ];
                                                ?>
                                                <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter <?php echo  $certColors[$cert['status']] ?? 'bg-slate-500 text-white' ?>">
                                                    <?php echo  $cert['status'] ?>
                                                </span>
                                            </div>
                                            <div class="mt-4 flex items-center text-[9px] font-black text-slate-600 uppercase tracking-widest group-hover:text-slate-400 transition-colors">
                                                <i class="fas fa-calendar-alt mr-2"></i> Expires: <?php echo  date('M d, Y', strtotime($cert['expiry_date'])) ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-8 text-center text-slate-500 italic text-xs">No certificates issued yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="bg-blue-600/5 rounded-3xl border border-blue-500/10 p-6 flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                            <div>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Record Metadata</div>
                                <div class="text-[10px] text-slate-500 leading-relaxed italic">
                                    Last modified by <span class="text-slate-300 font-bold"><?php echo  htmlspecialchars($establishment['created_by_name']) ?></span> 
                                    on <?php echo  date('F d, Y', strtotime($establishment['created_at'])) ?>.
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
