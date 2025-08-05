<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

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

// Get unread announcements count for navbar first
$unread_announcements_query = "
    SELECT COUNT(*) as count 
    FROM announcements a 
    LEFT JOIN user_announcement_views uav ON a.id = uav.announcement_id AND uav.user_id = $user_id
    WHERE uav.id IS NULL 
    AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
";
$unread_announcements = $conn->query($unread_announcements_query)->fetch_assoc()['count'];

$announcements = [];
$q = $conn->query("SELECT a.id, a.title, a.message, a.created_at, u.name, a.posted_role FROM announcements a LEFT JOIN users u ON a.posted_by=u.id ORDER BY a.created_at DESC");
while ($row = $q->fetch_assoc()) {
    $announcements[] = $row;
}

// Mark announcements as viewed when user actually visits this page (after a small delay)
// This happens after the page renders to allow NEW badges to show first
if (isset($_GET['mark_viewed']) && $_GET['mark_viewed'] == '1') {
    foreach ($announcements as $announcement) {
        $announcement_id = $announcement['id'];
        $conn->query("INSERT IGNORE INTO user_announcement_views (user_id, announcement_id) VALUES ($user_id, $announcement_id)");
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Announcements | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(-45deg, #dfe9f3, #ffffff, #e2ebf0, #f2f6ff);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-y: scroll;            
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

        .announcement-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .announcement-card.main-container {
            padding: 30px;
            margin-top: 20px;
        }

        .announcements-header {
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

        .announcement-title {
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .announcement-badge {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .announcement-description {
            font-size: 1rem;
            line-height: 1.6;
            color: #555;
            margin-bottom: 20px;
        }

        .announcement-footer {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.05), rgba(155, 89, 182, 0.05));
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 3px solid #3498db;
            margin-top: auto;
            font-size: 0.9rem;
        }

        .announcement-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .faculty-text {
            color: #333;
            font-weight: 500;
        }

        .time-text {
            color: #666;
            font-size: 0.85rem;
        }

        .alert.alert-info {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 2px solid rgba(52, 152, 219, 0.3);
            border-radius: 8px;
            color: #1976d2;
            font-weight: 500;
            padding: 20px;
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

        nav .container {
            padding-left: 4 !important;
            margin-left: 2 !important;
            max-width: 100% !important;
        }
        
        
        .navbar-brand {
            margin-left: 0 !important;
            padding-left: 3.25rem !important;
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
            padding-left: 0rem !important;
            margin-left: -20px !important;
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

        /* .logout-btn {
            background: #e74c3c !important;
            color: white !important;
            border: none;
            padding: 6px 12px !important;
            border-radius: 6px !important;
            font-weight: 500;
            transition: all 0.2s ease;
            margin: 0 2px;
            font-size: 0.9rem;
        } */

        .logout-btn:hover {
            background: #DC2626 !important;
            color: white !important;
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

    </style>
</head>
<body>
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container">
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
                <a class="nav-link active" href="student_announcements.php">
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

    <div class="container py-4">
        <div class="announcement-card main-container">
            <!-- Announcements Header -->
            <div class="announcements-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">University Announcements</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-bell me-2"></i>Stay updated with important notices and information
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

            <?php if (count($announcements) === 0): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle me-2"></i>No announcements posted yet.
                </div>
            <?php else: ?>
                <!-- Section Title with blue icon background -->
                <div class="section-title d-flex align-items-center mb-4" style="gap:16px;">
                    <span style="background:#3498db; color:#fff; border-radius:10px; width:48px; height:48px; display:flex; align-items:center; justify-content:center; font-size:2rem;">
                        <i class="bi bi-megaphone"></i>
                    </span>
                    <span style="font-size:2rem; font-weight:600; color:#2c3e50;">All Announcements</span>
                </div>
                <div class="row g-4">
                    <?php foreach ($announcements as $index => $a): 
                        // ...existing code...
                        $isNew = (time() - strtotime($a['created_at'])) < (3 * 24 * 60 * 60);
                        $viewed_query = $conn->query("SELECT id FROM user_announcement_views WHERE user_id = $user_id AND announcement_id = {$a['id']}");
                        $isViewed = $viewed_query->num_rows > 0;
                        $showNewBadge = $isNew && !$isViewed;
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card-glass h-100 d-flex flex-column">
                            <div class="announcement-title mb-2">
                                <i class="bi bi-megaphone text-primary me-2"></i><?= htmlspecialchars($a['title']) ?>
                            </div>
                            <div class="announcement-description mb-2">
                                <?= nl2br(htmlspecialchars($a['message'])) ?>
                            </div>
                            <div class="announcement-meta mb-2">
                                <span class="announcement-badge">
                                    <i class="bi bi-megaphone me-1"></i>Announcement
                                    <?php if($showNewBadge): ?>
                                        <span class="badge bg-success ms-2" style="font-size: 0.6rem; padding: 2px 6px;">NEW</span>
                                    <?php endif; ?>
                                </span>
                                <span class="faculty-text"><i class="bi bi-person-circle text-primary me-1"></i><?= htmlspecialchars(ucfirst($a['posted_role'])) ?><?= $a['name'] ? " - {$a['name']}" : "" ?></span>
                            </div>
                            <div class="announcement-footer mt-auto">
                                <i class="bi bi-calendar text-muted me-1"></i><?= date("M d, Y", strtotime($a['created_at'])) ?> • <?= date("H:i", strtotime($a['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update announcement count in other pages after viewing
        setTimeout(function() {
            // Mark announcements as viewed after 3 seconds
            if (!window.location.href.includes('mark_viewed=1')) {
                const currentUrl = window.location.href;
                const separator = currentUrl.includes('?') ? '&' : '?';
                window.location.href = currentUrl + separator + 'mark_viewed=1';
            }
        }, 3000);
    </script>
</body>
</html>
