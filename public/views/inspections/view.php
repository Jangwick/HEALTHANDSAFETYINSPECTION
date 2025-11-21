<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$inspectionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$inspectionId) {
    header('Location: /views/inspections/list.php');
    exit;
}

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
    
    // Get inspection details with establishment and inspector info
    $stmt = $db->prepare("
        SELECT 
            i.*,
            e.name AS establishment_name,
            e.type AS establishment_type,
            e.address_street,
            e.address_barangay,
            e.address_city,
            e.owner_name AS contact_person,
            e.owner_contact AS contact_number,
            e.owner_email AS establishment_email,
            u.first_name AS inspector_first_name,
            u.last_name AS inspector_last_name,
            u.email AS inspector_email
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        LEFT JOIN users u ON i.inspector_id = u.user_id
        WHERE i.inspection_id = ?
    ");
        WHERE i.id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        header('Location: /views/inspections/list.php');
        exit;
    }
    
    // Get checklist responses for this inspection
    $stmt = $db->prepare("
        SELECT 
            cr.*,
            ci.requirement_text,
            ci.category,
            ci.points_possible
        FROM inspection_checklist_responses cr
        JOIN checklist_items ci ON cr.checklist_item_id = ci.item_id
        WHERE cr.inspection_id = ?
        ORDER BY ci.category, ci.order_sequence
    ");
    $stmt->execute([$inspectionId]);
    $checklistResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group checklist responses by category
    $checklistByCategory = [];
    foreach ($checklistResponses as $response) {
        $category = $response['category'] ?? 'General';
        if (!isset($checklistByCategory[$category])) {
            $checklistByCategory[$category] = [];
        }
        $checklistByCategory[$category][] = $response;
    }
    
    // Get violations for this inspection
    $stmt = $db->prepare("
        SELECT * FROM violations
        WHERE inspection_id = ?
        ORDER BY severity DESC, created_at DESC
    ");
    $stmt->execute([$inspectionId]);
    $violations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get documents/photos for this inspection
    $stmt = $db->prepare("
        SELECT * FROM documents
        WHERE entity_type = 'inspection' AND entity_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$inspectionId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate checklist score if responses exist
    $totalPoints = 0;
    $earnedPoints = 0;
    foreach ($checklistResponses as $response) {
        $totalPoints += (int)$response['points_possible'];
        if ($response['response'] === 'pass') {
            $earnedPoints += (int)$response['points_possible'];
        }
    }
    $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while loading the inspection.");
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'scheduled' => '<span class="badge bg-info">Scheduled</span>',
        'in_progress' => '<span class="badge bg-primary">In Progress</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getSeverityBadge($severity) {
    $badges = [
        'minor' => '<span class="badge bg-warning">Minor</span>',
        'major' => '<span class="badge bg-danger">Major</span>',
        'critical' => '<span class="badge bg-dark">Critical</span>'
    ];
    return $badges[$severity] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function getPriorityBadge($priority) {
    $badges = [
        'low' => '<span class="badge bg-secondary">Low</span>',
        'medium' => '<span class="badge bg-info">Medium</span>',
        'high' => '<span class="badge bg-warning">High</span>',
        'urgent' => '<span class="badge bg-danger">Urgent</span>'
    ];
    return $badges[$priority] ?? '<span class="badge bg-secondary">Unknown</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspection #<?= $inspection['id'] ?> - Health & Safety Inspection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .action-buttons {
            position: sticky;
            top: 0;
            background: white;
            padding: 1rem 0;
            z-index: 1000;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 1rem;
        }
        .info-card {
            border-left: 4px solid #0d6efd;
        }
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto;
        }
        .score-excellent { background: #d4edda; color: #155724; }
        .score-good { background: #d1ecf1; color: #0c5460; }
        .score-fair { background: #fff3cd; color: #856404; }
        .score-poor { background: #f8d7da; color: #721c24; }
        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        .photo-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .photo-item img:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-shield-check"></i> Health & Safety Inspection
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/views/inspections/list.php">Inspections</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link"><?= htmlspecialchars($_SESSION['first_name']) ?> <?= htmlspecialchars($_SESSION['last_name']) ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/views/auth/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Action Buttons -->
        <div class="action-buttons">
            <div class="row">
                <div class="col-md-6">
                    <h2>
                        <i class="bi bi-clipboard-check"></i> Inspection #<?= $inspection['id'] ?>
                        <?= getStatusBadge($inspection['status']) ?>
                        <?= getPriorityBadge($inspection['priority']) ?>
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/views/inspections/list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    
                    <?php if ($inspection['status'] === 'pending' || $inspection['status'] === 'scheduled'): ?>
                        <a href="/views/inspections/conduct.php?id=<?= $inspection['id'] ?>" class="btn btn-success">
                            <i class="bi bi-play-circle"></i> Start Inspection
                        </a>
                    <?php elseif ($inspection['status'] === 'in_progress'): ?>
                        <a href="/views/inspections/conduct.php?id=<?= $inspection['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-arrow-right-circle"></i> Continue Inspection
                        </a>
                    <?php elseif ($inspection['status'] === 'completed'): ?>
                        <a href="/views/inspections/report.php?id=<?= $inspection['id'] ?>" class="btn btn-info" target="_blank">
                            <i class="bi bi-file-earmark-pdf"></i> View Report
                        </a>
                        <a href="/views/inspections/report.php?id=<?= $inspection['id'] ?>&download=1" class="btn btn-success">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($inspection['status'] !== 'completed' && $inspection['status'] !== 'cancelled'): ?>
                        <button class="btn btn-warning" onclick="editInspection()">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-danger" onclick="cancelInspection()">
                            <i class="bi bi-x-circle"></i> Cancel
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column: Main Information -->
            <div class="col-md-8">
                <!-- Establishment Details -->
                <div class="card mb-3 info-card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-building"></i> Establishment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?= htmlspecialchars($inspection['establishment_name']) ?></p>
                                <p><strong>Type:</strong> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $inspection['establishment_type']))) ?></p>
                                <p><strong>Contact Person:</strong> <?= htmlspecialchars($inspection['contact_person']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Address:</strong> <?= htmlspecialchars($inspection['address_street'] . ', ' . $inspection['address_barangay'] . ', ' . $inspection['address_city']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($inspection['contact_number']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($inspection['establishment_email']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inspection Details -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Inspection Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Type:</strong> <?= htmlspecialchars(str_replace('_', ' ', ucwords($inspection['inspection_type'], '_'))) ?></p>
                                <p><strong>Inspector:</strong> <?= htmlspecialchars($inspection['inspector_first_name'] . ' ' . $inspection['inspector_last_name']) ?></p>
                                <p><strong>Scheduled Date:</strong> <?= date('F d, Y', strtotime($inspection['scheduled_date'])) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Started:</strong> <?= $inspection['started_at'] ? date('F d, Y h:i A', strtotime($inspection['started_at'])) : 'Not started' ?></p>
                                <p><strong>Completed:</strong> <?= $inspection['completed_at'] ? date('F d, Y h:i A', strtotime($inspection['completed_at'])) : 'Not completed' ?></p>
                                <p><strong>Created:</strong> <?= date('F d, Y h:i A', strtotime($inspection['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php if ($inspection['notes']): ?>
                            <hr>
                            <p><strong>Notes:</strong></p>
                            <p><?= nl2br(htmlspecialchars($inspection['notes'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Checklist Results -->
                <?php if (!empty($checklistResponses)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-list-check"></i> Checklist Results</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($checklistByCategory as $category => $responses): ?>
                            <h6 class="text-primary mt-3"><?= htmlspecialchars($category) ?></h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th style="width: 100px;">Status</th>
                                            <th style="width: 80px;">Points</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($responses as $response): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($response['requirement_text']) ?></td>
                                            <td>
                                                <?php if ($response['response'] === 'pass'): ?>
                                                    <span class="badge bg-success">Pass</span>
                                                <?php elseif ($response['response'] === 'fail'): ?>
                                                    <span class="badge bg-danger">Fail</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $response['response'] === 'pass' ? $response['points_possible'] : 0 ?> / <?= $response['points_possible'] ?></td>
                                            <td><?= htmlspecialchars($response['notes'] ?? '') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Violations -->
                <?php if (!empty($violations)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Violations Found (<?= count($violations) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($violations as $violation): ?>
                        <div class="alert alert-<?= $violation['severity'] === 'critical' ? 'danger' : ($violation['severity'] === 'major' ? 'warning' : 'info') ?> mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6>
                                        <?= getSeverityBadge($violation['severity']) ?>
                                        <?= htmlspecialchars($violation['description']) ?>
                                    </h6>
                                    <p class="mb-1"><strong>Category:</strong> <?= htmlspecialchars($violation['violation_type']) ?></p>
                                    <?php if ($violation['corrective_action']): ?>
                                        <p class="mb-1"><strong>Corrective Action:</strong> <?= htmlspecialchars($violation['corrective_action']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($violation['deadline']): ?>
                                        <p class="mb-0"><strong>Deadline:</strong> <?= date('F d, Y', strtotime($violation['deadline'])) ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php
                                    $statusClass = [
                                        'open' => 'danger',
                                        'in_progress' => 'warning',
                                        'resolved' => 'success'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $statusClass[$violation['status']] ?? 'secondary' ?>">
                                        <?= ucfirst($violation['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Photos/Documents -->
                <?php if (!empty($documents)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-images"></i> Photos & Documents (<?= count($documents) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="photo-grid">
                            <?php foreach ($documents as $doc): ?>
                            <div class="photo-item">
                                <?php if (in_array(strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                    <img src="/<?= htmlspecialchars($doc['file_path']) ?>" 
                                         alt="<?= htmlspecialchars($doc['title']) ?>"
                                         onclick="viewImage('<?= htmlspecialchars($doc['file_path']) ?>')">
                                    <small class="d-block mt-1"><?= htmlspecialchars($doc['title']) ?></small>
                                <?php else: ?>
                                    <a href="/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-earmark"></i> <?= htmlspecialchars($doc['title']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Summary -->
            <div class="col-md-4">
                <!-- Score Card -->
                <?php if (!empty($checklistResponses)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Overall Score</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="score-circle <?= $score >= 90 ? 'score-excellent' : ($score >= 75 ? 'score-good' : ($score >= 60 ? 'score-fair' : 'score-poor')) ?>">
                            <?= $score ?>%
                        </div>
                        <p class="mt-3 mb-0">
                            <?= $earnedPoints ?> / <?= $totalPoints ?> points
                        </p>
                        <p class="text-muted small">
                            <?php
                            if ($score >= 90) echo 'Excellent compliance';
                            elseif ($score >= 75) echo 'Good compliance';
                            elseif ($score >= 60) echo 'Fair compliance';
                            else echo 'Needs improvement';
                            ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Summary Card -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Checklist Items:</span>
                            <strong><?= count($checklistResponses) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Violations:</span>
                            <strong class="text-danger"><?= count($violations) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Photos/Documents:</span>
                            <strong><?= count($documents) ?></strong>
                        </div>
                        <hr>
                        <?php
                        $passCount = count(array_filter($checklistResponses, fn($r) => $r['response'] === 'pass'));
                        $failCount = count(array_filter($checklistResponses, fn($r) => $r['response'] === 'fail'));
                        ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><i class="bi bi-check-circle text-success"></i> Passed:</span>
                            <strong class="text-success"><?= $passCount ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-x-circle text-danger"></i> Failed:</span>
                            <strong class="text-danger"><?= $failCount ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Timeline</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-primary"></i>
                                <small class="text-muted">Created</small><br>
                                <small><?= date('M d, Y h:i A', strtotime($inspection['created_at'])) ?></small>
                            </li>
                            <?php if ($inspection['started_at']): ?>
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-info"></i>
                                <small class="text-muted">Started</small><br>
                                <small><?= date('M d, Y h:i A', strtotime($inspection['started_at'])) ?></small>
                            </li>
                            <?php endif; ?>
                            <?php if ($inspection['completed_at']): ?>
                            <li class="mb-2">
                                <i class="bi bi-circle-fill text-success"></i>
                                <small class="text-muted">Completed</small><br>
                                <small><?= date('M d, Y h:i A', strtotime($inspection['completed_at'])) ?></small>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewImage(imagePath) {
            document.getElementById('modalImage').src = '/' + imagePath;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        function editInspection() {
            if (confirm('Edit this inspection?')) {
                window.location.href = '/views/inspections/edit.php?id=<?= $inspection['id'] ?>';
            }
        }

        function cancelInspection() {
            if (confirm('Are you sure you want to cancel this inspection? This action cannot be undone.')) {
                // Send AJAX request to update status
                fetch('/views/inspections/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        inspection_id: <?= $inspection['id'] ?>,
                        status: 'cancelled'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Inspection cancelled successfully');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>
