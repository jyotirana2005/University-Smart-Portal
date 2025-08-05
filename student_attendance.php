<?php
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
}

$records = [];
$student_info = null;

// Get user info and their roll number from student_profiles table
$user_query = $conn->prepare("SELECT u.name, u.email, sp.roll_number 
                             FROM users u 
                             LEFT JOIN student_profiles sp ON u.id = sp.user_id 
                             WHERE u.id = ?");
$user_query->bind_param("i", $_SESSION['user_id']);
$user_query->execute();
$user_result = $user_query->get_result();
$user_data = $user_result->fetch_assoc();
$user_query->close();

if ($user_data && $user_data['roll_number']) {
    $roll_number = $user_data['roll_number'];
    
    // Find student record by matching roll_number with roll in students table
    $student_query = $conn->prepare("SELECT id, name, roll, department, semester, section FROM students WHERE roll = ?");
    $student_query->bind_param("s", $roll_number);
    $student_query->execute();
    $student_result = $student_query->get_result();
    $student_info = $student_result->fetch_assoc();
    $student_query->close();
    
    // Get attendance records directly from attendance table using student_id
    if ($student_info) {
        $attendance_query = $conn->prepare("SELECT date, subject, status, department, semester, section 
                                          FROM attendance 
                                          WHERE student_id = ? 
                                          ORDER BY date DESC");
        $attendance_query->bind_param("i", $student_info['id']);
        $attendance_query->execute();
        $attendance_result = $attendance_query->get_result();
        
        while ($row = $attendance_result->fetch_assoc()) {
            $records[] = $row;
        }
        $attendance_query->close();
    }
}

$present = count(array_filter($records, fn($r) => $r['status']=='Present'));
$total = count($records);
$percent = $total ? round(($present/$total)*100,1) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Attendance | Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .attendance-header {
            background: #34495e;
            color: white;
            border-radius: 6px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .feature-icon {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 20px;
        }

        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .stats-card {
            background: white;
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
            color: white;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(52,152,219,0.15);
        }

        .stats-card.present {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }

        .stats-card.absent {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        }

        .stats-card.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stats-card.percentage {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .stats-label {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }

        .table-glass {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }

        .badge-present { 
            background: linear-gradient(135deg, #28a745, #20c997) !important; 
            color: white;
        }
        
        .badge-absent { 
            background: linear-gradient(135deg, #dc3545, #fd7e14) !important; 
            color: white;
        }

        .student-info-card {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(155, 89, 182, 0.1));
            border-left: 4px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .section-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .progress-ring {
            width: 120px;
            height: 120px;
            margin: 0 auto;
        }

        .progress-ring circle {
            fill: transparent;
            stroke-width: 8;
            stroke-linecap: round;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }

        .progress-ring .bg {
            stroke: #e9ecef;
        }

        .progress-ring .progress {
            stroke: #28a745;
            stroke-dasharray: 283;
            stroke-dashoffset: 283;
            transition: stroke-dashoffset 1s ease-in-out;
        }

        .no-records {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-records i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
                <a class="nav-link active" href="student_attendance.php">
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
        <div class="card-glass main-container">
            <!-- Header Section -->
            <div class="attendance-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">My Attendance Records</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-calendar-check me-2"></i>Track your attendance and academic progress
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
        <!-- Student Info Card -->
        <?php if ($student_info): ?>
            <div class="student-info-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-2">
                            <i class="bi bi-person-badge me-2"></i>Student Information
                        </h5>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Name:</strong> <?= htmlspecialchars($student_info['name']) ?><br>
                                <strong>Roll Number:</strong> <?= htmlspecialchars($student_info['roll']) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Department:</strong> <?= htmlspecialchars($student_info['department']) ?><br>
                                <strong>Semester:</strong> <?= htmlspecialchars($student_info['semester']) ?> | 
                                <strong>Section:</strong> <?= htmlspecialchars($student_info['section']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="progress-ring" style="position: relative;">
                            <svg width="120" height="120">
                                <circle class="bg" cx="60" cy="60" r="45"></circle>
                                <circle class="progress" cx="60" cy="60" r="45" 
                                        style="stroke-dashoffset: <?= 283 - (283 * $percent / 100) ?>"></circle>
                            </svg>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;"><?= $percent ?>%</div>
                                <div style="font-size: 0.8rem; color: #6c757d;">Overall</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Student Record Not Found</strong>
                        <?php if (!$user_data['roll_number']): ?>
                            <br><small>No roll number found in your profile. Please update your profile.</small>
                        <?php else: ?>
                            <br><small>Your Roll Number: <code><?= htmlspecialchars($user_data['roll_number']) ?></code></small>
                            <br><small>No matching student record found. Please contact faculty to ensure your attendance is being recorded.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($records)): ?>
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card present">
                        <div class="stats-number"><?= $present ?></div>
                        <div class="stats-label">
                            <i class="bi bi-check-circle me-1"></i>Days Present
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card absent">
                        <div class="stats-number"><?= $total - $present ?></div>
                        <div class="stats-label">
                            <i class="bi bi-x-circle me-1"></i>Days Absent
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card total">
                        <div class="stats-number"><?= $total ?></div>
                        <div class="stats-label">
                            <i class="bi bi-calendar-check me-1"></i>Total Classes
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stats-card percentage">
                        <div class="stats-number"><?= $percent ?>%</div>
                        <div class="stats-label">
                            <i class="bi bi-graph-up me-1"></i>Attendance Rate
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card-glass">
                        <h5 class="section-title">
                            <i class="bi bi-pie-chart"></i>Attendance Overview
                        </h5>
                        <div class="chart-container">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card-glass">
                        <h5 class="section-title">
                            <i class="bi bi-graph-up"></i>Subject-wise Attendance
                        </h5>
                        <div class="chart-container">
                            <canvas id="subjectChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Records Table -->
        <?php else: ?>
            <div class="no-records">
                <i class="bi bi-inbox"></i>
                <h4>No Attendance Records Found</h4>
                <p class="text-muted">Your attendance records will appear here once faculty starts marking attendance.</p>
            </div>
        <?php endif; ?>

        </div>
    </div>

    <!-- Attendance Records Table - Separate Card -->
    <div class="container" style="padding-top: 2px;">
        <div class="card-glass main-container">
            <?php if (!empty($records)): ?>
                <h5 class="section-title">
                    <i class="bi bi-table"></i>Detailed Attendance Records
                </h5>
                <div class="table-glass">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="bi bi-calendar-date me-1"></i>Date</th>
                                <th><i class="bi bi-book me-1"></i>Subject</th>
                                <th><i class="bi bi-check-circle me-1"></i>Status</th>
                                <th><i class="bi bi-building me-1"></i>Department</th>
                                <th><i class="bi bi-mortarboard me-1"></i>Semester</th>
                                <th><i class="bi bi-grid me-1"></i>Section</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('M j, Y', strtotime($r['date'])) ?></strong>
                                        <br><small class="text-muted"><?= date('l', strtotime($r['date'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-medium"><?= htmlspecialchars($r['subject']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $r['status']=='Present' ? 'badge-present' : 'badge-absent' ?> px-3 py-2">
                                            <i class="bi bi-<?= $r['status']=='Present' ? 'check-circle' : 'x-circle' ?> me-1"></i>
                                            <?= htmlspecialchars($r['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($r['department']) ?></td>
                                    <td><?= htmlspecialchars($r['semester']) ?></td>
                                    <td><?= htmlspecialchars($r['section']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-records">
                    <i class="bi bi-inbox"></i>
                    <h4>No Attendance Records Found</h4>
                    <p class="text-muted">Your attendance records will appear here once faculty starts marking attendance.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (!empty($records)): ?>
    <script>
        // Attendance Overview Pie Chart
        const ctx1 = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?= $present ?>, <?= $total - $present ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Subject-wise Chart
        <?php
        $subjects = [];
        $subject_present = [];
        $subject_total = [];
        
        foreach($records as $record) {
            $subject = $record['subject'];
            if (!isset($subjects[$subject])) {
                $subjects[$subject] = 0;
                $subject_present[$subject] = 0;
            }
            $subjects[$subject]++;
            if ($record['status'] == 'Present') {
                $subject_present[$subject]++;
            }
        }
        
        $subject_names = array_keys($subjects);
        $subject_percentages = [];
        foreach($subject_names as $subject) {
            $subject_percentages[] = round(($subject_present[$subject] / $subjects[$subject]) * 100, 1);
        }
        ?>
        
        const ctx2 = document.getElementById('subjectChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?= json_encode($subject_names) ?>,
                datasets: [{
                    label: 'Attendance %',
                    data: <?= json_encode($subject_percentages) ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
