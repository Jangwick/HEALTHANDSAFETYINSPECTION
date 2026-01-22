<?php
declare(strict_types=1);

/**
 * LGU Cluster Hub View
 * Shows interoperability and data sharing between safety units
 */

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Simulate integration data for demonstration
$integrations = [
    [
        'agency' => 'Philippine National Police (PNP)',
        'type' => 'Law Enforcement Gateway',
        'status' => 'Active',
        'shared_data' => ['Serious Violations', 'Uncooperative Establishments', 'Criminal Record Checks'],
        'last_sync' => '5 minutes ago',
        'alerts' => 3
    ],
    [
        'agency' => 'Bureau of Fire Protection (BFP)',
        'type' => 'Emergency Response',
        'status' => 'Active',
        'shared_data' => ['Occupancy Load', 'Fire Safety Permits', 'Structural Risks'],
        'last_sync' => '12 minutes ago',
        'alerts' => 1
    ],
    [
        'agency' => 'Sanitary & Environmental Office',
        'type' => 'Health Compliance',
        'status' => 'Active',
        'shared_data' => ['Water Quality', 'Waste Disposal', 'Food Handling Permits'],
        'last_sync' => '1 hour ago',
        'alerts' => 0
    ],
    [
        'agency' => 'Business Permit & Licensing (BPLO)',
        'type' => 'Administrative Registry',
        'status' => 'Syncing',
        'shared_data' => ['Business Status', 'Tax Compliance', 'Owner Information'],
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
    <title>LGU Cluster Hub - Health & Safety Inspection System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#0b0c10] font-sans antialiased text-slate-200 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'integrations';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight italic">LGU Cluster Hub</h1>
                    <p class="text-[10px] text-purple-400 font-bold uppercase tracking-widest">Cross-Agency Interoperability Portal</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="location.reload()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-blue-900/20 transition-all active:scale-95 group">
                        <i class="fas fa-sync-alt mr-2 group-hover:rotate-180 transition-transform duration-500"></i> Sync All Gateways
                    </button>
                </div>
            </header>

            <main class="p-8">
                <!-- System-wide AI Summary -->
                <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-2xl shadow-xl p-8 mb-8 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                    <div class="relative z-10">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4">
                                <i class="fas fa-project-diagram text-2xl"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-white">Consolidated Safety Performance</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="bg-black/20 p-6 rounded-2xl">
                                <span class="text-indigo-200 text-xs font-bold block uppercase tracking-wider mb-1">Joint Operations</span>
                                <span class="text-4xl font-black">12</span>
                                <p class="text-indigo-200/60 text-[10px] mt-2 italic">Active multi-agency tasks</p>
                            </div>
                            <div class="bg-black/20 p-6 rounded-2xl">
                                <span class="text-indigo-200 text-xs font-bold block uppercase tracking-wider mb-1">Cross-Checks</span>
                                <span class="text-4xl font-black">1,482</span>
                                <p class="text-indigo-200/60 text-[10px] mt-2 italic">Automated validation this month</p>
                            </div>
                            <div class="bg-black/20 p-6 rounded-2xl">
                                <span class="text-indigo-200 text-xs font-bold block uppercase tracking-wider mb-1">Incident Response</span>
                                <span class="text-4xl font-black">99.2%</span>
                                <p class="text-indigo-200/60 text-[10px] mt-2 italic">Gateway uptime last 30 days</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Active Gateways -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-bold text-white">Cluster Gateways</h3>
                            <span class="text-[10px] text-slate-500 uppercase font-black">4 Nodes Active</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($integrations as $gateway): ?>
                            <div class="bg-[#1a1c23] rounded-2xl p-6 border border-white/5 group hover:border-blue-500/30 transition-all">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="p-3 bg-white/5 rounded-xl group-hover:bg-blue-600/10 transition-colors">
                                        <i class="fas <?php 
                                            echo strpos($gateway['agency'], 'Police') !== false ? 'fa-shield-alt text-blue-400' : 
                                                (strpos($gateway['agency'], 'Fire') !== false ? 'fa-fire-extinguisher text-orange-400' : 
                                                (strpos($gateway['agency'], 'Health') !== false || strpos($gateway['agency'], 'Sanitary') !== false ? 'fa-heartbeat text-emerald-400' : 'fa-building text-purple-400')); 
                                        ?> text-xl"></i>
                                    </div>
                                    <span class="px-2 py-1 <?php echo $gateway['status'] === 'Active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-yellow-500/10 text-yellow-400'; ?> text-[10px] font-bold rounded-lg uppercase border <?php echo $gateway['status'] === 'Active' ? 'border-emerald-500/20' : 'border-yellow-500/20'; ?>">
                                        <?php echo $gateway['status']; ?>
                                    </span>
                                </div>
                                <h4 class="font-bold text-white mb-1"><?php echo $gateway['agency']; ?></h4>
                                <p class="text-[10px] text-slate-500 mb-4"><?php echo $gateway['type']; ?></p>
                                
                                <div class="space-y-2 mb-4">
                                    <?php foreach (array_slice($gateway['shared_data'], 0, 2) as $data): ?>
                                    <div class="flex items-center text-[11px] text-slate-300">
                                        <i class="fas fa-check-circle text-emerald-500/50 mr-2"></i>
                                        <?php echo $data; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="pt-4 border-t border-white/5 flex justify-between items-center">
                                    <span class="text-[9px] text-slate-500 uppercase">Last Sync: <?php echo $gateway['last_sync']; ?></span>
                                    <button class="text-blue-400 text-[10px] font-bold hover:underline">Manage</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Alerts & Activity -->
                    <div class="space-y-6">
                        <div class="bg-[#1a1c23] rounded-2xl border border-white/5 p-6">
                            <h3 class="text-lg font-bold text-white mb-6 flex items-center">
                                <i class="fas fa-bell text-yellow-400 mr-2"></i> Integration Alerts
                            </h3>
                            <div class="space-y-4">
                                <?php foreach ($recent_alerts as $alert): ?>
                                <div class="border-l-2 border-indigo-500 pl-4 py-1">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-[10px] font-black text-indigo-400 uppercase"><?php echo $alert['agency']; ?></span>
                                        <span class="text-[9px] text-slate-500"><?php echo $alert['time']; ?></span>
                                    </div>
                                    <p class="text-xs text-slate-300"><?php echo $alert['msg']; ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button class="w-full mt-6 py-2 bg-white/5 hover:bg-white/10 text-slate-400 text-[10px] font-bold rounded-xl transition-colors uppercase tracking-widest">
                                View All Activity
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h4 class="font-bold text-gray-800"><?php echo $gateway['agency']; ?></h4>
                                <span class="text-xs text-gray-500 uppercase"><?php echo $gateway['type']; ?></span>
                            </div>
                            <?php if ($gateway['alerts'] > 0): ?>
                                <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-1 rounded-full">
                                    <?php echo $gateway['alerts']; ?> Alerts
                                </span>
                            <?php else: ?>
                                <span class="bg-green-100 text-green-600 text-xs font-bold px-2 py-1 rounded-full">
                                    Synced
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <p class="text-xs font-medium text-gray-600">Shared Data Channels:</p>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($gateway['shared_data'] as $data): ?>
                                    <span class="bg-gray-100 text-gray-700 text-[10px] px-2 py-0.5 rounded border border-gray-200">
                                        <?php echo $data; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-xs text-gray-400">
                            <span>Last sync: <?php echo $gateway['last_sync']; ?></span>
                            <button class="text-blue-600 hover:underline font-medium">Configure Hub</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Shared Visual Evidence Feed -->
                <div class="mt-8 bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-bold text-gray-700">Inter-Agency Shared Evidence</h3>
                        <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">Secured JWT Channel</span>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-400 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2">Evidence Reference</th>
                                    <th class="px-3 py-2">Originating Unit</th>
                                    <th class="px-3 py-2">Target Agency</th>
                                    <th class="px-3 py-2">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-image mr-2 text-gray-400"></i>
                                            <span>IMG-VIO-2023-841</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 font-medium">Health Inspection</td>
                                    <td class="px-3 py-3">PNP</td>
                                    <td class="px-3 py-3">
                                        <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-external-link-alt"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-pdf mr-2 text-red-400"></i>
                                            <span>FIRE-RISK-ASSESSMENT-01</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 font-medium">BFP</td>
                                    <td class="px-3 py-3">Safety Dept</td>
                                    <td class="px-3 py-3">
                                        <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-external-link-alt"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Inter-agency Communication -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-bell text-yellow-500 mr-2"></i> Joint Alert Stream
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($recent_alerts as $alert): ?>
                        <div class="flex items-start">
                            <div class="bg-gray-100 rounded-full p-2 mr-3">
                                <i class="fas fa-paper-plane text-xs text-gray-500"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">[<?php echo $alert['agency']; ?>] <span class="font-normal text-gray-600"><?php echo $alert['msg']; ?></span></p>
                                <span class="text-[10px] text-gray-400"><?php echo $alert['time']; ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="w-full mt-6 bg-gray-50 text-gray-600 border border-gray-200 py-2 rounded text-sm hover:bg-gray-100 transition">
                        View Communication Log
                    </button>
                </div>

                <!-- API Connectivity Hub -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold mb-4">Interoperability API</h3>
                    <p class="text-xs text-gray-500 mb-4">Monitor secure endpoints and data traffic between public safety modules.</p>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Health Gateway API</span>
                            <span class="text-green-600 font-mono text-xs">ONLINE</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Police Link Service</span>
                            <span class="text-green-600 font-mono text-xs">ONLINE</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">BFP Real-time Relay</span>
                            <span class="text-green-600 font-mono text-xs">ONLINE</span>
                        </div>
                        <div class="h-12 bg-gray-900 rounded p-2 flex items-center justify-center">
                            <code class="text-green-400 text-[10px]">AUTH_MODE: JWT_SECURE_TOKEN</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
