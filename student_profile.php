<?php
session_start();
include 'config.php';

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $roll_number = $_POST['roll_number'];
    $semester = $_POST['semester'];
    $year = $_POST['year'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    // Update main users table
    $update_user = $conn->prepare("UPDATE users SET name = ?, department = ? WHERE id = ?");
    $update_user->bind_param("ssi", $name, $department, $user_id);

    
    // Insert or update student profile
    $update_profile = $conn->prepare("INSERT INTO student_profiles (user_id, roll_number, semester, year, phone, address) 
                                     VALUES (?, ?, ?, ?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE 
                                     roll_number = VALUES(roll_number),
                                     semester = VALUES(semester),
                                     year = VALUES(year),
                                     phone = VALUES(phone),
                                     address = VALUES(address)");
    $update_profile->bind_param("isssss", $user_id, $roll_number, $semester, $year, $phone, $address);
    
    if ($update_user->execute() && $update_profile->execute()) {
        $_SESSION['profile_message'] = "Profile updated successfully!";
        header("Location: student_profile.php");
        exit();
    } else {
        $_SESSION['profile_message'] = "Error updating profile!";
        header("Location: student_profile.php");
        exit();
    }
}

// Check for session message
$message = "";
if (isset($_SESSION['profile_message'])) {
    $message = $_SESSION['profile_message'];
    unset($_SESSION['profile_message']);
}

// Fetch current user data
$stmt = $conn->prepare("SELECT u.name, u.email, u.department, 
                              sp.roll_number, sp.semester, sp.year, sp.phone, sp.address 
                       FROM users u 
                       LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                       WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

        .profile-card {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .profile-card.main-container {
            padding: 30px;
            margin-top: 20px;
        }

        .profile-header {
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

        /* Advanced Form Features */
        .dropdown-menu {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            padding: 10px 0;
            max-height: 200px;
            overflow-y: auto;
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

        .form-control.is-valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.5-.01L8 1.28 6.72 0 2.8 3.92 1.28 2.4 0 3.68z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
            padding-right: calc(1.5em + 0.75rem);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background-image: none !important;
            padding-right: calc(1.5em + 0.75rem);
        }

        .form-control:focus.is-valid {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .form-control:focus.is-invalid {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        /* Navigation Menu Styles */
        /* .navbar {
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
            position: relative;
        

        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            opacity: 0.65;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        } */

        .validation-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .form-control.is-valid ~ .validation-icon.valid,
        .form-select.is-valid ~ .validation-icon.valid,
        textarea.form-control.is-valid ~ .validation-icon.valid {
            opacity: 1;
            color: #28a745;
            animation: checkmark 0.5s ease;
        }

        .form-control.is-invalid ~ .validation-icon.invalid,
        .form-select.is-invalid ~ .validation-icon.invalid,
        textarea.form-control.is-invalid ~ .validation-icon.invalid {
            opacity: 1;
            color: #dc3545;
            background: none !important;
            border: none !important;
        }

        .form-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group:focus-within .form-label {
            color: #3498db;
            transform: translateX(5px);
        }

        .btn-update {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-update::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn-update:hover::before {
            left: 100%;
        }

        .btn-update:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .btn-update:active {
            transform: translateY(-1px);
        }

        .btn-back {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.2);
        }

        .btn-back:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid transparent;
            background: linear-gradient(white, white) padding-box,
                       linear-gradient(135deg, #3498db, #2980b9) border-box;
            border-image: linear-gradient(135deg, #3498db, #2980b9) 1;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #333;
            font-size: 1.2rem;
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
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            transform: translateX(5px);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 15px 20px;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-left: 4px solid #3498db;
            border-radius: 15px;
            animation: slideInUp 0.5s ease;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border-left: 4px solid #e74c3c;
            border-radius: 15px;
            animation: slideInUp 0.5s ease;
        }

        .progress-container {
            margin-bottom: 30px;
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        .progress-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        /* Navigation Menu Styles */
        .navbar {
            background: white !important;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .custom-navbar-padding {
            padding-left: 4rem !important;
            padding-right: 2rem !important;
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

        .nav-link .badge {
            background: #e74c3c !important;
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
                    <?php 
                    // Get unread announcements count
                    $user_id = $_SESSION['user_id'];
                    $unread_query = "SELECT COUNT(*) as count FROM announcements a LEFT JOIN user_announcement_views uav ON a.id = uav.announcement_id AND uav.user_id = $user_id WHERE uav.id IS NULL AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
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
                <a class="nav-link" href="student_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link active" href="student_profile.php">
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
        <div class="profile-card main-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">Student Profile</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-gear me-2"></i>Update your personal information and settings
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

            <!-- Profile Completion Progress -->
            <div class="progress-container">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Profile Completion</span>
                    <span class="progress-percentage fw-bold">0%</span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text">Complete your profile to unlock all features</div>
            </div>

            <!-- Success/Error Message -->
            <?php if($message): ?>
                <div class="alert <?= strpos($message, 'success') !== false ? 'alert-success' : 'alert-danger' ?>" role="alert">
                    <i class="bi bi-<?= strpos($message, 'success') !== false ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                    <?= $message ?>
                </div>
            <?php endif; ?>



            <!-- Profile Update Form -->
            <h4 class="section-title">
                <i class="bi bi-person-circle text-primary me-2"></i>Personal Information
            </h4>
            <div class="card-glass">
                <form method="POST">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="position-relative form-group">
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($user_data['name'] ?? '') ?>" required>
                                <span class="validation-icon valid bi bi-check-lg"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="position-relative form-group">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>"
                                       placeholder="Enter your mobile number">
                                <span class="validation-icon valid bi bi-check-lg"></span>
                            </div>
                        </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department</label>
                        <div class="dropdown">
                            <input type="text" class="form-control dropdown-toggle" id="department" name="department" 
                                   value="<?= htmlspecialchars($user_data['department'] ?? '') ?>" 
                                   placeholder="Type or select your department" 
                                   data-bs-toggle="dropdown" 
                                   autocomplete="off" required>
                            
                            <ul class="dropdown-menu w-100" id="departmentDropdown">
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Computer Science')">Computer Science</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Electronics')">Electronics</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Mechanical')">Mechanical</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Civil')">Civil</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Electrical')">Electrical</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Chemical')">Chemical</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Information Technology')">Information Technology</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Biotechnology')">Biotechnology</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Aerospace')">Aerospace</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Mathematics')">Mathematics</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Physics')">Physics</a></li>
                                <li><a class="dropdown-item" href="#" onclick="selectDepartment('Chemistry')">Chemistry</a></li>
                            </ul>
                        </div>
                        <small class="text-muted">Type your department or click dropdown arrow to select from list</small>
                    </div>
                        <div class="col-md-4">
                            <label for="roll_number" class="form-label">Roll Number</label>
                            <input type="text" class="form-control" id="roll_number" name="roll_number" 
                                   value="<?= htmlspecialchars($user_data['roll_number'] ?? '') ?>" 
                                   placeholder="Enter your roll number">
                        </div>
                        <div class="col-md-4">
                            <label for="semester" class="form-label">Current Semester</label>
                            <select class="form-control" id="semester" name="semester">
                                <option value="">Select Semester</option>
                                <?php for($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($user_data['semester'] ?? '') == $i ? 'selected' : '' ?>>
                                        Semester <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Academic Year (Admission)</label>
                            <select class="form-control" id="year" name="year">
                                <option value="">Select Year</option>
                                <?php 
                                $current_year = date('Y');
                                for($i = $current_year; $i >= $current_year - 6; $i--): 
                                ?>
                                    <option value="<?= $i ?>" <?= ($user_data['year'] ?? '') == $i ? 'selected' : '' ?>>
                                        <?= $i ?> (Batch <?= $i ?>)
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" 
                                      placeholder="Enter your permanent address"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Department selection function
        function selectDepartment(dept) {
            document.getElementById('department').value = dept;
            const dropdown = new bootstrap.Dropdown(document.getElementById('department'));
            dropdown.hide();
            validateField(document.getElementById('department'));
            updateProgress();
        }
        
        // Auto-filter dropdown items as user types
        document.getElementById('department').addEventListener('input', function() {
            const input = this.value.toLowerCase();
            const items = document.querySelectorAll('#departmentDropdown .dropdown-item');
            
            items.forEach(function(item) {
                const text = item.textContent.toLowerCase();
                if (text.includes(input)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            validateField(this);
            updateProgress();
        });
        
        // Show all items when dropdown is opened
        document.getElementById('department').addEventListener('focus', function() {
            const items = document.querySelectorAll('#departmentDropdown .dropdown-item');
            items.forEach(function(item) {
                item.style.display = 'block';
            });
        });

        // Real-time form validation
        function validateField(field) {
            const value = field.value.trim();
            const fieldType = field.type || field.tagName.toLowerCase();
            let isValid = false;

            switch(fieldType) {
                case 'text':
                    isValid = value.length >= 2;
                    break;
                case 'email':
                    isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    break;
                case 'tel':
                    isValid = /^[0-9]{10}$/.test(value.replace(/\D/g, ''));
                    break;
                case 'select':
                    isValid = value !== '';
                    break;
                case 'textarea':
                    isValid = value.length >= 10;
                    break;
                default:
                    isValid = value !== '';
            }

            if (field.readOnly) return; // Skip validation for readonly fields

            // Show tick/cross only if field is changed from initial value
            if (field.dataset.initialValue === undefined) {
                field.dataset.initialValue = value;
            }
            const changed = field.dataset.initialValue !== value;
            field.classList.remove('is-valid', 'is-invalid');
            if (changed && value !== '') {
                if (isValid) {
                    field.classList.add('is-valid');
                } else {
                    field.classList.add('is-invalid');
                }
            }
        }

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                if (value.length >= 6) {
                    value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
                } else if (value.length >= 3) {
                    value = value.replace(/(\d{3})(\d+)/, '$1-$2');
                }
                e.target.value = value;
            }
            validateField(e.target);
            updateProgress();
        });

        // Progress calculation
        function updateProgress() {
            const fields = ['name', 'phone', 'department', 'roll_number', 'semester', 'year', 'address'];
            let completedFields = 0;
            
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field && field.value.trim() !== '') {
                    completedFields++;
                }
            });

            const percentage = Math.round((completedFields / fields.length) * 100);
            // Update progress display if elements exist
            const progressFill = document.querySelector('.progress-fill');
            const progressText = document.querySelector('.progress-percentage');
            
            if (progressFill && progressText) {
                progressFill.style.width = percentage + '%';
                progressText.textContent = percentage + '%';
            }
        }

        // Add validation to all form fields
        document.addEventListener('DOMContentLoaded', function() {
            const formFields = document.querySelectorAll('input:not([readonly]), select, textarea');
            formFields.forEach(field => {
                // Store initial value
                field.dataset.initialValue = field.value;
                field.addEventListener('blur', () => validateField(field));
                field.addEventListener('input', () => {
                    validateField(field);
                    updateProgress();
                });
                // Initial validation state: do not show tick/cross for pre-filled fields
                validateField(field);
            });

            // Initial progress calculation
            updateProgress();

            // Add form submission handler with loading state
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('.btn-primary');
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    submitBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i>Updating...';
                    submitBtn.disabled = true;
                    setTimeout(() => {
                        submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Update Profile';
                        submitBtn.disabled = false;
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>