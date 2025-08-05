<?php
session_start();
include 'config.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // 'faculty' or 'student'

// Get unread announcements count for navbar (for students)
$unread_announcements = 0;
if ($role === 'student') {
    $unread_announcements_query = "
        SELECT COUNT(*) as count 
        FROM announcements a 
        LEFT JOIN user_announcement_views uav ON a.id = uav.announcement_id AND uav.user_id = $user_id
        WHERE uav.id IS NULL 
        AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ";
    $unread_announcements = $conn->query($unread_announcements_query)->fetch_assoc()['count'];
}

// Dynamic dashboard path
$dashboard_link = ($role === 'faculty') ? 'faculty_dashboard.php' : 'student_dashboard.php';

// Handle leave application
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle cancel leave request
    if (isset($_POST['cancel_leave']) && isset($_POST['leave_id'])) {
        $leave_id = $_POST['leave_id'];
        
        // Verify the leave belongs to the current user and is pending
        $check_stmt = $conn->prepare("SELECT status FROM leave_applications WHERE id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $leave_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $leave = $result->fetch_assoc();
            if ($leave['status'] === 'Pending') {
                // Delete the leave application completely
                $delete_stmt = $conn->prepare("DELETE FROM leave_applications WHERE id = ? AND user_id = ?");
                $delete_stmt->bind_param("ii", $leave_id, $user_id);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $_SESSION['success'] = "Leave application cancelled and removed successfully!";
            } else {
                $_SESSION['error'] = "Cannot cancel a leave that is not pending!";
            }
        } else {
            $_SESSION['error'] = "Leave application not found!";
        }
        $check_stmt->close();
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    // Handle new leave application
    else if (isset($_POST['leave_type'])) {
        $type = $_POST['leave_type'];
        $from = $_POST['from_date'];
        $to = $_POST['to_date'];
        $reason = $_POST['reason'];

        $stmt = $conn->prepare("INSERT INTO leave_applications (user_id, leave_type, from_date, to_date, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $type, $from, $to, $reason);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success'] = "Leave application submitted successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Filter handling
$status_filter = $_GET['status'] ?? 'all';
$filter_query = "WHERE l.user_id = $user_id";
if ($status_filter !== 'all') {
    $filter_query .= " AND l.status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Fetch leave applications
$applications = $conn->query("
    SELECT l.*, u.name, u.role, u.department
    FROM leave_applications l 
    JOIN users u ON u.id = l.user_id
    $filter_query
    ORDER BY l.applied_on DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Application | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .leave-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
            transition: all 0.3s ease;
            max-width: 100%;
        }
        .navbar {
            background: white !important;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 12px 0;
            z-index: 1030;
        }

        .leave-card.main-container {
            padding: 30px;
            margin-top: 20px;
        }

        .leave-header {
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
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 12px 16px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
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

        /* Navigation Menu Styles - DEFAULT (for students) */
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
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        .btn-secondary:hover {
            color: white;
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }
        .btn-outline-secondary {
            border: 2px solid #3498db;
            color: #3498db;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border-color: #3498db;
        }
        .btn-outline-danger {
            border: 2px solid #e74c3c;
            color: #e74c3c;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        .btn-outline-danger:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
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
        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.95rem;
        }
        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.08);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.15);
            transition: all 0.3s ease;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-button {
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 20px;
            min-width: 80px;
            text-align: center;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            background: none;
        }

        .badge-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
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

        .badge.bg-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%) !important;
            color: white !important;
            padding: 8px 12px !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
        }
        .filter-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.1);
        }
        .alert {
            border-radius: 15px;
            border: none;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-row {
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: none;
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
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

        .date-range {
            position: relative;
        }
        .date-range::after {
            content: "to";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #6b7280;
            z-index: 2;
            border: 2px solid #e5e7eb;
        }
    </style>
    
    <!-- Faculty Navigation Override - EXACT Faculty Dashboard Match -->
    <style>
        <?php if ($role == 'faculty'): ?>
        /* Override for FACULTY - Exact Faculty Dashboard Styling */
        .navbar {
            background: white !important;
            border-bottom: 1px solid #e0e0e0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
            padding: 15px 0 !important;
            z-index: 1030 !important;
        }

        .navbar-brand {
            font-weight: 600 !important;
            font-size: 1.3rem !important;
            color: #333 !important;
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            text-decoration: none !important;
        }

        .navbar-brand:hover {
            color: #3498db !important;
        }

        .nav-link {
            color: #495057 !important;
            font-weight: 500 !important;
            border-radius: 8px !important;
            padding: 8px 16px !important;
            margin: 0 3px !important;
            transition: all 0.2s ease !important;
            display: flex !important;
            align-items: center !important;
            font-size: inherit !important;
            min-width: auto !important;
            justify-content: flex-start !important;
        }

        .nav-link:hover, .nav-link.active {
            background: #3498db !important;
            color: white !important;
        }

        .navbar-nav {
            padding-left: 4rem!important;
            margin-left: 36px !important;
            margin-right: 1rem !important;
            justify-content: flex-end !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            width: auto !important;
        }

        .brand-icon {
            background: #3498db !important;
            color: white !important;
            width: 35px !important;
            height: 35px !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.1rem !important;
        }

        .logout-btn {
            background: #e74c3c !important;
            color: white !important;
            border: none !important;
            padding: 9px 18px !important;
            border-radius: 4px !important;
            font-weight: 625 !important;
            transition: all 0.2s ease !important;
            text-decoration: none !important;
        }

        .logout-btn:hover {
            background: #DC2626 !important;
            color: white !important;
        }

        .d-flex.align-items-center {
            margin-left: 0 !important;
        }
        <?php endif; ?>
    </style>
</head>
<body class="bg-light">
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <!-- Student Navigation Structure -->
        <?php if ($role == 'student'): ?>
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
                <a class="nav-link" href="student_grievance.php">
                    <i class="bi bi-exclamation-triangle me-1"></i>Grievance
                </a>
                <a class="nav-link active" href="leave_application.php">
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
        <?php endif; ?>

        <!-- Faculty Navigation Structure -->
        <?php if ($role == 'faculty'): ?>
        <div class="container">
            <a class="navbar-brand" href="faculty_dashboard.php">
                <div class="brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2">Faculty</span>
            </a>
            
            <!-- Navigation Links -->
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link" href="faculty_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <a class="nav-link" href="faculty_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a>
                <a class="nav-link active" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link" href="faculty_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link" href="faculty_profile.php">
                    <i class="bi bi-person-circle me-1"></i>Profile
                </a>
            </div>
            
            <!-- Logout Button -->
            <div class="d-flex align-items-center">
                <a href="logout.php" class="btn logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- student Navigation Structure -->
        <?php if ($role == 'hod'): ?>
        <div class="container">
            <a class="navbar-brand" href="<?= $role == 'student' ? 'student_dashboard.php' : ($role == 'hod' ? 'hod_dashboard.html' : 'faculty_dashboard.php') ?>">
                <div class="brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2"><?= ucfirst($role) ?></span>
            </a>
            
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link" href="hod_dashboard.html">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="hod_leave_approval.php">
                    <i class="bi bi-clipboard-check me-1"></i>Leave Approval
                </a>
                <a class="nav-link active" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link" href="discussion_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link" href="post_announcement.php">
                    <i class="bi bi-megaphone me-1"></i>Announcements
                </a>
            </div>
            
            <div class="d-flex align-items-center">
                <a href="logout.php" class="btn logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
        <?php endif; ?>
    </nav>

    <div class="container py-4">
        <div class="leave-card main-container">
            <!-- Leave Application Header -->
            <div class="leave-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">Leave Application Portal</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-calendar3 me-2"></i>Submit and manage your leave requests (<?= ucfirst($role) ?>)
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

            <!-- Statistics Section -->
            <?php 
            $pending_count = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE user_id = $user_id AND status = 'Pending'")->fetch_assoc()['count'];
            $approved_count = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE user_id = $user_id AND status = 'Approved'")->fetch_assoc()['count'];
            $rejected_count = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE user_id = $user_id AND status = 'Rejected'")->fetch_assoc()['count'];
            ?>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Submit New Leave Application Form -->
            <h4 class="section-title">
                <i class="bi bi-plus-circle text-primary me-2"></i>Submit New Leave Application
            </h4>
            <div class="card-glass mb-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-tag"></i> Leave Type
                    </label>
                    <select name="leave_type" class="form-select" required>
                        <option value="">Select leave type...</option>
                        <option value="Sick Leave">🏥 Sick Leave</option>
                        <option value="Casual Leave">🏖️ Casual Leave</option>
                        <option value="Emergency Leave">🚨 Emergency Leave</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-chat-text"></i> Reason for Leave
                    </label>
                    <textarea name="reason" class="form-control" rows="1" placeholder="Brief description of reason..." required></textarea>
                </div>
            </div>
            
            <div class="row date-range">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-calendar-date"></i> From Date
                    </label>
                    <input type="date" name="from_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-calendar-date"></i> To Date
                    </label>
                    <input type="date" name="to_date" class="form-control" required>
                </div>
            </div>
            
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send"></i> Submit Application
                </button>
                <button type="reset" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset Form
                </button>
            </div>
        </form>
        </div>

        <!-- Leave History Section -->
        <h4 class="section-title">
            <i class="bi bi-clock-history text-primary me-2"></i>Your Leave History
        </h4>

        <!-- Filter Form -->
        <div class="card-glass mb-4" id="filter-section">
            <form method="GET" class="row g-3 align-items-end" onsubmit="return applyFilterForm(event)">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-funnel me-1"></i>Filter by Status
                    </label>
                    <select name="status" class="form-select" id="statusSelect">
                        <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Applications</option>
                        <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $status_filter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $status_filter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2">
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

    <!-- Leave Table -->
    <div class="table-container" id="results-section">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="bi bi-person me-1"></i> Name</th>
                        <th><i class="bi bi-calendar-event me-1"></i> Dates</th>
                        <th><i class="bi bi-chat-text me-1"></i> Reason</th>
                        <th><i class="bi bi-flag me-1"></i> Status</th>
                        <th><i class="bi bi-clock me-1"></i> Applied</th>
                        <th><i class="bi bi-gear me-1"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($applications->num_rows > 0): ?>
                        <?php while($row = $applications->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 0.8rem; font-weight: bold;">
                                            <?= strtoupper(substr($row['name'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= $row['leave_type'] ?></div>
                                    <div class="text-muted small">
                                        <i class="bi bi-calendar-date"></i> <?= date('M d', strtotime($row['from_date'])) ?> - <?= date('M d, Y', strtotime($row['to_date'])) ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php 
                                        $days = ceil((strtotime($row['to_date']) - strtotime($row['from_date'])) / (60*60*24)) + 1;
                                        echo $days . ' day' . ($days > 1 ? 's' : '');
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <div style="max-width: 200px;" title="<?= htmlspecialchars($row['reason']) ?>">
                                        <?= htmlspecialchars($row['reason']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $row['status'] == 'Approved' ? 'success' : 
                                        ($row['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                        <i class="bi bi-<?= $row['status'] == 'Approved' ? 'check-circle' : ($row['status'] == 'Rejected' ? 'x-circle' : 'clock') ?>"></i>
                                        <?= $row['status'] ?>
                                    </span>
                                    <?php if ($row['status'] == 'Rejected' && !empty($row['rejection_reason'])): ?>
                                        <div class="mt-1">
                                            <small class="text-danger">
                                                <i class="bi bi-info-circle"></i> <?= htmlspecialchars($row['rejection_reason']) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= date('M d, Y', strtotime($row['applied_on'])) ?></div>
                                    <small class="text-muted"><?= date('g:i A', strtotime($row['applied_on'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Pending'): ?>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this leave application?')" class="d-inline">
                                            <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="cancel_leave" value="1">
                                            <button type="submit" class="badge bg-danger badge-button">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-check2-all"></i> Processed
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                <h5>No leave applications found</h5>
                                <p class="mb-0">Submit your first leave application using the form above.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filter functions that don't cause page jump
        function applyFilter() {
            const statusSelect = document.getElementById('statusSelect');
            const statusValue = statusSelect.value;
            
            // Build URL with current scroll position preserved
            const url = new URL(window.location);
            
            if (statusValue && statusValue !== 'all') {
                url.searchParams.set('status', statusValue);
            } else {
                url.searchParams.delete('status');
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
            // Reset the dropdown value to 'all'
            document.getElementById('statusSelect').value = 'all';
            
            // Navigate to clean URL and then scroll to results
            window.location = 'leave_application.php#results-section';
        }

        // Scroll to results section after form submission if filter parameters exist
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hash = window.location.hash;
            
            // Scroll to results if there are filter parameters OR if hash is results-section
            if (urlParams.has('status') || hash === '#results-section') {
                setTimeout(() => {
                    const resultsSection = document.getElementById('results-section');
                    if (resultsSection) {
                        resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            }
        });
    </script>
</body>
</html>
