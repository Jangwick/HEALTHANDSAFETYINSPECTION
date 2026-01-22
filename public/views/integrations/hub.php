<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Simulated data
$integrations = [
    [
        'agency' => 'Philippine National Police (PNP)',
        'type' => 'Law Enforcement Gateway',
        'status' => 'Active',
        'shared_data' => ['Serious Violations', 'Closure Orders', 'Security Escorts'],
        'last_sync' => '5 minutes ago',
        'alerts' => 3
    ],
    [
        'agency' => 'Bureau of Fire Protection (BFP)',
        'type' => 'Emergency Response',
        'status' => 'Active',
        'shared_data' => ['Fire Safety Permits', 'Hazardous Materials', 'Structural Risks'],
        'last_sync' => '12 minutes ago',
        'alerts' => 1
    ],
    [
        'agency' => 'Business Permit & Licensing (BPLO)',
        'type' => 'Administrative Registry',
        'status' => 'Syncing',
        'shared_data' => ['Business Status', 'Tax Compliance', 'Entity Ownership'],
        'last_sync' => 'Now',
        'alerts' => 0
    ]
];

$recent_alerts = [
    ['agency' => 'PNP', 'msg' => 'Assistance requested for Closure Order #1294', 'time' => '10:45 AM'],
    ['agency' => 'BFP', 'msg' => 'Fire risk detected: Overcapacity at establishment #85', 'time' => '09:30 AM'],
    ['agency' => 'System', 'msg' => 'Automated data sync with BPLO completed successfully', 'time' => '08:15 AM']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interoperability Hub - Health & Safety Insight</title>
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
            $activePage = 'integrations';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">
            <!-- Institutional Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0 z-20">
                <div class="flex items-center space-x-4">
                    <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-800">Integration Hub</h1>
                    <div class="px-3 py-1 bg-blue-50 border border-blue-100 rounded-full">
                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest leading-none">Interoperability</span>
                    </div>
                </div>

                <div class="flex items-center space-x-6">
                    <div class="hidden md:flex flex-col items-end mr-4">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Gateway Context</span>
                        <span class="text-sm font-bold text-slate-900 leading-none mt-1 uppercase">Inter-Agency Liaison</span>
                    </div>
                    <button onclick="location.reload()" class="bg-white border border-slate-200 hover:border-blue-600 text-slate-600 hover:text-blue-600 px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-3 active:scale-95 group">
                        <i class="fas fa-sync-alt group-hover:rotate-180 transition-transform duration-700 text-[10px]"></i>
                        Execute Global Sync
                    </button>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto bg-[#f8fafc] p-10">
                <div class="max-w-7xl mx-auto space-y-10">
                    
                    <!-- Page Intro -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Inter-Agency Hub</h2>
                            <p class="text-slate-500 mt-2 font-medium">Central gateway for institutional synchronization with public safety and licensing agencies.</p>
                        </div>
                    </div>

                <!-- Institutional Intelligence Brief -->
                <div class="mb-10 bg-blue-700 rounded-[2.5rem] shadow-2xl shadow-blue-900/20 p-10 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -mr-48 -mt-48 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-black/10 rounded-full -ml-32 -mb-32 blur-2xl"></div>
                    
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                        <div class="max-w-xl">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="px-2 py-0.5 bg-blue-500 text-[8px] font-black uppercase tracking-widest rounded italic">Operational Status</span>
                                <span class="text-[10px] font-bold text-blue-100 uppercase tracking-widest italic">All Gateways Active</span>
                            </div>
                            <h2 class="text-2xl font-black uppercase italic leading-tight tracking-tighter mb-4">Institutional Intelligence Brief</h2>
                            <p class="text-sm font-medium text-blue-100/80 italic leading-relaxed">
                                Our data clusters are currently synchronizing with <span class="text-white font-bold underline decoration-blue-400 decoration-2">PNP</span>, <span class="text-white font-bold underline decoration-blue-400 decoration-2">BFP</span>, and <span class="text-white font-bold underline decoration-blue-400 decoration-2">BPLO</span>. 
                                High-priority alerts have been flagged for personnel review regarding structural risks and non-compliant entities.
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/10 backdrop-blur-sm text-center min-w-[100px]">
                                <div class="text-2xl font-black italic underline decoration-blue-400">03</div>
                                <div class="text-[8px] font-black uppercase tracking-widest mt-1 text-blue-200">Agency Gateways</div>
                            </div>
                            <div class="p-4 bg-white/5 rounded-2xl border border-white/10 backdrop-blur-sm text-center min-w-[100px]">
                                <div class="text-2xl font-black italic underline decoration-blue-400">12</div>
                                <div class="text-[8px] font-black uppercase tracking-widest mt-1 text-blue-200">Active Dossiers</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-10">
                    <!-- Agency Gateway Cards -->
                    <div class="xl:col-span-2 space-y-8">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="text-xs font-black text-slate-900 uppercase italic">Agency Interoperability Grid</h3>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">3 Nodes Detected</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($integrations as $node): ?>
                            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-blue-900/5 p-8 group hover:shadow-blue-900/10 transition-all relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-8 opacity-[0.03] group-hover:scale-110 transition-transform">
                                    <i class="fas fa-microchip text-[120px]"></i>
                                </div>
                                
                                <div class="flex justify-between items-start mb-6">
                                    <div class="w-12 h-12 bg-slate-50 border border-slate-100 rounded-2xl flex items-center justify-center text-blue-700 shadow-sm transition-transform group-hover:rotate-6">
                                        <i class="fas fa-university text-lg"></i>
                                    </div>
                                    <span class="px-3 py-1 <?= $node['status'] === 'Active' ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600 animate-pulse' ?> text-[9px] font-black uppercase rounded-lg italic">
                                        <?= htmlspecialchars($node['status']) ?>
                                    </span>
                                </div>

                                <h4 class="text-xs font-black text-slate-900 uppercase italic mb-1"><?= htmlspecialchars($node['agency']) ?></h4>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tight italic mb-6"><?= htmlspecialchars($node['type']) ?></p>

                                <div class="space-y-4 pt-6 border-t border-slate-50">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[9px] font-black text-slate-400 uppercase italic">Data Streams</span>
                                        <span class="text-[9px] font-black text-blue-700 uppercase italic"><?= count($node['shared_data']) ?> Active</span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($node['shared_data'] as $stream): ?>
                                        <span class="px-2 py-1 bg-slate-50 border border-slate-100 text-[8px] font-bold text-slate-600 uppercase italic rounded"><?= htmlspecialchars($stream) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center justify-between text-[8px] font-black text-slate-300 uppercase italic">
                                    <span>Last Sync: <?= $node['last_sync'] ?></span>
                                    <?php if ($node['alerts'] > 0): ?>
                                    <span class="text-rose-500"><i class="fas fa-exclamation-triangle mr-1"></i> <?= $node['alerts'] ?> Alerts Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Cluster Activity Timeline -->
                    <div class="space-y-8">
                        <div class="flex items-center justify-between px-2">
                            <h3 class="text-xs font-black text-slate-900 uppercase italic">Critical Activity Log</h3>
                            <i class="fas fa-stream text-slate-300"></i>
                        </div>

                        <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl shadow-blue-900/5 p-10 space-y-8 relative overflow-hidden">
                            <?php foreach ($recent_alerts as $alert): ?>
                            <div class="relative pl-10 group">
                                <div class="absolute left-[3px] top-4 w-[2px] h-full bg-slate-50 group-last:bg-transparent"></div>
                                <div class="absolute left-0 top-1 w-2 h-2 rounded-full border-2 border-white bg-blue-700 shadow-lg shadow-blue-900/20 z-10 group-hover:scale-150 transition-transform"></div>
                                
                                <div class="text-[8px] font-black text-slate-300 uppercase tracking-widest mb-1 italic"><?= $alert['time'] ?>  Cluster: <?= $alert['agency'] ?></div>
                                <p class="text-[11px] font-bold text-slate-700 italic leading-snug">
                                    <?= htmlspecialchars($alert['msg']) ?>
                                </p>
                            </div>
                            <?php endforeach; ?>

                            <div class="pt-4">
                                <button class="w-full h-12 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 text-[9px] font-black uppercase tracking-widest rounded-2xl transition-all italic">
                                    Access Full Interop Logs
                                </button>
                            </div>
                        </div>

                        <!-- System Integrity Card -->
                        <div class="bg-emerald-50 rounded-3xl border border-emerald-100 p-8 flex items-center gap-6">
                            <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-emerald-500 shadow-sm border border-emerald-100">
                                <i class="fas fa-shield-check text-xl"></i>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-black text-emerald-900 uppercase italic">Registry Integrity</h4>
                                <p class="text-[9px] font-bold text-emerald-600/70 uppercase tracking-widest mt-1">Status: 99.9% Encrypted</p>
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
