<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getConnection();
        
        // Validate required fields
        $required = ['name', 'type', 'owner_name', 'owner_phone', 'address_street', 'address_barangay', 'address_city'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Insert establishment
        $stmt = $db->prepare("
            INSERT INTO establishments (
                name, type, owner_name, owner_email, owner_phone,
                address_street, address_barangay, address_city, address_province,
                business_permit_number, permit_issue_date, permit_expiry_date,
                capacity, operating_hours, description,
                compliance_status, created_by
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                'pending', ?
            )
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
            $_SESSION['user_id']
        ]);
        
        $establishmentId = $db->lastInsertId();
        
        // Redirect to view page
        header("Location: /views/establishments/view.php?id=$establishmentId&success=1");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Establishment creation error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Establishment - Health & Safety Inspection System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section { background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .section-title { color: #495057; font-weight: 600; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #dee2e6; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">
                <i class="bi bi-shield-check"></i> Health & Safety Inspection
            </a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['first_name'] ?? '') ?> <?= htmlspecialchars($_SESSION['last_name'] ?? '') ?>
                </span>
                <a class="nav-link text-white" href="/views/auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-plus-circle"></i> Add New Establishment</h2>
                <p class="text-muted mb-0">Register a new establishment for health & safety inspections</p>
            </div>
            <a href="/views/establishments/list.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

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
                                           placeholder="e.g., Juan's Restaurant">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="restaurant">Restaurant</option>
                                        <option value="school">School</option>
                                        <option value="hospital">Hospital/Clinic</option>
                                        <option value="hotel">Hotel/Resort</option>
                                        <option value="market">Market</option>
                                        <option value="factory">Factory/Manufacturing</option>
                                        <option value="office">Office Building</option>
                                        <option value="salon">Salon/Spa</option>
                                        <option value="gym">Gym/Fitness Center</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Capacity (persons)</label>
                                    <input type="number" class="form-control" name="capacity" min="0"
                                           placeholder="e.g., 50">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Operating Hours</label>
                                    <input type="text" class="form-control" name="operating_hours"
                                           placeholder="e.g., Mon-Sat: 8:00 AM - 8:00 PM">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description/Notes</label>
                            <textarea class="form-control" name="description" rows="3"
                                      placeholder="Additional information about the establishment..."></textarea>
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
                                           placeholder="e.g., Juan Dela Cruz">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Owner Phone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" name="owner_phone" required
                                           placeholder="e.g., 09171234567">
                                </div>
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Owner Email</label>
                            <input type="email" class="form-control" name="owner_email"
                                   placeholder="e.g., owner@example.com">
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="form-section">
                        <h5 class="section-title"><i class="bi bi-geo-alt"></i> Address Information</h5>
                        <div class="mb-3">
                            <label class="form-label">Street Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address_street" required
                                   placeholder="e.g., 123 Rizal Street">
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Barangay <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="address_barangay" required
                                           placeholder="e.g., Poblacion">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">City/Municipality <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="address_city" required
                                           placeholder="e.g., Quezon City">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Province</label>
                                    <input type="text" class="form-control" name="address_province"
                                           placeholder="e.g., Metro Manila">
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
                                           placeholder="e.g., BP-2025-00123">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Permit Issue Date</label>
                                    <input type="date" class="form-control" name="permit_issue_date">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-0">
                                    <label class="form-label">Permit Expiry Date</label>
                                    <input type="date" class="form-control" name="permit_expiry_date">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="text-muted">
                            <small><span class="text-danger">*</span> Required fields</small>
                        </div>
                        <div>
                            <a href="/views/establishments/list.php" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Establishment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
