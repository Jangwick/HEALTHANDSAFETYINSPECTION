<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credential Recovery - Health & Safety Insight</title>
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
<body class="font-sans antialiased text-base min-h-screen py-12 px-6 relative flex flex-col items-center justify-center">
    
    <!-- Institutional Pattern Overlay -->
    <div class="fixed inset-0 bg-[url('https://www.transparenttextures.com/patterns/clean-paper.png')] opacity-40 pointer-events-none"></div>
    <div class="fixed top-0 left-0 w-full h-2 bg-blue-700"></div>

    <div class="max-w-md w-full relative z-10 animate-in fade-in slide-in-from-bottom-8 duration-700">
        
        <!-- Header Branding -->
        <div class="text-center mb-10">
            <div class="mx-auto w-16 h-16 bg-white border-4 border-blue-700 rounded-2xl flex items-center justify-center mb-4 shadow-xl -rotate-3 hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-key text-blue-700 text-2xl"></i>
            </div>
            <h1 class="text-lg font-black text-slate-900 tracking-tighter uppercase italic">Credential Recovery</h1>
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1 italic">Initiate Identity Restoration Protocol</p>
        </div>

        <!-- recovery Card -->
        <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-blue-900/5 border border-slate-100 p-10 md:p-12 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-8 opacity-[0.03]">
                <i class="fas fa-shield-alt text-[120px]"></i>
            </div>

            <form id="forgotPasswordForm" class="space-y-8 relative z-10" onsubmit="handleForgotPassword(event)">
                
                <div class="space-y-2">
                    <label for="email" class="form-label font-black italic">Institutional Email Address</label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-slate-300 group-focus-within:text-blue-700 transition-colors"></i>
                        </div>
                        <input id="email" name="email" type="email" required class="form-input pl-10" placeholder="mail@institutional.gov">
                    </div>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tight italic mt-2">Recovery link will be transmitted to this registered dossier email.</p>
                </div>

                <!-- Alert Protocol -->
                <div id="alertBox" class="hidden"></div>

                <!-- Actions -->
                <div class="pt-2">
                    <button type="submit" id="submitBtn" class="w-full h-14 bg-blue-700 hover:bg-blue-800 text-white font-black text-xs uppercase tracking-[0.2em] rounded-2xl flex items-center justify-center gap-4 transition-all shadow-xl shadow-blue-900/10 group active:scale-[0.98]">
                        <i class="fas fa-paper-plane group-hover:-translate-y-1 group-hover:translate-x-1 transition-transform"></i>
                        <span id="submitBtnText">Transmit Reset Link</span>
                    </button>
                    
                    <div class="mt-8 text-center">
                        <a href="login.php" class="text-[10px] font-black text-blue-700 uppercase tracking-widest italic hover:text-blue-800 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Return to Gateway
                        </a>
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
        async function handleForgotPassword(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('submitBtnText');
            
            btn.disabled = true;
            btnText.textContent = "Transmitting Protocol...";

            try {
                // Mock implementation for UI demonstration
                // In production, this would call /api/v1/auth/forgot-password
                await new Promise(resolve => setTimeout(resolve, 1500));
                
                showAlert('Recovery Protocol Initialized. Please verify your inbox.', 'emerald');
                btnText.textContent = "Link Transmitted";
                
            } catch (error) {
                showAlert('Transmission protocol failure.', 'rose');
                btn.disabled = false;
                btnText.textContent = "Transmit Reset Link";
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
