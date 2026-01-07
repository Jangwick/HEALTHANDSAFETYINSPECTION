<?php
// No session needed for public verification
require_once __DIR__ . '/../../../config/database.php';

$certificateNumber = isset($_GET['cert']) ? trim($_GET['cert']) : '';
$certificate = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $certificateNumber) {
    if (!$certificateNumber) {
        $certificateNumber = isset($_POST['certificate_number']) ? trim($_POST['certificate_number']) : '';
    }
    
    if ($certificateNumber) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT c.*,
                       e.name as establishment_name,
                       e.type as establishment_type,
                       e.address_street,
                       e.address_barangay,
                       e.address_city,
                       e.owner_name,
                       CONCAT(u.first_name, ' ', u.last_name) as issued_by_name
                FROM certificates c
                LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
                LEFT JOIN users u ON c.issued_by = u.user_id
                WHERE c.certificate_number = ?
            ");
            $stmt->execute([$certificateNumber]);
            $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$certificate) {
                $error = 'Certificate not found. Please check the certificate number.';
            }
            
        } catch (PDOException $e) 
            error_log("Database error: " . $e->getMessage());
            $error = 'An error occurred while verifying the certificate.';
        }
    } else {
        $error = 'Please enter a certificate number.';
    }
}

// Determine certificate status
$status = 'unknown';
$statusClass = 'secondary';
$statusMessage = '';

