<?php
// No session needed for certificate display
require_once __DIR__ . '/../../../config/database.php';

$certificateId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$download = isset($_GET['download']);

if (!$certificateId) {
    die('Invalid certificate ID');
}

try {
    $db = Database::getConnection();
    
    // Get certificate details
    $stmt = $db->prepare("
        SELECT c.*,
               e.name as establishment_name,
               e.type as establishment_type,
               e.address_street,
               e.address_barangay,
               e.address_city,
               e.owner_name,
               i.reference_number as inspection_reference,
               i.inspection_type,
               CONCAT(u.first_name, ' ', u.last_name) as issued_by_name,
               u.email as issued_by_email
        FROM certificates c
        LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
        LEFT JOIN inspections i ON c.inspection_id = i.inspection_id
        LEFT JOIN users u ON c.issued_by = u.user_id
        WHERE c.certificate_id = ?
    ");
    $stmt->execute([$certificateId]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cert) {
        die('Certificate not found');
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred");
}

$isPrint = isset($_GET['print']) || $download;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CERTIFICATE | <?= htmlspecialchars($cert['certificate_number']) ?></title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
        @layer base {
            @media print {
                .no-print { display: none !important; }
                body { background: white !important; margin: 0; padding: 0; }
                .certificate-page { margin: 0 !important; box-shadow: none !important; border: none !important; }
            }
            
            @page {
                size: A4;
                margin: 0;
            }
            
            body {
                @apply bg-slate-100 font-sans text-slate-900;
            }

            .mono { font-family: 'JetBrains Mono', monospace; }
            .serif { font-family: 'Playfair Display', serif; }
            
            .certificate-page {
                width: 210mm;
                min-height: 297mm;
                @apply bg-white relative mx-auto my-8 p-[15mm] box-border shadow-2xl;
                background-image: 
                    radial-gradient(circle at 50% 50%, rgba(203, 213, 225, 0.1) 0%, transparent 80%),
                    url('https://www.transparenttextures.com/patterns/clean-paper.png');
            }

            .outer-border {
                @apply border-8 border-blue-900 h-full p-[5mm] relative;
            }

            .inner-border {
                @apply border-2 border-blue-700/30 h-full p-[10mm] relative;
            }

            .corner-accent {
                @apply absolute w-10 h-10 border-blue-900 border-solid;
            }
            .top-left { @apply top-[-4px] left-[-4px] border-t-4 border-l-4; }
            .top-right { @apply top-[-4px] right-[-4px] border-t-4 border-r-4; }
            .bottom-left { @apply bottom-[-4px] left-[-4px] border-b-4 border-l-4; }
            .bottom-right { @apply bottom-[-4px] right-[-4px] border-b-4 border-r-4; }

            .watermark {
                @apply absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 -rotate-[30deg] text-[150px] font-black text-slate-100 pointer-events-none z-0 whitespace-nowrap uppercase mono;
            }
        }
    </style>
    <?php if ($isPrint): ?>
    <script>
        window.onload = function() { window.print(); };
    </script>
    <?php endif; ?>
</head>
<body class="p-4 md:p-8">

    <div class="no-print flex justify-center gap-4 mb-8">
        <button onclick="window.print()" class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-3 rounded-lg text-xs font-bold uppercase tracking-widest shadow-lg transition-all flex items-center gap-3">
            <i class="fas fa-print"></i> Official Print Protocol
        </button>
        <a href="/views/certificates/view.php?id=<?= $cert['certificate_id'] ?>" class="bg-slate-800 hover:bg-slate-900 text-white px-8 py-3 rounded-lg text-xs font-bold uppercase tracking-widest shadow-lg transition-all flex items-center gap-3">
            <i class="fas fa-arrow-left"></i> Return to Registry
        </a>
    </div>

    <div class="certificate-page">
        <div class="outer-border">
            <div class="inner-border">
                <div class="watermark">OFFICIAL REGISTRY</div>
                
                <!-- Corner Accents -->
                <div class="corner-accent top-left"></div>
                <div class="corner-accent top-right"></div>
                <div class="corner-accent bottom-left"></div>
                <div class="corner-accent bottom-right"></div>

                <!-- Header -->
                <div class="text-center mb-10 relative z-10">
                    <div class="flex items-center justify-center gap-6 mb-6">
                        <div class="w-24 h-24 border-4 border-blue-900 bg-white rounded-full flex items-center justify-center p-2 shadow-sm">
                            <i class="fas fa-shield-halved text-5xl text-blue-900"></i>
                        </div>
                    </div>
                    <div class="uppercase tracking-[0.2em] font-bold text-slate-500 text-xs mb-1">Republic of the Philippines</div>
                    <div class="uppercase tracking-[0.3em] font-black text-blue-900 text-xl mb-1">LOCAL GOVERNMENT UNIT</div>
                    <div class="uppercase tracking-[0.1em] font-bold text-blue-700 text-sm italic">Bureau of Health & Safety Compliance</div>
                    <div class="w-32 h-1 bg-blue-900 mx-auto mt-4 mb-2 shadow-sm"></div>
                    <div class="w-16 h-1 bg-blue-700 mx-auto opacity-50"></div>
                </div>

                <!-- Title Section -->
                <div class="text-center mb-10 relative z-10">
                    <h1 class="serif italic text-[54px] text-slate-900 mb-2 leading-none">Certificate of Compliance</h1>
                    <div class="mono text-[10px] text-blue-700 font-bold tracking-[0.5em] uppercase">OFFICIAL OPERATIONAL AUTHORIZATION</div>
                </div>

                <!-- Main Content -->
                <div class="text-center px-12 mb-10 space-y-6 relative z-10">
                    <p class="text-slate-500 text-sm font-bold uppercase tracking-widest italic">Protocol Verification Statement</p>
                    
                    <p class="text-slate-700 text-lg mx-auto max-w-2xl leading-relaxed">
                        This is to officially certify that the establishment known as:
                    </p>

                    <h2 class="text-[44px] font-black text-blue-900 uppercase tracking-tight border-b-4 border-blue-50 inline-block pb-3 px-8 leading-tight italic serif">
                        <?= htmlspecialchars($cert['establishment_name']) ?>
                    </h2>
                    
                    <p class="text-slate-600 font-medium">
                        Properly registered as a <span class="font-bold text-blue-800 italic uppercase underline decoration-blue-100 decoration-4"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['establishment_type']))) ?></span>
                        <br>Located and verified at the operational address:
                    </p>
                    
                    <div class="bg-blue-50/50 border border-blue-100 py-4 px-10 rounded-2xl inline-block shadow-sm">
                        <span class="font-black text-slate-800 italic text-xl uppercase tracking-tight"><?= htmlspecialchars($cert['address_street']) ?>, <?= htmlspecialchars($cert['address_barangay']) ?>, <?= htmlspecialchars($cert['address_city']) ?></span>
                    </div>

                    <p class="text-slate-700 leading-relaxed max-w-3xl mx-auto font-medium">
                        Has undergone rigorous inspection protocols and verified to be in full compliance with the 
                        <span class="font-black text-blue-800 uppercase italic">Institutional Safety Code</span>. 
                        This authorization confirms that all mandatory hygiene, safety, and operational standards have been 
                        met for the specified audit period.
                    </p>
                </div>

                <!-- Metadata Grid -->
                <div class="grid grid-cols-2 gap-12 mb-12 px-10 relative z-10">
                    <div class="space-y-5 border-l-4 border-blue-700 pl-8 bg-slate-50/50 py-4 pr-4 rounded-r-2xl">
                        <div>
                            <div class="mono text-[10px] text-blue-700 font-bold uppercase tracking-widest">Registry ID</div>
                            <div class="font-black text-slate-900 text-lg mono"><?= htmlspecialchars($cert['certificate_number']) ?></div>
                        </div>
                        <div>
                            <div class="mono text-[10px] text-slate-500 font-bold uppercase tracking-widest">Proprietor / Representative</div>
                            <div class="font-black text-slate-900 italic uppercase"><?= htmlspecialchars($cert['owner_name']) ?></div>
                        </div>
                        <div>
                            <div class="mono text-[10px] text-slate-500 font-bold uppercase tracking-widest">Protocol Reference</div>
                            <div class="font-black text-slate-900 mono text-sm"><?= htmlspecialchars($cert['inspection_reference'] ?: 'PROTO-ADMIN-001') ?></div>
                        </div>
                    </div>
                    <div class="space-y-5 border-l-4 border-blue-700 pl-8 bg-slate-50/50 py-4 pr-4 rounded-r-2xl">
                        <div>
                            <div class="mono text-[10px] text-blue-700 font-bold uppercase tracking-widest">Effective Segment</div>
                            <div class="font-black text-emerald-700 uppercase italic leading-none text-lg"><?= date('F d, Y', strtotime($cert['issue_date'])) ?></div>
                            <div class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1 italic">Activation Date</div>
                        </div>
                        <div>
                            <div class="mono text-[10px] text-rose-700 font-bold uppercase tracking-widest">De-Listing Sequence</div>
                            <div class="font-black text-rose-700 uppercase italic leading-none text-lg"><?= date('F d, Y', strtotime($cert['expiry_date'])) ?></div>
                            <div class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-1 italic">Expiry Date</div>
                        </div>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="grid grid-cols-2 gap-16 px-10 mb-10 relative z-10">
                    <div class="text-center">
                        <div class="mb-5 h-20 flex items-end justify-center">
                            <!-- Placeholder for e-signature -->
                            <div class="italic text-blue-700/20 font-black mono text-xs uppercase leading-none tracking-tighter">SIG_VALID_<?= substr(md5($cert['issued_by_name']), 0, 12) ?></div>
                        </div>
                        <div class="border-t-2 border-slate-900 pt-3">
                            <div class="font-black text-slate-900 uppercase italic tracking-tight"><?= htmlspecialchars($cert['issued_by_name']) ?></div>
                            <div class="text-[10px] text-blue-700 uppercase font-black tracking-widest italic mt-1 underline decoration-blue-100 decoration-2">Inspectorate General</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="mb-5 h-20 flex items-end justify-center">
                            <div class="w-20 h-20 border-2 border-blue-700/10 rounded-full flex items-center justify-center border-dashed">
                                <i class="fas fa-stamp text-blue-700/10 text-3xl"></i>
                            </div>
                        </div>
                        <div class="border-t-2 border-slate-900 pt-3">
                            <div class="font-black text-slate-900 uppercase italic tracking-tight">Office of Registry Control</div>
                            <div class="text-[10px] text-blue-700 uppercase font-black tracking-widest italic mt-1 underline decoration-blue-100 decoration-2">Seal of Authorization</div>
                        </div>
                    </div>
                </div>

                <!-- Footer / Security -->
                <div class="flex items-end justify-between border-t-4 border-slate-50 pt-10 mt-auto relative z-10">
                    <div class="flex-1 max-w-xl">
                        <div class="flex items-center space-x-3 mb-3">
                            <i class="fas fa-circle-exclamation text-blue-700 text-xs"></i>
                            <h4 class="text-[10px] font-black text-slate-900 uppercase tracking-widest italic">Institutional Notice</h4>
                        </div>
                        <p class="text-[10px] text-slate-500 font-medium leading-relaxed italic pr-8">This certificate serves as a primary legal instrument of compliance. It must be prominently rendered at the establishment entrypoint. Any unauthorized duplication or tampering with this digital registry entry is a violation of the Municipal Administrative Code Section 12-A.</p>
                        <div class="flex gap-6 mt-6 mono text-[8px] text-slate-400 font-bold uppercase tracking-tight">
                            <span class="bg-slate-50 px-2 py-0.5 rounded">Ledger Hash: <?= hash('crc32', $cert['certificate_number'] . 'SECRET') ?></span>
                            <span class="bg-slate-50 px-2 py-0.5 rounded">Node-X: <?= strtoupper(gethostname()) ?></span>
                            <span class="bg-slate-50 px-2 py-0.5 rounded">Commit: <?= date('Y.m.d H:i') ?></span>
                        </div>
                    </div>
                    <div class="text-center ml-8 bg-white p-3 border-2 border-blue-50 rounded-2xl shadow-sm">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode('AUTH_CERT_' . $cert['certificate_number'] . '_VERIFIED') ?>" 
                             class="w-[85px] h-[85px] mb-2 grayscale opacity-90" alt="Verification QR">
                        <div class="mono text-[8px] font-black text-blue-700 uppercase tracking-widest italic">Digital Audit Scan</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <div class="no-print mt-12 text-center text-slate-400 pb-12">
        <p class="text-[10px] font-bold uppercase tracking-[0.3em]">Institutional Printing Protocol Active</p>
    </div>

</body>
</html>
