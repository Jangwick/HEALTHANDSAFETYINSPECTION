<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$establishmentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if (!$establishmentId) {
    header('Location: /views/establishments/list.php');
    exit;
}

try {
    $db = Database::getConnection();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate required fields
        $required = ['name', 'type', 'owner_name', 'owner_phone', 'address_street', 'address_barangay', 'address_city'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Update establishment
        $stmt = $db->prepare("
            UPDATE establishments SET
                name = ?,
                type = ?,
                owner_name = ?,
                owner_email = ?,
                owner_phone = ?,
                address_street = ?,
                address_barangay = ?,
                address_city = ?,
                address_province = ?,
                business_permit_number = ?,
                permit_issue_date = ?,
                permit_expiry_date = ?,
                capacity = ?,
                operating_hours = ?,
                description = ?,
                compliance_status = ?,
                updated_at = NOW()
            WHERE establishment_id = ?
        ");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['type'],
            $_POST['owner_name'],
            $_POST['owner_email'] ?? null,
            $_POST['owner_phone'],
            $_POST['address_street'],
            $_POST['address_barangay'],
            $_POST['address_city'],
            $_POST['address_province'] ?? null,
            $_POST['business_permit_number'] ?? null,
            $_POST['permit_issue_date'] ?? null,
            $_POST['permit_expiry_date'] ?? null,
            $_POST['capacity'] ?? null,
            $_POST['operating_hours'] ?? null,
            $_POST['description'] ?? null,
            $_POST['compliance_status'] ?? 'pending',
            $establishmentId
        ]);
        
        $success = "Establishment updated successfully!";
        
        // Redirect to view page after brief delay
        header("Refresh: 2; url=/views/establishments/view.php?id=$establishmentId");
    }
    
    // Get establishment data
    $stmt = $db->prepare("SELECT * FROM establishments WHERE establishment_id = ?");
    $stmt->execute([$establishmentId]);
    $establishment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$establishment) {
        header('Location: /views/establishments/list.php');
        exit;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Establishment update error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo  htmlspecialchars($establishment['name']) ?> - Health & Safety Inspection System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 105%; }
            body { @apply text-slate-900 font-medium; }
        }
        .form-section { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .section-title { color: #495057; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #dee2e6; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'establishments';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-xl font-bold text-slate-800">Edit Establishment</h1>
                <div class="flex items-center space-x-3">
                    <a href="/views/establishments/view.php?id=<?php echo  $establishmentId ?>" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold flex items-center transition-all">
                        <i class="fas fa-eye mr-2"></i> View Details
                    </a>
                    <a href="/views/establishments/list.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-lg text-sm font-bold flex items-center transition-all">
                        <i class="fas fa-arrow-left mr-2"></i> Back to List
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="max-w-5xl mx-auto">
                    <!-- Notifications -->
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-6">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo  htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-6">
                        <i class="fas fa-check-circle mr-2"></i> <?php echo  htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-slate-900 mb-1"><?php echo htmlspecialchars($establishment['name']); ?></h2>
                        <p class="text-slate-500">Update establishment information and compliance status</p>
                    </div>

                    <form method="POST" action="">
                        <div class="card">
                            <div class="card-body">
                                <!-- Basic Information -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="bi bi-info-circle"></i> Basic Information</h5>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="form-label">Establishment Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name" required 
                                                       value="<?php echo  htmlspecialchars($establishment['name']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                                <select class="form-select" name="type" required>
                                                    <option value="">Select Type</option>
                                                    <?php
                                                    $types = ['restaurant', 'school', 'hospital', 'hotel', 'market', 'factory', 'office', 'salon', 'gym', 'other'];
                                                    foreach ($types as $type):
                                                    ?>
                                                    <option value="<?php echo  $type ?>" <?php echo  $establishment['type'] === $type ? 'selected' : '' ?>>
                                                        <?php echo  ucfirst($type) ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Compliance Status <span class="text-danger">*</span></label>
                                                <select class="form-select" name="compliance_status" required>
                                                    <option value="compliant" <?php echo  $establishment['compliance_status'] === 'compliant' ? 'selected' : '' ?>>Compliant</option>
                                                    <option value="non_compliant" <?php echo  $establishment['compliance_status'] === 'non_compliant' ? 'selected' : '' ?>>Non-Compliant</option>
                                                    <option value="pending" <?php echo  $establishment['compliance_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="suspended" <?php echo  $establishment['compliance_status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Capacity (persons)</label>
                                                <input type="number" class="form-control" name="capacity" min="0"
                                                       value="<?php echo  htmlspecialchars($establishment['capacity'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Operating Hours</label>
                                                <input type="text" class="form-control" name="operating_hours"
                                                       value="<?php echo  htmlspecialchars($establishment['operating_hours'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">Description/Notes</label>
                                        <textarea class="form-control" name="description" rows="3"><?php echo  htmlspecialchars($establishment['description'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <!-- Owner Information -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="bi bi-person"></i> Owner Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Owner Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="owner_name" required
                                                       value="<?php echo  htmlspecialchars($establishment['owner_name']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Owner Phone <span class="text-danger">*</span></label>
                                                <input type="tel" class="form-control" name="owner_phone" required
                                                       value="<?php echo  htmlspecialchars($establishment['owner_phone']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">Owner Email</label>
                                        <input type="email" class="form-control" name="owner_email"
                                               value="<?php echo  htmlspecialchars($establishment['owner_email'] ?? '') ?>">
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="bi bi-geo-alt"></i> Address Information</h5>
                                    <div class="mb-3">
                                        <label class="form-label">Street Address <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="address_street" required
                                               value="<?php echo  htmlspecialchars($establishment['address_street']) ?>">
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Barangay <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="address_barangay" required
                                                       value="<?php echo  htmlspecialchars($establishment['address_barangay']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">City/Municipality <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="address_city" required
                                                       value="<?php echo  htmlspecialchars($establishment['address_city']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-control" name="address_province"
                                                       value="<?php echo  htmlspecialchars($establishment['address_province'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Business Permit Information -->
                                <div class="form-section">
                                    <h5 class="section-title"><i class="bi bi-file-text"></i> Business Permit Information</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Business Permit Number</label>
                                                <input type="text" class="form-control" name="business_permit_number"
                                                       value="<?php echo  htmlspecialchars($establishment['business_permit_number'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Permit Issue Date</label>
                                                <input type="date" class="form-control" name="permit_issue_date"
                                                       value="<?php echo  htmlspecialchars($establishment['permit_issue_date'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-0">
                                                <label class="form-label">Permit Expiry Date</label>
                                                <input type="date" class="form-control" name="permit_expiry_date"
                                                       value="<?php echo  htmlspecialchars($establishment['permit_expiry_date'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Actions -->
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <div class="text-muted">
                                        <small>
                                            <span class="text-danger">*</span> Required fields<br>
                                            Last updated: <?php echo  date('F d, Y g:i A', strtotime($establishment['updated_at'] ?? $establishment['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="/views/establishments/view.php?id=<?php echo  $establishmentId ?>" class="btn btn-outline-secondary me-2">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
