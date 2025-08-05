<?php
session_start();
include 'config.php';

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $department = $_POST['department'];
    $employee_id = $_POST['employee_id'];
    $designation = $_POST['designation'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $qualification = $_POST['qualification'];
    $experience = (int)$_POST['experience'];
    $specialization = $_POST['specialization'];
    $address = $_POST['address'];

    // Update main users table
    $update_user = $conn->prepare("UPDATE users SET name = ?, department = ? WHERE id = ?");
    $update_user->bind_param("ssi", $name, $department, $user_id);
    
    // Insert or update faculty profile using ON DUPLICATE KEY UPDATE
    $update_profile = $conn->prepare("INSERT INTO faculty_profiles (user_id, employee_id, designation, phone, email, qualification, experience, specialization, address) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE 
                                     employee_id = VALUES(employee_id),
                                     designation = VALUES(designation),
                                     phone = VALUES(phone),
                                     email = VALUES(email),
                                     qualification = VALUES(qualification),
                                     experience = VALUES(experience),
                                     specialization = VALUES(specialization),
                                     address = VALUES(address)");
    $update_profile->bind_param("isssssiss", $user_id, $employee_id, $designation, $phone, $email, $qualification, $experience, $specialization, $address);
    
    if ($update_user->execute() && $update_profile->execute()) {
        header("Location: faculty_profile.php?success=1");
        exit();
    } else {
        $message = "Error updating profile!";
    }
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Profile updated successfully!";
}
}

