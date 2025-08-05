<?php
session_start();
require_once "config.php";

$logged_in_user_id = $_SESSION['user_id'] ?? 1;
$current_user = $conn->query("SELECT * FROM users WHERE id = $logged_in_user_id")->fetch_assoc();
$role = $current_user['role'] ?? 'student';

// Set dashboard URL based on role
switch($role) {
    case 'teacher':
    case 'faculty':
        $dashboard_url = 'faculty_dashboard.php';
        break;
    case 'hod':
        $dashboard_url = 'hod_dashboard.html';
        break;
    default:
        $dashboard_url = 'student_dashboard.php';
        break;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_post'])) {
        $topic = $conn->real_escape_string($_POST['topic']);
        $message = $conn->real_escape_string($_POST['message']);
        $conn->query("INSERT INTO discussion_posts (user_id, topic, message) VALUES ($logged_in_user_id, '$topic', '$message')");
    }
    if (isset($_POST['new_reply'])) {
        $post_id = $_POST['post_id'];
        $message = $conn->real_escape_string($_POST['reply_message']);
        $conn->query("INSERT INTO discussion_replies (post_id, user_id, message) VALUES ($post_id, $logged_in_user_id, '$message')");
    }
    if (isset($_POST['edit_post'])) {
        $post_id = $_POST['post_id'];
        $message = $conn->real_escape_string($_POST['message']);
        $conn->query("UPDATE discussion_posts SET message='$message' WHERE id=$post_id AND user_id=$logged_in_user_id");
    }
    if (isset($_POST['delete_post'])) {
        $post_id = $_POST['post_id'];
        $conn->query("DELETE FROM discussion_posts WHERE id=$post_id AND user_id=$logged_in_user_id");
    }
    if (isset($_POST['edit_reply'])) {
        $reply_id = $_POST['reply_id'];
        $message = $conn->real_escape_string($_POST['message']);
        $conn->query("UPDATE discussion_replies SET message='$message' WHERE id=$reply_id AND user_id=$logged_in_user_id");
    }
    if (isset($_POST['delete_reply'])) {
        $reply_id = $_POST['reply_id'];
        $conn->query("DELETE FROM discussion_replies WHERE id=$reply_id AND user_id=$logged_in_user_id");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discussion Forum | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(-45deg, #dfe9f3, #ffffff, #e2ebf0, #f2f6ff);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
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

        .card-glass {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .card-glass.main-container {
            padding: 30px;
            margin-top: 20px;
        }

        .dashboard-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
        }

        .card-glass:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(52,152,219,0.25);
            border-color: #3498db;
        }

        .forum-header {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .forum-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50px, -50px);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .feature-icon {
            font-size: 3rem;
            color: white;
            margin-bottom: 20px;
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

        .forum-title {
            color: #2c3e50;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .post, .reply {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .post::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }

        .post:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(52,152,219,0.25);
            border-color: #3498db;
        }

        .reply {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            margin-left: 2rem;
            padding: 20px;
            border-radius: 6px;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(52, 152, 219, 0.1);
        }

        .post-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }

        .meta {
            font-size: 0.9rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-badge {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .post-content {
            line-height: 1.7;
            color: #495057;
            margin: 1.5rem 0;
            font-size: 1.05rem;
        }

        .actions-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(52, 152, 219, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-primary:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-success:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
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
            transform: translateY(-2px);
        }

        .btn-outline-primary {
            border: 2px solid #3498db;
            color: #3498db;
            background: rgba(255, 255, 255, 0.9);
        }

        .btn-outline-primary:hover {
            background: #3498db;
            color: white;
            border-color: #3498db;
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            border: 2px solid #e74c3c;
            color: #e74c3c;
            background: rgba(255, 255, 255, 0.9);
        }

        .btn-outline-danger:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-danger:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .collapse-toggle {
            cursor: pointer;
            color: #3498db;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 25px;
            background: rgba(52, 152, 219, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 2px solid rgba(52, 152, 219, 0.2);
        }

        .collapse-toggle:hover {
            background: rgba(52, 152, 219, 0.2);
            color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .arrow-icon {
            transition: transform 0.3s ease;
            display: inline-block;
        }

        .arrow-icon.rotate {
            transform: rotate(180deg);
        }

        #postForm {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid rgba(52, 152, 219, 0.2);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.1);
        }

        #toggleFormButtons {
            margin-bottom: 30px;
        }

        #toggleFormButtons .btn {
            min-width: 200px;
            padding: 15px 25px;
            font-size: 1.1rem;
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid rgba(52, 152, 219, 0.2);
            padding: 15px 20px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
        }

        .replies-section {
            background: rgba(248, 249, 251, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(52, 152, 219, 0.1);
        }

        .reply-form {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
            border: 2px solid rgba(52, 152, 219, 0.1);
        }

        .discussion-stats {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #6c757d;
            font-size: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 20px;
            font-weight: 500;
        }

        .section-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.1);
        }

        .section-title {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
        }

        @media (max-width: 768px) {
            .container {
                margin-top: 20px;
            }
            
            .header-section, .post, #postForm {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .forum-title {
                font-size: 2rem;
            }
            
            .reply {
                margin-left: 1rem;
            }
            
            .discussion-stats {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
    
    <!-- Role-Specific Styles -->
    <style>
        <?php if ($role == 'faculty' || $role == 'teacher'): ?>
        /* Faculty Specific Styles - Exact copy from dashboard */
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
        }

        .faculty-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
        }
        <?php elseif ($role == 'hod'): ?>
        /* HOD Specific Styles - Match Student Container */
        .navbar {
            background: white !important;
            border-bottom: 1px solid #e0e0e0 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
            padding: 12px 0 !important;
            z-index: 1030 !important;
        }

        .hod-card {
            border-radius: 6px !important;
            background: white !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            border: 1px solid #ddd !important;
            padding: 30px !important;
            margin-top: 20px !important;
        }
        <?php endif; ?>
    </style>
