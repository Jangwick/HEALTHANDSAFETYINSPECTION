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
    $db = Database::getInstance()->getConnection();
    
    // Get inspection details
    $stmt = $db->prepare("
        SELECT i.*, e.name AS establishment_name, e.type AS establishment_type
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.establishment_id
        WHERE i.inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        header('Location: /views/inspections/list.php');
        exit;
    }
    
    // If inspection is pending/scheduled, start it
    if ($inspection['status'] === 'pending' || $inspection['status'] === 'scheduled') {
        $stmt = $db->prepare("
            UPDATE inspections 
            SET status = 'in_progress', started_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$inspectionId]);
        $inspection['status'] = 'in_progress';
        $inspection['started_at'] = date('Y-m-d H:i:s');
    }
    
    // Get checklist items for this inspection type via template
    $stmt = $db->prepare("
        SELECT ci.* FROM checklist_items ci
        JOIN checklist_templates ct ON ci.template_id = ct.template_id
        WHERE ct.inspection_type = ? AND ct.status = 'active'
        ORDER BY ci.category, ci.order_sequence
    ");
    $stmt->execute([$inspection['inspection_type']]);
    $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get existing responses
    $stmt = $db->prepare("
        SELECT * FROM inspection_checklist_responses
        WHERE inspection_id = ?
    ");
    $stmt->execute([$inspectionId]);
    $existingResponses = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $response) {
        $existingResponses[$response['checklist_item_id']] = $response;
    }
    
    // Group checklist by category
    $checklistByCategory = [];
    foreach ($checklistItems as $item) {
        $category = $item['category'] ?? 'General';
        if (!isset($checklistByCategory[$category])) {
            $checklistByCategory[$category] = [];
        }
        $checklistByCategory[$category][] = $item;
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'save_progress' || $action === 'complete') {
            // Save all checklist responses
            foreach ($_POST['responses'] ?? [] as $itemId => $data) {
                $response = $data['status'] ?? 'na';
                $notes = $data['notes'] ?? '';
                
                // Check if response exists
                if (isset($existingResponses[$itemId])) {
                    // Update existing
                    $stmt = $db->prepare("
                        UPDATE inspection_checklist_responses 
                        SET response = ?, notes = ?
                        WHERE response_id = ?
                    ");
                    $stmt->execute([$response, $notes, $existingResponses[$itemId]['response_id']]);
                } else {
                    // Insert new
                    $stmt = $db->prepare("
                        INSERT INTO inspection_checklist_responses 
                        (inspection_id, checklist_item_id, response, notes, recorded_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$inspectionId, $itemId, $response, $notes]);
                }
            }
            
            if ($action === 'complete') {
                // Mark inspection as completed
                $stmt = $db->prepare("
                    UPDATE inspections 
                    SET status = 'completed', completed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$inspectionId]);
                
                $_SESSION['message'] = 'Inspection completed successfully!';
                header('Location: /views/inspections/view.php?id=' . $inspectionId);
                exit;
            } else {
                $_SESSION['message'] = 'Progress saved successfully!';
                header('Location: /views/inspections/conduct.php?id=' . $inspectionId);
                exit;
            }
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conduct Inspection #<?= $inspection['id'] ?> - Health & Safety Inspection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .sticky-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 1000;
            padding: 1rem 0;
            border-bottom: 2px solid #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .checklist-category {
            background: #f8f9fa;
            padding: 1rem;
            margin: 1.5rem 0 1rem 0;
            border-left: 4px solid #0d6efd;
        }
        .checklist-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: box-shadow 0.2s;
        }
        .checklist-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .checklist-item.incomplete {
            border-left: 4px solid #ffc107;
        }
        .checklist-item.pass {
            border-left: 4px solid #28a745;
        }
        .checklist-item.fail {
            border-left: 4px solid #dc3545;
        }
        .radio-group {
            display: flex;
            gap: 1rem;
        }
        .radio-label {
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: 2px solid #dee2e6;
            transition: all 0.2s;
        }
        .radio-label:hover {
            background: #f8f9fa;
        }
        .radio-label.pass {
            border-color: #28a745;
            background: #d4edda;
        }
        .radio-label.fail {
            border-color: #dc3545;
            background: #f8d7da;
        }
        .radio-label.na {
            border-color: #6c757d;
            background: #e9ecef;
        }
        .progress-bar-custom {
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e9ecef;
            z-index: 999;
        }
        .progress-bar-fill {
            height: 100%;
            background: #28a745;
            transition: width 0.3s;
        }
        .floating-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        .floating-actions .btn {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-bottom: 10px;
            display: block;
            width: 200px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-shield-check"></i> Health & Safety Inspection
            </a>
            <div class="ms-auto text-white">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['first_name']) ?> <?= htmlspecialchars($_SESSION['last_name']) ?>
            </div>
        </div>
    </nav>

    <div class="progress-bar-custom">
        <div class="progress-bar-fill" id="progressBar" style="width: 0%"></div>
    </div>

    <div class="container-fluid mt-4">
        <div class="sticky-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3>
                        <i class="bi bi-clipboard-check"></i> 
                        Conducting Inspection #<?= $inspection['id'] ?>
                    </h3>
                    <p class="mb-0 text-muted">
                        <strong><?= htmlspecialchars($inspection['establishment_name']) ?></strong>
                        <span class="ms-2">(<?= htmlspecialchars(ucwords(str_replace('_', ' ', $inspection['establishment_type']))) ?>)</span>
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info fs-6">
                        <i class="bi bi-list-check"></i> 
                        <span id="completedCount">0</span> / <span id="totalCount"><?= count($checklistItems) ?></span> Items
                    </span>
                    <span class="badge bg-success fs-6 ms-2">
                        <i class="bi bi-percent"></i> 
                        <span id="progressPercent">0</span>% Complete
                    </span>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); endif; ?>

        <form method="POST" id="checklistForm">
            <div class="row">
                <div class="col-md-12">
                    <?php foreach ($checklistByCategory as $category => $items): ?>
                        <div class="checklist-category">
                            <h4><i class="bi bi-folder"></i> <?= htmlspecialchars($category) ?></h4>
                            <small class="text-muted"><?= count($items) ?> items</small>
                        </div>

                        <?php foreach ($items as $item): ?>
                            <?php
                            $itemId = $item['item_id'];
                            $currentResponse = $existingResponses[$itemId] ?? null;
                            $currentStatus = $currentResponse['response'] ?? '';
                            $currentNotes = $currentResponse['notes'] ?? '';
                            ?>
                            <div class="checklist-item <?= $currentStatus ?>" data-item-id="<?= $itemId ?>">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6><?= htmlspecialchars($item['requirement_text']) ?></h6>
                                        <?php if ($item['guidance_notes']): ?>
                                            <p class="text-muted small mb-2"><?= htmlspecialchars($item['guidance_notes']) ?></p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <i class="bi bi-award"></i> Points: <?= $item['points_possible'] ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="radio-group">
                                            <label class="radio-label <?= $currentStatus === 'pass' ? 'pass' : '' ?>">
                                                <input type="radio" 
                                                       name="responses[<?= $itemId ?>][status]" 
                                                       value="pass" 
                                                       <?= $currentStatus === 'pass' ? 'checked' : '' ?>
                                                       onchange="updateItemStatus(<?= $itemId ?>, 'pass')">
                                                <i class="bi bi-check-circle"></i> Pass
                                            </label>
                                            <label class="radio-label <?= $currentStatus === 'fail' ? 'fail' : '' ?>">
                                                <input type="radio" 
                                                       name="responses[<?= $itemId ?>][status]" 
                                                       value="fail" 
                                                       <?= $currentStatus === 'fail' ? 'checked' : '' ?>
                                                       onchange="updateItemStatus(<?= $itemId ?>, 'fail')">
                                                <i class="bi bi-x-circle"></i> Fail
                                            </label>
                                            <label class="radio-label <?= $currentStatus === 'na' ? 'na' : '' ?>">
                                                <input type="radio" 
                                                       name="responses[<?= $itemId ?>][status]" 
                                                       value="na" 
                                                       <?= $currentStatus === 'na' ? 'checked' : '' ?>
                                                       onchange="updateItemStatus(<?= $itemId ?>, 'na')">
                                                <i class="bi bi-dash-circle"></i> N/A
                                            </label>
                                        </div>
                                        <textarea class="form-control mt-2" 
                                                  name="responses[<?= $itemId ?>][notes]" 
                                                  rows="2" 
                                                  placeholder="Add notes..."><?= htmlspecialchars($currentNotes) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="floating-actions">
                <button type="button" class="btn btn-warning" onclick="addViolation()">
                    <i class="bi bi-exclamation-triangle"></i> Add Violation
                </button>
                <button type="submit" name="action" value="save_progress" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Progress
                </button>
                <button type="button" class="btn btn-success" onclick="confirmComplete()">
                    <i class="bi bi-check-circle"></i> Complete Inspection
                </button>
                <a href="/views/inspections/view.php?id=<?= $inspectionId ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to View
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateItemStatus(itemId, status) {
            const item = document.querySelector(`[data-item-id="${itemId}"]`);
            item.className = 'checklist-item ' + status;
            
            // Update radio label styles
            const labels = item.querySelectorAll('.radio-label');
            labels.forEach(label => {
                label.classList.remove('pass', 'fail', 'na');
            });
            
            const checkedInput = item.querySelector('input[type="radio"]:checked');
            const checkedLabel = checkedInput.closest('.radio-label');
            checkedLabel.classList.add(status === 'na' ? 'na' : status);
            
            updateProgress();
        }

        function updateProgress() {
            const total = document.querySelectorAll('.checklist-item').length;
            const completed = document.querySelectorAll('.checklist-item input[type="radio"]:checked').length;
            const percent = Math.round((completed / total) * 100);
            
            document.getElementById('completedCount').textContent = completed;
            document.getElementById('progressPercent').textContent = percent;
            document.getElementById('progressBar').style.width = percent + '%';
        }

        function confirmComplete() {
            const total = document.querySelectorAll('.checklist-item').length;
            const completed = document.querySelectorAll('.checklist-item input[type="radio"]:checked').length;
            
            if (completed < total) {
                if (!confirm(`You have only completed ${completed} out of ${total} items. Are you sure you want to complete this inspection?`)) {
                    return;
                }
            }
            
            if (confirm('Are you sure you want to mark this inspection as completed? This action cannot be undone.')) {
                // Create hidden input for action
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'complete';
                document.getElementById('checklistForm').appendChild(actionInput);
                document.getElementById('checklistForm').submit();
            }
        }

        function addViolation() {
            window.open('/views/violations/add.php?inspection_id=<?= $inspectionId ?>', 'Add Violation', 'width=800,height=600');
        }

        // Auto-save every 2 minutes
        let autoSaveInterval = setInterval(() => {
            const formData = new FormData(document.getElementById('checklistForm'));
            formData.append('action', 'save_progress');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(() => {
                // Show brief save indicator
                const saveIndicator = document.createElement('div');
                saveIndicator.className = 'alert alert-success position-fixed top-0 end-0 m-3';
                saveIndicator.innerHTML = '<i class="bi bi-check-circle"></i> Auto-saved';
                document.body.appendChild(saveIndicator);
                setTimeout(() => saveIndicator.remove(), 2000);
            });
        }, 120000); // 2 minutes

        // Initialize progress
        updateProgress();

        // Prevent accidental navigation
        window.addEventListener('beforeunload', (e) => {
            const completed = document.querySelectorAll('.checklist-item input[type="radio"]:checked').length;
            if (completed > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
