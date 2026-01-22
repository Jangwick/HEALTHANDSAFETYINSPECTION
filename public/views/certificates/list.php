<?php
/**
 * Health & Safety Inspection System
 * Certificates List View - Modern Tailwind Layout
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';

try {
    $db = Database::getConnection();
    
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 15;
    $offset = ($page - 1) * $perPage;
    
    // Filters
    $status = $_GET['status'] ?? '';
    $certificateType = $_GET['certificate_type'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "c.status = ?";
        $params[] = $status;
    }
    
    if ($certificateType) {
        $where[] = "c.certificate_type = ?";
        $params[] = $certificateType;
    }
    
    if ($search) {
        $where[] = "(e.name LIKE ? OR c.certificate_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Role-based filtering: Establishment Owners only see their own certificates
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'establishment_owner') {
        $where[] = "e.owner_user_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $countSql = "SELECT COUNT(*) FROM certificates c LEFT JOIN establishments e ON c.establishment_id = e.establishment_id $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalCertificates = (int)$stmt->fetchColumn();
    $totalPages = ceil($totalCertificates / $perPage);
    
    // Get certificates
    $sql = "
        SELECT c.*, 
               e.name as establishment_name,
               e.type as establishment_type,
               i.reference_number as inspection_reference,
               CONCAT(u.first_name, ' ', u.last_name) as issued_by_name
        FROM certificates c
        LEFT JOIN establishments e ON c.establishment_id = e.establishment_id
        LEFT JOIN inspections i ON c.inspection_id = i.inspection_id
        LEFT JOIN users u ON c.issued_by = u.user_id
        $whereClause
        ORDER BY c.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while loading certificates.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates - Health & Safety System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style type="text/tailwindcss">
        @layer base {
            html { font-size: 105%; }
            body { @apply text-slate-200; }
            h1, h2, h3, h4, h5, h6 { @apply font-bold tracking-tight text-white; }
        }
    </style>
</head>
<body class="bg-[#0b0c10] font-sans antialiased text-base overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'certificates';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-20 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-xl font-bold text-slate-800">Compliance Certificates</h1>
                <div class="flex items-center space-x-4">
                    <a href="/certificates/issue" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center shadow-sm transition-all active:scale-95">
                        <i class="fas fa-plus mr-2"></i> Issue New Certificate
                    </a>
                </div>
            </header>

            <!-- Scrollable Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8 text-base">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo  htmlspecialchars($search) ?>" placeholder="Number or Establishment..." 
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Type</label>
                            <select name="certificate_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Types</option>
                                <option value="food_safety" <?php echo  $certificateType === 'food_safety' ? 'selected' : '' ?>>Food Safety</option>
                                <option value="building_safety" <?php echo  $certificateType === 'building_safety' ? 'selected' : '' ?>>Building Safety</option>
                                <option value="fire_safety" <?php echo  $certificateType === 'fire_safety' ? 'selected' : '' ?>>Fire Safety</option>
                                <option value="sanitation" <?php echo  $certificateType === 'sanitation' ? 'selected' : '' ?>>Sanitation</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                            <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo  $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="expired" <?php echo  $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                                <option value="revoked" <?php echo  $status === 'revoked' ? 'selected' : '' ?>>Revoked</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors flex items-center justify-center">
                                <i class="fas fa-filter mr-2"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Results Table -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <?php if (!empty($certificates)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 border-b border-slate-200 text-sm font-black text-slate-700 uppercase tracking-widest">
                                        <th class="px-6 py-4">Certificate ID / Est.</th>
                                        <th class="px-6 py-4">Type</th>
                                        <th class="px-6 py-4">Validity</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($certificates as $cert): ?>
                                        <tr class="hover:bg-slate-100 transition-colors">
                                            <td class="px-6 py-5">
                                                <div class="font-black text-slate-900 font-mono text-sm"><?php echo  htmlspecialchars($cert['certificate_number']) ?></div>
                                                <div class="text-base text-slate-700 font-bold"><?php echo  htmlspecialchars($cert['establishment_name']) ?></div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <span class="text-base text-slate-700 font-black italic"><?php echo  ucwords(str_replace('_', ' ', $cert['certificate_type'])) ?></span>
                                            </td>
                                            <td class="px-6 py-5">
                                                <div class="text-sm text-slate-600 font-medium">Issued: <?php echo  date('M d, Y', strtotime($cert['issue_date'])) ?></div>
                                                <div class="text-sm font-black text-slate-900 italic">Expires: <?php echo  date('M d, Y', strtotime($cert['expiry_date'])) ?></div>
                                            </td>
                                            <td class="px-6 py-5">
                                                <?php
                                                    $statusClasses = [
                                                        'active' => 'bg-emerald-200 text-emerald-900 border border-emerald-300',
                                                        'expired' => 'bg-rose-200 text-rose-900 border border-rose-300',
                                                        'revoked' => 'bg-slate-200 text-slate-900 border border-slate-300',
                                                        'suspended' => 'bg-amber-200 text-amber-900 border border-amber-300'
                                                    ];
                                                    $class = $statusClasses[$cert['status']] ?? 'bg-slate-200 text-slate-900';
                                                ?>
                                                <span class="px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-widest <?php echo  $class ?>">
                                                    <?php echo  $cert['status'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="/views/certificates/view.php?id=<?php echo  $cert['certificate_id'] ?>" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="View"><i class="fas fa-eye"></i></a>
                                                    <a href="/views/certificates/certificate.php?id=<?php echo  $cert['certificate_id'] ?>" target="_blank" class="p-2 text-slate-400 hover:text-emerald-600 transition-colors" title="Download"><i class="fas fa-file-pdf"></i></a>
                                                    <?php if ($cert['status'] === 'active'): ?>
                                                        <button onclick="confirmRevoke(<?php echo  $cert['certificate_id'] ?>)" class="p-2 text-slate-400 hover:text-rose-600 transition-colors" title="Revoke"><i class="fas fa-ban"></i></button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                                <span class="text-xs text-slate-500 italic">Page <?php echo  $page ?> of <?php echo  $totalPages ?></span>
                                <div class="flex space-x-1">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo  $i ?>&status=<?php echo  $status ?>&type=<?php echo  $certificateType ?>&search=<?php echo  urlencode($search) ?>" 
                                            class="px-3 py-1 rounded border <?php echo  $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' ?> text-xs font-bold transition-all">
                                            <?php echo  $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="p-12 text-center italic">
                            <i class="fas fa-certificate text-4xl text-slate-200 mb-4"></i>
                            <h3 class="text-lg font-bold text-slate-800">No certificates found</h3>
                            <p class="text-slate-500">Adjust your search or filters to see results.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        function confirmRevoke(id) {
            if (confirm('Are you sure you want to revoke this certificate?')) {
                // We would normally call the revoke API here
                window.location.href = '/views/certificates/revoke.php?id=' + id;
            }
        }
    </script>
</body>
</html>
