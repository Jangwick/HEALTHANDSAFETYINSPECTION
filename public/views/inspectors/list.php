<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../src/Services/InspectorService.php';
require_once __DIR__ . '/../../../src/Utils/Logger.php';

$db = Database::getConnection();
$logger = new \HealthSafety\Utils\Logger();
$inspectorService = new \HealthSafety\Services\InspectorService($db, $logger);

$expiringCerts = $inspectorService->getExpiringCertifications(60); 
$inspectors = $inspectorService->listInspectors(1, 100)['data'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Registry - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-800 bg-slate-50; }
            .mono { font-family: 'JetBrains Mono', monospace; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base overflow-hidden selection:bg-blue-100 selection:text-blue-900">
    <div class="flex h-screen relative">
        <!-- Sidebar -->
        <?php 
            $activePage = 'inspectors';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 relative">
            <!-- Institutional Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0 z-20">
                <div class="flex items-center space-x-4">
                    <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-800">Inspector Personnel</h1>
                    <div class="px-3 py-1 bg-blue-50 border border-blue-100 rounded-full">
                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest leading-none">Security Registry</span>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="hidden md:flex flex-col items-end mr-4">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Operator Context</span>
                        <span class="text-sm font-bold text-slate-900 leading-none mt-1 uppercase"><?= $_SESSION['role'] ?? 'OFFICER' ?></span>
                    </div>
                    <button onclick="openRegistration()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-lg shadow-blue-500/20 flex items-center gap-3 active:scale-95 group">
                        <i class="fas fa-plus group-hover:rotate-90 transition-transform text-[10px]"></i>
                        Enlist Personnel
                    </button>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto bg-[#f8fafc] p-10">
                <div class="max-w-7xl mx-auto space-y-10">
                    
                    <!-- Page Intro -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Personnel Dossier</h2>
                            <p class="text-slate-500 mt-2 font-medium">Institutional registry of authorized field inspectors and certification compliance monitoring.</p>
                        </div>
                    </div>

                    <!-- Compliance Horizon (Expiring Certifications) -->
                <?php if (!empty($expiringCerts)): ?>
                <div class="mb-10 bg-white rounded-[2rem] border border-rose-100 shadow-xl shadow-rose-900/5 p-8 relative overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-rose-50 rounded-full blur-3xl opacity-50"></div>
                    
                    <div class="flex items-center gap-4 mb-8 relative">
                        <div class="w-10 h-10 bg-rose-500 rounded-lg flex items-center justify-center shadow-lg shadow-rose-900/20">
                            <i class="fas fa-hourglass-half text-white text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-xs font-black text-rose-800 uppercase italic">Compliance Horizon Alert</h2>
                            <p class="text-[9px] font-bold text-rose-400 uppercase tracking-widest mt-0.5 italic">Certification Terminus Detected (60-Day Protocol)</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 relative">
                        <?php foreach ($expiringCerts as $cert): ?>
                        <div class="bg-rose-50/30 border border-rose-100/50 rounded-2xl p-5 group hover:bg-white hover:shadow-xl hover:shadow-rose-900/5 transition-all">
                            <div class="flex justify-between items-start mb-3">
                                <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center border border-rose-100 italic font-black text-rose-500 text-xs">
                                    <?= substr($cert['inspector_name'], 0, 1) ?>
                                </span>
                                <span class="px-2 py-0.5 bg-rose-500 text-white text-[8px] font-black uppercase rounded block italic animate-pulse">Critical</span>
                            </div>
                            <h3 class="text-[11px] font-black text-slate-800 uppercase italic truncate leading-none mb-1">
                                <?= htmlspecialchars($cert['inspector_name']) ?>
                            </h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tight italic mb-4">
                                <?= htmlspecialchars($cert['certification_type']) ?>
                            </p>
                            <div class="flex items-center justify-between pt-3 border-t border-rose-100/50">
                                <div class="text-[9px] font-black text-rose-600 uppercase italic"><?= date('M d, Y', strtotime($cert['expiry_date'])) ?></div>
                                <div class="text-[8px] font-bold text-slate-400 uppercase italic">T-Minus <?= round((strtotime($cert['expiry_date']) - time()) / 86400) ?>D</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Active Personnel Registry -->
                <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-blue-900/5 border border-slate-100 overflow-hidden">
                    <div class="px-10 py-8 border-b border-slate-50 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <h3 class="text-xs font-black text-slate-900 uppercase italic">Active Registry Dossiers</h3>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1 italic">Authorized Inspection Personnel</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="relative group">
                                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-blue-700 transition-colors"></i>
                                <input type="text" placeholder="Filter Dossiers..." class="w-64 h-11 pl-12 pr-4 bg-slate-50 border-none rounded-xl text-[11px] font-bold uppercase italic focus:ring-2 focus:ring-blue-700/10 placeholder:text-slate-300 transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Personnel ID / Name</th>
                                    <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Rank / Class</th>
                                    <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Dossier Status</th>
                                    <th class="px-10 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Operations</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($inspectors as $i): ?>
                                <tr class="group hover:bg-blue-50/20 transition-colors">
                                    <td class="px-10 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center font-black text-blue-700 italic group-hover:scale-110 transition-transform shadow-sm">
                                                <?= substr($i['full_name'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <div class="text-[11px] font-black text-slate-900 uppercase italic leading-none mb-1">
                                                    <?= htmlspecialchars($i['full_name']) ?>
                                                </div>
                                                <div class="text-[9px] font-bold text-blue-600/60 mono italic tracking-tighter">
                                                    REG-<?= str_pad((string)$i['id'], 5, '0', STR_PAD_LEFT) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-10 py-6">
                                        <span class="text-[10px] font-black text-slate-700 uppercase italic"><?= htmlspecialchars($i['rank'] ?? 'Standard') ?></span>
                                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1 italic">Inspection Division</p>
                                    </td>
                                    <td class="px-10 py-6">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                                            <span class="text-[9px] font-black text-emerald-700 uppercase italic tracking-widest">Authorized</span>
                                        </div>
                                    </td>
                                    <td class="px-10 py-6">
                                        <div class="flex items-center gap-3">
                                            <button class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-700 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-900/5 flex items-center justify-center transition-all group/btn">
                                                <i class="fas fa-id-card text-xs group-hover/btn:scale-110 transition-transform"></i>
                                            </button>
                                            <button class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-blue-700 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-900/5 flex items-center justify-center transition-all group/btn">
                                                <i class="fas fa-history text-xs group-hover/btn:scale-110 transition-transform"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Registration UI Modal -->
    <div id="registrationModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100] hidden flex items-center justify-center p-6">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl overflow-hidden relative border border-white animate-in zoom-in-95 duration-300">
             <div class="p-10">
                <div class="flex justify-between items-start mb-10">
                    <div>
                        <h2 class="text-sm font-black text-slate-900 uppercase italic leading-none">Personnel Enlistment</h2>
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Register New Authorized Inspector</p>
                    </div>
                    <button onclick="closeRegistration()" class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:text-rose-500 transition-colors">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>

                <form class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest italic ml-1">Personnel Full Name</label>
                        <input type="text" class="w-full h-12 px-5 bg-slate-50 border-none rounded-xl text-xs font-bold uppercase italic focus:ring-2 focus:ring-blue-700/10 placeholder:text-slate-300" placeholder="e.g. ANTONIO VALERIO">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest italic ml-1">Credential Email</label>
                            <input type="email" class="w-full h-12 px-5 bg-slate-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-700/10 placeholder:text-slate-300" placeholder="inspector@lgu.gov">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest italic ml-1">Phone Protocol</label>
                            <input type="tel" class="w-full h-12 px-5 bg-slate-50 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-700/10 placeholder:text-slate-300" placeholder="+63 000...">
                        </div>
                    </div>
                    <div class="pt-6">
                        <button type="submit" class="w-full h-14 bg-blue-700 hover:bg-blue-800 text-white font-black text-xs uppercase tracking-widest rounded-2xl shadow-xl shadow-blue-900/20 transition-all active:scale-95">
                            Finalize Enlistment Protocol
                        </button>
                    </div>
                </form>
             </div>
        </div>
    </div>

    <script>
        function openRegistration() {
            document.getElementById('registrationModal').classList.remove('hidden');
        }
        function closeRegistration() {
            document.getElementById('registrationModal').classList.add('hidden');
        }
    </script>
</body>
</html>
