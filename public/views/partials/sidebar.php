<!-- Institutional Sidebar Navigation -->
<aside class="w-72 bg-white text-slate-600 flex-shrink-0 hidden md:flex flex-col border-r border-slate-200 shadow-sm z-30">
    
    <!-- Institutional Branding -->
    <div class="px-7 py-8 pb-6 flex items-center">
        <div class="h-10 w-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20 transition-transform duration-500 hover:rotate-6">
            <i class="fas fa-shield-halved text-white text-lg"></i>
        </div>
        <div class="ml-3">
            <span class="block text-sm font-bold tracking-tight text-slate-900 leading-none">Health & Safety</span>
            <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-1 block">LGU Registry</span>
        </div>
    </div>
    
    <!-- Command Interface -->
    <nav class="flex-1 overflow-y-auto py-4">
        <div class="px-4 space-y-1">
            
            <!-- Category: Command & Analytics -->
            <p class="px-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3 mt-4 opacity-70">Command Console</p>
            
            <a href="/dashboard" class="<?php echo ($activePage ?? '') == 'dashboard' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-layer-group mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'dashboard' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Dashboard Overview
            </a>

            <!-- Category: Field Operations -->
            <div class="pt-8 pb-2">
                <p class="px-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest opacity-70">Field Operations</p>
            </div>

            <?php if (($_SESSION['role'] ?? '') !== 'establishment_owner'): ?>
            <a href="/inspections" class="<?php echo ($activePage ?? '') == 'inspections' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-clipboard-check mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'inspections' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Inspection Logs
            </a>

            <a href="/scheduling" class="<?php echo ($activePage ?? '') == 'scheduling' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-calendar-day mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'scheduling' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Scheduling Grid
            </a>
            <?php endif; ?>
            
            <a href="/establishments" class="<?php echo ($activePage ?? '') == 'establishments' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-landmark mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'establishments' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Establishment List
            </a>
            
            <a href="/certificates" class="<?php echo ($activePage ?? '') == 'certificates' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-stamp mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'certificates' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Safety Certificates
            </a>

            <?php if (($_SESSION['role'] ?? '') !== 'establishment_owner'): ?>
            <a href="/violations" class="<?php echo ($activePage ?? '') == 'violations' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-skull-crossbones mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'violations' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Violations Log
            </a>

            <!-- Category: Governance -->
            <div class="pt-8 pb-2">
                <p class="px-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest opacity-70">Governance</p>
            </div>

            <a href="/inspectors" class="<?php echo ($activePage ?? '') == 'inspectors' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-id-badge mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'inspectors' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Inspector Personnel
            </a>

            <a href="/integrations" class="<?php echo ($activePage ?? '') == 'integrations' ? 'bg-blue-50 text-blue-700 border border-blue-100 shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> group flex items-center px-4 py-3 text-xs font-semibold rounded-xl transition-all duration-200">
                <i class="fas fa-hubspot mr-4 text-base w-5 <?php echo ($activePage ?? '') == 'integrations' ? 'text-blue-600' : 'text-slate-300 group-hover:text-slate-500'; ?>"></i>
                Integration Hub
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Personnel Authentication Context -->
    <div class="px-4 py-6 bg-white border-t border-slate-100">
        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 mb-4 transition-all hover:border-slate-200 shadow-sm">
            <div class="flex items-center">
                <div class="relative shrink-0">
                    <img class="h-10 w-10 rounded-xl border border-white shadow-sm" 
                         src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username'] ?? 'User'); ?>&background=1d4ed8&color=fff&bold=true" alt="Avatar">
                    <span class="absolute -top-0.5 -right-0.5 h-3 w-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                </div>
                <div class="ml-3 overflow-hidden">
                    <p class="text-[12px] font-bold text-slate-900 truncate"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Personnel'); ?></p>
                    <p class="text-[10px] text-slate-500 font-medium truncate uppercase tracking-tight mt-0.5"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Authorized'); ?></p>
                </div>
            </div>
        </div>
        <a href="/logout" class="flex items-center justify-center gap-3 w-full h-11 text-xs font-bold text-slate-500 hover:text-rose-600 border border-slate-100 rounded-xl transition-all hover:bg-rose-50 hover:border-rose-100 shadow-sm">
            <i class="fas fa-power-off text-sm opacity-60"></i>
            Sign Out
        </a>
    </div>
</aside>