// Fetch current user data
$stmt = $conn->prepare("SELECT u.name, u.email, u.department, 
                              fp.employee_id, fp.designation, fp.phone, fp.qualification, 
                              fp.experience, fp.specialization, fp.address 
                       FROM users u 
                       LEFT JOIN faculty_profiles fp ON u.id = fp.user_id 
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
    <title>Faculty Profile | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes checkmark {
            0% { transform: scale(0) rotate(0deg); }
            100% { transform: scale(1) rotate(360deg); }
        }

        .profile-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 40px;
            margin-top: 20px;
            animation: slideInUp 0.6s ease-out;
        }

        .profile-header {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 40px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* Removed animated gola (circle) from profile header */

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 6px;
            background: #7f8c8d;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: white;
            border: 3px solid #95a5a6;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        .profile-avatar::after {
            content: '📷';
            position: absolute;
            bottom: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.9);
            color: #3498db;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .profile-avatar:hover::after {
            opacity: 1;
        }

        .form-control {
            border-radius: 4px;
            border: 2px solid #e9ecef;
            padding: 15px 20px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.8);
            background-image: none !important;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
            transform: translateY(-2px);
            background: white;
        }

        .validation-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .form-control.is-valid + .validation-icon.valid {
            opacity: 1;
            color: #28a745;
            animation: checkmark 0.5s ease;
            background-image: none !important;
        }

        .form-control.is-invalid + .validation-icon.invalid {
            opacity: 1;
            color: #dc3545;
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
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.2s ease;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(231, 76, 60, 0.2);
        }

        .btn-back:hover {
            background: #DC2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3498db;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #374151;
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
            background: #3498db;
            color: white;
            transform: translateX(5px);
        }

        .alert {
            border-radius: 6px;
            border: none;
            padding: 15px 20px;
        }

        .alert-success {
            background: #FFFFFF;
            color: #155724;
            border: 1px solid #E5E7EB;
            border-left: 4px solid #3498db;
            border-radius: 6px;
            animation: slideInUp 0.5s ease;
        }

        .alert-danger {
            background: #FFFFFF;
            color: #721c24;
            border: 1px solid #E5E7EB;
            border-left: 4px solid #e74c3c;
            border-radius: 6px;
            animation: slideInUp 0.5s ease;
        }

        .progress-container {
            margin-bottom: 30px;
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background: #e9ecef;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #3498db;
            border-radius: 4px;
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
            padding: 12px 0;
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
                <a class="nav-link active" href="faculty_profile.php">
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
                    <div class="col-auto">
                        <div>
                            <h2 class="mb-2 fw-bold text-start">Faculty Profile</h2>
                            <p class="mb-0 opacity-75 text-start">
                                <i class="bi bi-gear me-2"></i>Update your personal information and settings
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
            <form method="POST">
                <h4 class="section-title">
                    <i class="bi bi-person-circle me-2"></i>Personal Information
                </h4>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($user_data['name'] ?? '') ?>" required>
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="employee_id" class="form-label">Employee ID</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="employee_id" name="employee_id" 
                                       value="<?= htmlspecialchars($user_data['employee_id'] ?? '') ?>" 
                                       placeholder="Enter Employee ID">
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <div class="position-relative">
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" 
                                       placeholder="Enter Email">
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="position-relative">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>"
                                       placeholder="Enter your mobile number">
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="department" class="form-label">Department</label>
                            <div class="position-relative dropdown">
                                <input type="text" class="form-control dropdown-toggle" id="department" name="department" 
                                       value="<?= htmlspecialchars($user_data['department'] ?? '') ?>" 
                                       placeholder="Type or select your department" 
                                       data-bs-toggle="dropdown" 
                                       autocomplete="off" required>
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                                <ul class="dropdown-menu w-100" id="departmentDropdown">
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Computer Science')">Computer Science</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Electronics')">Electronics</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Mechanical')">Mechanical</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Civil')">Civil</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Electrical')">Electrical</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Chemical')">Chemical</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Information Technology')">Information Technology</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Biotechnology')">Biotechnology</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Aerospace')">Aerospace</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Mathematics')">Mathematics</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Physics')">Physics</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDepartment('Chemistry')">Chemistry</a></li>
                                </ul>
                            </div>
                            <small class="text-muted">Type your department or click dropdown arrow to select from list</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="designation" class="form-label">Designation</label>
                            <div class="position-relative dropdown">
                                <input type="text" class="form-control dropdown-toggle" id="designation" name="designation" 
                                       value="<?= htmlspecialchars($user_data['designation'] ?? '') ?>" 
                                       placeholder="Type or select your designation" 
                                       data-bs-toggle="dropdown" 
                                       autocomplete="off">
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                                <ul class="dropdown-menu w-100">
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDesignation('Assistant Professor')">Assistant Professor</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDesignation('Associate Professor')">Associate Professor</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDesignation('Professor')">Professor</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="event.preventDefault();selectDesignation('Dean')">Dean</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="section-title">
                    <i class="bi bi-mortarboard me-2"></i>Academic Information
                </h4>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="qualification" class="form-label">Qualification</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="qualification" name="qualification" 
                                       value="<?= htmlspecialchars($user_data['qualification'] ?? '') ?>" 
                                       placeholder="e.g., Ph.D., M.Tech">
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="experience" class="form-label">Experience (Years)</label>
                            <div class="position-relative">
                                <input type="number" class="form-control" id="experience" name="experience" 
                                       value="<?= htmlspecialchars($user_data['experience'] ?? '') ?>" 
                                       placeholder="Years of Experience">
                                <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="specialization" class="form-label">Specialization</label>
                            <div class="position-relative">
                                <textarea class="form-control" id="specialization" name="specialization" rows="3" 
                                          placeholder="Areas of expertise and research interests"><?= htmlspecialchars($user_data['specialization'] ?? '') ?></textarea>
                            <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="address" class="form-label">Address</label>
                            <div class="position-relative">
                                <textarea class="form-control" id="address" name="address" rows="2" 
                                          placeholder="Complete Address"><?= htmlspecialchars($user_data['address'] ?? '') ?></textarea>
                            <span class="validation-icon valid"><i class="bi bi-check-lg"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-update btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectDepartment(dept) {
            var deptInput = document.getElementById('department');
            deptInput.value = dept;
            calculateProgress();
            // Trigger input event for validation and tick
            deptInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        function selectDesignation(designation) {
            var desigInput = document.getElementById('designation');
            desigInput.value = designation;
            calculateProgress();
            // Trigger input event for validation and tick
            desigInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function calculateProgress() {
            const fields = ['name', 'employee_id', 'email', 'phone', 'department', 'designation', 'qualification', 'experience', 'specialization', 'address'];
            let filledFields = 0;

            fields.forEach(field => {
                const element = document.getElementById(field);
                if (element && element.value.trim() !== '') {
                    filledFields++;
                }
            });

            const percentage = Math.round((filledFields / fields.length) * 100);
            document.querySelector('.progress-percentage').textContent = percentage + '%';
            document.querySelector('.progress-fill').style.width = percentage + '%';
        }

        // Form validation
        // Real-time validation for profile form (student profile logic)
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
                case 'number':
                    isValid = value !== '' && !isNaN(value);
                    break;
                case 'textarea':
                    isValid = value.length >= 5;
                    break;
                default:
                    isValid = value !== '';
            }

            field.classList.remove('is-valid', 'is-invalid');
            const tick = field.nextElementSibling;
            if (value !== '' && isValid) {
                field.classList.add('is-valid');
                if (tick && tick.classList.contains('validation-icon')) tick.style.opacity = '1';
            } else {
                field.classList.remove('is-valid');
                if (tick && tick.classList.contains('validation-icon')) tick.style.opacity = '0';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            calculateProgress();
            document.querySelectorAll('.form-control').forEach(input => {
                // Store initial value
                input.dataset.initialValue = input.value;
                input.addEventListener('input', function() {
                    calculateProgress();
                    validateField(input, true);
                });
                // Initial validation state: do not show tick for pre-filled fields
                validateField(input, false);
            });

            // Remove change event, rely on input event for tick logic

            function validateField(field, checkChanged) {
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
                    case 'number':
                        isValid = value !== '' && !isNaN(value);
                        break;
                    case 'textarea':
                        isValid = value.length >= 5;
                        break;
                    default:
                        isValid = value !== '';
                }

                field.classList.remove('is-valid', 'is-invalid');
                const tick = field.nextElementSibling;
                // Only show tick if valid AND (field changed OR checkChanged is false)
                const changed = field.dataset.initialValue !== undefined && field.dataset.initialValue !== value;
                if (value !== '' && isValid && (checkChanged ? changed : false)) {
                    field.classList.add('is-valid');
                    if (tick && tick.classList.contains('validation-icon')) tick.style.opacity = '1';
                } else {
                    field.classList.remove('is-valid');
                    if (tick && tick.classList.contains('validation-icon')) tick.style.opacity = '0';
                }
            }
        });
    </script>

</body>
</html>
