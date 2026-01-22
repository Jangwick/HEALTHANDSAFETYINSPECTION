<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Health & Safety Inspections</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 105%; }
            body { @apply text-slate-200; }
            h1, h2, h3, h4, h5, h6 { @apply font-bold tracking-tight text-white; }
        }
    </style>
</head>
<body class="bg-[#0b0c10] font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php 
            $activePage = 'dashboard';
            include __DIR__ . '/views/partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 z-10 shrink-0">
                <div class="flex items-center">
                    <button class="md:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-white/5 focus:outline-none">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="text-xl font-bold text-white ml-2 md:ml-0 tracking-tight flex items-center">
                        <span class="w-1.5 h-6 bg-blue-600 rounded-full mr-3"></span>
                        System Overview
                    </h2>
                </div>
                
                <div class="flex items-center space-x-6">
                    <button class="p-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-white/5 transition-all relative">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute top-2.5 right-2.5 h-2 w-2 bg-red-500 rounded-full border-2 border-[#0f1115]"></span>
                    </button>
                    
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center space-x-3 focus:outline-none p-1.5 rounded-xl hover:bg-white/5 transition-all group">
                            <img id="userAvatar" class="h-9 w-9 rounded-xl border border-white/10 shadow-inner" src="https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff" alt="">
                            <div class="hidden sm:block text-left">
                                <p id="userName" class="text-xs font-black text-white uppercase tracking-tight leading-none mb-1">Loading...</p>
                                <p id="userRole" class="text-[9px] text-blue-400 font-bold uppercase tracking-widest leading-none">Access Level</p>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] text-slate-500 group-hover:text-white transition-colors"></i>
                        </button>
                        
                        <div id="userMenu" class="hidden absolute right-0 mt-3 w-56 rounded-2xl shadow-2xl bg-[#15181e] border border-white/5 py-2 z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b border-white/5 mb-1 bg-white/[0.02]">
                                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mb-1">Logged In As</p>
                                <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($_SESSION['email'] ?? 'user@system.lgu'); ?></p>
                            </div>
                            <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-user mr-2 text-gray-400"></i>Profile
                            </a>
                            <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-cog mr-2 text-gray-400"></i>Settings
                            </a>
                            <hr class="my-1 border-gray-100">
                            <button onclick="handleLogout()" class="w-full flex items-center space-x-3 px-4 py-2.5 text-xs font-black text-rose-400 hover:bg-rose-600 hover:text-white transition-all">
                                <i class="fas fa-sign-out-alt text-base w-5"></i>
                                <span>SIGNOUT SYSTEM</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8 custom-scrollbar bg-[#0b0c10]">
                <div class="max-w-7xl mx-auto">
                    <!-- Welcome Banner -->
                    <div class="bg-gradient-to-br from-blue-700 via-indigo-800 to-indigo-950 rounded-3xl shadow-2xl p-10 mb-10 text-white relative overflow-hidden border border-white/5">
                        <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
                        <div class="relative z-10">
                            <span class="inline-block px-3 py-1 bg-white/10 rounded-lg text-[10px] font-black uppercase tracking-[0.2em] mb-4 border border-white/10 italic">LGU Safety Intelligence</span>
                            <h1 class="text-4xl font-black mb-3 tracking-tighter italic">Welcome back, <span id="welcomeName" class="text-blue-300">Hub Manager</span>!</h1>
                            <p class="text-blue-100/70 text-lg font-medium max-w-xl leading-relaxed">System diagnostics show that inspection throughput is up <span class="text-emerald-400 font-bold">12.4%</span> since last week. Here's your operational snapshot.</p>
                        </div>
                        <i class="fas fa-shield-alt absolute -right-8 -bottom-8 text-[12rem] text-white/5 rotate-12"></i>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-10">
                        <div class="bg-[#15181e] p-7 rounded-3xl border border-white/5 shadow-xl group hover:border-blue-500/30 transition-all duration-500">
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-3.5 bg-blue-500/10 rounded-2xl text-blue-400 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-clipboard-check text-2xl"></i>
                                </div>
                                <span class="text-[10px] font-black text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg">+12%</span>
                            </div>
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Total Inspections</h3>
                            <p class="text-3xl font-black text-white tracking-tighter">247</p>
                        </div>

                        <div class="bg-[#15181e] p-7 rounded-3xl border border-white/5 shadow-xl group hover:border-amber-500/30 transition-all duration-500">
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-3.5 bg-amber-500/10 rounded-2xl text-amber-400 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-clock text-2xl"></i>
                                </div>
                                <span class="text-[10px] font-black text-amber-400 bg-amber-400/10 px-2 py-1 rounded-lg">Due Soon</span>
                            </div>
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Pending</h3>
                            <p class="text-3xl font-black text-white tracking-tighter">18</p>
                        </div>

                        <div class="bg-[#15181e] p-7 rounded-3xl border border-white/5 shadow-xl group hover:border-rose-500/30 transition-all duration-500">
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-3.5 bg-rose-500/10 rounded-2xl text-rose-400 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                                </div>
                                <span class="text-[10px] font-black text-rose-400 bg-rose-400/10 px-2 py-1 rounded-lg">5 Active</span>
                            </div>
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Critical Violations</h3>
                            <p class="text-3xl font-black text-white tracking-tighter">2</p>
                        </div>

                        <div class="bg-[#15181e] p-7 rounded-3xl border border-white/5 shadow-xl group hover:border-emerald-500/30 transition-all duration-500">
                            <div class="flex items-center justify-between mb-6">
                                <div class="p-3.5 bg-emerald-500/10 rounded-2xl text-emerald-400 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-certificate text-2xl"></i>
                                </div>
                                <span class="text-[10px] font-black text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg">Valid</span>
                            </div>
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Certificates</h3>
                            <p class="text-3xl font-black text-white tracking-tighter">89</p>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                        <!-- Inspection Trends -->
                        <div class="bg-[#15181e] p-7 rounded-3xl border border-white/5 shadow-xl">
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Inspection Trends</h3>
                            <div class="h-[300px] relative">
                                <canvas id="inspectionTrendsChart"></canvas>
                            </div>
                        </div>

                        <!-- Violation Categories -->
                        <div class="bg-[#15181e] p-7 rounded-3xl border border-white/5 shadow-xl">
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Violation Categories</h3>
                            <div class="h-[300px] relative">
                                <canvas id="violationCategoriesChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Inspections -->
                    <div class="bg-[#15181e] rounded-3xl border border-white/5 shadow-xl overflow-hidden mt-8">
                        <div class="px-8 py-6 border-b border-white/5 bg-white/[0.02] flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight italic">Recent Operational Activity</h3>
                                <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Real-time inspection sync</p>
                            </div>
                            <a href="/inspections" class="text-[10px] font-black text-blue-400 hover:text-white uppercase tracking-widest transition-colors flex items-center bg-white/5 px-4 py-2 rounded-xl">
                                View Full Log <i class="fas fa-arrow-right ml-2 text-[8px]"></i>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-black/20 text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                    <tr>
                                        <th class="px-8 py-5">Reference</th>
                                        <th class="px-8 py-5">Establishment</th>
                                        <th class="px-8 py-5">Type / Category</th>
                                        <th class="px-8 py-5">Field Officer</th>
                                        <th class="px-8 py-5">Timestamp</th>
                                        <th class="px-8 py-5 text-center">Outcome</th>
                                        <th class="px-8 py-5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <tr class="hover:bg-white/[0.02] transition-all group">
                                        <td class="px-8 py-5 text-xs font-black text-blue-400 tracking-tighter">HSI-2025-00045</td>
                                        <td class="px-8 py-5">
                                            <p class="text-sm font-bold text-white tracking-tight">ABC Restaurant</p>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-2 py-1 bg-white/5 rounded-md">Food Safety</span>
                                        </td>
                                        <td class="px-8 py-5 text-xs font-bold text-slate-300">J. Dela Cruz</td>
                                        <td class="px-8 py-5 text-xs font-bold text-slate-300">Nov 21, 2025</td>
                                        <td class="px-8 py-5 text-center">
                                            <span class="px-4 py-1.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/10 rounded-full text-[10px] font-black uppercase tracking-widest italic">
                                                Completed
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            <button class="text-[10px] font-black text-slate-500 hover:text-white uppercase tracking-widest transition-all">Details</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize charts with theme awareness
        function initCharts() {
            Chart.defaults.color = '#94a3b8';
            Chart.defaults.font.family = "'Inter', 'sans-serif'";
            Chart.defaults.font.weight = '600';

            const trendsCtx = document.getElementById('inspectionTrendsChart');
            if (trendsCtx) {
                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN'],
                        datasets: [{
                            label: 'Inspections',
                            data: [45, 52, 58, 62, 68, 75],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#0b0c10',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                border: { display: false },
                                ticks: { font: { size: 10, weight: '900' } }
                            },
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { font: { size: 10, weight: '900' } }
                            }
                        }
                    }
                });
            }

            const categoriesCtx = document.getElementById('violationCategoriesChart');
            if (categoriesCtx) {
                new Chart(categoriesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Food Safety', 'Sanitation', 'Fire Safety', 'Other'],
                        datasets: [{
                            data: [35, 25, 15, 25],
                            backgroundColor: ['#3b82f6', '#10b981', '#f43f5e', '#64748b'],
                            borderWidth: 0,
                            hoverOffset: 20
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    font: { size: 10, weight: '900' },
                                    generateLabels: function(chart) {
                                        const original = Chart.defaults.plugins.legend.labels.generateLabels;
                                        const labels = original.call(this, chart);
                                        return labels.map(label => ({ ...label, text: label.text.toUpperCase() }));
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        window.addEventListener('DOMContentLoaded', initCharts);
    </script>
</body>
</html>
