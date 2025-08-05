<?php
session_start();
include 'config.php';

if ($_SESSION['role'] !== 'hod') {
    die("Unauthorized access");
}

$approver_id = $_SESSION['user_id'];

// Check if rejection_reason column exists, if not add it
$check_column = $conn->query("SHOW COLUMNS FROM leave_applications LIKE 'rejection_reason'");
if ($check_column->num_rows == 0) {
    $conn->query("ALTER TABLE leave_applications ADD COLUMN rejection_reason TEXT NULL");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $leave_id = $_POST['leave_id'];
    $action = $_POST['action']; // 'Approved' or 'Rejected'
    $rejection_reason = isset($_POST['rejection_reason']) ? $_POST['rejection_reason'] : null;

    if ($action == 'Rejected' && $rejection_reason) {
        $stmt = $conn->prepare("UPDATE leave_applications SET status = ?, approver_id = ?, rejection_reason = ? WHERE id = ?");
        $stmt->bind_param("sisi", $action, $approver_id, $rejection_reason, $leave_id);
    } else {
        $stmt = $conn->prepare("UPDATE leave_applications SET status = ?, approver_id = ? WHERE id = ?");
        $stmt->bind_param("sii", $action, $approver_id, $leave_id);
    }
    $stmt->execute();
    $stmt->close();
}

