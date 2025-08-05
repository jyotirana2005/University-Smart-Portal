<?php
session_start();
include 'config.php';

// Redirect if not logged in or not a faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $department = "";
$profile_data = [];

// Check if faculty_profiles table exists, create if not
$check_table = $conn->query("SHOW TABLES LIKE 'faculty_profiles'");
if ($check_table->num_rows == 0) {
    $create_table_sql = "
    CREATE TABLE faculty_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        employee_id VARCHAR(50),
        designation VARCHAR(100),
        phone VARCHAR(20),
        email VARCHAR(100),
        qualification VARCHAR(200),
        experience INT,
        specialization TEXT,
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user (user_id)
    )";
    $conn->query($create_table_sql);
}

// Fetch name, department and profile data from `users` table and faculty_profiles
$stmt = $conn->prepare("SELECT u.name, u.department, u.email,
                              fp.employee_id, fp.designation, fp.qualification, fp.experience 
                       FROM users u 
                       LEFT JOIN faculty_profiles fp ON u.id = fp.user_id 
                       WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$name = $user_data['name'] ?? '';
$department = $user_data['department'] ?? '';
$employee_id = $user_data['employee_id'] ?? '';
$designation = $user_data['designation'] ?? '';
$qualification = $user_data['qualification'] ?? '';
$experience = $user_data['experience'] ?? '';

// Fetch faculty profile data if exists
$profile_query = $conn->query("SELECT * FROM faculty_profiles WHERE user_id = $user_id");
if ($profile_query && $profile_query->num_rows > 0) {
    $profile_data = $profile_query->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard | University Smart Portal</title>
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
        .date-circle {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.12);
            margin-bottom: 0;
            border: 2px solid #fff;
        }
        .date-circle .bi-calendar-date {
            color: #fff;
            font-size: 1.2rem;
        }
        .date-text {
            font-size: 1.15rem;
            color: #fff;
            font-weight: 600;
        }
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

        .stats-row {
            margin-bottom: 25px;
        }

        .stat-card {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #E5E7EB;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

        .modal-header {
            background: #3498db !important;
            color: white;
            border-radius: 6px 6px 0 0;
        }

        .alert {
            border-radius: 6px;
            border: none;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
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
                <a class="nav-link active" href="faculty_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <a class="nav-link" href="faculty_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a>
                <a class="nav-link" href="leave_application.php">
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
    </nav>

    <!-- Dashboard Content -->
    <div class="container py-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['profile_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['profile_success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['profile_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['profile_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['profile_error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['profile_error']); ?>
        <?php endif; ?>

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
                        <h3 class="mb-2 fw-bold">Welcome back, <?php echo htmlspecialchars($name); ?>!</h3>
                        <p class="mb-0 opacity-90">
                            <i class="bi bi-buildings me-2"></i><?php echo htmlspecialchars($department); ?> Department
                            <?php if($employee_id): ?>
                                <span class="ms-3"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($employee_id) ?></span>
                            <?php endif; ?>
                            <?php if($designation): ?>
                                <span class="ms-3"><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($designation) ?></span>
                            <?php endif; ?>
                            <?php if($experience): ?>
                                <span class="ms-3"><i class="bi bi-calendar me-1"></i><?= htmlspecialchars($experience) ?> Years</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-auto d-none d-md-block">
                        <div class="text-end">
                            <p class="mb-1 opacity-75">Today's Date</p>
                            <h5 class="mb-0"><?= date('M d, Y') ?></h5>
                            <?php if(!$employee_id || !$designation): ?>
                                <small class="text-warning">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    <a href="faculty_profile.php" class="text-warning text-decoration-none">
                                        Complete Profile
                                    </a>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row stats-row g-3">
                <?php 
                // Get some basic stats for the faculty
                $total_applications = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE user_id = $user_id")->fetch_assoc()['count'];
                $pending_applications = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE user_id = $user_id AND status = 'Pending'")->fetch_assoc()['count'];
                $total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE posted_by = $user_id")->fetch_assoc()['count'];
                ?>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_applications ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?= $pending_applications ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-number"><?= $total_announcements ?></div>
                        <div class="stat-label">My Announcements</div>
                    </div>
                </div>
            </div>

            <!-- Services Section -->
            <div class="section-title">
                <div class="section-icon">
                    <i class="bi bi-grid-3x3-gap"></i>
                </div>
                Faculty Services
            </div>

            <div class="row g-3">
                <!-- Row 1 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-calendar2-week"></i>
                        </div>
                        <h6 class="feature-title">Course Planner</h6>
                        <p class="feature-description">Post planned courses and electives for students to view and plan.</p>
                        <a href="faculty_course_planner.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-1"></i>Manage Courses
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-clipboard-check"></i>
                        </div>
                        <h6 class="feature-title">Attendance Management</h6>
                        <p class="feature-description">Upload/view student attendance and generate defaulter lists.</p>
                        <a href="faculty_attendance.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-1"></i>Manage Attendance
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h6 class="feature-title">Announcements</h6>
                        <p class="feature-description">Post circulars and important notices for students and departments.</p>
                        <a href="post_announcement.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-1"></i>Post Announcement
                        </a>
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h6 class="feature-title">Leave Application</h6>
                        <p class="feature-description">Apply for leave and view approval status in real-time.</p>
                        <a href="leave_application.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-1"></i>Apply Leave
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-tile">
                        <div class="feature-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <h6 class="feature-title">Discussion Forum</h6>
                        <p class="feature-description">Engage in academic discussions and collaborate with colleagues.</p>
                        <a href="faculty_forum.php" class="feature-btn">
                            <i class="bi bi-arrow-right me-1"></i>Join Discussion
                        </a>
                    </div>
                </div>

                
            </div>
        </div>
    </div>

</body>

</html>