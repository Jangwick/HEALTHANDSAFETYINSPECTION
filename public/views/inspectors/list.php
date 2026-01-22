<?php
/**
 * Health & Safety Inspection System
 * Inspector Profile and Certification Tracking
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../src/Services/InspectorService.php';
require_once __DIR__ . '/../../../src/Utils/Logger.php';

$db = Database::getConnection();
$logger = new \HealthSafety\Utils\Logger();
$inspectorService = new \HealthSafety\Services\InspectorService($db, $logger);

$expiringCerts = $inspectorService->getExpiringCertifications(60); // Check next 60 days
$inspectors = $inspectorService->listInspectors(1, 50)['data'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspector Profiles - LGU 4 Public Safety</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'inspectors';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 shrink-0">
                <div class="flex items-center gap-3">
                    <h1 class="text-xl font-bold text-slate-800">Inspector Management</h1>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded uppercase">LGU 4 Authentication</span>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 text-base">
                
                <!-- Alerts for Expiring Certifications -->
                <?php if (!empty($expiringCerts)): ?>
                <div class="mb-8 bg-rose-50 border border-rose-200 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-certificate text-rose-500"></i>
                        <h2 class="text-sm font-bold text-rose-800 uppercase tracking-tight">Certification Alerts (Upcoming 60 Days)</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <?php foreach ($expiringCerts as $cert): ?>
                        <div class="bg-white border border-rose-100 p-3 rounded-lg flex justify-between items-center shadow-sm">
                            <div>
                                <div class="text-xs font-bold text-slate-800"><?= htmlspecialchars($cert['inspector_name']) ?></div>
                                <div class="text-[10px] text-slate-500 font-medium"><?= htmlspecialchars($cert['certification_type']) ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] text-rose-600 font-black"><?= date('M d', strtotime($cert['expiry_date'])) ?></div>
                                <div class="text-[8px] text-slate-400 font-bold uppercase">Expires In <?= round((strtotime($cert['expiry_date']) - time()) / 86400) ?> Days</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    <!-- Inspector List Column -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest">Active Inspectors</h3>
                            <button class="text-blue-600 text-xs font-bold hover:underline"><i class="fas fa-plus mr-1"></i> Register New</button>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <?php foreach ($inspectors as $i): ?>
                            <div class="p-4 hover:bg-slate-50 transition-colors flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 font-black border border-slate-200">
                                    <?= substr($i['full_name'], 0, 1) ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold text-slate-900"><?= htmlspecialchars((string)$i['full_name']) ?></h4>
                                    <div class="flex gap-4 mt-0.5">
                                        <div class="text-[10px] text-slate-500"><i class="fas fa-id-badge mr-1"></i> <?= htmlspecialchars((string)$i['badge_number']) ?></div>
                                        <div class="text-[10px] text-slate-500"><i class="fas fa-suitcase mr-1"></i> <?= htmlspecialchars((string)$i['years_of_experience']) ?> Years</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full uppercase">Active</span>
                                    <div class="mt-1 text-[8px] text-slate-400 font-black uppercase">Verified ID</div>
                                </div>
                                <button onclick="viewInspectorActionDetails(<?= $i['inspector_id'] ?>)" class="p-2 text-slate-300 hover:text-blue-600 transition-colors" title="Credential & Activity Details">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Performance Stats / Visual -->
                    <div class="space-y-8">
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-widest mb-4">Domain Specializations</h3>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1.5 bg-amber-50 text-amber-700 rounded-lg text-xs font-bold border border-amber-100">Fire Safety</span>
                                <span class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-bold border border-blue-100">Bio-Hazard</span>
                                <span class="px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-lg text-xs font-bold border border-emerald-100">Structural</span>
                                <span class="px-3 py-1.5 bg-rose-50 text-rose-700 rounded-lg text-xs font-bold border border-rose-100">Sanitary</span>
                                <span class="px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg text-xs font-bold border border-purple-100">Electrical</span>
                            </div>
                        </div>

                        <div class="bg-slate-900 rounded-xl p-6 text-white relative overflow-hidden shadow-2xl">
                             <div class="relative z-10">
                                <h3 class="text-xs font-black text-blue-400 uppercase tracking-widest mb-1">Safety Culture AI Insights</h3>
                                <p class="text-lg font-bold mb-4">Workload Distribution Analysis</p>
                                <div class="space-y-4">
                                    <div>
                                        <div class="flex justify-between text-[10px] font-bold mb-1 uppercase tracking-tight">
                                            <span>Current Deployment</span>
                                            <span>84%</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-white/10 rounded-full overflow-hidden">
                                            <div class="h-full w-[84%] bg-blue-500 shadow-glow shadow-blue-500/50 transition-all duration-1000"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-[10px] font-bold mb-1 uppercase tracking-tight text-emerald-400">
                                            <span>Compliance Accuracy Rate</span>
                                            <span>96.2%</span>
                                        </div>
                                        <div class="h-1.5 w-full bg-white/10 rounded-full overflow-hidden">
                                            <div class="h-full w-[96.2%] bg-emerald-500 shadow-glow shadow-emerald-500/50 transition-all duration-1000"></div>
                                        </div>
                                    </div>
                                </div>
                             </div>
                             <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl rotate-12">
                                <i class="fas fa-robot"></i>
                             </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
    <!-- Inspector Detail Modal -->
    <div id="inspectorModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
            <div id="inspectorModalContent">
                <!-- Data loaded via JS -->
            </div>
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button onclick="closeInspectorModal()" class="px-5 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-sm font-bold transition-all">
                    Dismiss
                </button>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('inspectorModal');
        const content = document.getElementById('inspectorModalContent');

        function closeInspectorModal() {
            modal.classList.add('hidden');
        }

        async function viewInspectorActionDetails(id) {
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="p-20 text-center"><i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i></div>';
            
            // Logic to fetch inspector details could go here
            // For now, we simulate the "Action Details" view
            setTimeout(() => {
                content.innerHTML = `
                    <div class="p-8">
                        <div class="flex items-center gap-6 mb-8">
                            <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-3xl font-black shadow-xl shadow-blue-200">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tight italic">Actionable Profile</h2>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">LGU Credentials & Performance</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl">
                                <h4 class="text-[10px] font-black text-blue-700 uppercase tracking-widest mb-3 flex items-center">
                                    <i class="fas fa-certificate mr-2"></i> Active Certifications
                                </h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center bg-white p-2.5 rounded-lg border border-blue-100 shadow-sm">
                                        <span class="text-xs font-bold text-slate-700">OHSAS Lead Auditor</span>
                                        <span class="text-[9px] px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full font-bold uppercase">Valid</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white p-2.5 rounded-lg border border-blue-100 shadow-sm">
                                        <span class="text-xs font-bold text-slate-700">Fire Safety Specialist</span>
                                        <span class="text-[9px] px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full font-bold uppercase">Valid</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Recent Operational Actions</h4>
                                <div class="relative pl-6 space-y-6 border-l-2 border-slate-100 ml-2">
                                    <div class="relative">
                                        <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-blue-500 border-4 border-white"></div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase italic">Yesterday</p>
                                        <p class="text-sm font-bold text-slate-800">Conducted High-Risk Food Safety Audit</p>
                                        <p class="text-[10px] text-slate-500 mt-1">Establishment: Green Valley Bistro (Ref #9234)</p>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-slate-300 border-4 border-white"></div>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase italic">3 Days Ago</p>
                                        <p class="text-sm font-bold text-slate-800">Completed LGU Forensic Training</p>
                                        <p class="text-[10px] text-slate-500 mt-1">Module: Multi-Agency Data Interoperability</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }, 600);
        }
    </script>
</body>
</html>
