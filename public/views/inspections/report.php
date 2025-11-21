<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$inspectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$download = isset($_GET['download']) ? true : false;

if (!$inspectionId) {
    die('Invalid inspection ID');
}

try {
    $db = Database::getConnection();
    
    // Get inspection details with all related data
    $stmt = $db->prepare("
        SELECT 
            i.*,
            e.name AS establishment_name,
            e.type AS establishment_type,
            e.address_street,
            e.address_barangay,
            e.address_city,
            e.owner_name,
            e.owner_contact,
            u.first_name AS inspector_first_name,
            u.last_name AS inspector_last_name,
            u.email AS inspector_email
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        LEFT JOIN users u ON i.inspector_id = u.user_id
        WHERE i.inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        die('Inspection not found');
    }
    
    // Get checklist responses
    $stmt = $db->prepare("
        SELECT 
            cr.*,
            ci.requirement_text,
            ci.category,
            ci.points_possible,
            ci.mandatory
        FROM inspection_checklist_responses cr
        JOIN checklist_items ci ON cr.checklist_item_id = ci.item_id
        WHERE cr.inspection_id = ?
        ORDER BY ci.category, ci.order_sequence
    ");
    $stmt->execute([$inspectionId]);
    $checklistResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by category
    $checklistByCategory = [];
    foreach ($checklistResponses as $response) {
        $category = $response['category'] ?? 'General';
        if (!isset($checklistByCategory[$category])) {
            $checklistByCategory[$category] = [];
        }
        $checklistByCategory[$category][] = $response;
    }
    
    // Get violations
    $stmt = $db->prepare("
        SELECT * FROM violations
        WHERE inspection_id = ?
        ORDER BY severity DESC, created_at DESC
    ");
    $stmt->execute([$inspectionId]);
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate score
    $totalPoints = 0;
    $earnedPoints = 0;
    $passCount = 0;
    $failCount = 0;
    
    foreach ($checklistResponses as $response) {
        $totalPoints += (int)$response['points_possible'];
        if ($response['response'] === 'pass') {
            $earnedPoints += (int)$response['points_possible'];
            $passCount++;
        } elseif ($response['response'] === 'fail') {
            $failCount++;
        }
    }
    
    $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
    
    // Generate compliance rating
    if ($score >= 90) {
        $rating = 'EXCELLENT';
        $ratingColor = '#28a745';
    } elseif ($score >= 75) {
        $rating = 'GOOD';
        $ratingColor = '#17a2b8';
    } elseif ($score >= 60) {
        $rating = 'FAIR';
        $ratingColor = '#ffc107';
    } else {
        $rating = 'NEEDS IMPROVEMENT';
        $ratingColor = '#dc3545';
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while generating the report.");
}

// If download is requested, generate PDF (we'll use HTML to PDF approach)
if ($download) {
    // For now, we'll use a simple HTML approach that can be printed to PDF by browser
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Inspection_Report_' . $inspectionId . '.pdf"');
    // In production, you'd use a proper PDF library here
    // For now, redirect to print view
    header('Location: /views/inspections/report.php?id=' . $inspectionId . '&print=1');
    exit;
}

$isPrint = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection Report #<?= $inspection['reference_number'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .container { max-width: 100%; }
        }
        
        .report-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 2rem;
            margin: -1rem -1rem 2rem -1rem;
            border-radius: 8px 8px 0 0;
        }
        
        .letterhead {
            text-align: center;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        
        .letterhead h1 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0d6efd;
            margin: 0;
        }
        
        .letterhead p {
            margin: 0;
            color: #6c757d;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 1rem;
            border-left: 4px solid #0d6efd;
            margin-bottom: 1rem;
        }
        
        .score-badge {
            font-size: 3rem;
            font-weight: bold;
            text-align: center;
            padding: 2rem;
            border-radius: 8px;
            background: #f8f9fa;
            margin: 2rem 0;
        }
        
        .checklist-table th {
            background: #0d6efd;
            color: white;
        }
        
        .violation-box {
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #fff5f5;
        }
        
        .signature-section {
            margin-top: 3rem;
            border-top: 2px solid #dee2e6;
            padding-top: 2rem;
        }
        
        .signature-box {
            border-top: 2px solid #000;
            margin-top: 3rem;
            padding-top: 0.5rem;
            text-align: center;
        }
    </style>
    <?php if ($isPrint): ?>
    <script>
        window.onload = function() { window.print(); };
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="container my-4">
        <div class="bg-white p-4 shadow-sm rounded">
            <!-- Letterhead -->
            <div class="letterhead">
                <h1>HEALTH & SAFETY INSPECTION SYSTEM</h1>
                <p>Local Government Unit - Health & Sanitation Office</p>
                <p>Republic of the Philippines</p>
            </div>
            
            <h2 class="text-center mb-4">INSPECTION REPORT</h2>
            
            <!-- Report Actions -->
            <div class="no-print mb-3">
                <a href="/views/inspections/view.php?id=<?= $inspectionId ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Inspection
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </div>
            
            <!-- Inspection Details -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="info-box">
                        <h5>Inspection Information</h5>
                        <p><strong>Reference Number:</strong> <?= htmlspecialchars($inspection['reference_number']) ?></p>
                        <p><strong>Inspection Type:</strong> <?= htmlspecialchars(str_replace('_', ' ', ucwords($inspection['inspection_type'], '_'))) ?></p>
                        <p><strong>Date Conducted:</strong> <?= $inspection['started_at'] ? date('F d, Y', strtotime($inspection['started_at'])) : 'N/A' ?></p>
                        <p><strong>Date Completed:</strong> <?= $inspection['completed_at'] ? date('F d, Y', strtotime($inspection['completed_at'])) : 'N/A' ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-success"><?= strtoupper($inspection['status']) ?></span></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <h5>Establishment Details</h5>
                        <p><strong>Name:</strong> <?= htmlspecialchars($inspection['establishment_name']) ?></p>
                        <p><strong>Type:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $inspection['establishment_type']))) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($inspection['address_street']) ?>, <?= htmlspecialchars($inspection['address_barangay']) ?>, <?= htmlspecialchars($inspection['address_city']) ?></p>
                        <p><strong>Owner:</strong> <?= htmlspecialchars($inspection['owner_name']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($inspection['owner_contact']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Inspector Information -->
            <div class="info-box mb-4">
                <h5>Inspector Information</h5>
                <p><strong>Inspector:</strong> <?= htmlspecialchars($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($inspection['inspector_email']) ?></p>
            </div>
            
            <!-- Overall Score -->
            <div class="score-badge" style="color: <?= $ratingColor ?>">
                <div style="font-size: 4rem;"><?= $score ?>%</div>
                <div style="font-size: 1.5rem;"><?= $rating ?></div>
                <div style="font-size: 1rem; color: #6c757d;">
                    <?= $earnedPoints ?> / <?= $totalPoints ?> Points | 
                    <?= $passCount ?> Passed, <?= $failCount ?> Failed
                </div>
            </div>
            
            <!-- Checklist Results -->
            <?php if (!empty($checklistByCategory)): ?>
            <h4 class="mt-4 mb-3">Checklist Results</h4>
            <?php foreach ($checklistByCategory as $category => $responses): ?>
                <h5 class="text-primary mt-3"><?= htmlspecialchars($category) ?></h5>
                <table class="table table-bordered checklist-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Requirement</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 15%;">Points</th>
                            <th style="width: 20%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $response): ?>
                        <tr>
                            <td><?= htmlspecialchars($response['requirement_text']) ?></td>
                            <td class="text-center">
                                <?php if ($response['response'] === 'pass'): ?>
                                    <span class="badge bg-success">PASS</span>
                                <?php elseif ($response['response'] === 'fail'): ?>
                                    <span class="badge bg-danger">FAIL</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?= $response['response'] === 'pass' ? $response['points_possible'] : 0 ?> / <?= $response['points_possible'] ?>
                            </td>
                            <td><?= htmlspecialchars($response['notes'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Violations -->
            <?php if (!empty($violations)): ?>
            <h4 class="mt-4 mb-3 text-danger">Violations Found (<?= count($violations) ?>)</h4>
            <?php foreach ($violations as $index => $violation): ?>
            <div class="violation-box">
                <h6>
                    Violation #<?= $index + 1 ?>: 
                    <span class="badge bg-<?= $violation['severity'] === 'critical' ? 'dark' : ($violation['severity'] === 'major' ? 'danger' : 'warning') ?>">
                        <?= strtoupper($violation['severity']) ?>
                    </span>
                </h6>
                <p><strong>Description:</strong> <?= htmlspecialchars($violation['description']) ?></p>
                <p><strong>Category:</strong> <?= htmlspecialchars($violation['category']) ?></p>
                <?php if ($violation['corrective_action_required']): ?>
                <p><strong>Corrective Action Required:</strong> <?= htmlspecialchars($violation['corrective_action_required']) ?></p>
                <?php endif; ?>
                <?php if ($violation['corrective_action_deadline']): ?>
                <p><strong>Deadline:</strong> <?= date('F d, Y', strtotime($violation['corrective_action_deadline'])) ?></p>
                <?php endif; ?>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?= $violation['status'] === 'resolved' ? 'success' : 'warning' ?>">
                        <?= strtoupper($violation['status']) ?>
                    </span>
                </p>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="alert alert-success mt-4">
                <h5><i class="bi bi-check-circle"></i> No Violations Found</h5>
                <p>This establishment has passed all inspection requirements with no violations recorded.</p>
            </div>
            <?php endif; ?>
            
            <!-- Recommendations -->
            <h4 class="mt-4 mb-3">Recommendations</h4>
            <div class="info-box">
                <?php if ($score >= 90): ?>
                    <p>The establishment demonstrates excellent compliance with health and safety standards. Continue maintaining current practices and standards.</p>
                <?php elseif ($score >= 75): ?>
                    <p>The establishment shows good compliance with health and safety standards. Address the identified violations and implement recommended corrective actions to achieve excellent compliance.</p>
                <?php elseif ($score >= 60): ?>
                    <p>The establishment shows fair compliance with health and safety standards. Immediate attention is required to address the identified violations. Follow-up inspection is recommended within 30 days.</p>
                <?php else: ?>
                    <p>The establishment shows poor compliance with health and safety standards. Immediate corrective actions are required. A re-inspection will be conducted within 14 days to verify compliance. Failure to comply may result in suspension of operations.</p>
                <?php endif; ?>
                
                <?php if (!empty($violations)): ?>
                <p class="mt-2"><strong>Required Actions:</strong></p>
                <ul>
                    <?php foreach ($violations as $violation): ?>
                    <li><?= htmlspecialchars($violation['corrective_action_required'] ?: 'Address the violation: ' . $violation['description']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Prepared by:</strong></p>
                        <div class="signature-box">
                            <?= htmlspecialchars($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']) ?><br>
                            <small>Health Inspector</small><br>
                            <small>Date: <?= date('F d, Y') ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Received by:</strong></p>
                        <div class="signature-box">
                            <?= htmlspecialchars($inspection['owner_name']) ?><br>
                            <small>Establishment Owner/Representative</small><br>
                            <small>Date: ___________________</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-4 pt-3 border-top">
                <small class="text-muted">
                    This is an official document generated by the Health & Safety Inspection System<br>
                    Report Generated: <?= date('F d, Y h:i A') ?><br>
                    Reference: <?= htmlspecialchars($inspection['reference_number']) ?>
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
