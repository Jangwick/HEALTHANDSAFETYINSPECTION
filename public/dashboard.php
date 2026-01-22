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
            html { font-size: 100%; }
            body { @apply text-slate-700; }
            h1, h2, h3, h4, h5, h6 { @apply font-bold tracking-tight text-slate-900; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <?php 
            $activePage = 'dashboard';
            include __DIR__ . '/views/partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 z-10 shrink-0">
                <div class="flex items-center">
                    <button class="md:hidden p-2 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-50 focus:outline-none">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2 class="text-sm font-bold text-slate-700 ml-2 md:ml-0 tracking-tight flex items-center">
                        <span class="w-1 h-4 bg-blue-700 rounded-full mr-2"></span>
                        Management Dashboard
                    </h2>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition-all relative border border-transparent hover:border-slate-200">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-1.5 right-1.5 h-2 w-2 bg-rose-500 rounded-full border-2 border-white"></span>
                    </button>
                    
                    <div class="relative">
                        <button onclick="toggleUserMenu()" class="flex items-center space-x-2 focus:outline-none p-1 rounded-lg hover:bg-slate-50 transition-all border border-transparent hover:border-slate-200">
                            <img id="userAvatar" class="h-8 w-8 rounded border border-slate-200" src="https://ui-avatars.com/api/?name=User&background=1e40af&color=fff" alt="">
                            <div class="hidden sm:block text-left mr-1">
                                <p id="userName" class="text-xs font-bold text-slate-800 leading-none mb-0.5">Officer</p>
                                <p id="userRole" class="text-[10px] text-slate-500 font-medium uppercase tracking-tight leading-none">Access Level</p>
                            </div>
                            <i class="fas fa-chevron-down text-[10px] text-slate-400"></i>
                        </button>
                        
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-52 rounded-xl shadow-lg bg-white border border-slate-200 py-1 z-50 overflow-hidden">
                            <div class="px-4 py-2.5 border-b border-slate-100 mb-1 bg-slate-50/50">
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">Account Identity</p>
                                <p class="text-xs font-bold text-slate-700 truncate"><?php echo htmlspecialchars($_SESSION['email'] ?? 'user@gov.ph'); ?></p>
                            </div>
                            <a href="/profile" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-blue-700 transition-colors">
                                <i class="fas fa-user-circle mr-2 opacity-50"></i>My Profile
                            </a>
                            <a href="/settings" class="block px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-blue-700 transition-colors">
                                <i class="fas fa-sliders-h mr-2 opacity-50"></i>Preferences
                            </a>
                            <div class="border-t border-slate-100 my-1"></div>
                            <button onclick="handleLogout()" class="w-full flex items-center space-x-2 px-4 py-2.5 text-xs font-bold text-rose-600 hover:bg-rose-50 transition-colors">
                                <i class="fas fa-sign-out-alt opacity-50"></i>
                                <span>Logout Session</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto bg-[#f8fafc] p-10 font-sans">
                <div class="max-w-7xl mx-auto space-y-8">
                    <!-- Statistics Header -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">System Oversight</h1>
                            <p class="text-slate-500 mt-2 font-medium">Personnel and inspection metrics for the current fiscal period.</p>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-blue-50 rounded-lg text-blue-700 border border-blue-100/50">
                                    <i class="fas fa-clipboard-list text-xl"></i>
                                </div>
                                <span class="text-[11px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded border border-green-100">+12%</span>
                            </div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Total Logs</h3>
                            <p class="text-3xl font-bold text-slate-900">247</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-amber-50 rounded-lg text-amber-700 border border-amber-100/50">
                                    <i class="fas fa-hourglass-half text-xl"></i>
                                </div>
                                <span class="text-[11px] font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded border border-amber-100">Review</span>
                            </div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Pending Assignments</h3>
                            <p class="text-3xl font-bold text-slate-900">18</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-rose-50 rounded-lg text-rose-700 border border-rose-100/50">
                                    <i class="fas fa-shield-virus text-xl"></i>
                                </div>
                                <span class="text-[11px] font-bold text-rose-600 bg-rose-50 px-2 py-1 rounded border border-rose-100">Action Required</span>
                            </div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Safety Violations</h3>
                            <p class="text-3xl font-bold text-slate-900">2</p>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-700 border border-indigo-100/50">
                                    <i class="fas fa-certificate text-xl"></i>
                                </div>
                                <span class="text-[11px] font-bold text-slate-500 bg-slate-50 px-2 py-1 rounded border border-slate-100 text-slate-500">Valid</span>
                            </div>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Issued Certificates</h3>
                            <p class="text-3xl font-bold text-slate-900">89</p>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <!-- Inspection Trends -->
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-sm font-bold text-slate-800">Inspection Volume (6 Mo)</h3>
                                <button class="text-slate-400 hover:text-slate-600"><i class="fas fa-ellipsis-v text-xs"></i></button>
                            </div>
                            <div class="h-[280px] relative">
                                <canvas id="inspectionTrendsChart"></canvas>
                            </div>
                        </div>

                        <!-- Violation Categories -->
                        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-sm font-bold text-slate-800">Violation Distribution</h3>
                                <button class="text-slate-400 hover:text-slate-600"><i class="fas fa-ellipsis-v text-xs"></i></button>
                            </div>
                            <div class="h-[280px] relative">
                                <canvas id="violationCategoriesChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Inspections -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/30 flex justify-between items-center">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">Recent Service Records</h3>
                                <p class="text-[11px] text-slate-500 font-medium mt-0.5">Most recent administrative and field observations.</p>
                            </div>
                            <a href="/inspections" class="text-[11px] font-bold text-blue-700 hover:text-blue-800 uppercase tracking-wider transition-colors flex items-center border border-slate-200 px-3 py-1.5 rounded-lg bg-white shadow-sm hover:bg-slate-50">
                                Detailed Log <i class="fas fa-chevron-right ml-1.5 text-[8px]"></i>
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-4">Ref ID</th>
                                        <th class="px-6 py-4">Establishment Name</th>
                                        <th class="px-6 py-4">Service Category</th>
                                        <th class="px-6 py-4">Officer in Charge</th>
                                        <th class="px-6 py-4">Record Date</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-right">Records</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4 text-xs font-bold text-blue-700">HSI-25-00045</td>
                                        <td class="px-6 py-4">
                                            <p class="text-xs font-bold text-slate-900 tracking-tight">ABC Restaurant & Catering</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded">FOOD SERVICES</span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-semibold text-slate-600">J. Dela Cruz</td>
                                        <td class="px-6 py-4 text-xs font-medium text-slate-500">Nov 21, 2025</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 bg-green-50 text-green-700 border border-green-100 rounded-full text-[10px] font-bold uppercase tracking-wider">
                                                Finalized
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button class="text-[10px] font-bold text-slate-400 hover:text-blue-700 uppercase tracking-wider transition-colors">View File</button>
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
            Chart.defaults.color = '#64748b';
            Chart.defaults.font.family = "'Inter', 'sans-serif'";
            Chart.defaults.font.weight = '500';

            const trendsCtx = document.getElementById('inspectionTrendsChart');
            if (trendsCtx) {
                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN'],
                        datasets: [{
                            label: 'Services Rendered',
                            data: [45, 52, 58, 62, 68, 75],
                            borderColor: '#1e40af',
                            backgroundColor: 'rgba(30, 64, 175, 0.05)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#1e40af',
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                grid: { color: '#f1f5f9' },
                                border: { display: false },
                                ticks: { font: { size: 10, weight: '600' } }
                            },
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { font: { size: 10, weight: '600' } }
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
                            backgroundColor: ['#1e40af', '#059669', '#dc2626', '#475569'],
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'rectRounded',
                                    font: { size: 10, weight: '600' }
                                }
                            }
                        }
                    }
                });
            }
        }

        window.addEventListener('DOMContentLoaded', initCharts);
    </script>
