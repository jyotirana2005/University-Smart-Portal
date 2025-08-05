<?php
session_start();
include 'config.php';

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $department = "";

// Fetch name, department and profile data from `users` table and student_profiles
$stmt = $conn->prepare("SELECT u.name, u.department, u.email,
                              sp.roll_number, sp.semester, sp.year 
                       FROM users u 
                       LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                       WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$name = $user_data['name'] ?? '';
$department = $user_data['department'] ?? '';
$roll_number = $user_data['roll_number'] ?? '';
$semester = $user_data['semester'] ?? '';
$year = $user_data['year'] ?? '';

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
    AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$unread_announcements = $conn->query($unread_announcements_query)->fetch_assoc()['count'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | University Smart Portal</title>
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

        .stats-row {
            margin-bottom: 25px;
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

        .dashboard-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
        }

        .welcome-section {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
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

        .feature-tile {
            border-radius: 6px;
            background: #ecf0f1;
            transition: all 0.2s ease;
            border: 1px solid #bdc3c7;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 25px;
            text-align: center;
            height: 100%;
            cursor: pointer;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 15px;
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

        .stat-card {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #E5E7EB;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(52,152,219,0.15);
            border-color: #3498db;
            background: #f8fbff;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #6B7280;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .dashboard-card {
                padding: 20px;
                margin-top: 15px;
            }
            
            .welcome-section {
                padding: 20px;
            }
            
            .user-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- ✅ Enhanced Top Menu Bar -->
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
                <a class="nav-link active" href="student_dashboard.php">
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
                <a class="nav-link" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                <!-- </a>
                <a class="nav-link" href="discussion_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a> -->
                <!-- Update forum link to student_forum.php -->
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

    <!-- ✅ Dashboard Content -->
    <div class="container py-4">
        <div class="dashboard-card">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <?= strtoupper(substr($name, 0, 2)) ?>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">Welcome back, <?php echo htmlspecialchars($name); ?>!</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-buildings me-2"></i><?php echo htmlspecialchars($department); ?> Department
                            <?php if($roll_number): ?>
                                <span class="ms-3"><i class="bi bi-person-badge me-2"></i><?= htmlspecialchars($roll_number) ?></span>
                            <?php endif; ?>
                            <?php if($semester): ?>
                                <span class="ms-3"><i class="bi bi-mortarboard me-2"></i>Semester <?= htmlspecialchars($semester) ?></span>
                            <?php endif; ?>
                            <?php if($year): ?>
                                <span class="ms-3"><i class="bi bi-calendar me-2"></i><?= htmlspecialchars($year) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-auto d-none d-md-block">
                        <div class="text-end">
                            <p class="mb-1 opacity-75">Today's Date</p>
                            <h5 class="mb-0"><?= date('M d, Y') ?></h5>
                            <?php if(!$roll_number || !$semester): ?>
                                <small class="text-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <a href="student_profile.php" class="text-warning text-decoration-none">
                                        Complete Profile
                                    </a>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row stats-row g-4">
                <?php 
                // Get combined stats for student (both leave applications and grievances)
                $leave_total = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE user_id = $user_id")->fetch_assoc()['count'];
                $grievance_total = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE user_id = $user_id")->fetch_assoc()['count'];
                $total_applications = $leave_total + $grievance_total;
                
                $leave_pending = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE user_id = $user_id AND status = 'Pending'")->fetch_assoc()['count'];
                $grievance_pending = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE user_id = $user_id AND status = 'pending'")->fetch_assoc()['count'];
                $pending_applications = $leave_pending + $grievance_pending;
                ?>
                <div class="col-md-4">
                    <div class="stat-card" onclick="window.location.href='leave_application.php'">
                        <div class="stat-number"><?= $total_applications ?></div>
                        <div class="stat-label">Total Applications</div>
                        <small class="text-muted d-block mt-1"><?= $leave_total ?> Leave + <?= $grievance_total ?> Grievance</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" onclick="window.location.href='student_grievance.php'">
                        <div class="stat-number"><?= $pending_applications ?></div>
                        <div class="stat-label">Pending Requests</div>
                        <small class="text-muted d-block mt-1"><?= $leave_pending ?> Leave + <?= $grievance_pending ?> Grievance</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" onclick="window.location.href='student_announcements.php'">
                        <div class="stat-number"><?= $unread_announcements ?></div>
                        <div class="stat-label">Unread Announcements</div>
                    </div>
                </div>
            </div>

            <!-- Services Section -->
            <h3 class="section-title">
                <div class="section-icon">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
                Student Services
            </h3>

            <div class="row g-4">
                <!-- Tile 1 - Attendance -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <h5 class="feature-title">My Attendance</h5>
                        <p class="feature-description">View your attendance records, statistics, and track your progress.</p>
                        <a href="student_attendance.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-2"></i>View Attendance
                        </a>
                    </div>
                </div>

                <!-- Tile 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-calendar2-week"></i>
                        </div>
                        <h5 class="feature-title">Course Planner</h5>
                        <p class="feature-description">See all planned courses and electives for your semester.</p>
                        <a href="student_course_planner.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-2"></i>Access Planner
                        </a>
                    </div>
                </div>

                <!-- Tile 3 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <h5 class="feature-title">Grievance Portal</h5>
                        <p class="feature-description">Submit academic, hostel, or administrative complaints and track status.</p>
                        <a href="student_grievance.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-2"></i>Submit Grievance
                        </a>
                    </div>
                </div>

                <!-- Tile 4 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h5 class="feature-title">Leave Application</h5>
                        <p class="feature-description">Apply for leave and view approval status in real-time.</p>
                        <a href="leave_application.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-2"></i>Apply Leave
                        </a>
                    </div>
                </div>

                <!-- Tile 5 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h5 class="feature-title">Announcements</h5>
                        <p class="feature-description">Stay updated with latest news from faculty and administration.</p>
                        <a href="student_announcements.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-2"></i>View Updates
                        </a>
                    </div>
                </div>

                <!-- Tile 6 - Discussion Forum -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <h5 class="feature-title">Discussion Forum</h5>
                        <p class="feature-description">Participate in academic discussions and connect with peers.</p>
                        <a href="student_forum.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-2"></i>Join Forum
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
