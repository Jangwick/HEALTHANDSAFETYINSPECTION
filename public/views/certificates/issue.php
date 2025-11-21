<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $db = Database::getConnection();
    
    // Ensure session has first_name and last_name
    if (!isset($_SESSION['first_name']) || !isset($_SESSION['last_name'])) {
        $userStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($userData) {
            $_SESSION['first_name'] = $userData['first_name'];
            $_SESSION['last_name'] = $userData['last_name'];
        }
    }
    
    // Get completed inspections without certificates
    $inspections = $db->query("
        SELECT i.*, 
               e.name as establishment_name,
               e.type as establishment_type
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        LEFT JOIN certificates c ON i.inspection_id = c.inspection_id
        WHERE i.status = 'completed' 
        AND c.certificate_id IS NULL
        ORDER BY i.actual_end_datetime DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $inspectionId = (int)$_POST['inspection_id'];
        $certificateType = $_POST['certificate_type'];
        $validityMonths = (int)$_POST['validity_months'];
        $remarks = trim($_POST['remarks'] ?? '');
        
        // Get inspection details
        $stmt = $db->prepare("
            SELECT i.*, e.establishment_id 
            FROM inspections i
            JOIN establishments e ON i.establishment_id = e.establishment_id
            WHERE i.inspection_id = ?
        ");
        $stmt->execute([$inspectionId]);
        $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inspection) {
            // Generate certificate number
            $year = date('Y');
            $stmt = $db->query("SELECT COUNT(*) FROM certificates WHERE YEAR(issue_date) = $year");
            $count = $stmt->fetchColumn() + 1;
            $certificateNumber = 'CERT-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
            
            // Calculate dates
            $issueDate = date('Y-m-d');
            $expiryDate = date('Y-m-d', strtotime("+{$validityMonths} months"));
            
            // Insert certificate
            $stmt = $db->prepare("
                INSERT INTO certificates 
                (certificate_number, establishment_id, inspection_id, certificate_type, 
                 issue_date, expiry_date, status, issued_by, remarks, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, NOW())
            ");
            
            $stmt->execute([
                $certificateNumber,
                $inspection['establishment_id'],
                $inspectionId,
                $certificateType,
                $issueDate,
                $expiryDate,
                $_SESSION['user_id'],
                $remarks
            ]);
            
            $certificateId = $db->lastInsertId();
            
            // Update establishment compliance status
            $db->prepare("UPDATE establishments SET compliance_status = 'compliant' WHERE establishment_id = ?")->execute([$inspection['establishment_id']]);
            
            $_SESSION['success'] = 'Certificate issued successfully!';
            header('Location: /views/certificates/view.php?id=' . $certificateId);
            exit;
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while processing your request.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Certificate - Health & Safety Inspection System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-shield-check"></i> Health & Safety Inspection
            </a>
            <div class="ms-auto">
                <a href="/views/certificates/list.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Back to Certificates
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-award"></i> Issue New Certificate</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>

                        <?php if (empty($inspections)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No completed inspections available for certificate issuance.
                            <br>Please complete an inspection first.
                        </div>
                        <a href="/views/inspections/list.php" class="btn btn-primary">
                            <i class="bi bi-clipboard-check"></i> Go to Inspections
                        </a>
                        <?php else: ?>
                        <form method="POST">
                            <!-- Inspection Selection -->
                            <div class="mb-3">
                                <label class="form-label">Select Completed Inspection <span class="text-danger">*</span></label>
                                <select name="inspection_id" id="inspection_id" class="form-select" required onchange="updateInspectionInfo()">
                                    <option value="">Choose an inspection...</option>
                                    <?php foreach ($inspections as $insp): ?>
                                    <option value="<?= $insp['inspection_id'] ?>" 
                                            data-type="<?= $insp['inspection_type'] ?>"
                                            data-establishment="<?= htmlspecialchars($insp['establishment_name']) ?>"
                                            data-date="<?= date('M d, Y', strtotime($insp['actual_end_datetime'])) ?>"
                                            data-reference="<?= htmlspecialchars($insp['reference_number']) ?>">
                                        <?= htmlspecialchars($insp['reference_number']) ?> - 
                                        <?= htmlspecialchars($insp['establishment_name']) ?> 
                                        (<?= date('M d, Y', strtotime($insp['actual_end_datetime'])) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Inspection Info Display -->
                            <div id="inspection-info" class="alert alert-light d-none">
                                <h6>Inspection Details:</h6>
                                <p class="mb-1"><strong>Reference:</strong> <span id="info-reference"></span></p>
                                <p class="mb-1"><strong>Establishment:</strong> <span id="info-establishment"></span></p>
                                <p class="mb-1"><strong>Inspection Type:</strong> <span id="info-type"></span></p>
                                <p class="mb-0"><strong>Completed:</strong> <span id="info-date"></span></p>
                            </div>

                            <!-- Certificate Type -->
                            <div class="mb-3">
                                <label class="form-label">Certificate Type <span class="text-danger">*</span></label>
                                <select name="certificate_type" id="certificate_type" class="form-select" required>
                                    <option value="">Select certificate type...</option>
                                    <option value="food_safety">Food Safety Certificate</option>
                                    <option value="building_safety">Building Safety Certificate</option>
                                    <option value="fire_safety">Fire Safety Certificate</option>
                                    <option value="sanitation">Sanitation Certificate</option>
                                    <option value="occupational_health">Occupational Health Certificate</option>
                                    <option value="general_compliance">General Compliance Certificate</option>
                                </select>
                            </div>

                            <!-- Validity Period -->
                            <div class="mb-3">
                                <label class="form-label">Validity Period <span class="text-danger">*</span></label>
                                <select name="validity_months" class="form-select" required>
                                    <option value="6">6 Months</option>
                                    <option value="12" selected>1 Year (12 Months)</option>
                                    <option value="24">2 Years (24 Months)</option>
                                    <option value="36">3 Years (36 Months)</option>
                                </select>
                                <small class="form-text text-muted">The certificate will be valid from today's date</small>
                            </div>

                            <!-- Remarks -->
                            <div class="mb-3">
                                <label class="form-label">Remarks/Notes</label>
                                <textarea name="remarks" class="form-control" rows="3" placeholder="Enter any additional notes or conditions..."></textarea>
                            </div>

                            <!-- Preview Box -->
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle"></i> Certificate Preview</h6>
                                <p class="mb-1"><strong>Issue Date:</strong> <?= date('F d, Y') ?></p>
                                <p class="mb-1"><strong>Expiry Date:</strong> <span id="expiry-preview"><?= date('F d, Y', strtotime('+12 months')) ?></span></p>
                                <p class="mb-0"><strong>Issued By:</strong> <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></p>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-award"></i> Issue Certificate
                                </button>
                                <a href="/views/certificates/list.php" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateInspectionInfo() {
            const select = document.getElementById('inspection_id');
            const selectedOption = select.options[select.selectedIndex];
            const infoDiv = document.getElementById('inspection-info');
            
            if (selectedOption.value) {
                document.getElementById('info-reference').textContent = selectedOption.dataset.reference;
                document.getElementById('info-establishment').textContent = selectedOption.dataset.establishment;
                document.getElementById('info-type').textContent = selectedOption.dataset.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                document.getElementById('info-date').textContent = selectedOption.dataset.date;
                infoDiv.classList.remove('d-none');
                
                // Auto-select certificate type based on inspection type
                const inspectionType = selectedOption.dataset.type;
                const certTypeSelect = document.getElementById('certificate_type');
                if (inspectionType && certTypeSelect) {
                    certTypeSelect.value = inspectionType;
                }
            } else {
                infoDiv.classList.add('d-none');
            }
        }
        
        // Update expiry date preview when validity changes
        document.querySelector('[name="validity_months"]').addEventListener('change', function() {
            const months = parseInt(this.value);
            const expiryDate = new Date();
            expiryDate.setMonth(expiryDate.getMonth() + months);
            
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('expiry-preview').textContent = expiryDate.toLocaleDateString('en-US', options);
        });
    </script>
</body>
</html>
