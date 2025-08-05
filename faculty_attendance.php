<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['faculty','hod'])) {
    header('Location: login.php');
    exit();
}

$success = $error = "";
$show_attendance_form = true; 
$selected_subject = ""; // Don't auto-populate from session initially
$selected_date = $selected_department = $selected_semester = $selected_section = "";
$view_attendance_detail = false;
$attendance_data = [];

// Check for session messages
if (isset($_SESSION['import_success'])) {
    $success = $_SESSION['import_success'];
    unset($_SESSION['import_success']);
}

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $section = mysqli_real_escape_string($conn, $_POST['section']);

    if (empty($department) || empty($semester) || empty($section)) {
        $error = "Please provide department, semester, and section information.";
    } else if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $imported_count = 0;
        $duplicate_count = 0;
        
        if ($file !== FALSE) {
            fgetcsv($file, 1000, ',');
            
            while (($data = fgetcsv($file, 1000, ',')) !== FALSE) {
                if (count($data) >= 2 && !empty(trim($data[0])) && !empty(trim($data[1]))) {
                    $name = mysqli_real_escape_string($conn, trim($data[0]));
                    $roll = mysqli_real_escape_string($conn, trim($data[1]));
                    
                    $check_query = "SELECT id FROM students WHERE roll = '$roll' AND department = '$department' AND semester = '$semester' AND section = '$section'";
                    $check_result = mysqli_query($conn, $check_query);
                    
                    if (mysqli_num_rows($check_result) == 0) {
                        $insert = "INSERT INTO students (roll, name, department, semester, section) VALUES ('$roll', '$name', '$department', '$semester', '$section')";
                        if (mysqli_query($conn, $insert)) {
                            $imported_count++;
                        }
                    } else {
                        $duplicate_count++;
                    }
                }
            }
            fclose($file);
            
            if ($imported_count > 0) {
                $message = "Successfully imported $imported_count students.";
                if ($duplicate_count > 0) {
                    $message .= " ($duplicate_count duplicates skipped)";
                }
                $_SESSION['import_success'] = $message;
                header("Location: faculty_attendance.php?dept=" . urlencode($department) . "&sem=" . urlencode($semester) . "&sec=" . urlencode($section) . "&action=mark_attendance");
                exit();
            } else {
                $error = "No new students imported. $duplicate_count duplicates found.";
            }
        } else {
            $error = "Failed to read CSV file.";
        }
    } else {
        $error = "CSV file is empty.";
    }
}

// Handle Attendance Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance_data'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    
    $_SESSION['last_subject'] = $subject;
    
    // Enhanced duplicate check
    $check_query = "SELECT COUNT(DISTINCT a.student_id) as count 
                   FROM attendance a 
                   JOIN students s ON s.id = a.student_id 
                   WHERE a.date = '$date' AND a.subject = '$subject' 
                   AND s.department = '$department' AND s.semester = '$semester' AND s.section = '$section'";
    $check_result = mysqli_query($conn, $check_query);
    $existing = mysqli_fetch_assoc($check_result)['count'];
    
    if ($existing > 0) {
        $error = "Attendance for '$subject' on " . date('M j, Y', strtotime($date)) . " already exists for this class. Please choose a different subject or date.";
        $show_attendance_form = true;
        $selected_subject = $subject;
        $selected_date = $date;
        $selected_department = $department;
        $selected_semester = $semester;
        $selected_section = $section;
    } else {
        $attendance_json = json_decode($_POST['attendance_data'], true);
        $inserted_count = 0;
        
        foreach ($attendance_json as $student_id => $status) {
            $student_id = intval($student_id);
            $status = mysqli_real_escape_string($conn, $status);
            
            $insert = "INSERT INTO attendance (student_id, subject, date, department, semester, section, status, faculty_id) VALUES ('$student_id', '$subject', '$date', '$department', '$semester', '$section', '$status', '{$_SESSION['user_id']}')";
            if (mysqli_query($conn, $insert)) {
                $inserted_count++;
            }
        }
        
        $success = "Attendance submitted successfully for $inserted_count students.";
        $show_attendance_form = false;
    }
}

// Auto-populate from URL parameters
if (isset($_GET['dept']) && isset($_GET['sem']) && isset($_GET['sec'])) {
    $selected_department = $_GET['dept'];
    $selected_semester = $_GET['sem'];
    $selected_section = $_GET['sec'];
    
    $selected_subject = "";
    $show_attendance_form = true;
    
    if (isset($_GET['action']) && $_GET['action'] == 'mark_attendance') {
        $show_attendance_form = true;
    }
}

