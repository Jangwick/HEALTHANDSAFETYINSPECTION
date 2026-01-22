<?php
// Session already started by index.php
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
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
        header("Location: /establishments/view?id=$establishmentId&success=1");
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
    <title>Add Establishment - Health & Safety System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#0b0c10] font-sans antialiased text-slate-200 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'establishments';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-[#0f1115] border-b border-white/5 h-20 flex items-center justify-between px-8 shrink-0">
                <div class="flex items-center space-x-4">
                    <a href="/establishments" class="text-slate-500 hover:text-white transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h1 class="text-2xl font-bold text-white tracking-tight">Register New Establishment</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-xs font-black text-slate-500 uppercase tracking-widest hidden md:block">
                        <i class="fas fa-user-circle mr-2"></i> <?php echo  htmlspecialchars($_SESSION['first_name'] ?? '') ?>
                    </span>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8 bg-[#0b0c10] text-base">
                <div class="max-w-4xl mx-auto">
                    <?php if (!empty($error)): ?>
                        <div class="mb-8 p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl flex items-center text-rose-500 animate-pulse">
                            <i class="fas fa-exclamation-circle mr-3"></i>
                            <span class="text-sm font-bold uppercase tracking-wider"><?php echo  htmlspecialchars($error) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-8 pb-12">
                        <!-- Basic Information Section -->
                        <div class="bg-[#15181e] rounded-3xl shadow-2xl border border-white/5 overflow-hidden">
                            <div class="px-8 py-6 bg-white/[0.02] border-b border-white/5 flex justify-between items-center">
                                <h2 class="text-xs font-black text-blue-500 uppercase tracking-[0.2em] flex items-center">
                                    <i class="fas fa-info-circle mr-2 text-sm"></i> Basic Information
                                </h2>
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">General Details</span>
                            </div>
                            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Establishment Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="name" required placeholder="e.g. Grand Central Hotel"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all group hover:border-white/20">
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Business Type <span class="text-rose-500">*</span></label>
                                    <div class="relative group">
                                        <select name="type" required
                                            class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white appearance-none cursor-pointer focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                            <option value="">Select Type</option>
                                            <option value="restaurant">Restaurant</option>
                                            <option value="school">School</option>
                                            <option value="hospital">Hospital / Clinic</option>
                                            <option value="hotel">Hotel / Resort</option>
                                            <option value="market">Public Market</option>
                                            <option value="office">Office Building</option>
                                            <option value="factory">Factory</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 pr-5 flex items-center pointer-events-none text-slate-500 group-hover:text-white transition-colors">
                                            <i class="fas fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Business Permit Number</label>
                                    <input type="text" name="business_permit_number" placeholder="BP-2023-XXXX"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all font-mono hover:border-white/20">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Capacity (Persons)</label>
                                    <input type="number" name="capacity" placeholder="e.g. 50"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Operating Hours</label>
                                    <input type="text" name="operating_hours" placeholder="e.g. 8:00 AM - 10:00 PM"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Description / Notes</label>
                                    <textarea name="description" rows="3" placeholder="Additional details about the establishment..."
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all resize-none hover:border-white/20"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Owner Information Section -->
                        <div class="bg-[#15181e] rounded-3xl shadow-2xl border border-white/5 overflow-hidden">
                            <div class="px-8 py-6 bg-white/[0.02] border-b border-white/5 flex justify-between items-center">
                                <h2 class="text-xs font-black text-emerald-500 uppercase tracking-[0.2em] flex items-center">
                                    <i class="fas fa-user-tie mr-2 text-sm"></i> Owner Details
                                </h2>
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Contact Person</span>
                            </div>
                            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Owner Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="owner_name" required placeholder="Full Legal Name"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Contact Number <span class="text-rose-500">*</span></label>
                                    <input type="tel" name="owner_phone" required placeholder="0917-XXX-XXXX"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Email Address</label>
                                    <input type="email" name="owner_email" placeholder="owner@example.com"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <div class="bg-[#15181e] rounded-3xl shadow-2xl border border-white/5 overflow-hidden">
                            <div class="px-8 py-6 bg-white/[0.02] border-b border-white/5 flex justify-between items-center">
                                <h2 class="text-xs font-black text-amber-500 uppercase tracking-[0.2em] flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-sm"></i> Location Details
                                </h2>
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Physical Address</span>
                            </div>
                            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Street Address <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_street" required placeholder="House No., Building, Street Name"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">Barangay <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_barangay" required placeholder="Barangay Name"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2.5 ml-1">City / Municipality <span class="text-rose-500">*</span></label>
                                    <input type="text" name="address_city" required placeholder="Legazpi City"
                                        class="w-full px-5 py-4 bg-[#0b0c10] border border-white/10 rounded-2xl text-white placeholder:text-slate-600 focus:ring-2 focus:ring-blue-500/50 outline-none transition-all hover:border-white/20">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-between pt-4">
                            <div class="text-[9px] font-black text-slate-600 uppercase tracking-[0.2em] italic">
                                * fields are strictly required for registration
                            </div>
                            <div class="flex space-x-4">
                                <a href="/establishments" class="px-8 py-4 text-sm font-black text-slate-500 uppercase tracking-widest hover:text-white transition-colors">
                                    Cancel
                                </a>
                                <button type="submit" class="px-10 py-4 bg-blue-600 hover:bg-blue-700 text-white font-black text-sm uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-blue-900/20 transition-all active:scale-95 flex items-center group">
                                    <i class="fas fa-save mr-2 group-hover:scale-110 transition-transform"></i> Save Establishment
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
