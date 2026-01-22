<?php
declare(strict_types=1);
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
                throw new Exception("Field '$field' is required for the Institutional Registry");
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
        
        $success = "Establishment record successfully updated in registry.";
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
    <title>Edit Registry Entry - Health & Safety Insight</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 100%; }
            body { @apply text-slate-700 bg-slate-50; }
            h1, h2, h3 { @apply font-bold tracking-tight text-slate-900; }
            .form-input { 
                @apply w-full px-4 py-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-700 
                placeholder:text-slate-400 focus:ring-2 focus:ring-blue-700/10 focus:border-blue-700 
                outline-none transition-all shadow-sm;
            }
            .form-label { @apply block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1; }
            .card { @apply bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden; }
        }
    </style>
</head>
<body class="font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'establishments';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden text-base">
            <!-- Institutional Header -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0 z-10">
                <div class="flex items-center space-x-4">
                    <a href="/establishments/view?id=<?= $establishmentId ?>" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-sm font-bold text-slate-800 tracking-tight uppercase">Registry Revision</h1>
                    <div class="h-4 w-px bg-slate-200"></div>
                    <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest italic"><?= htmlspecialchars($establishment['name']) ?></span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="/establishments/view?id=<?= $establishmentId ?>" class="text-[10px] font-black text-slate-400 hover:text-blue-700 uppercase tracking-[0.2em] transition-colors">
                        View Dossier
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-8 bg-slate-50">
                <div class="max-w-4xl mx-auto">
                    <?php if (!empty($error)): ?>
                        <div class="mb-8 p-4 bg-rose-50 border border-rose-100 rounded-xl flex items-center text-rose-700 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-exclamation-circle mr-3"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center text-emerald-700 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-check-circle mr-3"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8 pb-12">
                        <!-- Entity Core Details -->
                        <div class="card relative p-8">
                            <div class="absolute top-0 left-0 w-full h-1 bg-amber-500"></div>
                            <div class="mb-8 flex justify-between items-center border-b border-slate-50 pb-4">
                                <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-building mr-2 text-amber-500"></i> Amend Core Parameters
                                </h2>
                                <span class="text-[9px] font-bold text-slate-300 italic">Registry Section 1.0</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="form-label">Institution Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name" required value="<?= htmlspecialchars($establishment['name']) ?>" class="form-input">
                                </div>
                                
                                <div>
                                    <label class="form-label">Classification <span class="text-rose-500">*</span></label>
                                    <select name="type" required class="form-input appearance-none">
                                        <?php
                                            $types = [
                                                'restaurant' => 'Restaurant / Culinary',
                                                'school' => 'Educational Institution',
                                                'hospital' => 'Medical Facility',
                                                'hotel' => 'Hospitality / Resort',
                                                'market' => 'Public Commerce',
                                                'office' => 'Corporate / Admin',
                                                'factory' => 'Industrial / Mfg',
                                                'other' => 'General Commercial'
                                            ];
                                            foreach($types as $val => $label):
                                        ?>
                                            <option value="<?= $val ?>" <?= $establishment['type'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Compliance Mandate <span class="text-rose-500">*</span></label>
                                    <select name="compliance_status" required class="form-input appearance-none font-bold italic">
                                        <option value="compliant" <?= $establishment['compliance_status'] === 'compliant' ? 'selected' : '' ?>>Compliant</option>
                                        <option value="non_compliant" <?= $establishment['compliance_status'] === 'non_compliant' ? 'selected' : '' ?>>Non-Compliant</option>
                                        <option value="pending" <?= $establishment['compliance_status'] === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                                        <option value="suspended" <?= $establishment['compliance_status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Permit Identifier</label>
                                    <input type="text" name="business_permit_number" value="<?= htmlspecialchars($establishment['business_permit_number'] ?? '') ?>" class="form-input font-mono">
                                </div>

                                <div>
                                    <label class="form-label">Occupancy Capacity</label>
                                    <input type="number" name="capacity" value="<?= htmlspecialchars($establishment['capacity'] ?? '') ?>" class="form-input">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="form-label">Abridged Operational Scope</label>
                                    <textarea name="description" rows="3" class="form-input resize-none"><?= htmlspecialchars($establishment['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Ownership Structure -->
                        <div class="card p-8">
                            <div class="mb-8 flex justify-between items-center border-b border-slate-50 pb-4">
                                <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-user-tie mr-2 text-emerald-600"></i> Legal Records
                                </h2>
                                <span class="text-[9px] font-bold text-slate-300 italic">Registry Section 2.0</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="form-label">Registered Principal <span class="text-rose-500">*</span></label>
                                    <input type="text" name="owner_name" required value="<?= htmlspecialchars($establishment['owner_name']) ?>" class="form-input">
                                </div>
                                
                                <div>
                                    <label class="form-label">Direct Line <span class="text-rose-500">*</span></label>
                                    <input type="tel" name="owner_phone" required value="<?= htmlspecialchars($establishment['owner_phone']) ?>" class="form-input font-mono text-blue-700">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="form-label">Institutional Email</label>
                                    <input type="email" name="owner_email" value="<?= htmlspecialchars($establishment['owner_email'] ?? '') ?>" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Physical Location -->
                        <div class="card p-8">
                            <div class="mb-8 flex justify-between items-center border-b border-slate-50 pb-4">
                                <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-rose-500"></i> Geospatial Entry
                                </h2>
                                <span class="text-[9px] font-bold text-slate-300 italic">Registry Section 3.0</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-3">
                                    <label class="form-label">Street / Site Address <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_street" required value="<?= htmlspecialchars($establishment['address_street']) ?>" class="form-input">
                                </div>

                                <div>
                                    <label class="form-label">Barangay <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_barangay" required value="<?= htmlspecialchars($establishment['address_barangay']) ?>" class="form-input">
                                </div>

                                <div>
                                    <label class="form-label">City / LGU <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_city" required value="<?= htmlspecialchars($establishment['address_city']) ?>" class="form-input">
                                </div>

                                <div>
                                    <label class="form-label">Province</label>
                                    <input type="text" name="address_province" value="<?= htmlspecialchars($establishment['address_province'] ?? '') ?>" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Update Interface -->
                        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
                            <div class="text-[9px] font-bold text-slate-400 italic uppercase">
                                <i class="fas fa-history mr-2"></i> Last audit update: <?= date('M d, Y H:i', strtotime($establishment['updated_at'] ?? $establishment['created_at'])) ?>
                            </div>
                            <div class="flex items-center space-x-6">
                                <a href="/establishments/view?id=<?= $establishmentId ?>" class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors">
                                    Revert Changes
                                </a>
                                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white px-8 py-3 rounded-lg text-xs font-bold uppercase tracking-widest transition-all shadow-md shadow-amber-900/10">
                                    <i class="fas fa-save mr-2"></i> Update Registry Record
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
