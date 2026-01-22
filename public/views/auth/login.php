<?php
// Session handled by index.php
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../../config/database.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Get database connection
        $db = Database::getConnection();
        
        // Find user by email or username
        $stmt = $db->prepare("
            SELECT u.*, r.role_name, r.permissions 
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.email = ? OR u.username = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['logged_in'] = true;
            
            // Redirect to dashboard
            header('Location: /dashboard');
            exit;
        } else {
            $error = 'Invalid authentication credentials provided.';
        }
    } catch (PDOException $e) {
        $error = 'Internal registry connection protocol failed.';
        error_log('Login error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutional Gateway - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-800 bg-slate-50; }
            .form-input { @apply w-full px-4 py-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all shadow-sm; }
            .form-label { @apply block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1 italic; }
            .mono { font-family: 'JetBrains Mono', monospace; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Institutional Pattern Overlay -->
    <div class="fixed inset-0 bg-[url('https://www.transparenttextures.com/patterns/clean-paper.png')] opacity-40 pointer-events-none"></div>
    <div class="fixed top-0 left-0 w-full h-2 bg-blue-700"></div>

    <div class="max-w-md w-full relative z-10 animate-in fade-in slide-in-from-bottom-8 duration-700">
        
        <!-- Header Branding -->
        <div class="text-center mb-10">
            <div class="mx-auto w-20 h-20 bg-white border-4 border-blue-700 rounded-full flex items-center justify-center mb-6 shadow-xl ring-8 ring-blue-50">
                <i class="fas fa-shield-halved text-blue-700 text-3xl"></i>
            </div>
            <h1 class="text-xl font-black text-slate-900 tracking-tighter uppercase italic italic">Registry Gateway</h1>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1 ml-0.5">Health & Safety Institutional Access</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-[2rem] shadow-2xl shadow-blue-900/5 border border-slate-100 p-10 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-5">
                <i class="fas fa-gavel text-6xl"></i>
            </div>

            <?php if (isset($error)): ?>
            <div class="mb-8 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center gap-4 animate-pulse">
                <i class="fas fa-exclamation-triangle text-rose-500"></i>
                <p class="text-[10px] font-black text-rose-800 uppercase tracking-widest"><?php echo htmlspecialchars($error) ?></p>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <!-- Username -->
                <div class="space-y-2">
                    <label for="username" class="form-label">System Descriptor (CLI/Web)</label>
                    <div class="relative group">
                        <i class="fas fa-user-shield absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-blue-700 transition-colors text-xs"></i>
                        <input 
                            id="username" 
                            name="username" 
                            type="text" 
                            required 
                            autocomplete="username"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? '') ?>"
                            class="w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all shadow-inner placeholder:text-slate-300"
                            placeholder="OPERATOR_ID / EMAIL"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-2">
                    <label for="password" class="form-label">Integrity Protocol Key</label>
                    <div class="relative group">
                        <i class="fas fa-key absolute left-4 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-blue-700 transition-colors text-xs"></i>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            autocomplete="current-password"
                            class="w-full pl-11 pr-12 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all shadow-inner placeholder:text-slate-300"
                            placeholder="ENCRYPTED_PHRASE"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-600 transition-colors"
                        >
                            <i id="toggleIcon" class="fas fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Authentication Trigger -->
                <div class="pt-2">
                    <button 
                        type="submit" 
                        class="w-full h-14 bg-blue-700 hover:bg-blue-800 text-white font-black text-xs uppercase tracking-[0.2em] rounded-2xl flex items-center justify-center gap-3 transition-all shadow-xl shadow-blue-900/10 group active:scale-[0.98]"
                    >
                        <i class="fas fa-unlock group-hover:scale-125 transition-transform duration-300"></i>
                        <span>Initialize Protocol</span>
                    </button>
                </div>
            </form>

            <div class="mt-10 pt-8 border-t border-slate-50 flex flex-col items-center space-y-4">
                <a href="/register" class="text-[10px] font-black text-blue-700 hover:text-blue-800 uppercase tracking-widest flex items-center gap-2 group transition-all">
                    <span>Account Request Pipeline</span>
                    <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
                <a href="/forgot-password" class="text-[10px] font-black text-slate-400 hover:text-slate-600 uppercase tracking-widest">
                    Credentials Misplacement?
                </a>
            </div>
        </div>

        <!-- System Footer -->
        <div class="mt-10 text-center space-y-2 pb-10">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic">&copy; 2025 Local Government Unit  Compliance Registry</p>
            <div class="flex items-center justify-center gap-4">
                <div class="h-px w-8 bg-slate-200"></div>
                <p class="mono text-[8px] text-slate-300 uppercase tracking-widest">v1.2.0-STABLE</p>
                <div class="h-px w-8 bg-slate-200"></div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        
        // Focus state on load
        window.onload = () => document.getElementById('username').focus();
    </script>
</body>
</html>
