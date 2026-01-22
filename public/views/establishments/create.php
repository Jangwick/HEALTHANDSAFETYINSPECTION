<?php
declare(strict_types=1);
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
                throw new Exception("Field '$field' is required for the Institutional Registry");
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
        header("Location: /establishments/view?id=$establishmentId&success=1");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Establishment registration error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutional Registration - Health & Safety Insight</title>
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
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">
            <!-- Institutional Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-10 shrink-0 z-20">
                <div class="flex items-center space-x-4">
                    <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-800">Establishment Registration</h1>
                    <div class="px-3 py-1 bg-blue-50 border border-blue-100 rounded-full">
                        <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest leading-none">Entity Protocol</span>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/establishments" class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 transition-all shadow-sm">
                        <i class="fas fa-arrow-left opacity-50"></i>
                        Back to Registry
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto bg-[#f8fafc] p-10 font-sans">
                <div class="max-w-4xl mx-auto space-y-8">
                    <!-- Page Intro -->
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 tracking-tight">Register New Entity</h2>
                        <p class="text-slate-500 mt-2 font-medium">Provision new commercial or public institutional records into the master registry.</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="mb-8 p-4 bg-rose-50 border border-rose-100 rounded-xl flex items-center text-rose-700 text-xs font-bold uppercase tracking-wider">
                            <i class="fas fa-exclamation-circle mr-3"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8 pb-12">
                        <!-- Entity Core Details -->
                        <div class="card relative p-8">
                            <div class="absolute top-0 left-0 w-full h-1 bg-blue-700"></div>
                            <div class="mb-8 flex justify-between items-center border-b border-slate-50 pb-4">
                                <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-building mr-2 text-blue-700"></i> Core Institutional Parameters
                                </h2>
                                <span class="text-[9px] font-bold text-slate-300 italic">Registry Section 1.0</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="form-label">Name of Institution / Business <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name" required placeholder="Official Registered Name" class="form-input">
                                </div>
                                
                                <div>
                                    <label class="form-label">Establishment Classification <span class="text-rose-500">*</span></label>
                                    <select name="type" required class="form-input appearance-none">
                                        <option value="">Select Category</option>
                                        <option value="restaurant">Restaurant / Culinary</option>
                                        <option value="school">Educational Institution</option>
                                        <option value="hospital">Medical Facility</option>
                                        <option value="hotel">Hospitality / Resort</option>
                                        <option value="market">Public Commerce Sector</option>
                                        <option value="office">Corporate / Administration</option>
                                        <option value="factory">Industrial / Manufacturing</option>
                                        <option value="other">General Commercial</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Permit Identifier (BP No.)</label>
                                    <input type="text" name="business_permit_number" placeholder="BP-202X-XXXX" class="form-input font-mono">
                                </div>

                                <div>
                                    <label class="form-label">Occupancy Capacity</label>
                                    <input type="number" name="capacity" placeholder="Maximum Persons" class="form-input">
                                </div>

                                <div>
                                    <label class="form-label">Operational Hours</label>
                                    <input type="text" name="operating_hours" placeholder="e.g. 08:00 - 17:00" class="form-input">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="form-label">Operational Scope / Dossier Notes</label>
                                    <textarea name="description" rows="3" placeholder="Brief overview of institutional functions..." class="form-input resize-none"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Ownership Structure -->
                        <div class="card p-8">
                            <div class="mb-8 flex justify-between items-center border-b border-slate-50 pb-4">
                                <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-user-tie mr-2 text-emerald-600"></i> Legal Representation
                                </h2>
                                <span class="text-[9px] font-bold text-slate-300 italic">Registry Section 2.0</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="form-label">Registered Principal <span class="text-rose-500">*</span></label>
                                    <input type="text" name="owner_name" required placeholder="Full Legal Name" class="form-input">
                                </div>
                                
                                <div>
                                    <label class="form-label">Direct Contact Number <span class="text-rose-500">*</span></label>
                                    <input type="tel" name="owner_phone" required placeholder="Official Phone" class="form-input font-mono text-blue-700">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="form-label">Formal Correspondence Email</label>
                                    <input type="email" name="owner_email" placeholder="official@domain.com" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Geospatial Positioning -->
                        <div class="card p-8">
                            <div class="mb-8 flex justify-between items-center border-b border-slate-50 pb-4">
                                <h2 class="text-[11px] font-black text-slate-800 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-amber-600"></i> Physical Location
                                </h2>
                                <span class="text-[9px] font-bold text-slate-300 italic">Registry Section 3.0</span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-3">
                                    <label class="form-label">Street / Site Address <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_street" required placeholder="Building No. / Street" class="form-input">
                                </div>

                                <div>
                                    <label class="form-label">Barangay <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_barangay" required placeholder="District" class="form-input">
                                </div>

                                <div>
                                    <label class="form-label">City / Municipality <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_city" required placeholder="LGU Jurisdiction" class="form-input">
                                </div>

                                <div>
                                    <label class="form-label">Province</label>
                                    <input type="text" name="address_province" placeholder="State/Region" class="form-input">
                                </div>
                            </div>
                        </div>

                        <!-- Submission Interface -->
                        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
                            <div class="text-[9px] font-bold text-slate-400 italic">
                                Registry logs are permanent. Ensure all institutional data is verified.
                            </div>
                            <div class="flex items-center space-x-6">
                                <a href="/establishments" class="text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors">
                                    Discard Entry
                                </a>
                                <button type="submit" class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-3 rounded-lg text-xs font-bold uppercase tracking-widest transition-all shadow-md shadow-blue-900/10">
                                    <i class="fas fa-check-double mr-2"></i> Commit to Registry
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
