<!-- Enhanced Sidebar Navigation Partial -->
<aside class="w-72 bg-[#0f1115] text-slate-300 flex-shrink-0 hidden md:flex flex-col border-r border-white/5 shadow-2xl z-20">
    <!-- Brand / Logo Section -->
    <div class="p-8 border-b border-white/5 flex items-center bg-black/20">
        <div class="h-11 w-11 bg-gradient-to-tr from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
            <i class="fas fa-shield-virus text-white text-xl"></i>
        </div>
        <div class="ml-4">
            <span class="block text-base font-black tracking-tighter uppercase text-white leading-none italic">HSI <span class="text-blue-500">SYSTEM</span></span>
            <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] mt-1 italic block">Safety Intelligence</span>
        </div>
    </div>
    
    <!-- Navigation Links -->
    <nav class="flex-1 overflow-y-auto py-8 custom-scrollbar">
        <div class="px-6 space-y-2">
            <!-- Section Title -->
            <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-[0.3em] mb-4">Core Analytics</p>
            
            <a href="/dashboard" class="<?php echo ($activePage ?? '') == 'dashboard' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-grid-2 mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'dashboard' ? 'text-white' : 'text-blue-500'; ?>"></i>
                Operational Hub
            </a>

            <!-- Section Title -->
            <div class="pt-8 pb-4">
                <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-[0.3em]">Field Operations</p>
            </div>

            <?php if (($_SESSION['role'] ?? '') !== 'establishment_owner'): ?>
            <a href="/inspections" class="<?php echo ($activePage ?? '') == 'inspections' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-clipboard-check mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'inspections' ? 'text-white' : 'text-emerald-500'; ?>"></i>
                Inspection Logs
            </a>

            <a href="/scheduling" class="<?php echo ($activePage ?? '') == 'scheduling' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-calendar-day mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'scheduling' ? 'text-white' : 'text-amber-500'; ?>"></i>
                Scheduler
            </a>
            <?php endif; ?>
            
            <a href="/establishments" class="<?php echo ($activePage ?? '') == 'establishments' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-building-circle-check mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'establishments' ? 'text-white' : 'text-blue-400'; ?>"></i>
                Establishments
            </a>
            
            <a href="/certificates" class="<?php echo ($activePage ?? '') == 'certificates' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-certificate mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'certificates' ? 'text-white' : 'text-indigo-400'; ?>"></i>
                Certificates
            </a>

            <?php if (($_SESSION['role'] ?? '') !== 'establishment_owner'): ?>
            <a href="/violations" class="<?php echo ($activePage ?? '') == 'violations' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-triangle-exclamation mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'violations' ? 'text-white' : 'text-rose-500'; ?>"></i>
                Violations Log
            </a>

            <a href="/inspectors" class="<?php echo ($activePage ?? '') == 'inspectors' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-user-shield mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'inspectors' ? 'text-white' : 'text-slate-400'; ?>"></i>
                Force Personnel
            </a>

            <a href="/integrations" class="<?php echo ($activePage ?? '') == 'integrations' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-hubspot mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'integrations' ? 'text-white' : 'text-violet-400'; ?>"></i>
                Cluster Hub
            </a>

            <!-- Section Title -->
            <div class="pt-8 pb-4">
                <p class="px-4 text-[10px] font-black text-slate-600 uppercase tracking-[0.3em]">Intelligence</p>
            </div>

            <a href="/analytics" class="<?php echo ($activePage ?? '') == 'statistics' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-900/40' : 'hover:bg-white/5 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-xs font-black rounded-2xl transition-all duration-300 uppercase tracking-widest italic">
                <i class="fas fa-chart-mixed mr-3 text-lg w-6 <?php echo ($activePage ?? '') == 'statistics' ? 'text-white' : 'text-cyan-400'; ?>"></i>
                Compliance AI
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- User Profile Section -->
    <div class="p-6 border-t border-white/5 bg-black/20">
        <div class="flex items-center p-3.5 rounded-2xl bg-white/[0.03] border border-white/5 group hover:border-blue-500/30 transition-all duration-500">
            <div class="relative">
                <img id="sideUserAvatar" class="h-10 w-10 rounded-xl border border-white/10 shadow-inner group-hover:scale-105 transition-transform" 
                     src="https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff" alt="">
                <span class="absolute -bottom-1 -right-1 h-3 w-3 bg-emerald-500 border-2 border-[#0f1115] rounded-full"></span>
            </div>
            <div class="ml-3 overflow-hidden">
                <p id="sideUserName" class="text-xs font-black text-white truncate uppercase tracking-tight italic"><?php echo htmlspecialchars($_SESSION['username'] ?? 'OFFICER'); ?></p>
                <p id="sideUserRole" class="text-[9px] text-blue-500 font-black uppercase truncate tracking-[0.15em] mt-0.5"><?php echo htmlspecialchars($_SESSION['role'] ?? 'AUTHORIZED'); ?></p>
            </div>
        </div>
        <button onclick="handleLogout()" class="mt-4 w-full flex items-center justify-center px-4 py-3 text-[10px] font-black text-slate-500 hover:text-white border border-white/5 rounded-xl transition-all hover:bg-rose-600 hover:border-rose-600 hover:shadow-lg hover:shadow-rose-900/20 uppercase tracking-[0.2em] italic">
            <i class="fas fa-power-off mr-2 text-xs"></i> TERMINATE SESSION
        </button>
    </div>
