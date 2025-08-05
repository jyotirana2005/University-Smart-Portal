<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];
$error = "";
$success_message = "";

// Handle delete operation
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM grievances WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $delete_id, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Grievance deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete grievance.";
    }
    $stmt->close();
    header('Location: student_grievance.php');
    exit();
}

// Handle edit operation
if (isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $type = $_POST['type'];
    $desc = $_POST['description'];
    $stmt = $conn->prepare("UPDATE grievances SET type = ?, description = ? WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ssii", $type, $desc, $edit_id, $user_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Grievance updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update grievance.";
    }
    $stmt->close();
    header('Location: student_grievance.php');
    exit();
}

// Grievance submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['edit_id'])) {
    $type = $_POST['type'];
    $desc = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO grievances (user_id, type, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $type, $desc);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Your grievance has been submitted successfully! You can track its status below.";
    } else {
        $_SESSION['error'] = "Failed to submit grievance.";
    }
    header('Location: student_grievance.php');
    exit();
}

// Get messages from session and clear them
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "";
$error = isset($_SESSION['error']) ? $_SESSION['error'] : "";
if ($success_message) unset($_SESSION['success_message']);
if ($error) unset($_SESSION['error']);

// Fetch previous grievances with filters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';

$prev = [];
$query = "SELECT id, type, description, status, response, created_at FROM grievances WHERE user_id=?";
$params = [$user_id];
$types = "i";

