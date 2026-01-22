<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Request - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-800 bg-slate-50; }
            .form-input { @apply w-full px-4 py-2.5 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 outline-none transition-all shadow-sm; }
            .form-label { @apply block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1 italic; }
            .mono { font-family: 'JetBrains Mono', monospace; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base min-h-screen py-12 px-6 relative flex flex-col items-center">
    
    <!-- Institutional Pattern Overlay -->
    <div class="fixed inset-0 bg-[url('https://www.transparenttextures.com/patterns/clean-paper.png')] opacity-40 pointer-events-none"></div>
    <div class="fixed top-0 left-0 w-full h-2 bg-blue-700"></div>

    <div class="max-w-2xl w-full relative z-10 animate-in fade-in slide-in-from-bottom-8 duration-700">
        
        <!-- Header Branding -->
        <div class="text-center mb-10">
            <div class="mx-auto w-16 h-16 bg-white border-4 border-blue-700 rounded-2xl flex items-center justify-center mb-4 shadow-xl -rotate-3 hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-id-card text-blue-700 text-2xl"></i>
            </div>
            <h1 class="text-lg font-black text-slate-900 tracking-tighter uppercase italic">Enrollment Pipeline</h1>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1 italic">Initialize Individual Registry Dossier</p>
        </div>

        <!-- Registration Card -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-blue-900/5 border border-slate-100 p-10 md:p-12 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-[0.03]">
                <i class="fas fa-users-cog text-[120px]"></i>
            </div>

            <form id="registerForm" class="space-y-10 relative z-10" onsubmit="handleRegister(event)">
                
                <!-- Account Type Selection Tier -->
                <div class="space-y-4">
                    <label class="form-label">Deployment Classification</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer group">
                            <input type="radio" name="role" value="establishment_owner" checked class="sr-only peer">
                            <div class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-5 group-hover:bg-white group-hover:border-blue-100 transition-all peer-checked:border-blue-700 peer-checked:bg-blue-50/30">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 peer-checked:text-blue-700 transition-colors shadow-sm">
                                        <i class="fas fa-landmark text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-black text-slate-800 uppercase italic">Entity Manager</p>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tight mt-0.5 italic">Proprietor / Representative</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer group">
                            <input type="radio" name="role" value="public" class="sr-only peer">
                            <div class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-5 group-hover:bg-white group-hover:border-blue-100 transition-all peer-checked:border-blue-700 peer-checked:bg-blue-50/30">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 peer-checked:text-blue-700 transition-colors shadow-sm">
                                        <i class="fas fa-globe-asia text-lg"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-black text-slate-800 uppercase italic">Public Observer</p>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tight mt-0.5 italic">General Registry Access</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Section: Identity Dossier -->
                <div class="space-y-6 pt-6 border-t border-slate-50">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="h-px flex-1 bg-slate-50"></div>
                        <h3 class="text-[10px] font-black text-slate-300 uppercase tracking-widest italic leading-none">Identity Dossier</h3>
                        <div class="h-px flex-1 bg-slate-50"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="first_name" class="form-label">Forename(s)</label>
                            <input id="first_name" name="first_name" type="text" required class="form-input italic" placeholder="e.g. JUAN">
                        </div>
                        <div class="space-y-2">
                            <label for="last_name" class="form-label">Surname</label>
                            <input id="last_name" name="last_name" type="text" required class="form-input italic" placeholder="e.g. DELA CRUZ">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="email" class="form-label">Registered Electronic Mail</label>
                            <input id="email" name="email" type="email" required class="form-input" placeholder="mail@institutional.gov">
                        </div>
                        <div class="space-y-2">
                            <label for="phone" class="form-label">Contact Link (Mobile)</label>
                            <input id="phone" name="phone" type="tel" class="form-input" placeholder="+63 000 000 0000">
                        </div>
                    </div>
                </div>

                <!-- Section: Secure Credentials -->
                <div class="space-y-6 pt-6 border-t border-slate-50">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="h-px flex-1 bg-slate-50"></div>
                        <h3 class="text-[10px] font-black text-slate-300 uppercase tracking-widest italic leading-none">Security Credentials</h3>
                        <div class="h-px flex-1 bg-slate-50"></div>
                    </div>

                    <div class="space-y-2">
                        <label for="username" class="form-label">Institutional Username</label>
                        <input id="username" name="username" type="text" required minlength="3" class="form-input mono" placeholder="operator_identity">
                        <p class="text-[8px] text-slate-400 font-bold uppercase tracking-widest italic">3-50 characters | Alpha-numeric protocol only</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="password" class="form-label">Integrity Key (Password)</label>
                            <input id="password" name="password" type="password" required minlength="8" class="form-input" placeholder="********">
                        </div>
                        <div class="space-y-2">
                            <label for="confirm_password" class="form-label">Confirm Integrity Key</label>
                            <input id="confirm_password" name="confirm_password" type="password" required class="form-input" placeholder="********">
                        </div>
                    </div>
                </div>

                <!-- Alert Protocol -->
                <div id="alertBox" class="hidden"></div>

                <!-- Actions -->
                <div class="pt-6">
                    <button type="submit" id="registerBtn" class="w-full h-14 bg-blue-700 hover:bg-blue-800 text-white font-black text-xs uppercase tracking-[0.2em] rounded-2xl flex items-center justify-center gap-4 transition-all shadow-xl shadow-blue-900/10 group active:scale-[0.98]">
                        <i class="fas fa-id-badge group-hover:scale-125 transition-transform"></i>
                        <span id="registerBtnText">Submit Enrollment Request</span>
                    </button>
                    
                    <div class="mt-8 text-center">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                            Already possess credentials? 
                            <a href="login.php" class="text-blue-700 hover:text-blue-800 underline decoration-blue-100 decoration-2 transition-all">Execute Login Protocol</a>
                        </p>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-12 text-center text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] pb-10 italic">
            &copy; 2025 Local Government Unit  Compliance Enforcement Division
        </div>
    </div>

    <script>
        async function handleRegister(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('registerBtn');
            const alertBox = document.getElementById('alertBox');
            
            // Password match check
            if (form.password.value !== form.confirm_password.value) {
                showAlert('Integrity Key mismatch detected.', 'rose');
                return;
            }

            btn.disabled = true;
            document.getElementById('registerBtnText').textContent = "Enrollment Processing...";

            try {
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                // Assuming an API endpoint exists, otherwise we'd need to write the PHP logic
                const response = await fetch('/api/v1/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert('Enrollment Initialized. Redirecting to Gateway...', 'emerald');
                    setTimeout(() => window.location.href = 'login.php', 2000);
                } else {
                    showAlert(result.message || 'Enrollment protocol failed.', 'rose');
                    btn.disabled = false;
                    document.getElementById('registerBtnText').textContent = "Submit Enrollment Request";
                }
            } catch (error) {
                showAlert('Network connectivity protocol failure.', 'rose');
                btn.disabled = false;
                document.getElementById('registerBtnText').textContent = "Submit Enrollment Request";
            }
        }

        function showAlert(msg, type) {
            const alertBox = document.getElementById('alertBox');
            alertBox.className = `p-4 rounded-xl border border-\${type}-100 bg-\${type}-50 flex items-center gap-4 animate-in slide-in-from-top-2 duration-300`;
            alertBox.innerHTML = `
                <i class="fas fa-info-circle text-\${type}-500"></i>
                <span class="text-[10px] font-black text-\${type}-800 uppercase tracking-widest">\${msg}</span>
            `;
            alertBox.classList.remove('hidden');
        }
    </script>
</body>
</html>
