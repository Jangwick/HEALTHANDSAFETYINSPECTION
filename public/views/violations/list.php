<?php
/**
 * Health & Safety Inspection System
 * Violations List View
 */

declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: /views/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/database.php';
$db = Database::getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? '';
$severity = $_GET['severity'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where = [];
$params = [];

if ($status) {
    $where[] = "v.status = ?";
    $params[] = $status;
}

if ($severity) {
    $where[] = "v.severity = ?";
    $params[] = $severity;
}

if ($search) {
    $where[] = "(e.name LIKE ? OR v.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM violations v LEFT JOIN establishments e ON v.establishment_id = e.establishment_id $whereClause");
$countStmt->execute($params);
$totalViolations = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalViolations / $perPage);

// Get violations
$sql = "
    SELECT v.*, e.name as establishment_name, i.reference_number as inspection_ref
    FROM violations v
    LEFT JOIN establishments e ON v.establishment_id = e.establishment_id
    LEFT JOIN inspections i ON v.inspection_id = i.inspection_id
    $whereClause
    ORDER BY v.reported_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$violations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Violations - Health & Safety System</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-900 overflow-hidden">
    <div class="flex h-screen">
        <!-- Sidebar Navigation -->
        <?php 
            $activePage = 'violations';
            include __DIR__ . '/../partials/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top Navbar -->
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-8 shrink-0">
                <h1 class="text-xl font-bold text-slate-800">Safety Violations</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tracking <?= $totalViolations ?> issues</span>
                </div>
            </header>

            <!-- Scrollable Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Establishment or issue..." 
                                class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Severity</label>
                            <select name="severity" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Severities</option>
                                <option value="minor" <?= $severity === 'minor' ? 'selected' : '' ?>>Minor</option>
                                <option value="major" <?= $severity === 'major' ? 'selected' : '' ?>>Major</option>
                                <option value="critical" <?= $severity === 'critical' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                            <select name="status" class="w-full bg-slate-50 border border-slate-200 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="">All Statuses</option>
                                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded-lg text-sm transition-colors flex items-center justify-center">
                                <i class="fas fa-filter mr-2"></i> Filter Results
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Results -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <?php if (!empty($violations)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        <th class="px-6 py-4">Violation / Establishment</th>
                                        <th class="px-6 py-4">Severity</th>
                                        <th class="px-6 py-4">Deadline</th>
                                        <th class="px-6 py-4">Status</th>
                                        <th class="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($violations as $v): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-6 py-4 italic">
                                                <div class="text-sm font-bold text-slate-900 mb-0.5 line-clamp-1"><?= htmlspecialchars($v['description']) ?></div>
                                                <div class="text-xs text-slate-500 font-medium tracking-tight"><?= htmlspecialchars($v['establishment_name']) ?></div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $sevClasses = [
                                                        'minor' => 'text-blue-600 bg-blue-50',
                                                        'major' => 'text-amber-600 bg-amber-50',
                                                        'critical' => 'text-rose-600 bg-rose-50 font-black'
                                                    ];
                                                    $class = $sevClasses[$v['severity']] ?? 'text-slate-600 bg-slate-50';
                                                ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $class ?>">
                                                    <?= $v['severity'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-xs font-bold <?= (strtotime($v['corrective_action_deadline']) < time() && $v['status'] != 'resolved') ? 'text-rose-600' : 'text-slate-600' ?>">
                                                    <?= $v['corrective_action_deadline'] ? date('M d, Y', strtotime($v['corrective_action_deadline'])) : 'N/A' ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                    $statusClasses = [
                                                        'open' => 'bg-rose-100 text-rose-700',
                                                        'in_progress' => 'bg-amber-100 text-amber-700 font-bold',
                                                        'resolved' => 'bg-emerald-100 text-emerald-700',
                                                        'waived' => 'bg-slate-100 text-slate-700'
                                                    ];
                                                    $class = $statusClasses[$v['status']] ?? 'bg-slate-100 text-slate-700';
                                                ?>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $class ?>">
                                                    <?= str_replace('_', ' ', $v['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <a href="/views/violations/view.php?id=<?= $v['violation_id'] ?>" class="text-slate-400 hover:text-blue-600 p-2"><i class="fas fa-eye"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-check-circle text-4xl text-emerald-100 mb-4"></i>
                            <h3 class="text-lg font-bold text-slate-800">No violations found</h3>
                            <p class="text-slate-500 italic">Excellent! No safety issues currently recorded.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