</aside>

<!-- Integration Script -->
<script>
    /**
     * Shared Sidebar & Session Management
     */
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        if (menu) {
            menu.classList.toggle('hidden');
            menu.classList.toggle('animate-in');
            menu.classList.toggle('fade-in');
            menu.classList.toggle('zoom-in-95');
        }
    }

    async function handleLogout() {
        if (!confirm('TERMINATE OPERATIONAL SESSION?')) return;
        
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
        window.location.href = '/login';
    }

    function updateSharedUserInfo() {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        const sessionUser = {
            fullName: '<?php echo addslashes($_SESSION['full_name'] ?? ($_SESSION['first_name'] . " " . $_SESSION['last_name'] ?? "Inspection Officer")) ?>',
            role: '<?php echo addslashes($_SESSION['role'] ?? "Field Agent") ?>',
            username: '<?php echo addslashes($_SESSION['username'] ?? "officer") ?>'
        };

        const name = user.full_name || sessionUser.fullName;
        const role = user.role || sessionUser.role;
        const avatarUrl = user.profile_photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=3B82F6&color=fff`;

        // Update Sidebar Elements
        const sideName = document.getElementById('sideUserName');
        const sideRole = document.getElementById('sideUserRole');
        const sideAvatar = document.getElementById('sideUserAvatar');

        if (sideName) sideName.textContent = name;
        if (sideRole) sideRole.textContent = role;
        if (sideAvatar) sideAvatar.src = avatarUrl;

        // Update Global Header Elements
        const topName = document.getElementById('userName');
        const topRole = document.getElementById('userRole');
        const topAvatar = document.getElementById('userAvatar');
        const welcomeName = document.getElementById('welcomeName');

        if (topName) topName.textContent = name;
        if (topRole) topRole.textContent = role;
        if (topAvatar) topAvatar.src = avatarUrl;
        if (welcomeName) welcomeName.textContent = (user.first_name || sessionUser.fullName.split(' ')[0]).toUpperCase();
    }

    // Initialize on Load
    window.addEventListener('DOMContentLoaded', updateSharedUserInfo);
    
    // Global Event Listener for Dropdowns
    window.addEventListener('click', function(e) {
        const userMenu = document.getElementById('userMenu');
        if (userMenu && !e.target.closest('.relative')) {
            userMenu.classList.add('hidden');
        }
    });

    // Custom Scrollbar Support
    const style = document.createElement('style');
    style.innerHTML = `
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.2); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.4); }
    `;
    document.head.appendChild(style);
</script>
