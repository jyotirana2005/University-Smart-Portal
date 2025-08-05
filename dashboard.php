<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

switch ($_SESSION['role']) {
    case 'student':
        header('Location: student_dashboard.php');
        break;
    case 'faculty':
        header('Location: faculty_dashboard.php');
        break;
    case 'hod':
        header('Location: hod_dashboard.html');
        break;
    case 'admin':
        header('Location: admin_dashboard.html');
        break;
    default:
        echo "Unknown role.";
}
exit();
?>