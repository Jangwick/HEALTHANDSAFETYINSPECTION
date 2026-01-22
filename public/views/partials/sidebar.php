<!-- Institutional Sidebar Navigation -->
<aside class="w-72 bg-white text-slate-600 flex-shrink-0 hidden md:flex flex-col border-r border-slate-200 shadow-sm z-30">
    
    <!-- Institutional Branding -->
    <div class="p-8 pb-6 border-b border-slate-100 flex items-center">
        <div class="h-11 w-11 bg-blue-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-900/20 rotate-3 group-hover:rotate-0 transition-transform duration-500">
            <i class="fas fa-shield-halved text-white text-xl"></i>
        </div>
        <div class="ml-4">
            <span class="block text-[12px] font-black tracking-tighter text-slate-900 uppercase italic leading-none">Health & Safety</span>
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1.5 block italic">LGU Insight Registry</span>
        </div>
    </div>
    
    <!-- Command Interface -->
    <nav class="flex-1 overflow-y-auto py-8">
        <div class="px-5 space-y-1.5">
            
            <!-- Category: Command & Analytics -->
            <p class="px-4 text-[9px] font-black text-slate-300 uppercase tracking-[0.25em] mb-4 mt-2 italic leading-none">Command Intelligence</p>
            
            <a href="/public/dashboard.php" class="<?php echo ($activePage ?? '') == 'dashboard' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-layer-group mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'dashboard' ? 'text-white' : 'text-slate-300 group-hover:text-blue-600'; ?>"></i>
                Operational Hub
            </a>

            <!-- Category: Field Operations -->
            <div class="pt-10 pb-4">
                <p class="px-4 text-[9px] font-black text-slate-300 uppercase tracking-[0.25em] italic leading-none">Regulatory Protocols</p>
            </div>

            <?php if (($_SESSION['role'] ?? '') !== 'establishment_owner'): ?>
            <a href="/public/views/inspections/list.php" class="<?php echo ($activePage ?? '') == 'inspections' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-clipboard-check mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'inspections' ? 'text-white' : 'text-slate-300 group-hover:text-blue-600'; ?>"></i>
                Protocol Logs
            </a>

            <a href="/public/views/inspections/scheduling.php" class="<?php echo ($activePage ?? '') == 'scheduling' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-calendar-day mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'scheduling' ? 'text-white' : 'text-slate-300 group-hover:text-amber-600'; ?>"></i>
                Schedule Grid
            </a>
            <?php endif; ?>
            
            <a href="/public/views/establishments/list.php" class="<?php echo ($activePage ?? '') == 'establishments' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-landmark mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'establishments' ? 'text-white' : 'text-slate-300 group-hover:text-blue-600'; ?>"></i>
                Entity Registry
            </a>
            
            <a href="/public/views/certificates/list.php" class="<?php echo ($activePage ?? '') == 'certificates' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-stamp mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'certificates' ? 'text-white' : 'text-slate-300 group-hover:text-indigo-600'; ?>"></i>
                Compliance Dossiers
            </a>

            <?php if (($_SESSION['role'] ?? '') !== 'establishment_owner'): ?>
            <a href="/public/views/violations/list.php" class="<?php echo ($activePage ?? '') == 'violations' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-skull-crossbones mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'violations' ? 'text-white' : 'text-slate-300 group-hover:text-rose-600'; ?>"></i>
                Anomaly Registry
            </a>

            <!-- Category: Administration -->
            <div class="pt-10 pb-4">
                <p class="px-4 text-[9px] font-black text-slate-300 uppercase tracking-[0.25em] italic leading-none">Administration</p>
            </div>

            <a href="/public/views/inspectors/list.php" class="<?php echo ($activePage ?? '') == 'inspectors' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-id-badge mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'inspectors' ? 'text-white' : 'text-slate-300 group-hover:text-emerald-600'; ?>"></i>
                Personnel Directory
            </a>

            <a href="/public/views/integrations/hub.php" class="<?php echo ($activePage ?? '') == 'integrations' ? 'bg-blue-700 text-white shadow-xl shadow-blue-900/20' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-700'; ?> group flex items-center px-4 py-3 text-[11px] font-black uppercase tracking-widest rounded-xl transition-all duration-300 italic">
                <i class="fas fa-hubspot mr-4 text-sm w-5 <?php echo ($activePage ?? '') == 'integrations' ? 'text-white' : 'text-slate-300 group-hover:text-blue-600'; ?>"></i>
                Cluster Hub
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Personnel Authentication Context -->
    <div class="p-6 bg-slate-50/50 border-t border-slate-100">
        <div class="p-4 bg-white rounded-[1.5rem] border border-slate-200 shadow-sm mb-4">
            <div class="flex items-center">
                <div class="relative shrink-0">
                    <img class="h-10 w-10 rounded-xl border border-slate-100 shadow-sm" 
                         src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username'] ?? 'User'); ?>&background=1d4ed8&color=fff&bold=true" alt="Avatar">
                    <span class="absolute -top-1 -right-1 h-3 w-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                </div>
                <div class="ml-3 overflow-hidden">
                    <p class="text-[10px] font-black text-slate-900 truncate uppercase italic leading-none mb-1"><?php echo htmlspecialchars($_SESSION['username'] ?? 'OFFICER'); ?></p>
                    <p class="text-[8px] text-slate-400 font-bold uppercase tracking-wider truncate"><?php echo htmlspecialchars($_SESSION['role'] ?? 'AUTHORIZED'); ?></p>
                </div>
            </div>
        </div>
        <a href="/public/views/auth/logout.php" class="flex items-center justify-center gap-3 w-full h-11 text-[9px] font-black text-slate-400 hover:text-rose-600 uppercase tracking-[0.2em] border border-slate-200 rounded-xl transition-all hover:bg-rose-50 hover:border-rose-100 italic">
            <i class="fas fa-power-off text-[10px]"></i>
            Terminate Session
        </a>
    </div>
</aside>
