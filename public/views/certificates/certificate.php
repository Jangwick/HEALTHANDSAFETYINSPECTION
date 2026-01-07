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
    <script src="https://cdn.tailwindcss.com/@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <style>
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
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .mono { font-family: 'JetBrains Mono', monospace; }
        .serif { font-family: 'Playfair Display', serif; }
        
        .certificate-page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            margin: 2rem auto;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 15mm;
            box-sizing: border-box;
            background-image: 
                radial-gradient(circle at 50% 50%, rgba(203, 213, 225, 0.1) 0%, transparent 80%),
                url('https://www.transparenttextures.com/patterns/clean-paper.png');
        }

        .outer-border {
            border: 8px solid #1e293b;
            height: 100%;
            padding: 5mm;
            position: relative;
        }

        .inner-border {
            border: 2px solid #94a3b8;
            height: 100%;
            padding: 10mm;
            position: relative;
        }

        .corner-accent {
            position: absolute;
            width: 40px;
            height: 40px;
            border-color: #0f172a;
            border-style: solid;
        }
        .top-left { top: -4px; left: -4px; border-width: 4px 0 0 4px; }
        .top-right { top: -4px; right: -4px; border-width: 4px 4px 0 0; }
        .bottom-left { bottom: -4px; left: -4px; border-width: 0 0 4px 4px; }
        .bottom-right { bottom: -4px; right: -4px; border-width: 0 4px 4px 0; }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 150px;
            color: rgba(226, 232, 240, 0.4);
            font-weight: 800;
            pointer-events: none;
            z-index: 0;
            white-space: nowrap;
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
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold shadow-lg transition-all flex items-center gap-2">
            <i class="fas fa-print"></i> PRINT AUTHORIZATION
        </button>
        <a href="/certificates/view?id=<?= $cert['certificate_id'] ?>" class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-2 rounded-lg font-bold shadow-lg transition-all flex items-center gap-2">
            <i class="fas fa-arrow-left"></i> SYSTEM VIEW
        </a>
    </div>

    <div class="certificate-page">
        <div class="outer-border">
            <div class="inner-border">
                <div class="watermark uppercase mono">LGU AUTHENTIC</div>
                
                <!-- Corner Accents -->
                <div class="corner-accent top-left"></div>
                <div class="corner-accent top-right"></div>
                <div class="corner-accent bottom-left"></div>
                <div class="corner-accent bottom-right"></div>

                <!-- Header -->
                <div class="text-center mb-10 relative z-10">
                    <div class="flex items-center justify-center gap-6 mb-6">
                        <div class="w-24 h-24 border-4 border-slate-900 rounded-full flex items-center justify-center p-2">
                            <i class="fas fa-shield-halved text-5xl text-slate-900"></i>
                        </div>
                    </div>
                    <div class="uppercase tracking-[0.2em] font-bold text-slate-600 text-xs mb-1">Republic of the Philippines</div>
                    <div class="uppercase tracking-[0.3em] font-black text-slate-900 text-lg mb-1">LOCAL GOVERNMENT UNIT</div>
                    <div class="uppercase tracking-[0.1em] font-bold text-blue-700 text-sm">HEALTH & SANITATION OFFICE</div>
                    <div class="w-32 h-1 bg-slate-900 mx-auto mt-4 mb-2"></div>
                    <div class="w-16 h-0.5 bg-slate-900 mx-auto"></div>
                </div>

                <!-- Title Section -->
                <div class="text-center mb-12 relative z-10">
                    <h1 class="serif italic text-5xl text-slate-900 mb-2">Certificate of Compliance</h1>
                    <div class="mono text-[10px] text-slate-500 tracking-[0.5em] uppercase">STRICT PROTOCOL ADHERENCE GRANTED</div>
                </div>

                <!-- Main Content -->
                <div class="text-center px-8 mb-12 space-y-6 relative z-10">
                    <p class="text-slate-600 text-lg">This official document hereby authorizes and confirms that</p>
                    
                    <h2 class="text-4xl font-black text-slate-900 uppercase tracking-tight border-b-2 border-slate-200 inline-block pb-2 px-4"><?= htmlspecialchars($cert['establishment_name']) ?></h2>
                    
                    <p class="text-slate-600">
                        Categorized as a <span class="font-bold text-slate-900 italic"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['establishment_type']))) ?></span>
                        <br>Located at the premises of:
                    </p>
                    
                    <div class="bg-slate-50 border border-slate-200 py-3 px-6 rounded-lg inline-block">
                        <span class="font-bold text-slate-900 italic text-lg"><?= htmlspecialchars($cert['address_street']) ?>, <?= htmlspecialchars($cert['address_barangay']) ?>, <?= htmlspecialchars($cert['address_city']) ?></span>
                    </div>

                    <p class="text-slate-700 leading-relaxed max-w-2xl mx-auto">
                        Has successfully passed full security and sanitation inspection protocols conducted on 
                        <span class="font-bold"><?= date('F d, Y', strtotime($cert['issue_date'])) ?></span>. 
                        This establishment is verified to be in full compliance with the Health & Safety 
                        Standard Operating Procedures (SOP) under existing municipal ordinances.
                    </p>
                </div>

                <!-- Metadata Grid -->
                <div class="grid grid-cols-2 gap-8 mb-16 px-10 relative z-10">
                    <div class="space-y-4 border-l-2 border-slate-200 pl-6">
                        <div>
                            <div class="mono text-[10px] text-slate-500 uppercase">Authorization Number</div>
                            <div class="font-bold text-slate-900"><?= htmlspecialchars($cert['certificate_number']) ?></div>
                        </div>
                        <div>
                            <div class="mono text-[10px] text-slate-500 uppercase">Owner / Authorized Representative</div>
                            <div class="font-bold text-slate-900"><?= htmlspecialchars($cert['owner_name']) ?></div>
                        </div>
                        <div>
                            <div class="mono text-[10px] text-slate-500 uppercase">Issuance Protocol</div>
                            <div class="font-bold text-slate-900"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['certificate_type']))) ?></div>
                        </div>
                    </div>
                    <div class="space-y-4 border-l-2 border-slate-200 pl-6">
                        <div>
                            <div class="mono text-[10px] text-slate-500 uppercase">Effective Date</div>
                            <div class="font-bold text-emerald-700 uppercase"><?= date('F d, Y', strtotime($cert['issue_date'])) ?></div>
                        </div>
                        <div>
                            <div class="mono text-[10px] text-slate-500 uppercase">Valid Until</div>
                            <div class="font-bold text-rose-700 uppercase"><?= date('F d, Y', strtotime($cert['expiry_date'])) ?></div>
                        </div>
                        <div>
                            <div class="mono text-[10px] text-slate-500 uppercase">Security Reference</div>
                            <div class="font-bold text-slate-900 mono text-sm"><?= htmlspecialchars($cert['inspection_reference'] ?: 'SYSTEM_GEN_000') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="grid grid-cols-2 gap-12 px-10 mb-12 relative z-10">
                    <div class="text-center">
                        <div class="mb-4 h-16 flex items-end justify-center">
                            <!-- Placeholder for e-signature -->
                            <div class="italic text-slate-400 opacity-30 mono text-xs uppercase leading-none">[ DIGITAL_STAMP_ID: <?= substr(md5($cert['issued_by_name']), 0, 8) ?> ]</div>
                        </div>
                        <div class="border-t border-slate-900 pt-2">
                            <div class="font-bold text-slate-900 uppercase"><?= htmlspecialchars($cert['issued_by_name']) ?></div>
                            <div class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Lead Safety Inspector</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="mb-4 h-16 flex items-end justify-center">
                            <div class="italic text-slate-400 opacity-30 mono text-xs uppercase leading-none">[ AUTH_SECURE_OVERRIDE ]</div>
                        </div>
                        <div class="border-t border-slate-900 pt-2">
                            <div class="font-bold text-slate-900 uppercase">Office of the Health Director</div>
                            <div class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">Approving Authority</div>
                        </div>
                    </div>
                </div>

                <!-- Footer / Security -->
                <div class="flex items-end justify-between border-t-2 border-slate-100 pt-8 mt-auto relative z-10">
                    <div class="flex-1">
                        <p class="text-[9px] text-slate-400 italic mb-2">NOTICE: This certificate must be permanently and prominently displayed at the primary entrance of the establishment. Unauthorized alteration, forgery, or misuse of this protocol authorization is strictly prohibited under Republic Act protocols and local ordinances.</p>
                        <div class="flex gap-4 mono text-[8px] text-slate-500 font-bold">
                            <span>TOKEN: <?= hash('crc32', $cert['certificate_number']) ?></span>
                            <span>NODE: <?= strtoupper(gethostname()) ?></span>
                            <span>GEN_DATE: <?= date('Y.m.d H:i') ?></span>
                        </div>
                    </div>
                    <div class="text-center pl-8">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode('https://lgu-safety.gov/verify?cert=' . $cert['certificate_number']) ?>" 
                             class="w-20 h-20 border border-slate-200 p-1 mb-1" alt="Verification QR">
                        <div class="mono text-[8px] font-bold text-slate-500 uppercase">Scan to Verify</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>