</head>
<body>
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
            
            <!-- Navigation Links for Students -->
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
                    <?php 
                    // Get unread announcements count
                    $unread_query = "SELECT COUNT(*) as count FROM announcements a LEFT JOIN user_announcement_views uav ON a.id = uav.announcement_id AND uav.user_id = $logged_in_user_id WHERE uav.id IS NULL AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    $unread_count = $conn->query($unread_query)->fetch_assoc()['count'];
                    if($unread_count > 0): 
                    ?>
                        <span class="badge bg-danger ms-1"><?= $unread_count ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="student_grievance.php">
                    <i class="bi bi-exclamation-triangle me-1"></i>Grievance
                </a>
                <a class="nav-link" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link active" href="discussion_forum.php">
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
        <?php elseif ($role == 'faculty' || $role == 'teacher'): ?>
        <!-- Faculty Navigation Structure (EXACT copy from faculty_dashboard.php) -->
        <div class="container">
            <a class="navbar-brand" href="faculty_dashboard.php">
                <div class="brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2">Faculty</span>
            </a>
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_dashboard.php') echo ' active'; ?>" href="faculty_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_course_planner.php') echo ' active'; ?>" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_attendance.php') echo ' active'; ?>" href="faculty_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='leave_application.php') echo ' active'; ?>" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='discussion_forum.php') echo ' active'; ?>" href="discussion_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_profile.php') echo ' active'; ?>" href="faculty_profile.php">
                    <i class="bi bi-person-circle me-1"></i>Profile
                </a>
            </div>
            <div class="d-flex align-items-center">
                <a href="logout.php" class="btn logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
        <?php elseif ($role == 'hod'): ?>
        <!-- HOD Navigation Structure -->
        <div class="container">
            <a class="navbar-brand" href="hod_dashboard.html">
                <div class="brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2">HOD</span>
            </a>
            
            <!-- Navigation Links for HOD -->
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link" href="hod_dashboard.html">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="hod_leave_approval.php">
                    <i class="bi bi-clipboard-check me-1"></i>Leave Approval
                </a>
                <a class="nav-link" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link active" href="discussion_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link" href="post_announcement.php">
                    <i class="bi bi-megaphone me-1"></i>Announcements
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
    </nav>

    <!-- Main Content Container -->
    <?php if ($role == 'student'): ?>
    <div class="container py-4">
        <div class="dashboard-card">
    <?php elseif ($role == 'faculty' || $role == 'teacher'): ?>
    <div class="container py-4">
        <div class="faculty-card">
    <?php elseif ($role == 'hod'): ?>
    <div class="container py-4">
        <div class="hod-card">
    <?php endif; ?>
            <!-- Forum Header -->
            <div class="forum-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">Discussion Forum</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-people-fill me-2"></i>Connect, share ideas, and engage with the community
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

    <!-- Discussion Form Toggle -->
    <div class="text-end" id="toggleFormButtons">
        <button class="btn btn-primary" id="showDiscussionBtn">
            <i class="bi bi-plus-circle-fill"></i> Start New Discussion
        </button>
        <button class="btn btn-danger d-none" id="hideDiscussionBtn">
            <i class="bi bi-x-circle-fill"></i> Cancel Discussion
        </button>
    </div>

    <!-- New Post Form -->
    <div id="postForm" class="d-none">
        <h5 class="mb-4"><i class="bi bi-chat-square-text"></i> Create New Discussion</h5>
        <form method="post">
            <div class="mb-4">
                <label class="form-label">📝 Discussion Topic</label>
                <input type="text" name="topic" id="topicField" class="form-control" placeholder="Enter an engaging discussion topic..." required>
            </div>
            <div class="mb-4">
                <label class="form-label">💭 Your Message</label>
                <textarea name="message" class="form-control" placeholder="Share your thoughts, questions, or ideas with the community..." rows="5" required></textarea>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" name="new_post" class="btn btn-success">
                    <i class="bi bi-send-fill"></i> Post Discussion
                </button>
                <button type="button" class="btn btn-outline-secondary" id="clearForm">
                    <i class="bi bi-arrow-clockwise"></i> Clear Form
                </button>
            </div>
        </form>
    </div>

    <!-- All Posts -->
    <div class="section-header">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="section-title mb-0"><i class="bi bi-chat-dots-fill"></i> Community Discussions</h4>
            <div class="discussion-stats">
                <?php 
                $total_posts = $conn->query("SELECT COUNT(*) as count FROM discussion_posts")->fetch_assoc()['count'];
                $total_replies = $conn->query("SELECT COUNT(*) as count FROM discussion_replies")->fetch_assoc()['count'];
                ?>
                <div class="stat-item">
                    <i class="bi bi-chat-square-fill"></i>
                    <span><?= $total_posts ?> Posts</span>
                </div>
                <div class="stat-item">
                    <i class="bi bi-reply-fill"></i>
                    <span><?= $total_replies ?> Replies</span>
                </div>
            </div>
        </div>
    </div>
    <?php
    $posts = $conn->query("SELECT p.*, u.name, u.role FROM discussion_posts p JOIN users u ON p.user_id = u.id ORDER BY p.posted_on DESC");
    while ($post = $posts->fetch_assoc()):
        $post_id = $post['id'];
    ?>
        <div class="post">
            <div class="post-header">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div class="d-flex align-items-center gap-3">
                        <h5 class="post-title mb-0"><?= htmlspecialchars($post['topic']) ?></h5>
                        <div class="meta">
                            <i class="bi bi-person"></i>
                            <span><?= htmlspecialchars($post['name']) ?></span>
                            <span class="user-badge"><?= ucfirst($post['role']) ?></span>
                            <i class="bi bi-clock"></i>
                            <span><?= date('M d, Y \a\t g:i A', strtotime($post['posted_on'])) ?></span>
                        </div>
                    </div>
                    <?php if ($logged_in_user_id == $post['user_id'] && (!isset($_POST['edit_post_id']) || $_POST['edit_post_id'] != $post_id)): ?>
                        <div class="action-buttons">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="edit_post_id" value="<?= $post_id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this post?')">
                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                <button type="submit" name="delete_post" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_POST['edit_post_id']) && $_POST['edit_post_id'] == $post_id): ?>
                <form method="post">
                    <textarea name="message" class="form-control"><?= htmlspecialchars($post['message']) ?></textarea>
                    <input type="hidden" name="post_id" value="<?= $post_id ?>">
                    <div class="mt-3">
                        <button type="submit" name="edit_post" class="btn btn-warning btn-sm">
                            <i class="bi bi-check-circle"></i> Update Post
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="post-content"><?= nl2br(htmlspecialchars($post['message'])) ?></div>
            <?php endif; ?>

            <div class="actions-container">
                <div class="collapse-toggle" data-bs-toggle="collapse" data-bs-target="#replies-<?= $post_id ?>">
                    <i class="bi bi-chat-left-dots-fill"></i>
                    <span>View Replies & Discussions</span>
                    <i class="bi bi-chevron-down arrow-icon" id="arrow-<?= $post_id ?>"></i>
                </div>
            </div>

            <div class="collapse mt-2" id="replies-<?= $post_id ?>">
                <?php
                $replies = $conn->query("SELECT r.*, u.name, u.role FROM discussion_replies r JOIN users u ON r.user_id = u.id WHERE r.post_id = $post_id ORDER BY r.replied_on ASC");
                while ($reply = $replies->fetch_assoc()):
                ?>
                    <div class="reply mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="meta"><?= $reply['name'] ?> (<?= $reply['role'] ?>) on <?= $reply['replied_on'] ?></div>
                            <?php if ($logged_in_user_id == $reply['user_id'] && (!isset($_POST['edit_reply_id']) || $_POST['edit_reply_id'] != $reply['id'])): ?>
                                <div class="action-buttons">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="edit_reply_id" value="<?= $reply['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this reply?')">
                                        <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                                        <button type="submit" name="delete_reply" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (isset($_POST['edit_reply_id']) && $_POST['edit_reply_id'] == $reply['id']): ?>
                            <form method="post">
                                <textarea name="message" class="form-control mt-2"><?= htmlspecialchars($reply['message']) ?></textarea>
                                <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                                <button type="submit" name="edit_reply" class="btn btn-warning btn-sm mt-2">Update</button>
                            </form>
                        <?php else: ?>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>

                <form method="post" class="mt-3">
                    <div class="reply-form">
                        <textarea name="reply_message" class="form-control" placeholder="💬 Write a thoughtful reply to this discussion..." rows="3" required></textarea>
                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" name="new_reply" class="btn btn-success">
                                <i class="bi bi-reply-fill"></i> Add Reply
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endwhile; ?>
        </div> <!-- Close role-specific card -->
    </div> <!-- Close main container -->

<script>
    // Rotate arrow icon on toggle
    document.querySelectorAll('.collapse-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const icon = toggle.querySelector('.arrow-icon');
            icon.classList.toggle('rotate');
        });
    });

    // Toggle new discussion form
    const showBtn = document.getElementById('showDiscussionBtn');
    const hideBtn = document.getElementById('hideDiscussionBtn');
    const postForm = document.getElementById('postForm');

    showBtn.addEventListener('click', () => {
        postForm.classList.remove('d-none');
        showBtn.classList.add('d-none');
        hideBtn.classList.remove('d-none');
        document.getElementById('topicField').focus();
    });

    hideBtn.addEventListener('click', () => {
        postForm.classList.add('d-none');
        showBtn.classList.remove('d-none');
        hideBtn.classList.add('d-none');
        // Clear form when hiding
        document.querySelector('#postForm form').reset();
    });

    // Clear form button functionality
    document.getElementById('clearForm').addEventListener('click', () => {
        document.querySelector('#postForm form').reset();
        document.getElementById('topicField').focus();
    });
</script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
