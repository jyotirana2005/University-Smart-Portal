<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hod') {
    header('Location: login.php');
    exit();
}
$hod_id = $_SESSION['user_id'];
$error = "";
$success_message = "";

// Handle status update (approve/reject)
if (isset($_POST['action']) && isset($_POST['grievance_id'])) {
    $gid = intval($_POST['grievance_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE grievances SET status='resolved' WHERE id=? AND status='pending'");
        $stmt->bind_param("i", $gid);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Grievance marked as resolved.";
        } else {
            $_SESSION['error'] = "Failed to resolve grievance.";
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE grievances SET status='rejected' WHERE id=? AND status='pending'");
        $stmt->bind_param("i", $gid);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Grievance rejected.";
        } else {
            $_SESSION['error'] = "Failed to reject grievance.";
        }
        $stmt->close();
    }
    header('Location: hod_grievance_approval.php');
    exit();
}

// Get messages from session and clear them
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "";
$error = isset($_SESSION['error']) ? $_SESSION['error'] : "";
if ($success_message) unset($_SESSION['success_message']);
if ($error) unset($_SESSION['error']);

// Filters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

// Fetch all grievances with filters
$prev = [];
$query = "SELECT g.id, g.type, g.description, g.status, g.created_at, u.name, sp.roll_number
          FROM grievances g
          JOIN users u ON g.user_id = u.id
          LEFT JOIN student_profiles sp ON u.id = sp.user_id
          WHERE 1";
$params = [];
$types = "";

if ($filter_status && $filter_status !== 'all') {
    $query .= " AND g.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if ($filter_type && $filter_type !== 'all') {
    $query .= " AND g.type = ?";
    $params[] = $filter_type;
    $types .= "s";
}
$query .= " ORDER BY g.created_at DESC";

$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($pid, $ptype, $pdesc, $pstatus, $pcreated, $pname, $proll);
while ($stmt->fetch()) {
    $prev[] = [
        'id' => $pid,
        'type' => $ptype,
        'description' => $pdesc,
        'status' => $pstatus,
        'created_at' => $pcreated,
        'name' => $pname,
        'roll_no' => $proll
    ];
}
$stmt->close();

// Get unread announcements count for navbar (optional, adjust as needed)
$unread_announcements = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>HOD Grievance Approval | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(-45deg, #dfe9f3, #ffffff, #e2ebf0, #f2f6ff);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .approval-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .approval-card.main-container {
            padding: 40px;
            margin-top: 20px;
        }
        .approval-header {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .card-glass {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            height: 100%;
        }
        .card-glass:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(52,152,219,0.25);
            border-color: #3498db;
        }
        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            background: #7f8c8d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            border: 2px solid #95a5a6;
        }
        .section-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.alert-success, .alert.alert-danger {
            border-radius: 15   px;
            border: none;
            padding: 15px 20px;
            animation: slideInUp 0.5s ease;
        }
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #3498db;
        }
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.1);
            overflow: hidden;
        }
        .table {
            margin: 0;
            border-radius: 20px;
            width: 100%;
        }
        .table thead th {
            border: none;
            padding: 20px 15px;
            text-align: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        .table th, .table td {
            vertical-align: middle;
            padding: 18px 15px;
            font-size: 0.92rem;
            border: none;
            text-align: center;
        }
        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.95rem;
        }
        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(52, 152, 219, 0.1);
        }
        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.08);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.15);
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .badge.bg-warning {
            background: linear-gradient(135deg, #ff9500 0%, #ff6b35 100%) !important;
            color: white !important;
            padding: 8px 12px !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #00d4aa 0%, #00a085 100%) !important;
            color: white !important;
            padding: 8px 12px !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        .badge.bg-danger {
            background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%) !important;
            color: white !important;
            padding: 8px 12px !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        .navbar {
            background: white !important;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 12px 0;
            z-index: 1030;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.3rem;
            color: #333 !important;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .navbar-brand:hover {
            color: #3498db !important;
        }
        .nav-link {
            color: #495057 !important;
            font-weight: 500;
            border-radius: 8px;
            padding: 8px 16px !important;
            margin: 0 3px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }
        .nav-link:hover, .nav-link.active {
            background: #3498db;
            color: white !important;
        }
        .brand-icon {
            background: #3498db;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: #DC2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        .profile-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 40px;
            margin-top: 20px;
        }
        .profile-header {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 25px !important;
            margin-bottom: 30px;
        }
        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            background: #7f8c8d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            border: 2px solid #95a5a6;
        }
        .action-btn {
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 3px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 80px;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
        }
        .btn-approve {
            background: linear-gradient(135deg, #00d4aa 0%, #00a085 100%);
            color: white;
        }
        .btn-approve:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }
        .btn-reject {
            background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);
            color: white;
        }
        .btn-reject:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.4);
        }
        .stat-card {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            color: #34495e;
            border-radius: 12px;
            padding: 18px 12px;
            margin-bottom: 0;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.10);
            border: 1px solid #d0e3fa;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: box-shadow 0.2s, border-color 0.2s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            box-shadow: 0 6px 18px rgba(52, 152, 219, 0.13);
            border-color: #3498db;
            transform: translateY(-1px) scale(1.01);
        }
        .stat-number {
            font-size: 1.7rem;
            font-weight: 700;
            margin-bottom: 0;
            min-width: 38px;
            text-align: center;
            color: #3498db;
            text-shadow: 0 2px 8px rgba(52,152,219,0.08);
            letter-spacing: 0.5px;
        }
        .stat-label {
            font-size: 0.98rem;
            font-weight: 600;
            color: #34495e;
            margin-top: 2px;
            letter-spacing: 0.3px;
        }
        @media (max-width: 768px) {
            .stat-card {
                padding: 10px 6px;
                font-size: 0.95rem;
            }
            .stat-number {
                font-size: 1.2rem;
                min-width: 28px;
            }
        }

    </style>
