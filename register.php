<?php
include 'config.php';
$error = "";
$showSuccess = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $department = $_POST['department'];

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed, $role, $department);
        if ($stmt->execute()) {
            $showSuccess = true;
        } else {
            $error = "Registration failed. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e2e6f3, #cfd6f0);
            min-height: 100vh;
        }
        .split-card {
            max-width: 1100px;
            margin: 5vh auto;
            background: transparent;
            border-radius: 28px;
            box-shadow: 0 10px 32px rgba(61,80,193,0.13);
            display: flex;
            overflow: hidden;
        }
        .register-card {
            flex: 1;
            background: rgba(255,255,255,0.97);
            border-radius: 28px 0 0 28px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.13);
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .brand-logo {
            width: 60px;
            margin-bottom: 10px;
        }
        .btn-primary {
            background: #4e54c8;
            border: none;
        }
        .btn-primary:hover {
            background: #3b3f99;
        }
        .login-link {
            color: #4e54c8;
            text-decoration: underline;
        }
        .info-side {
            flex: 1.1;
            background: linear-gradient(135deg, #34495e 0%, #3d50c1 100%);
            color: #fff;
            border-radius: 0 28px 28px 0;
            box-shadow: -2px 0 16px rgba(61,80,193,0.07);
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .info-side h2 {
            color: #fff;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.7rem;
            letter-spacing: 1px;
            text-shadow: 0 2px 8px rgba(61,80,193,0.10);
        }
        .info-side p {
            color: #e2e6f3;
            font-size: 1.08rem;
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
        @media (max-width: 900px) {
            .split-card {
                flex-direction: column;
                max-width: 98vw;
            }
            .register-card, .info-side {
                border-radius: 28px;
                margin-bottom: 2rem;
                padding: 2rem 1rem;
            }
        }
        @media (max-width: 600px) {
            .register-card { padding: 1.2rem 0.5rem; }
            .info-side { padding: 1.2rem 0.5rem; }
            .features-list { padding: 1rem 0.3rem; }
        }
    </style>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center" style="min-height:100vh;">
        <div class="split-card">
            <div class="register-card">
                <div class="text-center">
                    <img src="https://img.icons8.com/ios-filled/100/4e54c8/graduation-cap.png" class="brand-logo" alt="Logo">
                    <h3 class="mb-4 fw-bold">Create Your Account</h3>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="off">
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-semibold">Email address</label>
                            <input type="email" class="form-control" name="email" placeholder="student@university.edu" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Create password" required>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" class="form-control" name="department" placeholder="e.g. Computer Science">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select Role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                            <option value="hod">HOD</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Register</button>
                </form>
                <div class="text-center mt-3">
                    <span>Already have an account? <a href="login.php" class="login-link">Login here</a></span>
                </div>
            </div>
            <div class="info-side">
                <h2>University Smart Portal</h2>
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
        </div>
    </div>
    <!-- Bootstrap Modal for Success -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
          <div class="modal-body py-5">
            <svg width="64" height="64" fill="#4e54c8" class="mb-3" viewBox="0 0 16 16">
              <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM7 11.414l5.207-5.207-1.414-1.414L7 8.586 5.207 6.793 3.793 8.207 7 11.414z"/>
            </svg>
            <h4 class="mb-2">Registered Successfully!</h4>
            <p class="mb-4">You will be redirected to the login page.</p>
            <button type="button" class="btn btn-primary" id="goToLoginBtn">Go to Login</button>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($showSuccess): ?>
    <script>
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        setTimeout(function() {
            window.location.href = "login.php";
        }, 2500);
        document.getElementById('goToLoginBtn').onclick = function() {
            window.location.href = "login.php";
        };
    </script>
    <?php endif; ?>
</body>
</html>