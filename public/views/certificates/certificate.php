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
    <title>Certificate - <?= htmlspecialchars($cert['certificate_number']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
        }
        
        @page {
            size: A4;
            margin: 0;
        }
        
        body {
            background: #e9ecef;
            font-family: 'Times New Roman', Times, serif;
        }
        
        .certificate-container {
            width: 21cm;
            min-height: 29.7cm;
            background: white;
            margin: 2rem auto;
            padding: 0;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .certificate-border {
            border: 15px solid #1e40af;
            border-image: linear-gradient(135deg, #1e40af, #7c3aed, #1e40af) 1;
            padding: 3rem;
            min-height: 29.7cm;
            position: relative;
        }
        
        .certificate-inner-border {
            border: 2px solid #d97706;
            padding: 2rem;
            min-height: calc(29.7cm - 8rem);
        }
        
        .seal {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 5px solid #d97706;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin: 0 auto;
            font-weight: bold;
            color: #78350f;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 3px double #1e40af;
            padding-bottom: 1rem;
        }
        
        .header h1 {
            font-size: 2rem;
            color: #1e40af;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 1.5rem;
            color: #d97706;
            margin: 0.5rem 0;
            font-weight: bold;
        }
        
        .certificate-title {
            text-align: center;
            font-size: 2.5rem;
            color: #1e40af;
            font-weight: bold;
            margin: 2rem 0;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        
        .certificate-body {
            text-align: center;
            font-size: 1.1rem;
            line-height: 2;
            margin: 2rem 0;
        }
        
        .establishment-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1e40af;
            text-decoration: underline;
            margin: 1rem 0;
        }
        
        .certificate-details {
            margin: 2rem 0;
            padding: 1rem;
            background: #f3f4f6;
            border-radius: 8px;
        }
        
        .signature-section {
            margin-top: 4rem;
            display: flex;
            justify-content: space-around;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-top: 2px solid #000;
            margin-top: 3rem;
            padding-top: 0.5rem;
            font-weight: bold;
        }
        
        .qr-code-corner {
            position: absolute;
            bottom: 2rem;
            right: 2rem;
            text-align: center;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 5rem;
            color: rgba(0,0,0,0.05);
            font-weight: bold;
            pointer-events: none;
            z-index: 0;
        }
    </style>
    <?php if ($isPrint): ?>
    <script>
        window.onload = function() { window.print(); };
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="no-print text-center mt-3">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Certificate
        </button>
        <a href="/views/certificates/view.php?id=<?= $cert['certificate_id'] ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="certificate-container">
        <div class="certificate-border">
            <div class="certificate-inner-border">
                <!-- Watermark -->
                <div class="watermark">OFFICIAL</div>
                
                <!-- Header -->
                <div class="header">
                    <div class="seal">
                        <div>LGU</div>
                        <div style="font-size: 0.7rem;">SEAL</div>
                    </div>
                    <h1>Republic of the Philippines</h1>
                    <h2>Local Government Unit</h2>
                    <p style="margin: 0; color: #6b7280;">Health & Sanitation Office</p>
                </div>
                
                <!-- Certificate Title -->
                <div class="certificate-title">
                    CERTIFICATE OF COMPLIANCE
                </div>
                
                <!-- Certificate Body -->
                <div class="certificate-body" style="position: relative; z-index: 1;">
                    <p>This is to certify that</p>
                    
                    <div class="establishment-name">
                        <?= htmlspecialchars($cert['establishment_name']) ?>
                    </div>
                    
                    <p>
                        a <strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['establishment_type']))) ?></strong>
                        located at
                    </p>
                    
                    <p style="font-weight: bold;">
                        <?= htmlspecialchars($cert['address_street']) ?>,
                        <?= htmlspecialchars($cert['address_barangay']) ?>,
                        <?= htmlspecialchars($cert['address_city']) ?>
                    </p>
                    
                    <p>
                        has been inspected and found to be in compliance with the applicable
                        health, safety, and sanitation standards and regulations.
                    </p>
                    
                    <!-- Certificate Details -->
                    <div class="certificate-details text-start">
                        <div class="row">
                            <div class="col-6">
                                <p><strong>Certificate Number:</strong><br><?= htmlspecialchars($cert['certificate_number']) ?></p>
                                <p><strong>Certificate Type:</strong><br><?= htmlspecialchars(ucwords(str_replace('_', ' ', $cert['certificate_type']))) ?></p>
                                <p><strong>Owner/Operator:</strong><br><?= htmlspecialchars($cert['owner_name']) ?></p>
                            </div>
                            <div class="col-6">
                                <p><strong>Date Issued:</strong><br><?= date('F d, Y', strtotime($cert['issue_date'])) ?></p>
                                <p><strong>Valid Until:</strong><br><?= date('F d, Y', strtotime($cert['expiry_date'])) ?></p>
                                <?php if ($cert['inspection_reference']): ?>
                                <p><strong>Inspection Reference:</strong><br><?= htmlspecialchars($cert['inspection_reference']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($cert['remarks']): ?>
                        <p class="mt-2"><strong>Remarks:</strong><br><?= htmlspecialchars($cert['remarks']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <p style="margin-top: 2rem; font-size: 0.95rem; color: #6b7280;">
                        This certificate is valid for the period specified above and must be
                        prominently displayed at the establishment. Regular inspections will be
                        conducted to ensure continued compliance.
                    </p>
                </div>
                
                <!-- Signatures -->
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line">
                            <?= htmlspecialchars($cert['issued_by_name']) ?>
                        </div>
                        <p style="margin: 0; font-size: 0.9rem;">Health Inspector</p>
                        <p style="margin: 0; font-size: 0.85rem; color: #6b7280;">
                            <?= date('F d, Y', strtotime($cert['issue_date'])) ?>
                        </p>
                    </div>
                    
                    <div class="signature-box">
                        <div class="signature-line">
                            Municipal Health Officer
                        </div>
                        <p style="margin: 0; font-size: 0.9rem;">Approving Authority</p>
                        <p style="margin: 0; font-size: 0.85rem; color: #6b7280;">
                            <?= date('F d, Y', strtotime($cert['issue_date'])) ?>
                        </p>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="qr-code-corner">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode('https://lgu.gov.ph/verify?cert=' . $cert['certificate_number']) ?>" 
                         alt="QR Code">
                    <p style="font-size: 0.7rem; margin: 0.25rem 0 0 0;">Scan to verify</p>
                </div>
                
                <!-- Footer -->
                <div style="text-align: center; margin-top: 3rem; font-size: 0.8rem; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                    <p style="margin: 0;">This is an official document issued by the Local Government Unit</p>
                    <p style="margin: 0;">Any alteration or unauthorized use of this certificate is punishable by law</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