$applications = $conn->query("
    SELECT l.*, u.name, u.role, u.department 
    FROM leave_applications l 
    JOIN users u ON u.id = l.user_id 
    ORDER BY l.applied_on DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>HOD Leave Approval Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* ======= MENU BAR STYLES (Used in Menu Bar) ======= */
        .navbar {
            background: white !important;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
        .nav-link:hover,
        .nav-link.active {
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
            border-radius: 4px !important;
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
        /* ======= END MENU BAR STYLES ======= */
        /* Faculty Dashboard Theme & Card Styles */
        body {
            background: linear-gradient(-45deg, #dfe9f3, #ffffff, #e2ebf0, #f2f6ff);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            overflow: scroll;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        .profile-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 40px;
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
        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 30px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        .card-glass:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        /* ======= FEATURE TILE CARD STYLES (Faculty Dashboard Style) ======= */
        .feature-tile {
            background: #ecf0f1;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border: 1px solid #bdc3c7;
            transition: all 0.2s ease;
            padding: 25px;
            text-align: center;
            height: 100%;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .feature-tile:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.25);
            border-color: #3498db;
            background: #e3f2fd;
        }
        .feature-tile:active {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
            border-color: #2980b9;
            background: #bbdefb;
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin: 0 auto 15px auto;
        }
        .feature-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 12px;
        }
        .feature-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 18px;
        }
        .feature-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
        }
        .feature-btn:hover {
            background: #2980b9;
            color: white;
        }
        /* Improved Count Cards */
        .stat-card {
            background: linear-gradient(135deg, #e3f0ff 0%, #f8fafc 100%);
            color: #34495e;
            border-radius: 12px;
            padding: 18px 12px;
            margin-bottom: 0;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.10);
            border: 1px solid #d0e3fa;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: box-shadow 0.2s, border-color 0.2s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            box-shadow: 0 6px 18px rgba(52, 152, 219, 0.13);
            border-color: #3498db;
            transform: translateY(-1px) scale(1.01);
        }
        .stat-number {
            font-size: 1.7rem;
            font-weight: 700;
            margin-bottom: 0;
            min-width: 38px;
            text-align: center;
            color: #3498db;
            text-shadow: 0 2px 8px rgba(52,152,219,0.08);
            letter-spacing: 0.5px;
        }
        .stat-label {
            font-size: 0.98rem;
            font-weight: 600;
            color: #34495e;
            margin-top: 2px;
            letter-spacing: 0.3px;
        }
        @media (max-width: 768px) {
            .stat-card {
                padding: 10px 6px;
                font-size: 0.95rem;
            }
            .stat-number {
                font-size: 1.2rem;
                min-width: 28px;
            }
        }

        /* Improved Filter Section */
        .stats-row {
            margin-bottom: 32px; /* Add gap after count cards */
        }

        .filter-section {
            background: rgba(255,255,255,0.97);
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(52,152,219,0.07);
            border: 1px solid #d0e3fa;
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .filter-section h5 {
            font-weight: 700;
            color: #3498db;
            margin-bottom: 0;
            font-size: 1.15rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-section .filter-label {
            font-weight: 600;
            color: #3498db;
            margin-bottom: 0;
            font-size: 1rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-section .form-select {
            border-radius: 10px;
            border: 2px solid #3498db;
            font-weight: 500;
            font-size: 1rem;
            padding: 10px 18px;
            background: #fff;
            color: #34495e;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(52,152,219,0.04);
            max-width: 220px;
        }

        .filter-section .form-select:focus {
            border-color: #2980b9;
            box-shadow: 0 0 0 0.15rem rgba(52, 152, 219, 0.15);
        }

        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
                align-items: stretch;
                padding: 12px 8px;
                gap: 10px;
            }
            .filter-section .form-select {
                width: 100%;
                max-width: 100%;
            }
        }
       
        /* Match leave_application.php table style */
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.1);
            overflow: hidden;
        }
        .table {
            margin: 0;
            border-radius: 20px;
            background: #fff;
        }
        .table thead th {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 20px 15px;
            text-align: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 700;
        }
        .table tbody td {
            padding: 18px 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.95rem;
            background: #fff;
        }
        .table tbody tr:hover {
            background: rgba(52, 152, 219, 0.08);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.15);
            transition: all 0.3s ease;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        /* .badge {
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 20px;
        } */
        .btn {
            border-radius: 25px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.3s ease;
            border: none;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-success {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
        }
        .btn-outline-secondary {
            border: 2px solid #6c757d;
            color: #6c757d;
            background: transparent;
        }
        .btn-outline-secondary:hover {
            background: #6c757d;
            color: white;
        }
        .applicant-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .applicant-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .leave-dates {
            font-size: 0.85rem;
            color: #666;
        }
        .reason-text {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>

<!-- ======= MENU BAR START (Faculty Dashboard Style) ======= -->
<nav class="navbar navbar-expand-lg navbar-light py-3">
    <div class="container">
        <a class="navbar-brand" href="hod_dashboard.php">
            <div class="brand-icon">
                <i class="bi bi-building-fill"></i>
            </div>
            University Smart Portal
            <span class="badge bg-primary ms-2">HOD</span>
        </a>
        <div class="navbar-nav ms-auto me-3 d-none d-lg-flex">
            <a class="nav-link" href="hod_dashboard.php">
                <i class="bi bi-house-door me-1"></i>Dashboard
            </a>
            <a class="nav-link active" href="hod_leave_approval.php">
                <i class="bi bi-calendar-check me-1"></i>Leave Approval
            </a>
            <a class="nav-link" href="hod_forum.php">
                <i class="bi bi-chat-dots me-1"></i>Forum
            </a>
            <!-- <a class="nav-link" href="faculty_attendence.php">
                <i class="bi bi-megaphone me-1"></i>Attendence
            </a> -->
            <a class="nav-link" href="hod_grievance_approval.php">
                    <i class="bi bi-exclamation-triangle me-1"></i>Grievance Approval
            </a>
            <a class="nav-link" href="hod_profile.php">
                <i class="bi bi-person-circle me-1"></i>Profile
            </a>
        </div>
        <div class="d-flex align-items-center">
            <a href="logout.php" class="btn logout-btn">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="profile-card main-container">
        <!-- Header Section (dashboard style) -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="user-avatar">
                        <i class="bi bi-building-fill"></i>
                    </div>
                </div>
                <div class="col-auto">
                    <div>
                        <h2 class="mb-2 fw-bold text-start"> Leave Applications Management</h2>
                        <p class="mb-0 opacity-75 text-start">
                            <i class="bi bi-gear me-2"></i>Review and approve leave requests from faculty and staff members
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
        
        

        <!-- Statistics Row -->
        <div class="row stats-row">
            <?php 
            $pending_count = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE status = 'Pending'")->fetch_assoc()['count'];
            $approved_count = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE status = 'Approved'")->fetch_assoc()['count'];
            $rejected_count = $conn->query("SELECT COUNT(*) as count FROM leave_applications WHERE status = 'Rejected'")->fetch_assoc()['count'];
            ?>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $pending_count ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $approved_count ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-number"><?= $rejected_count ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </div>

<!-- Filter Section -->
<div class="filter-section mb-4">
    <label for="statusFilter" class="filter-label mb-0 me-2">
        <i class="bi bi-funnel-fill me-1"></i>
        <span style="font-weight:600;font-size:1.08rem;">Filter by Status</span>
    </label>
    <select class="form-select me-2" id="statusFilter" style="width:200px;min-width:150px;">
        <option value="">All Status</option>
        <option value="Pending">Pending</option>
        <option value="Approved">Approved</option>
        <option value="Rejected">Rejected</option>
    </select>
    <button type="button" class="btn btn-outline-secondary btn-sm" id="resetFilterBtn">
        <i class="bi bi-arrow-clockwise"></i> Reset
    </button>
</div>

        <!-- Applications Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table" id="applicationsTable">
                    <thead>
                        <tr>
                            <th><i class="bi bi-person"></i> Applicant</th>
                            <th><i class="bi bi-briefcase"></i> Role & Dept</th>
                            <th><i class="bi bi-calendar-event"></i> Leave Details</th>
                            <th><i class="bi bi-chat-text"></i> Reason</th>
                            <th><i class="bi bi-flag"></i> Status</th>
                            <th><i class="bi bi-clock"></i> Applied</th>
                            <th><i class="bi bi-gear"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $applications->fetch_assoc()): ?>
<tr data-status="<?= $row['status'] ?>">
    <td>
        <div class="applicant-info">
            <div class="applicant-avatar">
                <?= strtoupper(substr($row['name'], 0, 2)) ?>
            </div>
            <div>
                <div class="fw-bold"><?= htmlspecialchars($row['name']) ?></div>
            </div>
        </div>
    </td>
    <td>
        <div class="fw-bold text-primary"><?= ucfirst($row['role']) ?></div>
        <small class="text-muted"><?= $row['department'] ?></small>
    </td>
    <td>
        <div class="fw-bold"><?= $row['leave_type'] ?></div>
        <div class="leave-dates">
            <i class="bi bi-calendar-date"></i> <?= date('M d', strtotime($row['from_date'])) ?> - <?= date('M d, Y', strtotime($row['to_date'])) ?>
        </div>
        <small class="text-muted">
            <?php 
            $days = ceil((strtotime($row['to_date']) - strtotime($row['from_date'])) / (60*60*24)) + 1;
            echo $days . ' day' . ($days > 1 ? 's' : '');
            ?>
        </small>
    </td>
    <td>
        <div class="reason-text" title="<?= htmlspecialchars($row['reason']) ?>">
            <?= htmlspecialchars($row['reason']) ?>
        </div>
    </td>
    <td>
        <span class="badge bg-<?= $row['status'] == 'Approved' ? 'success' : ($row['status'] == 'Rejected' ? 'danger' : 'warning') ?>">
            <i class="bi bi-<?= $row['status'] == 'Approved' ? 'check-circle' : ($row['status'] == 'Rejected' ? 'x-circle' : 'clock') ?>"></i>
            <?= $row['status'] ?>
        </span>
        <?php if ($row['status'] == 'Rejected' && !empty($row['rejection_reason'])): ?>
            <div class="mt-1 small text-danger" style="font-size:0.85em;">
                <i class="bi bi-info-circle"></i> <?= htmlspecialchars($row['rejection_reason']) ?>
            </div>
        <?php endif; ?>
    </td>
    <td>
        <div><?= date('M d, Y', strtotime($row['applied_on'])) ?></div>
        <small class="text-muted"><?= date('g:i A', strtotime($row['applied_on'])) ?></small>
    </td>
    <td>
        <?php if ($row['status'] == 'Pending'): ?>
            <div class="d-flex flex-row gap-2 align-items-center">
                <form method="POST" class="d-inline">
                    <input type="hidden" name="leave_id" value="<?= $row['id'] ?>">
                    <button name="action" value="Approved" class="btn btn-success btn-sm px-3" title="Approve" onclick="return confirm('Approve this leave application?')">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </form>
                <button class="btn btn-danger btn-sm px-3" title="Reject" onclick="openRejectModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        <?php else: ?>
            <span class="badge bg-secondary">
                <i class="bi bi-check2-all"></i> Reviewed
            </span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="bi bi-exclamation-triangle"></i> Reject Leave Application
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="rejectForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <p class="mb-3">You are about to reject the leave application for <strong id="applicantName"></strong>.</p>
                        <label for="rejectionReason" class="form-label">
                            <i class="bi bi-chat-text"></i> Reason for Rejection <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="rejectionReason" name="rejection_reason" rows="4" 
                                  placeholder="Please provide a clear reason for rejecting this leave application..." required></textarea>
                        <div class="form-text">This reason will be visible to the applicant.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="leave_id" id="rejectLeaveId">
                    <input type="hidden" name="action" value="Rejected">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-lg"></i> Reject Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Open reject modal
function openRejectModal(leaveId, applicantName) {
    document.getElementById('rejectLeaveId').value = leaveId;
    document.getElementById('applicantName').textContent = applicantName;
    document.getElementById('rejectionReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

// Filter functionality
document.getElementById('statusFilter').addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('#applicationsTable tbody tr');
    
    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (filterValue === '' || status === filterValue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});


// Filter functionality
document.getElementById('statusFilter').addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('#applicationsTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const status = row.getAttribute('data-status');
        if (filterValue === '' || status === filterValue) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide "No results found" row
    let noResultRow = document.getElementById('noResultRow');
    if (!noResultRow) {
        noResultRow = document.createElement('tr');
        noResultRow.id = 'noResultRow';
        noResultRow.innerHTML = `<td colspan="7" class="text-center text-muted py-4">No results found</td>`;
        document.querySelector('#applicationsTable tbody').appendChild(noResultRow);
    }
    noResultRow.style.display = (visibleCount === 0) ? '' : 'none';
});

// Show "No results found" on page load if needed
window.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#applicationsTable tbody tr');
    let visibleCount = 0;
    rows.forEach(row => {
        if (row.style.display !== 'none') visibleCount++;
    });
    let noResultRow = document.getElementById('noResultRow');
    if (!noResultRow) {
        noResultRow = document.createElement('tr');
        noResultRow.id = 'noResultRow';
        noResultRow.innerHTML = `<td colspan="7" class="text-center text-muted py-4">No results found</td>`;
        document.querySelector('#applicationsTable tbody').appendChild(noResultRow);
    }
    noResultRow.style.display = (visibleCount === 0) ? '' : 'none';
});

document.getElementById('resetFilterBtn').addEventListener('click', function() {
    // Reset dropdown
    document.getElementById('statusFilter').value = '';
    // Trigger filter change event to show all rows
    const event = new Event('change');
    document.getElementById('statusFilter').dispatchEvent(event);
    // Scroll to table
    setTimeout(() => {
        const tableSection = document.getElementById('applicationsTable');
        if (tableSection) {
            tableSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100);
});

// Add success/error messages
<?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])): ?>
    // Show success message
    const toast = document.createElement('div');
    toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <i class="bi bi-check-circle"></i> Leave application <?= strtolower($_POST['action']) ?> successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
<?php endif; ?>
</script>

</body>
</html>
