<!-- Sidebar Navigation Partial -->
<aside class="w-64 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col">
    <div class="p-6 border-b border-slate-800 flex items-center">
        <div class="h-10 w-10 bg-blue-600 rounded-lg flex items-center justify-center">
            <i class="fas fa-shield-alt text-white text-xl"></i>
        </div>
        <span class="ml-3 text-xl font-bold tracking-tight uppercase">H&S System</span>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-4">
        <div class="px-4 space-y-1">
            <a href="/dashboard" class="<?php echo $activePage == 'dashboard' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-home mr-3 text-lg w-6"></i>
                Dashboard
            </a>
            
            <a href="/inspections" class="<?php echo $activePage == 'inspections' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-clipboard-check mr-3 text-lg w-6"></i>
                Inspections
            </a>

            <a href="/inspections/scheduling" class="<?php echo $activePage == 'scheduling' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-calendar-alt mr-3 text-lg w-6"></i>
                Scheduling
            </a>
            
            <a href="/establishments" class="<?php echo $activePage == 'establishments' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-building mr-3 text-lg w-6"></i>
                Establishments
            </a>
            
            <a href="/certificates" class="<?php echo $activePage == 'certificates' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-certificate mr-3 text-lg w-6"></i>
                Certificates
            </a>

            <a href="/violations" class="<?php echo $activePage == 'violations' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-exclamation-triangle mr-3 text-lg w-6"></i>
                Violations
            </a>

            <div class="pt-4 pb-2">
                <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Reports & Analytics</p>
            </div>

            <a href="/establishments/statistics.php" class="<?php echo $activePage == 'statistics' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-3 py-3 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-chart-bar mr-3 text-lg w-6"></i>
                Compliance Analytics
            </a>
        </div>
    </nav>

    <div class="p-4 border-t border-slate-800">
        <div class="flex items-center p-2 rounded-lg bg-slate-800/50">
            <img id="sideUserAvatar" class="h-9 w-9 rounded-full border border-slate-700" src="https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff" alt="">
            <div class="ml-3 overflow-hidden">
                <p id="sideUserName" class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Inspector'); ?></p>
                <p id="sideUserRole" class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></p>
            </div>
        </div>
        <button onclick="handleLogout()" class="mt-4 w-full flex items-center justify-center px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white border border-slate-700 rounded-lg transition-colors hover:bg-slate-800">
            <i class="fas fa-sign-out-alt mr-2"></i> SIGN OUT
        </button>
    </div>
</aside>
