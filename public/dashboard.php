<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Health & Safety Inspections</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="h-10 w-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                        <span class="ml-3 text-xl font-bold text-gray-900">H&S Inspections</span>
                    </div>
                    <div class="hidden md:ml-10 md:flex md:space-x-8">
                        <a href="/dashboard.php" class="border-blue-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="/inspections" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-clipboard-check mr-2"></i>Inspections
                        </a>
                        <a href="/establishments" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-building mr-2"></i>Establishments
                        </a>
                        <a href="/certificates" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <i class="fas fa-certificate mr-2"></i>Certificates
                        </a>
                    </div>
                </div>
                <div class="flex items-center">
                    <button class="p-2 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 mr-2">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="absolute top-2 right-2 h-2 w-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="ml-3 relative">
                        <div class="flex items-center cursor-pointer" onclick="toggleUserMenu()">
                            <img id="userAvatar" class="h-10 w-10 rounded-full border-2 border-blue-500" src="https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff" alt="User">
                            <div class="ml-3 hidden md:block">
                                <p id="userName" class="text-sm font-medium text-gray-700">Loading...</p>
                                <p id="userRole" class="text-xs text-gray-500">User</p>
                            </div>
                            <i class="fas fa-chevron-down ml-2 text-gray-400"></i>
                        </div>
                        <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                            <div class="py-1">
                                <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i>Profile
                                </a>
                                <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog mr-2"></i>Settings
                                </a>
                                <hr class="my-1">
                                <a href="#" onclick="handleLogout()" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg shadow-lg p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Welcome back, <span id="welcomeName">User</span>!</h1>
                    <p class="text-blue-100">Here's what's happening with your inspections today.</p>
                </div>
                <div class="hidden md:block">
                    <i class="fas fa-clipboard-list text-6xl opacity-30"></i>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Inspections -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-500 rounded-lg p-3">
                        <i class="fas fa-clipboard-check text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Inspections</p>
                        <p class="text-2xl font-bold text-gray-900">247</p>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-green-600 font-medium">
                        <i class="fas fa-arrow-up"></i> 12% from last month
                    </span>
                </div>
            </div>

            <!-- Pending Inspections -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-yellow-500 rounded-lg p-3">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Pending</p>
                        <p class="text-2xl font-bold text-gray-900">18</p>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-calendar"></i> Due this week
                    </span>
                </div>
            </div>

            <!-- Active Violations -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-red-500 rounded-lg p-3">
                        <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Violations</p>
                        <p class="text-2xl font-bold text-gray-900">5</p>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-red-600 font-medium">
                        <i class="fas fa-exclamation-circle"></i> 2 critical
                    </span>
                </div>
            </div>

            <!-- Certificates Issued -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-500 rounded-lg p-3">
                        <i class="fas fa-certificate text-white text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Certificates</p>
                        <p class="text-2xl font-bold text-gray-900">89</p>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-sm text-green-600 font-medium">
                        <i class="fas fa-check-circle"></i> 15 this month
                    </span>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Inspection Trends -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Inspection Trends</h3>
                <canvas id="inspectionTrendsChart"></canvas>
            </div>

            <!-- Violation Categories -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Violation Categories</h3>
                <canvas id="violationCategoriesChart"></canvas>
            </div>
        </div>

        <!-- Recent Inspections -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Inspections</h3>
                    <a href="/inspections" class="text-sm text-blue-600 hover:text-blue-500 font-medium">
                        View all <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Establishment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">HSI-2025-00045</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">ABC Restaurant</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Food Safety</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">J. Dela Cruz</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Nov 21, 2025</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Completed
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                <button class="text-gray-600 hover:text-gray-900">Download</button>
                            </td>
                        </tr>
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Load user data
        function loadUserData() {
            const user = JSON.parse(localStorage.getItem('user') || '{}');
            if (user.first_name) {
                document.getElementById('welcomeName').textContent = user.first_name;
                document.getElementById('userName').textContent = `${user.first_name} ${user.last_name}`;
                document.getElementById('userRole').textContent = user.role || 'User';
                
                const avatar = document.getElementById('userAvatar');
                avatar.src = user.profile_photo_url || `https://ui-avatars.com/api/?name=${user.first_name}+${user.last_name}&background=3B82F6&color=fff`;
            }
        }

        function toggleUserMenu() {
            document.getElementById('userMenu').classList.toggle('hidden');
        }

        async function handleLogout() {
            const token = localStorage.getItem('access_token');
            
            try {
                await fetch('/api/v1/auth/logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
            } catch (error) {
                console.error('Logout error:', error);
            }
            
            localStorage.removeItem('access_token');
            localStorage.removeItem('user');
            window.location.href = '/views/auth/login.php';
        }

        // Initialize charts
        function initCharts() {
            // Inspection Trends Chart
            const trendsCtx = document.getElementById('inspectionTrendsChart').getContext('2d');
            new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Inspections',
                        data: [45, 52, 48, 61, 58, 67],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Violation Categories Chart
            const categoriesCtx = document.getElementById('violationCategoriesChart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Food Safety', 'Sanitation', 'Structural', 'Fire Safety', 'Other'],
                    datasets: [{
                        data: [35, 25, 20, 15, 5],
                        backgroundColor: [
                            'rgb(59, 130, 246)',
                            'rgb(234, 179, 8)',
                            'rgb(239, 68, 68)',
                            'rgb(249, 115, 22)',
                            'rgb(156, 163, 175)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Close menu when clicking outside
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.relative')) {
                document.getElementById('userMenu').classList.add('hidden');
            }
        });

        // Initialize on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadUserData();
            initCharts();
        });
    </script>
</body>
</html>
