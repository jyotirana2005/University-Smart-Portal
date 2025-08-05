<?php
session_start();
include 'config.php';

$error = "";
$showSuccess = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $hashed, $role);
        $stmt->fetch();
        if (password_verify($password, $hashed)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $role;

            switch ($role) {
                case 'student':
                    header("Location: student_dashboard.php");
                    break;
                case 'faculty':
                    header("Location: faculty_dashboard.php");
                    break;
                case 'hod':
                    header("Location: hod_dashboard.php");
                    break;
                default:
                    $error = "Invalid user role.";
                    break;
            }
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #e2e6f3 0%, #bfcbe6 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .split-card {
            max-width: 950px;
            width: 100%;
            margin: auto;
            background: transparent;
            border-radius: 28px;
            box-shadow: 0 10px 32px rgba(61,80,193,0.13);
            display: flex;
            overflow: hidden;
        }
        .welcome-side {
            flex: 1.1;
            background: linear-gradient(135deg, #34495e 0%, #3d50c1 100%);
            color: #fff;
            padding: 3rem 2.2rem 3rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-top-left-radius: 28px;
            border-bottom-left-radius: 28px;
            box-shadow: 0 4px 32px rgba(61,80,193,0.10);
            position: relative;
            backdrop-filter: blur(3px);
            /* Glass effect */
            background-clip: padding-box;
        }
        .welcome-side h2 {
            color: #fff;
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.7rem;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(61,80,193,0.10);
        }
        .welcome-side p {
            color: #e2e6f3;
            font-size: 1.15rem;
            margin-bottom: 1.2rem;
        }
        .internship-credit {
            font-size: 1rem;
            color: #e2e6f3;
            margin-bottom: 1.5rem;
            opacity: 0.93;
        }
        .features-list {
            margin-top: 1.2rem;
            padding: 1.2rem 1rem 0.2rem 0;
            border-radius: 14px;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 2px 8px rgba(61,80,193,0.07);
            max-width: 400px;
        }
        .features-list .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
            color: #f4f6fa;
            margin-bottom: 10px;
        }
        .features-list .feature-icon {
            background: #fff;
            color: #3d50c1;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 2px 8px rgba(61,80,193,0.10);
        }
        .login-side {
            flex: 1;
            padding: 3rem 2.2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(255,255,255,0.98);
            border-top-right-radius: 28px;
            border-bottom-right-radius: 28px;
            box-shadow: -2px 0 16px rgba(61,80,193,0.07);
        }
        .brand-logo {
            width: 60px;
            margin-bottom: 10px;
        }
        h3 {
            color: #3d50c1;
            font-weight: 700;
        }
        .form-label {
            color: #2e3c60;
            font-weight: 500;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: all 0.2s ease-in-out;
        }
        .form-control:focus {
            border-color: #3d50c1;
            box-shadow: 0 0 0 0.15rem rgba(61, 80, 193, 0.18);
        }
        .btn-primary {
            background: linear-gradient(90deg, #3d50c1 70%, #5a7bd8 100%);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.08rem;
            box-shadow: 0 2px 8px rgba(61,80,193,0.08);
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #5a7bd8 70%, #3d50c1 100%);
        }
        .register-link {
            color: #3d50c1;
            text-decoration: none;
            font-weight: 500;
        }
        .register-link:hover {
            text-decoration: underline;
        }
        .alert {
            font-size: 0.95rem;
            border-radius: 8px;
        }
        .show-password {
            cursor: pointer;
            color: #3d50c1;
            font-size: 1.1rem;
            position: absolute;
            right: 18px;
            top: 38px;
            z-index: 2;
        }
        @media (max-width: 900px) {
            .split-card {
                flex-direction: column;
                max-width: 98vw;
            }
            .welcome-side, .login-side {
                border-radius: 28px;
                margin-bottom: 2rem;
                padding: 2rem 1rem;
            }
        }
        @media (max-width: 600px) {
            .welcome-side { padding: 1.2rem 0.5rem; }
            .login-side { padding: 1.2rem 0.5rem; }
            .features-list { padding: 1rem 0.3rem; }
        }
    </style>
</head>
<body>
    <div class="split-card">
        <div class="welcome-side">
            <h2>Welcome to University Smart Portal</h2>
            <p>
                Your all-in-one solution for campus management.<br>
                <b>Project:</b> University Smart Portal<br>
                <span class="internship-credit">
                    <i class="bi bi-award me-1"></i>
                    Developed as Internship Project &mdash; Summer 2025
                </span>
            </p>
            <div class="features-list mt-2">
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-clipboard-check"></i></span>
                    Attendance & Leave Tracking
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-calendar2-week"></i></span>
                    Course Planner & Announcements
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-chat-dots"></i></span>
                    Discussion Forum & Grievance Portal
                </div>
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-person-circle"></i></span>
                    Personalized Dashboard & Profile
                </div>
            </div>
        </div>
        <div class="login-side">
            <div class="text-center mb-2">
                <img src="https://img.icons8.com/ios-filled/100/3d50c1/graduation-cap.png" class="brand-logo" alt="Logo">
                <h3 class="mb-2 fw-bold">Login</h3>
                <div class="opacity-75 mb-2" style="font-size:1.05rem;">
                    <i class="bi bi-shield-lock-fill text-primary me-1"></i>
                    Secure Access
                </div>
            </div>
            <?php if ($showSuccess): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Registered successfully! Please login.</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="student@university.edu" required autofocus>
                </div>
                <div class="mb-3 position-relative">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                    <span class="show-password" onclick="togglePassword()" title="Show/Hide Password">
                        <i class="bi bi-eye-fill" id="eyeIcon"></i>
                    </span>
                </div>
                <button type="submit" class="btn btn-primary w-100 mt-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                </button>
            </form>
            <div class="text-center mt-3">
                <span>New user? <a href="register.php" class="register-link">Register here</a></span>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const eye = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.classList.remove('bi-eye-fill');
                eye.classList.add('bi-eye-slash-fill');
            } else {
                pwd.type = 'password';
                eye.classList.remove('bi-eye-slash-fill');
                eye.classList.add('bi-eye-fill');
            }
        }
    </script>
</body>
</html>