if ($certificate) {
    $now = new DateTime();
    $expiryDate = new DateTime($certificate['expiry_date']);
    $issueDate = new DateTime($certificate['issue_date']);
    
    if ($certificate['status'] === 'revoked') {
        $status = 'revoked';
        $statusClass = 'danger';
        $statusMessage = 'This certificate has been REVOKED and is no longer valid.';
    } elseif ($certificate['status'] === 'suspended') {
        $status = 'suspended';
        $statusClass = 'warning';
        $statusMessage = 'This certificate is currently SUSPENDED.';
    } elseif ($expiryDate < $now) {
        $status = 'expired';
        $statusClass = 'danger';
        $statusMessage = 'This certificate has EXPIRED.';
    } elseif ($certificate['status'] === 'active') {
        $daysRemaining = $now->diff($expiryDate)->days;
        if ($daysRemaining <= 30) {
            $status = 'expiring';
            $statusClass = 'warning';
            $statusMessage = "This certificate is VALID but will expire in {$daysRemaining} days.";
        } else {
            $status = 'valid';
            $statusClass = 'success';
            $statusMessage = 'This certificate is VALID and in good standing.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate - Health & Safety Inspection System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .verify-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .verify-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 2rem;
        }
        
        .verify-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .verify-header h1 {
            color: #1e40af;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .search-box {
            background: #f3f4f6;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .status-badge {
            font-size: 1.5rem;
            padding: 1rem 2rem;
            border-radius: 10px;
            display: inline-block;
            font-weight: bold;
            margin: 1rem 0;
        }
        
        .certificate-info {
            background: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 5px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: #6b7280;
        }
        
        .info-value {
            text-align: right;
            color: #1f2937;
        }
        
        .seal-badge {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 5px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
        }
        
        .seal-valid {
            border-color: #10b981;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .seal-invalid {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .seal-warning {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <h1><i class="bi bi-shield-check"></i> Certificate Verification</h1>
                <p class="text-muted mb-0">Verify the authenticity and validity of health & safety certificates</p>
            </div>
            
            <!-- Search Form -->
            <div class="search-box">
                <form method="POST" action="">
                    <div class="input-group input-group-lg">
                        <input type="text" 
                               class="form-control" 
                               name="certificate_number" 
                               placeholder="Enter Certificate Number (e.g., CERT-2025-00001)" 
                               value="<?= htmlspecialchars($certificateNumber) ?>"
                               required>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i> Verify
                        </button>
                    </div>
                    <div class="form-text mt-2">
                        <i class="bi bi-info-circle"></i> Enter the certificate number or scan the QR code on the certificate
                    </div>
                </form>
            </div>
            
            <?php if ($error): ?>
            <!-- Error Message -->
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <?php if ($certificate): ?>
            <!-- Verification Result -->
            <div class="text-center">
                <?php if ($status === 'valid' || $status === 'expiring'): ?>
                <div class="seal-badge seal-valid">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <?php elseif ($status === 'revoked' || $status === 'expired'): ?>
                <div class="seal-badge seal-invalid">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <?php else: ?>
                <div class="seal-badge seal-warning">
                    <i class="bi bi-exclamation-circle-fill"></i>
                </div>
                <?php endif; ?>
                
                <span class="status-badge bg-<?= $statusClass ?> text-white">
                    <?= strtoupper($status) ?>
                </span>
                
                <p class="lead mt-3">
                    <?= htmlspecialchars($statusMessage) ?>
                </p>
            </div>
            
            <!-- Certificate Details -->
            <div class="certificate-info mt-4">
                <h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> Certificate Information</h5>
                
                <div class="info-row">
                    <span class="info-label">Certificate Number:</span>
                    <span class="info-value"><strong><?= htmlspecialchars($certificate['certificate_number']) ?></strong></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Certificate Type:</span>
                    <span class="info-value"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $certificate['certificate_type']))) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Date Issued:</span>
                    <span class="info-value"><?= date('F d, Y', strtotime($certificate['issue_date'])) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Expiry Date:</span>
                    <span class="info-value">
                        <?= date('F d, Y', strtotime($certificate['expiry_date'])) ?>
                        <?php 
                        $now = new DateTime();
                        $expiry = new DateTime($certificate['expiry_date']);
                        if ($certificate['status'] === 'active' && $expiry > $now) {
                            $daysRemaining = $now->diff($expiry)->days;
                            echo " <span class='badge bg-info'>($daysRemaining days remaining)</span>";
                        }
                        ?>
                    </span>
                </div>
                
                <?php if ($certificate['status'] === 'revoked'): ?>
                <div class="info-row">
                    <span class="info-label">Revoked On:</span>
                    <span class="info-value text-danger"><?= date('F d, Y', strtotime($certificate['revoked_at'])) ?></span>
                </div>
                
                <?php if ($certificate['revocation_reason']): ?>
                <div class="info-row">
                    <span class="info-label">Revocation Reason:</span>
                    <span class="info-value text-danger"><?= htmlspecialchars($certificate['revocation_reason']) ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">Issued By:</span>
                    <span class="info-value"><?= htmlspecialchars($certificate['issued_by_name']) ?></span>
                </div>
            </div>
            
            <!-- Establishment Information -->
            <div class="certificate-info mt-3">
                <h5 class="mb-3"><i class="bi bi-building"></i> Establishment Information</h5>
                
                <div class="info-row">
                    <span class="info-label">Establishment Name:</span>
                    <span class="info-value"><strong><?= htmlspecialchars($certificate['establishment_name']) ?></strong></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Type:</span>
                    <span class="info-value"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $certificate['establishment_type']))) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Owner:</span>
                    <span class="info-value"><?= htmlspecialchars($certificate['owner_name']) ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value">
                        <?= htmlspecialchars($certificate['address_street']) ?>,
                        <?= htmlspecialchars($certificate['address_barangay']) ?>,
                        <?= htmlspecialchars($certificate['address_city']) ?>
                    </span>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="verify.php" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Verify Another Certificate
                </a>
                
                <?php if ($status === 'valid' || $status === 'expiring'): ?>
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="bi bi-printer"></i> Print Verification
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Disclaimer -->
            <div class="alert alert-light mt-4 mb-0">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> 
                    This verification is provided for informational purposes only. 
                    For official matters, please contact the Local Government Unit Health & Sanitation Office.
                    Last verified: <?= date('F d, Y g:i A') ?>
                </small>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Help Section -->
        <div class="text-center mt-4">
            <p class="text-white">
                <i class="bi bi-question-circle"></i> 
                Need help? Contact the LGU Health Office or visit 
                <a href="/" class="text-white"><u>our website</u></a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