</head>
<body>
    <!-- ======= MENU BAR START (Faculty Dashboard Style) ======= -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container">
            <a class="navbar-brand" href="hod_dashboard.php">
                <div class="brand-icon">
                    <i class="bi bi-building-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2">HOD</span>
            </a>
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link" href="hod_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="hod_leave_approval.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave Approval
                </a>
                <!-- <a class="nav-link" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a> -->
                <!-- <a class="nav-link" href="faculty_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a> -->
                <a class="nav-link" href="hod_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link active" href="hod_grievance_approval.php">
                    <i class="bi bi-exclamation-triangle me-1"></i>Grievance Approval
                </a>
                <a class="nav-link" href="hod_profile.php">
                    <i class="bi bi-person-circle me-1"></i>Profile
                </a>
            </div>
            <div class="d-flex align-items-center">
                <a href="logout.php" class="btn logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="approval-card main-container">
            <!-- Header -->
            <div class="approval-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">HOD Grievance Approval</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-exclamation-circle me-2"></i>Review and resolve student grievances
                        </p>
                    </div>
                    <div class="col-auto d-none d-md-block">
                        <div class="text-end">
                            <p class="mb-1 opacity-75">Today's Date</p>
                            <h5 class="mb-0"><?= date('M d, Y') ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Row -->
        <div class="row stats-row">
            <?php 
            $pending_count = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE status = 'Pending'")->fetch_assoc()['count'];
            $approved_count = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE status = 'Resolved'")->fetch_assoc()['count'];
            $rejected_count = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE status = 'Rejected'")->fetch_assoc()['count'];
            ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $pending_count ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $approved_count ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $rejected_count ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

            <!-- Success/Error Message -->
            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Filter Form -->
            <h4 class="section-title mt-4">
                <i class="bi bi-funnel text-primary me-2"></i>Filter & Search Grievances
            </h4>
            <div class="card-glass mb-4" id="filter-section">
                <form method="GET" class="row g-3 align-items-end" id="filterForm" onsubmit="return applyFilterForm(event)">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-info-circle me-1"></i>Filter by Status
                        </label>
                        <select name="status" class="form-select" id="statusSelect">
                            <option value="all" <?= $filter_status === '' || $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="resolved" <?= $filter_status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-tag me-1"></i>Filter by Type
                        </label>
                        <select name="type" class="form-select" id="typeSelect">
                            <option value="all" <?= $filter_type === '' || $filter_type === 'all' ? 'selected' : '' ?>>All Types</option>
                            <option value="academic" <?= $filter_type === 'academic' ? 'selected' : '' ?>>Academic</option>
                            <option value="hostel" <?= $filter_type === 'hostel' ? 'selected' : '' ?>>Hostel</option>
                            <option value="admin" <?= $filter_type === 'admin' ? 'selected' : '' ?>>Administration</option>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <div class="d-flex gap-2 justify-content-md-start justify-content-center">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-search"></i> Apply Filter
                            </button>
                            <button type="button" onclick="resetFilter()" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Grievance Table -->
            <div class="card-glass table-container" id="results-section">
                <div class="table-responsive">
                    <table class="table align-middle shadow-sm">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar3 me-1"></i>Date</th>
                                <th><i class="bi bi-clock me-1"></i>Time</th>
                                <th><i class="bi bi-person me-1"></i>Student</th>
                                <th><i class="bi bi-tag me-1"></i>Type</th>
                                <th><i class="bi bi-chat-text me-1"></i>Description</th>
                                <th><i class="bi bi-info-circle me-1"></i>Status</th>
                                <th><i class="bi bi-gear me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($prev) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox display-4 d-block mb-3 opacity-50"></i>
                                    <h5 class="mb-2">No grievances found</h5>
                                    <p class="mb-0">No grievances to review at this time.</p>
                                </td>
                            </tr>
                        <?php else: foreach ($prev as $row): ?>
                            <tr id="row-<?= $row['id'] ?>">
                                <td><?= htmlspecialchars(date("d-m-Y", strtotime($row['created_at']))) ?></td>
                                <td><?= htmlspecialchars(date("H:i", strtotime($row['created_at']))) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($row['name']) ?></strong>
                                    <?php if ($row['roll_no']): ?>
                                        <br><span class="text-muted small"><?= htmlspecialchars($row['roll_no']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(ucfirst($row['type'])) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td>
                                    <?php
                                    if ($row['status'] == 'pending') echo '<span class="badge bg-warning">Pending</span>';
                                    elseif ($row['status'] == 'resolved') echo '<span class="badge bg-success">Resolved</span>';
                                    elseif ($row['status'] == 'rejected') echo '<span class="badge bg-danger">Rejected</span>';
                                    else echo '<span class="badge bg-secondary">' . htmlspecialchars($row['status']) . '</span>';
                                    ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="grievance_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="action-btn btn-approve" onclick="return confirm('Mark as resolved?')">
                                                    <i class="bi bi-check-circle"></i>Resolve
                                                </button>
                                            </form>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="grievance_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="action-btn btn-reject" onclick="return confirm('Reject this grievance?')">
                                                    <i class="bi bi-x-circle"></i>Reject
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">No actions available</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function applyFilterForm(event) {
    event.preventDefault();
    const statusSelect = document.getElementById('statusSelect');
    const typeSelect = document.getElementById('typeSelect');
    const statusValue = statusSelect.value;
    const typeValue = typeSelect.value;
    const url = new URL(window.location);
    if (statusValue && statusValue !== 'all') {
        url.searchParams.set('status', statusValue);
    } else {
        url.searchParams.delete('status');
    }
    if (typeValue && typeValue !== 'all') {
        url.searchParams.set('type', typeValue);
    } else {
        url.searchParams.delete('type');
    }
    url.hash = 'results-section';
    window.location = url.toString();
    return false;
}
function resetFilter() {
    document.getElementById('statusSelect').value = 'all';
    document.getElementById('typeSelect').value = 'all';
    window.location = 'hod_grievance_approval.php#results-section';
}
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hash = window.location.hash;
    if (urlParams.has('status') || urlParams.has('type') || hash === '#results-section') {
        setTimeout(() => {
            const resultsSection = document.getElementById('results-section');
            if (resultsSection) {
                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 200);
    }
    if (hash === '#results-section') {
        setTimeout(() => {
            const resultsSection = document.getElementById('results-section');
            if (resultsSection) {
                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 300);
    }
});
</script>
</body>