// Reuse from Card
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_again'])) {
    $selected_subject = !empty($_POST['subject']) ? $_POST['subject'] : "";
    $selected_date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $selected_department = $_POST['department'];
    $selected_semester = $_POST['semester'];
    $selected_section = $_POST['section'];
    $show_attendance_form = true;
} else if (!isset($_GET['dept']) && !isset($_POST['view_attendance']) && !isset($_FILES['csv_file']) && !isset($_POST['attendance_data'])) {
    $show_attendance_form = true;
}

// View Attendance Detail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['view_attendance'])) {
    $selected_subject = $_POST['subject'];
    $selected_date = $_POST['date'];
    $selected_department = $_POST['department'];
    $selected_semester = $_POST['semester'];
    $selected_section = $_POST['section'];
    $view_attendance_detail = true;

    $q = "SELECT s.name, s.roll, s.department, s.section, a.subject, a.date, a.status 
          FROM attendance a
          JOIN students s ON s.id = a.student_id
          WHERE a.date = '$selected_date' AND a.subject = '$selected_subject'
            AND a.department = '$selected_department'
            AND a.semester = '$selected_semester'
            AND a.section = '$selected_section'";
    $res = mysqli_query($conn, $q);
    while ($row = mysqli_fetch_assoc($res)) {
        $attendance_data[] = $row;
    }
}

