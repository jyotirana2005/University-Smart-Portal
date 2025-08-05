<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty','hod','admin'])) {
    header('Location: login.php');
    exit();
}
$error = "";

// Handle delete operation
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ? AND posted_by = ?");
    $stmt->bind_param("ii", $delete_id, $_SESSION['user_id']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Announcement deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete announcement.";
    }
    $stmt->close();
    header('Location: post_announcement.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $posted_by = $_SESSION['user_id'];
    $posted_role = $_SESSION['role'];

    $stmt = $conn->prepare("INSERT INTO announcements (title, message, posted_by, posted_role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $title, $message, $posted_by, $posted_role);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Announcement posted successfully! Your announcement has been shared with the university community.";
    } else {
        $_SESSION['error'] = "Failed to post announcement.";
    }
    $stmt->close();
    header('Location: post_announcement.php');
    exit();
}

// Get messages from session and clear them
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "";
$error = isset($_SESSION['error']) ? $_SESSION['error'] : "";
if ($success_message) unset($_SESSION['success_message']);
if ($error) unset($_SESSION['error']);

// Fetch previous announcements by this user
$myann = [];
$stmt = $conn->prepare("SELECT id, title, message, created_at FROM announcements WHERE posted_by=? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($aid, $atitle, $amsg, $acreated);
while ($stmt->fetch()) {
    $myann[] = [
        'id' => $aid,
        'title' => $atitle,
        'message' => $amsg,
        'created_at' => $acreated
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Announcement | University Smart Portal</title>
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

        .dashboard-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
        }

        .announcement-header {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
            overflow: hidden;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            font-size: 2rem;
            font-weight: bold;
            color: white;
            border: 2px solid #95a5a6;
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Navigation Menu Styles - Exact Dashboard Copy */
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

        h3, h4 {
            color: #333;
            font-weight: 700;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 15px 20px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            background: white;
            transform: translateY(-2px);
        }

        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .announcement-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 25px;
            transition: all 0.4s ease;
            overflow: hidden;
            position: relative;
        }

        .announcement-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(52, 152, 219, 0.25);
            border-color: rgba(52, 152, 219, 0.4);
        }

        .announcement-card:hover .announcement-card-header {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(52, 152, 219, 0.1));
        }

        .announcement-card:hover .title-icon {
            transform: rotate(5deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .announcement-card:hover .announcement-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .announcement-card-header {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.05));
            padding: 20px 25px;
            border-bottom: 2px solid rgba(52, 152, 219, 0.1);
            position: relative;
        }

        .announcement-actions {
            position: absolute;
            top: 20px;
            right: 20px;
            opacity: 0;
            transform: translateX(20px);
            transition: all 0.3s ease;
            display: flex;
            gap: 8px;
            z-index: 2;
        }

        .badge.bg-light.text-dark.px-3.py-2 {
            font-weight:500;
            position: relative;
            z-index: 1;
        }

        .announcement-title {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .title-icon {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 10px;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .action-btn.delete-btn {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }

        .action-btn.delete-btn:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
            color: white;
        }

        .announcement-card-body {
            padding: 25px;
        }

        .announcement-text {
            color: #555;
            line-height: 1.6;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .announcement-meta {
            background: rgba(52, 152, 219, 0.05);
            padding: 15px 20px;
            border-radius: 12px;
            border-left: 4px solid #3498db;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .meta-icon {
            color: #3498db;
            font-size: 1rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            animation: slideInUp 0.5s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #3498db;
            opacity: 0.3;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .dashboard-card {
                padding: 15px;
                margin-top: 10px;
            }
            .user-avatar {
                width: 45px;
                height: 45px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container">
            <a class="navbar-brand" href="<?= $_SESSION['role'] == 'hod' ? 'hod_dashboard.php' : 'faculty_dashboard.php' ?>">
                <div class="brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2"><?= ucfirst($_SESSION['role']) ?></span>
            </a>
            
            <!-- Navigation Links for Faculty -->
            <?php if ($_SESSION['role'] == 'faculty'): ?>
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link" href="faculty_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <!-- <a class="nav-link" href="faculty_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a> -->
                <a class="nav-link active" href="post_announcement.php">
                    <i class="bi bi-megaphone me-1"></i>Announcement
                </a>
                <a class="nav-link" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link" href="discussion_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link" href="faculty_profile.php">
                    <i class="bi bi-person-circle me-1"></i>Profile
                </a>
            </div>
            <?php elseif ($_SESSION['role'] == 'hod'): ?>
            <!-- Navigation Links for HOD -->
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link" href="hod_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="hod_leave_approval.php">
                    <i class="bi bi-clipboard-check me-1"></i>Leave Approval
                </a>
                <a class="nav-link active" href="post_announcement.php">
                    <i class="bi bi-megaphone me-1"></i>Announcements
                </a>
                <a class="nav-link" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <a class="nav-link" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Logout Button -->
            <div class="d-flex align-items-center">
                <a href="logout.php" class="btn logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="dashboard-card">
            <!-- Header Section (Profile style for Announcements) -->
            <div class="announcement-header mb-4 position-relative d-flex align-items-center">
                <div class="user-avatar me-3">
                    <i class="bi bi-megaphone-fill" style="font-size:2rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h2 class="mb-1 fw-bold">Post Announcement</h2>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-broadcast me-2"></i>Share important updates with the university community
                    </p>
                </div>
                <div class="text-end d-none d-md-block">
                    <p class="mb-1 opacity-75">Today's Date</p>
                    <span style="display: block; font-size: 1.25rem; color: #fff; font-weight: 600; margin-bottom: 0;">
                        <?= date('M d, Y') ?>
                    </span>
                </div>
            </div>

            <!-- Stat Card: Only Announcements Posted -->
            <div class="row mb-4 g-3">
                <div class="col-md-4">
                    <div class="stat-card h-100 d-flex align-items-center justify-content-center p-0" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border-radius: 18px; box-shadow: 0 4px 16px rgba(52,152,219,0.12);">
                        <div class="d-flex align-items-center gap-3 p-4 w-100">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.18); width: 60px; height: 60px;">
                                <i class="bi bi-megaphone-fill" style="font-size:2rem; color:white;"></i>
                            </div>
                            <div>
                                <div class="display-5 fw-bold mb-1" style="color:white; line-height:1;"><?= count($myann) ?></div>
                                <div class="fs-5" style="color:#eaf6fb; font-weight:500;">Announcements Posted</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcement Form Section -->
            <div class="card mb-4 shadow-sm border-0" style="border-radius:18px;">
                <div class="card-body p-4">
                    <h3 class="mb-4 d-flex align-items-center">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>
                        Post New Announcement
                    </h3>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success mb-3"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" id="announcementForm" autocomplete="off">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label">Announcement Title</label>
                                <input name="title" class="form-control" maxlength="255" placeholder="Enter announcement title (e.g., Important Exam Schedule Update)" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message Content</label>
                                <textarea name="message" class="form-control" required rows="5" placeholder="Write your announcement here... Include all important details for the university community."></textarea>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-3 mt-3">
                                    <button class="btn btn-primary px-4" type="submit">
                                        <i class="bi bi-send me-2"></i>Post Announcement
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Posted Announcements Section -->
            <h3 class="mb-4 mt-5">
                <i class="bi bi-collection me-2 text-primary"></i>
                Your Posted Announcements
            </h3>

            <?php if (count($myann) == 0): ?>
                <div class="empty-state">
                    <i class="bi bi-megaphone-fill"></i>
                    <h4>No Announcements Posted Yet</h4>
                    <p class="text-muted">You haven't posted any announcements yet. Create your first announcement above to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach ($myann as $a): ?>
                    <div class="announcement-card mb-4">
                        <!-- Action Buttons (visible on hover) -->
                        <div class="announcement-actions">
                            <a href="?delete=<?= $a['id'] ?>" class="action-btn delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.')">
                                <i class="bi bi-trash"></i>
                                Delete
                            </a>
                        </div>
                        <div class="announcement-card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="announcement-title mb-0">
                                    <div class="title-icon">
                                        <i class="bi bi-bell-fill"></i>
                                    </div>
                                    <?= htmlspecialchars($a['title']) ?>
                                </h5>
                                <span class="badge bg-light text-dark px-3 py-2" style="font-weight:500;">
                                    <i class="bi bi-clock me-1"></i> <?= date("d M Y, H:i", strtotime($a['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="announcement-card-body">
                            <div class="announcement-meta mb-2">
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="meta-item">
                                        <i class="bi bi-calendar meta-icon"></i>
                                        <span>Posted <?= date("M j, Y", strtotime($a['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="badge">
                                        <i class="bi bi-people me-1"></i>Public Announcement
                                    </span>
                                </div>
                            </div>
                            <p class="announcement-text mb-0"><?= nl2br(htmlspecialchars($a['message'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>