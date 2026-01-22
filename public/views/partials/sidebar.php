<!-- Sidebar Navigation Partial -->
<aside class="w-72 bg-slate-900 text-white flex-shrink-0 hidden md:flex flex-col border-r border-white/5 shadow-2xl">
    <div class="p-8 border-b border-slate-800 flex items-center bg-slate-950/20">
        <div class="h-12 w-12 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
            <i class="fas fa-shield-alt text-white text-2xl"></i>
        </div>
        <div class="ml-4">
            <span class="block text-lg font-black tracking-tighter uppercase text-white leading-none">H&S INSPECTION</span>
            <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">LGU Cluster System</span>
        </div>
    </div>
    
    <nav class="flex-1 overflow-y-auto py-6">
        <div class="px-5 space-y-1.5">
            <a href="/dashboard" class="<?php echo $activePage == 'dashboard' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-home mr-3 text-xl w-8"></i>
                Dashboard
            </a>
            
            <?php if ($_SESSION['role'] !== 'establishment_owner'): ?>
            <a href="/inspections" class="<?php echo $activePage == 'inspections' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-clipboard-check mr-3 text-xl w-8"></i>
                Inspections
            </a>

            <a href="/scheduling" class="<?php echo $activePage == 'scheduling' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-calendar-alt mr-3 text-xl w-8"></i>
                Scheduling
            </a>
            <?php endif; ?>
            
            <a href="/establishments" class="<?php echo $activePage == 'establishments' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-building mr-3 text-xl w-8"></i>
                Establishments
            </a>
            
            <a href="/certificates" class="<?php echo $activePage == 'certificates' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-certificate mr-3 text-xl w-8"></i>
                Certificates
            </a>

            <?php if ($_SESSION['role'] !== 'establishment_owner'): ?>
            <a href="/violations" class="<?php echo $activePage == 'violations' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-exclamation-triangle mr-3 text-xl w-8"></i>
                Violations
            </a>

            <a href="/inspectors" class="<?php echo $activePage == 'inspectors' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-user-shield mr-3 text-xl w-8"></i>
                Inspectors
            </a>

            <a href="/integrations" class="<?php echo $activePage == 'integrations' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-project-diagram mr-3 text-xl w-8"></i>
                LGU Cluster Hub
            </a>

            <div class="pt-6 pb-2">
                <p class="px-5 text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Reports & Performance</p>
            </div>

            <a href="/analytics" class="<?php echo $activePage == 'statistics' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'text-slate-300 hover:bg-slate-800 hover:text-white'; ?> group flex items-center px-4 py-3.5 text-base font-bold rounded-xl transition-all">
                <i class="fas fa-chart-pie mr-3 text-xl w-8"></i>
                Compliance Analytics
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="p-6 border-t border-slate-800 bg-slate-950/20">
        <div class="flex items-center p-3 rounded-2xl bg-white/5 border border-white/5">
            <img id="sideUserAvatar" class="h-10 w-10 rounded-xl border border-white/10 shadow-inner" src="https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff" alt="">
            <div class="ml-3 overflow-hidden">
                <p id="sideUserName" class="text-xs font-black text-white truncate uppercase tracking-tight"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Inspector'); ?></p>
                <p id="sideUserRole" class="text-[9px] text-blue-400 font-bold uppercase truncate tracking-widest mt-0.5"><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?></p>
            </div>
        </div>
        <button onclick="handleLogout()" class="mt-4 w-full flex items-center justify-center px-4 py-3 text-[10px] font-black text-slate-400 hover:text-white border border-slate-700 rounded-xl transition-all hover:bg-rose-600 hover:border-rose-600 uppercase tracking-widest">
            <i class="fas fa-power-off mr-2"></i> Sign Out System
        </button>
    </div>
</aside>

<script>
    /**
     * Shared Sidebar & User Management Module
     */
    function toggleUserMenu() {
        const menu = document.getElementById('userMenu');
        if (menu) menu.classList.toggle('hidden');
    }

    async function handleLogout() {
        if (!confirm('Are you sure you want to sign out?')) return;
        
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

    // Load user data into sidebar and header
    function updateSharedUserInfo() {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        const sessionUser = {
            fullName: '<?php echo addslashes($_SESSION['full_name'] ?? ($_SESSION['first_name'] . " " . $_SESSION['last_name'] ?? "User")) ?>',
            role: '<?php echo addslashes($_SESSION['role'] ?? "User") ?>',
            username: '<?php echo addslashes($_SESSION['username'] ?? "user") ?>'
        };

        const name = user.full_name || sessionUser.fullName;
        const role = user.role || sessionUser.role;
        const avatarUrl = user.profile_photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=3B82F6&color=fff`;

        // Update Sidebar
        const sideName = document.getElementById('sideUserName');
        const sideRole = document.getElementById('sideUserRole');
        const sideAvatar = document.getElementById('sideUserAvatar');

        if (sideName) sideName.textContent = name;
        if (sideRole) sideRole.textContent = role;
        if (sideAvatar) sideAvatar.src = avatarUrl;

        // Update Top Header (if exists)
        const topName = document.getElementById('userName');
        const topRole = document.getElementById('userRole');
        const topAvatar = document.getElementById('userAvatar');
        const welcomeName = document.getElementById('welcomeName');

        if (topName) topName.textContent = name;
        if (topRole) topRole.textContent = role;
        if (topAvatar) topAvatar.src = avatarUrl;
        if (welcomeName) welcomeName.textContent = user.first_name || sessionUser.fullName.split(' ')[0];
    }

    // Initialize
    window.addEventListener('DOMContentLoaded', updateSharedUserInfo);
    
    // Close dropdowns on click outside
    window.addEventListener('click', function(e) {
        const userMenu = document.getElementById('userMenu');
        if (userMenu && !e.target.closest('.relative')) {
            userMenu.classList.add('hidden');
        }
    });
</script>
