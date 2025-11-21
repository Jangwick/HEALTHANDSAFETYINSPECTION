<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Get user info
$stmt = $db->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN user_roles ur ON u.user_id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.role_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $establishment_id = $_POST['establishment_id'] ?? '';
    $inspection_type = $_POST['inspection_type'] ?? '';
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $assigned_inspector_id = $_POST['assigned_inspector_id'] ?? $_SESSION['user_id'];
    $priority = $_POST['priority'] ?? 'medium';
    $notes = $_POST['notes'] ?? '';
    
    if ($establishment_id && $inspection_type && $scheduled_date) {
        try {
            $stmt = $db->prepare("
                INSERT INTO inspections (
                    establishment_id, inspection_type, scheduled_date,
                    assigned_inspector_id, priority, status, notes,
                    created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
            ");
            
            $stmt->execute([
                $establishment_id,
                $inspection_type,
                $scheduled_date,
                $assigned_inspector_id,
                $priority,
                $notes,
                $_SESSION['user_id']
            ]);
            
            $inspection_id = $db->lastInsertId();
            $success = "Inspection created successfully! ID: #$inspection_id";
            
            // Redirect after 2 seconds
            header("refresh:2;url=/views/inspections/view.php?id=$inspection_id");
        } catch (PDOException $e) {
            $error = "Error creating inspection: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
    }
}

// Get establishments
$establishments = $db->query("SELECT establishment_id, name, type, address_street, address_barangay, address_city FROM establishments WHERE compliance_status != 'revoked' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get inspectors
$inspectors = $db->query("
    SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as full_name, i.specializations
    FROM users u
    INNER JOIN user_roles ur ON u.user_id = ur.user_id
    INNER JOIN roles r ON ur.role_id = r.role_id
    LEFT JOIN inspectors i ON u.user_id = i.user_id
    WHERE r.role_name IN ('inspector', 'senior_inspector', 'admin', 'super_admin')
    AND u.status = 'active'
    ORDER BY u.first_name
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Inspection - Health & Safety Inspection System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 2rem; }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; }
        
        .nav { background: white; border-bottom: 1px solid #e5e7eb; padding: 0 2rem; }
        .nav-content { max-width: 1400px; margin: 0 auto; display: flex; gap: 2rem; }
        .nav-link { padding: 1rem 0; color: #6b7280; text-decoration: none; border-bottom: 2px solid transparent; }
        .nav-link:hover, .nav-link.active { color: #667eea; border-bottom-color: #667eea; }
        
        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .page-header { margin-bottom: 2rem; }
        .page-title { font-size: 2rem; color: #1f2937; margin-bottom: 0.5rem; }
        .breadcrumb { color: #6b7280; font-size: 0.875rem; }
        .breadcrumb a { color: #667eea; text-decoration: none; }
        
        .form-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        
        .form-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        .form-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        
        .form-group { display: flex; flex-direction: column; }
        .form-label { font-weight: 600; margin-bottom: 0.5rem; color: #374151; }
        .form-label-required::after { content: ' *'; color: #ef4444; }
        .form-input, .form-select, .form-textarea { padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .form-textarea { resize: vertical; min-height: 100px; font-family: inherit; }
        .form-help { font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem; }
        
        .establishment-info { background: #f9fafb; padding: 1rem; border-radius: 6px; margin-top: 0.5rem; display: none; }
        .establishment-info.show { display: block; }
        
        .form-actions { display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
        .btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; border: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: white; color: #374151; border: 1px solid #d1d5db; }
        .btn-secondary:hover { background: #f9fafb; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">🏥 Health & Safety Inspection System</div>
            <div class="user-info">
                <div><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                <a href="/views/auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="nav">
        <div class="nav-content">
            <a href="/dashboard.php" class="nav-link">Dashboard</a>
            <a href="/views/inspections/list.php" class="nav-link active">Inspections</a>
            <a href="/views/establishments/list.php" class="nav-link">Establishments</a>
            <a href="/views/violations/list.php" class="nav-link">Violations</a>
            <a href="/views/certificates/list.php" class="nav-link">Certificates</a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Create New Inspection</h1>
            <div class="breadcrumb">
                <a href="/dashboard.php">Dashboard</a> / 
                <a href="/views/inspections/list.php">Inspections</a> / 
                New Inspection
            </div>
        </div>
        
        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?><br>
                    <small>Redirecting to inspection details...</small>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-grid">
                    <!-- Establishment Selection -->
                    <div class="form-group">
                        <label class="form-label form-label-required">Establishment</label>
                        <select name="establishment_id" id="establishment_id" class="form-select" required onchange="showEstablishmentInfo(this.value)">
                            <option value="">Select an establishment</option>
                            <?php foreach ($establishments as $est): ?>
                                <option value="<?php echo $est['establishment_id']; ?>" 
                                        data-type="<?php echo htmlspecialchars($est['type']); ?>"
                                        data-address="<?php echo htmlspecialchars($est['address_street'] . ', ' . $est['address_barangay'] . ', ' . $est['address_city']); ?>">
                                    <?php echo htmlspecialchars($est['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="establishment-info" class="establishment-info"></div>
                    </div>
                    
                    <!-- Inspection Type -->
                    <div class="form-group">
                        <label class="form-label form-label-required">Inspection Type</label>
                        <select name="inspection_type" class="form-select" required>
                            <option value="">Select inspection type</option>
                            <option value="food_safety">Food Safety</option>
                            <option value="building_safety">Building Safety</option>
                            <option value="workplace_safety">Workplace Safety</option>
                            <option value="fire_safety">Fire Safety</option>
                            <option value="sanitation">Sanitation</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-grid-2">
                    <!-- Scheduled Date -->
                    <div class="form-group">
                        <label class="form-label form-label-required">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-input" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                        <span class="form-help">Inspection cannot be scheduled in the past</span>
                    </div>
                    
                    <!-- Priority -->
                    <div class="form-group">
                        <label class="form-label form-label-required">Priority</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <!-- Assigned Inspector -->
                <div class="form-group">
                    <label class="form-label form-label-required">Assigned Inspector</label>
                    <select name="assigned_inspector_id" class="form-select" required>
                        <?php foreach ($inspectors as $inspector): ?>
                            <option value="<?php echo $inspector['user_id']; ?>"
                                    <?php echo $inspector['user_id'] == $_SESSION['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($inspector['full_name']); ?>
                                <?php if ($inspector['specializations']): ?>
                                    - (<?php echo htmlspecialchars(implode(', ', json_decode($inspector['specializations'], true))); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Notes -->
                <div class="form-group">
                    <label class="form-label">Notes / Instructions</label>
                    <textarea name="notes" class="form-textarea" 
                              placeholder="Add any special notes or instructions for this inspection..."></textarea>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Inspection</button>
                    <a href="/views/inspections/list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showEstablishmentInfo(establishmentId) {
            const select = document.getElementById('establishment_id');
            const infoDiv = document.getElementById('establishment-info');
            
            if (!establishmentId) {
                infoDiv.classList.remove('show');
                return;
            }
            
            const option = select.options[select.selectedIndex];
            const type = option.getAttribute('data-type');
            const address = option.getAttribute('data-address');
            
            infoDiv.innerHTML = `
                <strong>Business Type:</strong> ${type}<br>
                <strong>Address:</strong> ${address}
            `;
            infoDiv.classList.add('show');
        }
    </script>
</body>
</html>
