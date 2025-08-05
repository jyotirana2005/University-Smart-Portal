<?php
session_start();
include 'config.php';

// Redirect if not logged in or not an HOD
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hod') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch HOD details
$hod_query = $conn->prepare("SELECT name, department FROM users WHERE id = ?");
$hod_query->bind_param("i", $user_id);
$hod_query->execute();
$hod_data = $hod_query->get_result()->fetch_assoc();
$hod_query->close();

$hod_name = $hod_data['name'] ?? 'HOD';
$department = $hod_data['department'] ?? '';

// Fetch dynamic statistics
// 1. Pending Leave Applications
$pending_leaves_query = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE status = 'pending'");
$pending_leaves = $pending_leaves_query ? $pending_leaves_query->fetch_assoc()['count'] : 0;

// 2. Total Faculty Members in department
$faculty_query = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'faculty' AND department = ?");
$faculty_query->bind_param("s", $department);
$faculty_query->execute();
$faculty_count = $faculty_query->get_result()->fetch_assoc()['count'];
$faculty_query->close();

// 3. Calculate average attendance (mock calculation - you can modify based on your attendance table)
$attendance_query = $conn->query("SELECT AVG(attendance_percentage) as avg_attendance FROM (SELECT 85 as attendance_percentage UNION SELECT 92 UNION SELECT 78 UNION SELECT 95) as mock_data");
$avg_attendance = $attendance_query ? round($attendance_query->fetch_assoc()['avg_attendance']) : 85;

// 4. Active Issues/Grievances
$active_issues_query = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE status = 'pending' OR status = 'in_progress'");
$active_issues = $active_issues_query ? $active_issues_query->fetch_assoc()['count'] : 0;

// 5. Recent announcements count
$announcements_query = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$recent_announcements = $announcements_query ? $announcements_query->fetch_assoc()['count'] : 0;

// 6. Total students in department
$students_query = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND department = ?");
$students_query->bind_param("s", $department);
$students_query->execute();
$student_count = $students_query->get_result()->fetch_assoc()['count'];
$students_query->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Dashboard | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        
        body {
            background: linear-gradient(-45deg, #dfe9f3, #ffffff, #e2ebf0, #f2f6ff);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
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
            padding: 25px;
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
        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 30px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .card-glass:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        /* ======= FEATURE TILE CARD STYLES (Faculty Dashboard Style) ======= */
        .feature-tile {
            background: #ecf0f1;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #bdc3c7;
            transition: all 0.2s ease;
            padding: 25px;
            text-align: center;
            height: 100%;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .feature-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(52,152,219,0.25);
            border-color: #3498db;
            background: #e3f2fd;
        }
        .feature-tile:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(52,152,219,0.4);
            border-color: #2980b9;
            background: #bbdefb;
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 15px auto;
        }
        .feature-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
        }
        .feature-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 18px;
        }
        .feature-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
        }
        .feature-btn:hover {
            background: #2980b9;
            color: white;
        }
        .stats-card {
            background: #fff;
            color: #333;
            text-align: left;
            border-radius: 12px;
            padding: 24px 28px;
            margin-bottom: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 18px;
                }
        .stats-number {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0;
            min-width: 60px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .profile-card {
                padding: 20px;
            }
            .card-glass {
                padding: 15px;
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
                <a class="nav-link active" href="hod_dashboard.">
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
                <a class="nav-link" href="hod_grievance_approval.php">
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
    <!-- ======= MENU BAR END ======= -->

    <div class="container py-4">
        <div class="profile-card main-container">
            <!-- Profile Header (Faculty Dashboard Style) -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-building-fill"></i>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div>
                            <h2 class="mb-2 fw-bold text-start">HOD Dashboard</h2>
                            <p class="mb-0 opacity-75 text-start">
                                <i class="bi bi-gear me-2"></i>Manage your department efficiently with our comprehensive tools
                            </p>
                        </div>
                    </div>
                    <div class="col d-none d-md-block">
                        <div class="text-end">
                            <p class="mb-1 opacity-75">Today's Date</p>
                            <h5 class="mb-0"><?= date('M d, Y') ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <!-- HOD Cards Section (No Tabs) -->
            <div class="row g-4">
                <!-- Small Stats Cards Row (Faculty Dashboard Style) -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <span class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;font-size:1.7rem;">
                                <i class="bi bi-exclamation-circle"></i>
                            </span>
                            <div>
                                <div class="stats-number"><?php echo $active_issues; ?></div>
                                <div class="fw-semibold">Pending Grievances</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <span class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;font-size:1.7rem;">
                                <i class="bi bi-calendar-check"></i>
                            </span>
                            <div>
                                <div class="stats-number"><?php echo $pending_leaves; ?></div>
                                <div class="fw-semibold">Pending Leaves</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <span class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;font-size:1.7rem;">
                                <i class="bi bi-megaphone"></i>
                            </span>
                            <div>
                                <div class="stats-number"><?php echo $recent_announcements; ?></div>
                                <div class="fw-semibold">My Announcements</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Big Dashboard Cards Row -->
            <!-- Services Section Title -->
            <div class="d-flex align-items-center mb-4" style="gap:12px;">
                <div style="width:40px;height:40px;background:#3498db;border-radius:6px;display:flex;align-items:center;justify-content:center;color:white;font-size:1.3rem;">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
                <span style="font-size:1.5rem;font-weight:600;color:#374151;">HOD Services</span>
            </div>
            <div class="row g-3">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile text-center">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h6 class="feature-title">Leave Approval</h6>
                        <p class="feature-description">Review, approve, and manage leave requests from faculty and students.</p>
                        <a href="hod_leave_approval.php" class="feature-btn w-100">
                            <i class="bi bi-arrow-right me-1"></i>Manage Leaves
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile text-center">
                        <div class="feature-icon">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                        </div>
                        <h6 class="feature-title">Grievance Approval</h6>
                        <p class="feature-description">Seamlessly review, approve, or reject student grievances submitted to your department. </p>
                        <a href="hod_grievance_approval.php" class="feature-btn w-100">
                            <i class="bi bi-arrow-right me-1"></i>Manage Grievance
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile text-center">
                        <div class="feature-icon">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h6 class="feature-title">Announcements</h6>
                        <p class="feature-description">Create, post, and manage department-wide announcements and notifications.</p>
                        <a href="post_announcement.php" class="feature-btn w-100">
                            <i class="bi bi-arrow-right me-1"></i>Post Announcement
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile text-center">
                        <div class="feature-icon">
                            <i class="bi bi-chat-dots me-1"></i>
                        </div>
                        <h6 class="feature-title">Discussion Forum</h6>
                        <p class="feature-description">Engage in academic discussions and collaborate with colleagues.</p>
                        <a href="hod_forum.php" class="feature-btn w-100">
                            <i class="bi bi-arrow-right me-1"></i>Join Discussion
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile text-center">
                        <div class="feature-icon">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <h6 class="feature-title">Attendence Management</h6>
                        <p class="feature-description">Upload/view student attendance and generate defaulter lists.</p>
                        <a href="faculty_attendance.php" class="feature-btn w-100">
                            <i class="bi bi-arrow-right me-1"></i>Manage Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

</body>
</html>