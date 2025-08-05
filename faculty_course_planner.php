<?php
session_start();
include 'config.php';
$error = "";

// Handle delete operation
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM planned_courses WHERE id = ? AND posted_by = ?");
    $stmt->bind_param("ii", $delete_id, $_SESSION['user_id']);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Course deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete course.";
    }
    $stmt->close();
    header('Location: faculty_course_planner.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $semester = trim($_POST['semester']);
    $posted_by = $_SESSION['user_id'];
    $posted_role = $_SESSION['role'];

    $stmt = $conn->prepare("INSERT INTO planned_courses (title, description, semester, posted_by, posted_role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiss", $title, $desc, $semester, $posted_by, $posted_role);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Course posted successfully! Your course has been added and is now available for students.";
    } else {
        $_SESSION['error'] = "Failed to post course.";
    }
    $stmt->close();
    header('Location: faculty_course_planner.php');
    exit();
}

// Get messages from session and clear them
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : "";
$error = isset($_SESSION['error']) ? $_SESSION['error'] : "";
if ($success_message) unset($_SESSION['success_message']);
if ($error) unset($_SESSION['error']);

// Fetch posted courses by this faculty/hod
$mycourses = [];
$stmt = $conn->prepare("SELECT id, title, description, semester, created_at FROM planned_courses WHERE posted_by=? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($cid, $ctitle, $cdesc, $csem, $ccreated);
while ($stmt->fetch()) {
    $mycourses[] = [
        'id' => $cid,
        'title' => $ctitle,
        'description' => $cdesc,
        'semester' => $csem,
        'created_at' => $ccreated
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Course Planner | University Smart Portal</title>
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

        .welcome-section {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 25px;
            margin-bottom: 30px;
            position: relative;
        }

        .course-header {
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
        /* Removed animated gola (circle) from course planner header */
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
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
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

        .btn-outline-primary {
            border: 2px solid #3498db;
            color: #3498db;
            border-radius: 12px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-color: #3498db;
            color: white;
            transform: translateY(-2px);
        }

        .course-card {
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

        .course-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(52, 152, 219, 0.25);
            border-color: rgba(52, 152, 219, 0.4);
        }

        .course-card:hover .course-card-header {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(52, 152, 219, 0.1));
        }

        .course-card:hover .title-icon {
            transform: rotate(5deg) scale(1.1);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .course-card:hover .course-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .course-card-header {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.05));
            padding: 20px 25px;
            border-bottom: 2px solid rgba(52, 152, 219, 0.1);
            position: relative;
        }
        .course-actions {
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

        .course-title {
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

        .action-btn.edit-btn {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }

        .action-btn.edit-btn:hover {
            background: linear-gradient(135deg, #d68910 0%, #dc7633 100%);
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
            color: white;
        }

        .course-card-body {
            padding: 25px;
        }

        .course-text {
            color: #555;
            line-height: 1.6;
            font-size: 1rem;
            margin-bottom: 20px;
        }

        .course-meta {
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
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            color: #0c5460;
            border-left: 4px solid #3498db;
        }

        .custom-badge {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%) !important;
            border-radius: 10px;
            padding: 8px 15px;
            font-weight: 500;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            backdrop-filter: blur(20px);
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
            .welcome-section {
                padding: 12px;
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
                <a class="nav-link active" href="faculty_course_planner.php">
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

    <!-- Main Content -->
    <div class="container py-4">
        <div class="dashboard-card">
            <!-- Header Section (Profile style for Course Planner) -->
            <div class="course-header mb-4 position-relative d-flex align-items-center">
                <div class="user-avatar me-3">
                    <i class="bi bi-calendar2-week" style="font-size:2rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h2 class="mb-1 fw-bold">Faculty Course Planner</h2>
                    <p class="mb-0 opacity-75">
                        <i class="bi bi-gear me-2"></i>Create and manage courses for students
                    </p>
                </div>
                <div class="text-end d-none d-md-block">
                    <p class="mb-1 opacity-75">Today's Date</p>
                    <span style="display: block; font-size: 1.25rem; color: #fff; font-weight: 600; margin-bottom: 0;">
                        <?= date('M d, Y') ?>
                    </span>
                </div>
            </div>

            <!-- Stat Card: Only Courses Posted -->
            <div class="row mb-4 g-3">
                <div class="col-md-4">
                    <div class="stat-card h-100 d-flex align-items-center justify-content-center p-0" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border-radius: 18px; box-shadow: 0 4px 16px rgba(52,152,219,0.12);">
                        <div class="d-flex align-items-center gap-3 p-4 w-100">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.18); width: 60px; height: 60px;">
                                <i class="bi bi-journal-bookmark-fill" style="font-size:2rem; color:white;"></i>
                            </div>
                            <div>
                                <div class="display-5 fw-bold mb-1" style="color:white; line-height:1;"><?= count($mycourses) ?></div>
                                <div class="fs-5" style="color:#eaf6fb; font-weight:500;">Courses Posted</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Form Section -->
            <div class="card mb-4 shadow-sm border-0" style="border-radius:18px;">
                <div class="card-body p-4">
                    <h3 class="mb-4 d-flex align-items-center">
                        <i class="bi bi-plus-circle me-2 text-primary"></i>
                        Post New Course
                    </h3>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success mb-3"><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-3"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" id="courseForm" autocomplete="off">
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label">Course Title</label>
                                <input name="title" class="form-control" maxlength="255" placeholder="Enter course title (e.g., Advanced Database Systems)" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Course Description</label>
                                <textarea name="description" class="form-control" required rows="5" placeholder="Write detailed course description, learning objectives, and key topics that will be covered..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Semester</label>
                                <input name="semester" class="form-control" required placeholder="e.g. 3rd, 5th, 7th">
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-3 mt-3">
                                    <button class="btn btn-primary px-4" type="submit">
                                        <i class="bi bi-send me-2"></i>Post Course
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Posted Courses Section -->
            <h3 class="mb-4 mt-5">
                <i class="bi bi-collection me-2 text-primary"></i>
                Your Posted Courses
            </h3>

            <?php if (count($mycourses) == 0): ?>
                <div class="empty-state">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <h4>No Courses Posted Yet</h4>
                    <p class="text-muted">You haven't posted any courses yet. Create your first course above to get started!</p>
                </div>
            <?php else: ?>
                <?php foreach ($mycourses as $c): ?>
                    <div class="course-card mb-4">
                        <!-- Action Buttons (visible on hover) -->
                        <div class="course-actions">
                            <a href="?delete=<?= $c['id'] ?>" class="action-btn delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this course? This action cannot be undone.')">
                                <i class="bi bi-trash"></i>
                                Delete
                            </a>
                        </div>
                        <div class="course-card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <h5 class="course-title mb-0">
                                    <div class="title-icon">
                                        <i class="bi bi-book"></i>
                                    </div>
                                    <?= htmlspecialchars($c['title']) ?>
                                </h5>
                                <span class="badge bg-light text-dark px-3 py-2" style="font-weight:500;">
                                    <i class="bi bi-clock me-1"></i> <?= date("d M Y, H:i", strtotime($c['created_at'])) ?>
                                </span>
                            </div>
                            <div class="course-actions">
                                <a href="?delete=<?= $c['id'] ?>" class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this course? This action cannot be undone.')">
                                    <i class="bi bi-trash"></i>
                                    Delete
                                </a>
                            </div>
                        </div>
                        <div class="course-card-body">
                            <div class="course-meta mb-2">
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="meta-item">
                                        <i class="bi bi-calendar meta-icon"></i>
                                        <span><?= htmlspecialchars($c['semester']) ?> Semester</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <span class="badge">
                                        <i class="bi bi-people me-1"></i>Available to Students
                                    </span>
                                </div>
                            </div>
                            <p class="course-text mb-0"><?= nl2br(htmlspecialchars($c['description'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>