if ($filter_status && $filter_status !== 'all') {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_type && $filter_type !== 'all') {
    $query .= " AND type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($pid, $ptype, $pdesc, $pstatus, $presponse, $pcreated);
while ($stmt->fetch()) {
    $prev[] = [
        'id' => $pid,
        'type' => $ptype,
        'description' => $pdesc,
        'status' => $pstatus,
        'response' => $presponse,
        'created_at' => $pcreated
    ];
}
$stmt->close();

// Check if user_announcement_views table exists, create if not
$check_table = $conn->query("SHOW TABLES LIKE 'user_announcement_views'");
if ($check_table->num_rows == 0) {
    $conn->query("CREATE TABLE user_announcement_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        announcement_id INT NOT NULL,
        viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_view (user_id, announcement_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

// Get unread announcements count for navbar
$unread_announcements_query = "
    SELECT COUNT(*) as count 
    FROM announcements a 
    LEFT JOIN user_announcement_views uav ON a.id = uav.announcement_id AND uav.user_id = $user_id
    WHERE uav.id IS NULL 
    AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$unread_announcements = $conn->query($unread_announcements_query)->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Grievance | University Smart Portal</title>
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

        .grievance-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .grievance-card.main-container {
            padding: 30px;
            margin-top: 20px;
        }

        .grievance-header {
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

        .alert.alert-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid rgba(52, 152, 219, 0.3);
            border-radius: 8px;
            color: #1976d2;
            font-weight: 500;
            padding: 20px;
        }

        .alert.alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid rgba(40, 167, 69, 0.3);
            border-radius: 8px;
            color: #155724;
            font-weight: 500;
            padding: 20px;
        }

        .alert.alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 2px solid rgba(220, 53, 69, 0.3);
            border-radius: 8px;
            color: #721c24;
            font-weight: 500;
            padding: 20px;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #2980b9;
            color: white;
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
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

        .table thead {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .table thead th {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 20px 15px;
            text-align: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .table th, .table td {
            vertical-align: middle;
            padding: 18px 15px;
            font-size: 0.92rem;
            border: none;
            text-align: center;
        }

        .table th:nth-child(1), .table td:nth-child(1) {
            width: 10%;
            min-width: 90px;
        }

        .table th:nth-child(2), .table td:nth-child(2) {
            width: 8%;
            min-width: 70px;
        }

        .table th:nth-child(3), .table td:nth-child(3) {
            width: 10%;
            min-width: 85px;
        }

        .table th:nth-child(4), .table td:nth-child(4) {
            width: 35%;
            text-align: left;
            padding-left: 20px;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .table th:nth-child(5), .table td:nth-child(5) {
            width: 10%;
            min-width: 85px;
        }

        .table th:nth-child(6), .table td:nth-child(6) {
            width: 20%;
            text-align: left;
            padding-left: 20px;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.4;
        }

        .table th:nth-child(7), .table td:nth-child(7) {
            width: 17%;
            min-width: 120px;
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

        .table tbody tr:last-child {
            border-bottom: none;
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

        .alert {
            border-radius: 15px;
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

        /* Navigation Menu Styles */
        .navbar {
            background: white !important;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
        }

        .custom-navbar-padding {
            padding-left: 4rem !important;
            padding-right: 2rem !important;
            width: 100%;
            max-width: 100%;
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
            border-radius: 6px;
            padding: 6px 12px !important;
            margin: 0 2px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            min-width: 90px;
            justify-content: center;
        }

        .nav-link:hover, .nav-link.active {
            background: #3498db;
            color: white !important;
        }

        .navbar-nav {
            width: 100%;
            justify-content: center !important;
            align-items: center;
            flex-wrap: nowrap !important;
        }

        .nav-link .badge {
            background: #e74c3c !important;
            color: white !important;
            border-radius: 3px !important;
            padding: 3px 6px !important;
            font-size: 0.7rem !important;
            font-weight: 600 !important;
            min-width: 18px !important;
            text-align: center !important;
        }

        .dropdown-toggle {
            border-radius: 6px;
            border: 1px solid #3498db;
            color: #3498db;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .dropdown-toggle:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .dropdown-menu {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            padding: 10px 0;
        }

        .dropdown-item {
            padding: 10px 18px;
            transition: all 0.2s ease;
            color: #495057;
        }

        .dropdown-item:hover {
            background: #f1f3f4;
            color: #3498db;
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
            background: #e74c3c !important;
            color: white !important;
            border: none;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-weight: 500;
            transition: all 0.2s ease;
            margin: 0 2px;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #DC2626 !important;
            color: white !important;
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

        .btn-edit {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .btn-edit:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .btn-delete:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.4);
        }

        .edit-form {
            display: none;
            background: transparent;
        }

        .edit-form td {
            border-top: 3px solid #3498db !important;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.05), rgba(52, 152, 219, 0.02));
        }

        .edit-form .bg-light {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(52, 152, 219, 0.2);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.1);
        }

        .filter-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .filter-header h5 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-icon {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .filter-btn, .clear-btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .filter-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
        }

        .filter-btn:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }

        .clear-btn {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
            border: none;
        }

        .clear-btn:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.3);
        }
    </style>
</head>

<body>
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container-fluid custom-navbar-padding">
            <a class="navbar-brand me-1" href="student_dashboard.php">
                <div class="brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2">Student</span>
            </a>
            
            <!-- Navigation Links -->
            <div class="navbar-nav d-none d-lg-flex flex-fill justify-content-center">
                <a class="nav-link" href="student_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="student_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a>
                <a class="nav-link" href="student_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <a class="nav-link" href="student_announcements.php">
                    <i class="bi bi-megaphone me-1"></i>Announcements
                    <?php if($unread_announcements > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $unread_announcements ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link active" href="student_grievance.php">
                    <i class="bi bi-exclamation-triangle me-1"></i>Grievance
                </a>
                <a class="nav-link" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link" href="student_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link" href="student_profile.php">
                    <i class="bi bi-person-circle me-1"></i>Profile
                </a>
                <!-- Logout Button -->
                <a href="logout.php" class="nav-link logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    </nav>

    <div class="container py-4">
        <div class="grievance-card main-container">
            <!-- Grievance Header -->
            <div class="grievance-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">Student Grievance Portal</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-exclamation-circle me-2"></i>Report your concerns and track their resolution
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

            <!-- Submit New Grievance Form -->
            <h4 class="section-title">
                <i class="bi bi-plus-circle text-primary me-2"></i>Submit New Grievance
            </h4>
            <div class="card-glass mb-4">
                <form method="post" id="grievanceForm">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-semibold">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="academic">Academic</option>
                                <option value="hostel">Hostel</option>
                                <option value="admin">Administration</option>
                            </select>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" required rows="1" placeholder="Describe your issue"></textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary px-4" type="submit">
                        <i class="bi bi-send me-2"></i>Submit Grievance
                    </button>
                </form>
            </div>

            <!-- Previous Grievances Section -->
            <h4 class="section-title">
                <i class="bi bi-list-check text-primary me-2"></i>Your Previous Grievances
            </h4>

        <!-- Filter Form -->
        <div class="card-glass mb-4" id="filter-section">
            <h5 class="mb-3">
                <i class="bi bi-funnel text-primary me-2"></i>Filter & Search Grievances
            </h5>
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
        <div class="card-glass table-container" id="results-section">
            <div class="table-responsive">
                <table class="table align-middle shadow-sm">
                <thead>
                    <tr>
                        <th>
                            <i class="bi bi-calendar3 me-1"></i>
                            Date
                        </th>
                        <th>
                            <i class="bi bi-clock me-1"></i>
                            Time
                        </th>
                        <th>
                            <i class="bi bi-tag me-1"></i>
                            Type
                        </th>
                        <th>
                            <i class="bi bi-chat-text me-1"></i>
                            Description
                        </th>
                        <th>
                            <i class="bi bi-info-circle me-1"></i>
                            Status
                        </th>
                        <th>
                            <i class="bi bi-reply me-1"></i>
                            Response
                        </th>
                        <th>
                            <i class="bi bi-gear me-1"></i>
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($prev) == 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox display-4 d-block mb-3 opacity-50"></i>
                            <?php if ($filter_status || $filter_type): ?>
                                <h5 class="mb-2">No results found</h5>
                            <?php else: ?>
                                <h5 class="mb-2">No grievances submitted yet</h5>
                                <p class="mb-0">Submit your first grievance using the form above</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: foreach ($prev as $row): ?>
                    <tr id="row-<?= $row['id'] ?>">
                        <td><?= htmlspecialchars(date("d-m-Y", strtotime($row['created_at']))) ?></td>
                        <td><?= htmlspecialchars(date("H:i", strtotime($row['created_at']))) ?></td>
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
                        <td><?= $row['response'] ? htmlspecialchars($row['response']) : '<span class="text-muted fst-italic">No response yet</span>' ?></td>
                        <td>
                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                <?php if ($row['status'] == 'pending'): ?>
                                    <a href="#" class="action-btn btn-edit" onclick="toggleEdit(<?= $row['id'] ?>)">
                                        <i class="bi bi-pencil"></i>Edit
                                    </a>
                                    <a href="?delete=<?= $row['id'] ?>" class="action-btn btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this grievance?')">
                                        <i class="bi bi-trash"></i>Delete
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">No actions available</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php if ($row['status'] == 'pending'): ?>
                    <tr id="edit-form-<?= $row['id'] ?>" class="edit-form">
                        <td colspan="7" style="padding: 10px;">
                            <div class="bg-light rounded p-3">
                                <form method="post">
                                    <input type="hidden" name="edit_id" value="<?= $row['id'] ?>">
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label small">Type</label>
                                            <select name="type" class="form-select form-select-sm" required>
                                                <option value="academic" <?= $row['type'] == 'academic' ? 'selected' : '' ?>>Academic</option>
                                                <option value="hostel" <?= $row['type'] == 'hostel' ? 'selected' : '' ?>>Hostel</option>
                                                <option value="admin" <?= $row['type'] == 'admin' ? 'selected' : '' ?>>Administration</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Description</label>
                                            <textarea name="description" class="form-control form-control-sm" required rows="1" style="font-size: 0.85rem;"><?= htmlspecialchars($row['description']) ?></textarea>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end justify-content-center gap-1" style="padding-top: 1.5rem;">
                                            <button type="submit" class="btn btn-success btn-sm" style="padding: 6px 14px; font-size: 0.8rem;">
                                                <i class="bi bi-check"></i> Update
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" style="padding: 6px 14px; font-size: 0.8rem;" onclick="toggleEdit(<?= $row['id'] ?>)">
                                                <i class="bi bi-x"></i> Cancel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle edit form visibility
function toggleEdit(id) {
    const editForm = document.getElementById('edit-form-' + id);
    if (editForm.style.display === 'none' || editForm.style.display === '') {
        editForm.style.display = 'table-row';
        // Smooth scroll to the edit form
        setTimeout(() => {
            editForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    } else {
        editForm.style.display = 'none';
    }
}

// Filter functions that don't cause page jump
function applyFilter() {
    const statusSelect = document.getElementById('statusSelect');
    const typeSelect = document.getElementById('typeSelect');
    
    const statusValue = statusSelect.value;
    const typeValue = typeSelect.value;
    
    // Build URL with current scroll position preserved
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
    
    // Add hash to scroll to results
    url.hash = 'results-section';
    
    // Navigate to new URL without jumping to top
    window.location = url.toString();
}

// Handle form submission for Apply Filter button
function applyFilterForm(event) {
    event.preventDefault(); // Prevent default form submission
    applyFilter(); // Use the same filter logic
    return false;
}

function resetFilter() {
    // Reset the dropdown values to 'all'
    document.getElementById('statusSelect').value = 'all';
    document.getElementById('typeSelect').value = 'all';
    
    // Navigate to clean URL and then scroll to results
    window.location = 'student_grievance.php#results-section';
}

// Scroll to results section after form submission if filter parameters exist
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const hash = window.location.hash;
    
    // Scroll to results if there are filter parameters OR if hash is results-section
    if (urlParams.has('status') || urlParams.has('type') || hash === '#results-section') {
        setTimeout(() => {
            const resultsSection = document.getElementById('results-section');
            if (resultsSection) {
                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                console.log('Results section not found');
            }
        }, 200);
    }
    
    // Also check for hash change after page load
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
</html>
