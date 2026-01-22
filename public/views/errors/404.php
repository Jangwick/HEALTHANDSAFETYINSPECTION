<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Resource Not Found</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-800 bg-slate-50; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base min-h-screen py-12 px-6 relative flex flex-col items-center justify-center">
    
    <!-- Institutional Pattern Overlay -->
    <div class="fixed inset-0 bg-[url('https://www.transparenttextures.com/patterns/clean-paper.png')] opacity-40 pointer-events-none"></div>
    <div class="fixed top-0 left-0 w-full h-2 bg-blue-700"></div>

    <div class="max-w-md w-full relative z-10 text-center animate-in fade-in slide-in-from-bottom-8 duration-700">
        
        <div class="mx-auto w-24 h-24 bg-white border-4 border-slate-200 rounded-[2rem] flex items-center justify-center mb-8 shadow-xl -rotate-6">
            <i class="fas fa-search-location text-slate-300 text-4xl"></i>
        </div>

        <h1 class="text-6xl font-black text-slate-900 tracking-tighter uppercase italic leading-none mb-4">404</h1>
        <h2 class="text-sm font-black text-blue-700 uppercase tracking-widest italic mb-8">Resource Location Protocol Failed</h2>
        
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] leading-relaxed italic mb-12">
            The requested registry dossier or system endpoint cannot be located within the current institutional directory. 
            Verification of the URL address protocol is recommended.
        </p>

        <div class="flex flex-col items-center gap-4">
            <a href="/public/dashboard.php" class="inline-flex items-center gap-3 px-8 py-4 bg-blue-700 hover:bg-blue-800 text-white font-black text-[10px] uppercase tracking-[0.2em] rounded-2xl transition-all shadow-xl shadow-blue-900/10 active:scale-95">
                <i class="fas fa-home"></i>
                Return to Command Dashboard
            </a>
            
            <p class="text-[9px] font-black text-slate-300 uppercase tracking-widest italic pt-8">
                Error Code: REGISTRY_ENTRY_NULL
            </p>
        </div>

        <!-- Footer -->
        <div class="mt-20 text-[9px] font-black text-slate-300 uppercase tracking-[0.3em] italic">
            &copy; 2025 Local Government Unit  Compliance Enforcement Division
        </div>
    </div>
</body>
</html>
