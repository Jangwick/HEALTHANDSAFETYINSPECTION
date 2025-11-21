<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$inspectionId = isset($_GET['inspection_id']) ? (int)$_GET['inspection_id'] : 0;

if (!$inspectionId) {
    die('Invalid inspection ID');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get inspection details
    $stmt = $db->prepare("
        SELECT i.*, e.name AS establishment_name
        FROM inspections i
        LEFT JOIN establishments e ON i.establishment_id = e.id
        WHERE i.id = ?
    ");
    $stmt->execute([$inspectionId]);
    $inspection = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inspection) {
        die('Inspection not found');
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $description = trim($_POST['description'] ?? '');
        $violationType = trim($_POST['violation_type'] ?? '');
        $severity = $_POST['severity'] ?? 'minor';
        $correctiveAction = trim($_POST['corrective_action'] ?? '');
        $deadline = $_POST['deadline'] ?? null;
        
        if (empty($description)) {
            $error = 'Description is required';
        } else {
            // Insert violation
            $stmt = $db->prepare("
                INSERT INTO violations 
                (inspection_id, description, violation_type, severity, corrective_action, deadline, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'open', NOW())
            ");
            $stmt->execute([
                $inspectionId,
                $description,
                $violationType,
                $severity,
                $correctiveAction,
                $deadline ?: null
            ]);
            
            $violationId = $db->lastInsertId();
            
            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/violations/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'violation_' . $violationId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $filepath)) {
                    // Save document reference
                    $stmt = $db->prepare("
                        INSERT INTO documents 
                        (entity_type, entity_id, title, file_path, file_type, uploaded_by, created_at)
                        VALUES ('violation', ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $violationId,
                        'Violation Photo - ' . $description,
                        'uploads/violations/' . $filename,
                        $extension,
                        $_SESSION['user_id']
                    ]);
                }
            }
            
            $success = true;
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "An error occurred while saving the violation.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Violation - Inspection #<?= $inspectionId ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .violation-form {
            max-width: 700px;
            margin: 2rem auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .photo-preview {
            max-width: 100%;
            max-height: 300px;
            margin-top: 1rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="violation-form">
        <h4 class="mb-4">
            <i class="bi bi-exclamation-triangle text-warning"></i> 
            Add Violation
        </h4>
        
        <p class="text-muted mb-4">
            <strong>Inspection:</strong> #<?= $inspectionId ?> - <?= htmlspecialchars($inspection['establishment_name']) ?>
        </p>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i> Violation added successfully!
            <div class="mt-2">
                <button onclick="window.close()" class="btn btn-sm btn-success">Close Window</button>
                <button onclick="location.reload()" class="btn btn-sm btn-primary">Add Another</button>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Description <span class="text-danger">*</span></label>
                <textarea class="form-control" name="description" rows="3" required 
                          placeholder="Describe the violation..."></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Violation Type</label>
                    <select class="form-select" name="violation_type">
                        <option value="">Select type...</option>
                        <option value="food_safety">Food Safety</option>
                        <option value="sanitation">Sanitation</option>
                        <option value="structural">Structural</option>
                        <option value="fire_safety">Fire Safety</option>
                        <option value="occupational_health">Occupational Health</option>
                        <option value="environmental">Environmental</option>
                        <option value="documentation">Documentation</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Severity <span class="text-danger">*</span></label>
                    <select class="form-select" name="severity" required>
                        <option value="minor">Minor</option>
                        <option value="major">Major</option>
                        <option value="critical">Critical</option>
                    </select>
                    <small class="text-muted">
                        Minor: Low risk | Major: Moderate risk | Critical: Immediate action required
                    </small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Corrective Action Required</label>
                <textarea class="form-control" name="corrective_action" rows="2" 
                          placeholder="What needs to be done to fix this violation?"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Deadline for Correction</label>
                <input type="date" class="form-control" name="deadline" min="<?= date('Y-m-d') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Photo Evidence</label>
                <input type="file" class="form-control" name="photo" accept="image/*" 
                       onchange="previewPhoto(event)">
                <img id="photoPreview" class="photo-preview" style="display: none;">
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-exclamation-triangle"></i> Add Violation
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.close()">
                    Cancel
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewPhoto(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('photoPreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }

        // Notify parent window when violation is added
        <?php if (isset($success)): ?>
        if (window.opener && !window.opener.closed) {
            window.opener.location.reload();
        }
        <?php endif; ?>
    </script>
</body>
</html>