// Check for existing attendance
$attendance_warning = "";
if ($show_attendance_form && $selected_department && $selected_semester && $selected_section && !empty($selected_date)) {
    $existing_subjects_query = "SELECT DISTINCT a.subject 
                               FROM attendance a 
                               JOIN students s ON s.id = a.student_id 
                               WHERE a.date = '$selected_date' 
                               AND s.department = '$selected_department' 
                               AND s.semester = '$selected_semester' 
                               AND s.section = '$selected_section'";
    $existing_subjects_result = mysqli_query($conn, $existing_subjects_query);
    
    if (mysqli_num_rows($existing_subjects_result) > 0) {
        $existing_subjects = [];
        while ($row = mysqli_fetch_assoc($existing_subjects_result)) {
            $existing_subjects[] = $row['subject'];
        }
        $date_display = ($selected_date == date('Y-m-d')) ? 'today' : date('M j, Y', strtotime($selected_date));
        $attendance_warning = "Attendance marked $date_display for " . implode(', ', $existing_subjects) . ".";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Attendance System | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .profile-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 40px;
            margin-top: 20px;
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

        .container-fluid {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            min-height: 100vh;
            padding: 20px;
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

        .attendance-header {
            text-align: center;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.05));
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .feature-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
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

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 30px;
            margin-right: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar-item {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(52, 152, 219, 0.1);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .sidebar-item:hover {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.05));
            border-color: rgba(52, 152, 219, 0.3);
            transform: translateX(5px);
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 15px 20px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            background: white;
            transform: translateY(-2px);
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

        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(39, 174, 96, 0.4);
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 15px 20px;
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

        .student-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 2px solid rgba(52, 152, 219, 0.1);
            padding: 20px;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.2);
            border-color: rgba(52, 152, 219, 0.3);
        }

        .student-card.present {
            border-color: #27ae60;
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.1), rgba(39, 174, 96, 0.05));
        }

        .student-card.absent {
            border-color: #e74c3c;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1), rgba(231, 76, 60, 0.05));
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .student-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .student-details {
            flex: 1;
        }

        .student-name {
            font-weight: 700;
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 3px;
        }

        .student-roll {
            color: #666;
            font-size: 0.9rem;
        }

        .attendance-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .btn-present, .btn-absent {
            flex: 1;
            padding: 12px;
            border: 2px solid;
            border-radius: 10px;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
        }

        .btn-present {
            border-color: #27ae60;
            color: #27ae60;
        }

        .btn-present:hover, .btn-present.active {
            background: #27ae60;
            color: white;
            transform: scale(1.05);
        }

        .btn-absent {
            border-color: #e74c3c;
            color: #e74c3c;
        }

        .btn-absent:hover, .btn-absent.active {
            background: #e74c3c;
            color: white;
            transform: scale(1.05);
        }

        .attendance-status {
            text-align: center;
            font-weight: 600;
            padding: 8px;
            border-radius: 8px;
            display: none;
        }

        .attendance-status.present {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            display: block;
        }

        .attendance-status.absent {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            display: block;
        }

        .group-card {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(52, 152, 219, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-align: center;
        }

        .group-card:hover {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(52, 152, 219, 0.05));
            border-color: rgba(52, 152, 219, 0.3);
            transform: translateY(-5px);
        }

        .section-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>

<body>
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container">
            <a class="navbar-brand" href="<?= $_SESSION['role'] == 'hod' ? 'hod_dashboard.html' : 'faculty_dashboard.php' ?>">
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
                <a class="nav-link active" href="faculty_attendance.php">
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
            <?php elseif ($_SESSION['role'] == 'hod'): ?>
            <!-- Navigation Links for HOD -->
            <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
                <a class="nav-link" href="hod_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="hod_leave_approval.php">
                    <i class="bi bi-clipboard-check me-1"></i>Leave Approval
                </a>
                <a class="nav-link" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <a class="nav-link active" href="faculty_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a>
                <a class="nav-link" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link" href="discussion_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
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

    <div class="container py-4">
      <div class="profile-card main-container">
        <!-- Profile Header (Attendance) -->
        <div class="profile-header">
          <div class="row align-items-center">
            <div class="col-auto">
              <div class="user-avatar">
                <i class="bi bi-clipboard2-check-fill"></i>
              </div>
            </div>
            <div class="col-auto">
              <div>
                <h2 class="mb-2 fw-bold text-start">Faculty Attendance</h2>
                <p class="mb-0 opacity-75 text-start">
                  <i class="bi bi-gear me-2"></i>Mark and manage student attendance records
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

<div class="container-fluid py-4">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-lg-3">
            <div class="sidebar">
                <h5>
                    <i class="bi bi-clock-history"></i>
                    <?php if ($view_attendance_detail): ?>
                        Viewing Details
                    <?php else: ?>
                        Past Attendance
                    <?php endif; ?>
                </h5>
                <?php
                $side_q = mysqli_query($conn, "SELECT DISTINCT a.date, a.subject, a.department, a.semester, a.section, 
                                               COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                                               COUNT(a.student_id) as total_count
                                               FROM attendance a 
                                               GROUP BY a.date, a.subject, a.department, a.semester, a.section 
                                               ORDER BY a.date DESC LIMIT 10");
                if (mysqli_num_rows($side_q) > 0) {
                    while ($att = mysqli_fetch_assoc($side_q)) {
                        $attendance_rate = round(($att['present_count'] / $att['total_count']) * 100, 1);
                        echo "<div class='sidebar-item'>
                                <form method='POST' class='m-0'>
                                    <input type='hidden' name='view_attendance' value='1'>
                                    <input type='hidden' name='subject' value='{$att['subject']}'>
                                    <input type='hidden' name='date' value='{$att['date']}'>
                                    <input type='hidden' name='department' value='{$att['department']}'>
                                    <input type='hidden' name='semester' value='{$att['semester']}'>
                                    <input type='hidden' name='section' value='{$att['section']}'>
                                    <button type='submit' style='background:none;border:none;width:100%;text-align:left;'>
                                        <div class='d-flex justify-content-between align-items-center mb-2'>
                                            <div class='fw-bold'>" . date('M j, Y', strtotime($att['date'])) . "</div>
                                            <span class='badge bg-primary'>{$attendance_rate}%</span>
                                        </div>
                                        <div class='fw-medium text-start'>{$att['subject']}</div>
                                        <small class='text-muted'>{$att['department']} - Sem {$att['semester']} - Sec {$att['section']}</small>
                                    </button>
                                </form>
                              </div>";
                    }
                } else {
                    echo "<div class='text-center text-muted py-4'>
                            <i class='bi bi-inbox display-4 d-block mb-3'></i>
                            <p>No attendance records found</p>
                          </div>";
                }
                ?>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-lg-9">
            <!-- Status Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i><?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <!-- SUBJECT MISMATCH WARNING -->
            <?php if ($attendance_warning): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= $attendance_warning ?>
                </div>
            <?php endif; ?>

            <!-- CLASS SELECTION CARDS (Show when no specific class selected) -->
            <?php if (!$show_attendance_form || (!$selected_department && !$selected_semester && !$selected_section)): ?>
            <div class="card-glass">
                <h4 class="section-title">
                    <i class="bi bi-clipboard-plus"></i>Select Class for Attendance
                </h4>
                <p class="text-muted mb-4">Choose a class to mark attendance. Click on any class card below to begin.</p>
                
                <div class="row">
                    <?php
                    $groups = mysqli_query($conn, "SELECT DISTINCT department, semester, section FROM students ORDER BY department, semester, section");
                    if (mysqli_num_rows($groups) > 0) {
                        while ($g = mysqli_fetch_assoc($groups)) {
                            echo "<div class='col-md-4 col-lg-3 mb-3'>
                                    <div class='group-card'>
                                        <form method='POST' class='m-0'>
                                            <input type='hidden' name='mark_again' value='1'>
                                            <input type='hidden' name='department' value='{$g['department']}'>
                                            <input type='hidden' name='semester' value='{$g['semester']}'>
                                            <input type='hidden' name='section' value='{$g['section']}'>
                                            <input type='hidden' name='subject' value=''>
                                            <input type='hidden' name='date' value='" . date('Y-m-d') . "'>
                                            <button type='submit' style='background:none;border:none;width:100%;text-align:center;'>
                                                <i class='bi bi-people-fill d-block mb-2' style='font-size: 2.5rem; color: #3498db;'></i>
                                                <div class='fw-bold mb-1' style='font-size: 1.1rem;'>{$g['department']}</div>
                                                <small class='text-muted d-block'>Semester {$g['semester']}</small>
                                                <small class='text-muted d-block'>Section {$g['section']}</small>
                                                <div class='mt-2'>
                                                    <span class='badge bg-primary'>Select Class</span>
                                                </div>
                                            </button>
                                        </form>
                                    </div>
                                  </div>";
                        }
                    } else {
                        echo "<div class='col-12'>
                                <div class='text-center text-muted py-5'>
                                    <i class='bi bi-inbox display-1 d-block mb-3'></i>
                                    <h5>No Student Groups Found</h5>
                                    <p class='mb-3'>No classes have been set up yet. You need to import students first.</p>
                                </div>
                              </div>";
                    }
                    ?>
                </div>
            </div>

            <!-- QUICK START SECTION -->
            <div class="card-glass">
                <h4 class="section-title">
                    <i class="bi bi-lightning-charge"></i>Quick Start - New Class
                </h4>
                <p class="text-muted mb-4">Don't see your class above? Create a new class by filling the form below:</p>
                
                <form method="POST" class="row g-3">
                    <input type="hidden" name="mark_again" value="1">
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" placeholder="e.g. BBA, BCA, MBA" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-control" required>
                            <option value="">Select Semester</option>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                            <option value="3">3rd Semester</option>
                            <option value="4">4th Semester</option>
                            <option value="5">5th Semester</option>
                            <option value="6">6th Semester</option>
                            <option value="7">7th Semester</option>
                            <option value="8">8th Semester</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Section</label>
                        <input type="text" name="section" class="form-control" placeholder="e.g. A, B, C" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Start with This Class
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- MAIN ATTENDANCE SECTIONS (Only show when class is selected) -->
            <?php if (!$view_attendance_detail && $show_attendance_form && $selected_department && $selected_semester && $selected_section): ?>
            <div id="attendanceSections">
                <!-- ATTENDANCE FORM -->
                <div class="card-glass">
                    <h4 class="section-title">
                        <i class="bi bi-info-circle"></i>Attendance Information
                    </h4>
                    
                    <form id="attendanceForm" method="POST">
                        <input type="hidden" id="subject" name="subject" value="<?= htmlspecialchars($selected_subject) ?>">
                        <input type="hidden" id="date" name="date" value="<?= htmlspecialchars($selected_date ?: date('Y-m-d')) ?>">
                        <input type="hidden" id="department" name="department" value="<?= htmlspecialchars($selected_department) ?>">
                        <input type="hidden" id="semester" name="semester" value="<?= htmlspecialchars($selected_semester) ?>">
                        <input type="hidden" id="section" name="section" value="<?= htmlspecialchars($selected_section) ?>">
                        
                        <div class="alert alert-info mb-4">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Class Selected:</strong> 
                            <?= htmlspecialchars($selected_department) ?> - 
                            Semester <?= htmlspecialchars($selected_semester) ?> - 
                            Section <?= htmlspecialchars($selected_section) ?>
                            <a href="faculty_attendance.php" class="btn btn-sm btn-outline-primary ms-3">
                                <i class="bi bi-arrow-left me-1"></i>Change Class
                            </a>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Subject</label>
                                <input name="subject_display" id="subject_display" placeholder="Enter subject name" class="form-control" value="<?= $selected_subject ?>" onchange="updateHiddenField('subject', this.value)" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date</label>
                                <input type="date" name="date_display" id="date_display" class="form-control" value="<?= $selected_date ?: date('Y-m-d') ?>" onchange="updateHiddenField('date', this.value)" required>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- IMPORT CSV -->
                <div class="card-glass">
                    <h4 class="section-title">
                        <i class="bi bi-upload"></i>Import Students (CSV)
                    </h4>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>CSV Format:</strong> First column should be student name, second column should be roll number. Header row will be automatically skipped.
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" id="csvForm">
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Select CSV File
                            </label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        
                        <input type="hidden" name="department" id="csv_department" value="<?= htmlspecialchars($selected_department) ?>">
                        <input type="hidden" name="semester" id="csv_semester" value="<?= htmlspecialchars($selected_semester) ?>">
                        <input type="hidden" name="section" id="csv_section" value="<?= htmlspecialchars($selected_section) ?>">
                        
                        <div class="alert alert-success mb-3">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Using class details:</strong> 
                            <?= htmlspecialchars($selected_department) ?> - 
                            Semester <?= htmlspecialchars($selected_semester) ?> - 
                            Section <?= htmlspecialchars($selected_section) ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>Import Students
                        </button>
                    </form>
                </div>

                <!-- Students Cards -->
                <div class="card-glass">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="section-title mb-0">
                            <i class="bi bi-people"></i>Mark Student Attendance
                        </h4>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-success" onclick="markAllPresent()">
                                <i class="bi bi-check-all me-1"></i>Mark All Present
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="markAllAbsent()">
                                <i class="bi bi-x-lg me-1"></i>Mark All Absent
                            </button>
                        </div>
                    </div>
                    
                    <div id="studentsContainer">
                        <?php
                        $where = "department='" . mysqli_real_escape_string($conn, $selected_department) . "' AND semester='" . mysqli_real_escape_string($conn, $selected_semester) . "' AND section='" . mysqli_real_escape_string($conn, $selected_section) . "'";
                        $student_query = "SELECT * FROM students WHERE $where ORDER BY roll ASC";
                        $students = mysqli_query($conn, $student_query);
                        
                        if (mysqli_num_rows($students) > 0) {
                            echo "<div class='row'>";
                            while ($row = mysqli_fetch_assoc($students)) {
                                echo "<div class='col-md-6 col-lg-4 mb-3'>
                                        <div class='student-card' data-student-id='{$row['id']}'>
                                            <div class='student-info'>
                                                <div class='student-avatar'>
                                                    <i class='bi bi-person-fill'></i>
                                                </div>
                                                <div class='student-details'>
                                                    <div class='student-name'>{$row['name']}</div>
                                                    <div class='student-roll'>Roll: {$row['roll']}</div>
                                                </div>
                                            </div>
                                            <div class='attendance-buttons'>
                                                <button type='button' class='btn-present' onclick='markAttendance({$row['id']}, \"Present\")'>
                                                    <i class='bi bi-check-circle'></i>
                                                    <span>Present</span>
                                                </button>
                                                <button type='button' class='btn-absent' onclick='markAttendance({$row['id']}, \"Absent\")'>
                                                    <i class='bi bi-x-circle'></i>
                                                    <span>Absent</span>
                                                </button>
                                            </div>
                                            <div class='attendance-status'></div>
                                        </div>
                                      </div>";
                            }
                            echo "</div>";
                        } else {
                            echo "<div class='text-center text-muted py-5'>
                                    <i class='bi bi-inbox display-1 d-block mb-3'></i>
                                    <h5>No Students Found</h5>
                                    <p>Please import students first using CSV upload.</p>
                                  </div>";
                        }
                        ?>
                    </div>
                    
                    <div class="text-center mt-4" id="submitContainer" style="display: none;">
                        <button type="button" class="btn btn-success btn-lg" onclick="submitAttendance()">
                            <i class="bi bi-check2-circle me-2"></i>Submit Attendance
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- VIEW ATTENDANCE DETAIL SECTION -->
            <?php if ($view_attendance_detail): ?>
                <div class="card-glass">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="section-title mb-0">
                            <i class="bi bi-calendar-check"></i>Attendance Details
                        </h4>
                        <a href="faculty_attendance.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Main View
                        </a>
                    </div>
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-calendar3 text-primary"></i>
                                    <strong>Date:</strong> <?= date('F j, Y', strtotime($selected_date)) ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-book text-primary"></i>
                                    <strong>Subject:</strong> <?= htmlspecialchars($selected_subject) ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-building text-primary"></i>
                            <strong>Class:</strong> <?= htmlspecialchars($selected_department) ?> - Semester <?= htmlspecialchars($selected_semester) ?> - Section <?= htmlspecialchars($selected_section) ?>
                        </div>
                    </div>
                    
                    <?php if (count($attendance_data) > 0): ?>
                        <div class="list-group">
                            <?php 
                            $present_count = 0;
                            $total_count = count($attendance_data);
                            foreach ($attendance_data as $entry): 
                                if ($entry['status'] === 'Present') $present_count++;
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($entry['name']) ?></div>
                                        <small class="text-muted">
                                            Roll: <?= htmlspecialchars($entry['roll']) ?> | 
                                            <?= htmlspecialchars($entry['department']) ?> - <?= htmlspecialchars($entry['section']) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?= $entry['status'] === 'Present' ? 'success' : 'danger' ?> fs-6">
                                        <i class="bi bi-<?= $entry['status'] === 'Present' ? 'check-circle' : 'x-circle' ?> me-1"></i>
                                        <?= $entry['status'] ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4 p-3" style="background: rgba(52, 152, 219, 0.1); border-radius: 15px;">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="fw-bold text-success fs-4"><?= $present_count ?></div>
                                    <small class="text-muted">Present</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-bold text-danger fs-4"><?= $total_count - $present_count ?></div>
                                    <small class="text-muted">Absent</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-bold text-primary fs-4"><?= round(($present_count/$total_count)*100, 1) ?>%</div>
                                    <small class="text-muted">Attendance Rate</small>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-4 d-block mb-3"></i>
                            <h5>No Attendance Data Found</h5>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div> <!-- Close col-lg-9 -->
    </div> <!-- Close row -->
</div> <!-- Close container-fluid -->
</div> <!-- Close profile-card main-container -->
</div> <!-- Close container py-4 -->

<script>
    let attendanceData = {};
    
    function updateCSVFields() {
        let department = '';
        let semester = '';
        let section = '';
        
        const deptVisible = document.getElementById('department_visible');
        const semVisible = document.getElementById('semester_visible');
        const secVisible = document.getElementById('section_visible');
        
        const deptHidden = document.getElementById('department');
        const semHidden = document.getElementById('semester');
        const secHidden = document.getElementById('section');
        
        department = (deptVisible && deptVisible.value) || (deptHidden && deptHidden.value) || '';
        semester = (semVisible && semVisible.value) || (semHidden && semHidden.value) || '';
        section = (secVisible && secVisible.value) || (secHidden && secHidden.value) || '';
        
        const csvDept = document.getElementById('csv_department');
        const csvSem = document.getElementById('csv_semester');
        const csvSec = document.getElementById('csv_section');
        
        if (csvDept) csvDept.value = department;
        if (csvSem) csvSem.value = semester;
        if (csvSec) csvSec.value = section;
        
        const statusDiv = document.getElementById('csvStatusMessage');
        if (statusDiv) {
            if (department && semester && section) {
                statusDiv.innerHTML = `
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Using class details:</strong> 
                        ${department} - Semester ${semester} - Section ${section}
                    </div>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Please fill class details first.
                    </div>
                `;
            }
        }
    }

    function updateHiddenField(fieldName, value) {
        const hiddenField = document.getElementById(fieldName);
        if (hiddenField) {
            hiddenField.value = value;
        }

        if (fieldName === 'subject' && value.trim()) {
            localStorage.setItem('last_subject', value.trim());
        }

        updateCSVFields();
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCSVFields();

        const savedSubject = localStorage.getItem('last_subject');
        if (savedSubject) {
            const subjectDisplay = document.getElementById('subject_display');
            const subjectVisible = document.getElementById('subject_visible');
            const subjectHidden = document.getElementById('subject');

            if (subjectDisplay && subjectDisplay.value === '') {
                subjectDisplay.value = savedSubject;
                updateHiddenField('subject', savedSubject);
            }
            if (subjectVisible && subjectVisible.value === '') {
                subjectVisible.value = savedSubject;
                updateHiddenField('subject', savedSubject);
            }
            if (subjectHidden && subjectHidden.value === '') {
                subjectHidden.value = savedSubject;
            }
        }
    });
    
    function markAttendance(studentId, status) {
        attendanceData[studentId] = status;
        
        const card = document.querySelector(`[data-student-id="${studentId}"]`);
        if (!card) return;
        
        const presentBtn = card.querySelector('.btn-present');
        const absentBtn = card.querySelector('.btn-absent');
        const statusDiv = card.querySelector('.attendance-status');
        
        if (presentBtn) presentBtn.classList.remove('active');
        if (absentBtn) absentBtn.classList.remove('active');
        card.classList.remove('present', 'absent');
        if (statusDiv) statusDiv.classList.remove('present', 'absent');
        
        if (status === 'Present') {
            if (presentBtn) presentBtn.classList.add('active');
            card.classList.add('present');
            if (statusDiv) {
                statusDiv.classList.add('present');
                statusDiv.innerHTML = '<i class="bi bi-check-circle me-1"></i>Present';
            }
        } else {
            if (absentBtn) absentBtn.classList.add('active');
            card.classList.add('absent');
            if (statusDiv) {
                statusDiv.classList.add('absent');
                statusDiv.innerHTML = '<i class="bi bi-x-circle me-1"></i>Absent';
            }
        }
        
        const submitContainer = document.getElementById('submitContainer');
        if (submitContainer && Object.keys(attendanceData).length > 0) {
            submitContainer.style.display = 'block';
        }
    }
    
    function markAllPresent() {
        const studentCards = document.querySelectorAll('.student-card');
        studentCards.forEach(card => {
            const studentId = card.getAttribute('data-student-id');
            if (studentId) {
                markAttendance(studentId, 'Present');
            }
        });
    }
    
    function markAllAbsent() {
        const studentCards = document.querySelectorAll('.student-card');
        studentCards.forEach(card => {
            const studentId = card.getAttribute('data-student-id');
            if (studentId) {
                markAttendance(studentId, 'Absent');
            }
        });
    }
    
    function submitAttendance() {
        let subject = '';
        let date = '';
        let department = '';
        let semester = '';
        let section = '';
        
        const subjectDisplay = document.getElementById('subject_display');
        const subjectVisible = document.getElementById('subject_visible');
        const subjectHidden = document.getElementById('subject');
        
        if (subjectDisplay) {
            subject = subjectDisplay.value.trim();
        } else if (subjectVisible) {
            subject = subjectVisible.value.trim();
        } else if (subjectHidden) {
            subject = subjectHidden.value.trim();
        }
        
        const dateDisplay = document.getElementById('date_display');
        const dateVisible = document.getElementById('date_visible');
        const dateHidden = document.getElementById('date');
        
        if (dateDisplay) {
            date = dateDisplay.value;
        } else if (dateVisible) {
            date = dateVisible.value;
        } else if (dateHidden) {
            date = dateHidden.value;
        }
        
        const deptVisible = document.getElementById('department_visible');
        const deptHidden = document.getElementById('department');
        const semVisible = document.getElementById('semester_visible');
        const semHidden = document.getElementById('semester');
        const secVisible = document.getElementById('section_visible');
        const secHidden = document.getElementById('section');
        
        department = (deptVisible && deptVisible.value.trim()) || (deptHidden && deptHidden.value.trim()) || '';
        semester = (semVisible && semVisible.value) || (semHidden && semHidden.value) || '';
        section = (secVisible && secVisible.value.trim()) || (secHidden && secHidden.value.trim()) || '';
        
        if (!subject || !date || !department || !semester || !section) {
            alert('Please fill all required fields.');
            return;
        }
        
        if (Object.keys(attendanceData).length === 0) {
            alert('Please mark attendance for at least one student.');
            return;
        }

        const submitBtn = document.querySelector('#submitContainer button');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const fields = {
            'subject': subject,
            'date': date,
            'department': department,
            'semester': semester,
            'section': section,
            'attendance_data': JSON.stringify(attendanceData)
        };
        
        for (let key in fields) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
    }
</script>
</body>
</html>