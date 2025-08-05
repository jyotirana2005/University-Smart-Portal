<?php
session_start();
require_once "config.php";

$logged_in_user_id = $_SESSION['user_id'] ?? 1;
$current_user = $conn->query("SELECT * FROM users WHERE id = $logged_in_user_id")->fetch_assoc();
$role = $current_user['role'] ?? 'faculty';

// AJAX endpoint for updating a reply
if (isset($_POST['ajax_update_reply'])) {
    $reply_id = intval($_POST['reply_id']);
    $message = $conn->real_escape_string($_POST['message']);
    $updated = false;
    $result = $conn->query("SELECT user_id FROM discussion_replies WHERE id=$reply_id");
    if ($result && $row = $result->fetch_assoc()) {
        if ($row['user_id'] == $logged_in_user_id) {
            $conn->query("UPDATE discussion_replies SET message='$message' WHERE id=$reply_id");
            $updated = true;
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => $updated, 'message' => htmlspecialchars($message)]);
    exit;
}

// AJAX endpoint for updating a post
if (isset($_POST['ajax_update_post'])) {
    $post_id = intval($_POST['post_id']);
    $message = $conn->real_escape_string($_POST['message']);
    $updated = false;
    $result = $conn->query("SELECT user_id FROM discussion_posts WHERE id=$post_id");
    if ($result && $row = $result->fetch_assoc()) {
        if ($row['user_id'] == $logged_in_user_id) {
            $conn->query("UPDATE discussion_posts SET message='$message' WHERE id=$post_id");
            $updated = true;
        }
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $updated,
        'message_html' => nl2br(htmlspecialchars($message))
    ]);
    exit;
}

// Only for new post, new reply, and delete (no edit/cancel for posts here)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $redirect_needed = false;
    $response = ['success' => false];

    if (isset($_POST['new_post'])) {
        $topic = $conn->real_escape_string($_POST['topic']);
        $message = $conn->real_escape_string($_POST['message']);
        $conn->query("INSERT INTO discussion_posts (user_id, topic, message) VALUES ($logged_in_user_id, '$topic', '$message')");
        $redirect_needed = true;
        $response['success'] = true;
    }
    if (isset($_POST['new_reply'])) {
        $post_id = intval($_POST['post_id']);
        $message = $conn->real_escape_string($_POST['reply_message']);
        $conn->query("INSERT INTO discussion_replies (post_id, user_id, message) VALUES ($post_id, $logged_in_user_id, '$message')");
        $reply_id = $conn->insert_id;
        $reply = $conn->query("SELECT r.*, u.name, u.role FROM discussion_replies r JOIN users u ON r.user_id = u.id WHERE r.id = $reply_id")->fetch_assoc();

        ob_start();
        ?>
        <div class="reply mt-3" id="reply-<?= $reply['id'] ?>">
            <div class="d-flex justify-content-between align-items-center">
                <div class="meta"><?= $reply['name'] ?> (<?= $reply['role'] ?>) on <?= $reply['replied_on'] ?></div>
                <?php if ($logged_in_user_id == $reply['user_id']): ?>
                <div class="action-buttons">
                    <button type="button" class="btn btn-sm btn-outline-secondary edit-reply-btn" data-reply-id="<?= $reply['id'] ?>">
                        <i class="bi bi-pencil"></i> Edit
                    </button>
                    <form method="post" class="d-inline delete-reply-form">
                        <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                        <input type="hidden" name="delete_reply" value="1">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <div class="reply-content-view">
                <p class="mt-2"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
            </div>
            <div class="edit-reply-form-wrapper d-none">
                <form class="edit-reply-form" onsubmit="return false;">
                    <textarea name="message" class="form-control mt-2"><?= htmlspecialchars($reply['message']) ?></textarea>
                    <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                    <div class="mt-2 d-flex gap-2">
                        <button type="button" class="btn btn-warning btn-sm update-reply-btn">Update</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit-reply-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        $reply_html = ob_get_clean();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'reply_html' => $reply_html,
            'post_id' => $post_id,
            'reply_id' => $reply_id
        ]);
        exit;
    }
    if (isset($_POST['delete_post'])) {
        $post_id = $_POST['post_id'];
        $conn->query("DELETE FROM discussion_posts WHERE id=$post_id AND user_id=$logged_in_user_id");
        $redirect_needed = true;
        $response['success'] = true;
        $response['post_id'] = $post_id;
    }
    if (isset($_POST['delete_reply'])) {
        $reply_id = $_POST['reply_id'];
        $conn->query("DELETE FROM discussion_replies WHERE id=$reply_id AND user_id=$logged_in_user_id");
        $redirect_needed = true;
        $response['success'] = true;
        $response['reply_id'] = $reply_id;
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } elseif ($redirect_needed) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Discussion Forum | University Smart Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(-45deg, #dfe9f3, #ffffff, #e2ebf0, #f2f6ff);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 50px;
            overflow: scroll;
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

        .navbar-nav {
            /* justify-content: center !important;
            align-items: center;
            flex-wrap: nowrap !important; */
            padding-left: 4rem !important;
            margin-left: 20px !important;
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

        .faculty-card {
            border-radius: 6px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 30px;
            margin-top: 20px;
        }
        .forum-header {
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
        .forum-title {
            color: #2c3e50;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .post, .reply {
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
        }
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(52, 152, 219, 0.1);
        }
        .post-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0;
        }
        .meta {
            font-size: 0.9rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-badge {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .post-content {
            line-height: 1.7;
            color: #495057;
            margin: 1.5rem 0;
            font-size: 1.05rem;
        }
        .actions-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid rgba(52, 152, 219, 0.1);
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            border-radius: 12px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }
        .btn-outline-secondary {
            border: 2px solid #3498db;
            color: #3498db;
            background: rgba(255, 255, 255, 0.9);
        }
        .btn-outline-primary {
            border: 2px solid #3498db;
            color: #3498db;
            background: rgba(255, 255, 255, 0.9);
        }
        .btn-outline-danger {
            border: 2px solid #e74c3c;
            color: #e74c3c;
            background: rgba(255, 255, 255, 0.9);
        }
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        .btn-sm {
            padding: 8px 16px;
            font-size: 0.85rem;
        }
        .collapse-toggle {
            cursor: pointer;
            color: #3498db;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 25px;
            background: rgba(52, 152, 219, 0.1);
            border: 2px solid rgba(52, 152, 219, 0.2);
        }
        .arrow-icon {
            transition: transform 0.3s ease;
            display: inline-block;
        }
        .arrow-icon.rotate {
            transform: rotate(180deg);
        }
        #postForm {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid rgba(52, 152, 219, 0.2);
        }
        #toggleFormButtons {
            margin-bottom: 30px;
        }
        #toggleFormButtons .btn {
            min-width: 200px;
            padding: 15px 25px;
            font-size: 1.1rem;
        }
        .form-control {
            border-radius: 12px;
            border: 2px solid rgba(52, 152, 219, 0.2);
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.9);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
        }
        .reply {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            margin-left: 2rem;
            padding: 20px;
            border-radius: 6px;
        }
        .reply-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
            border: 2px solid rgba(52, 152, 219, 0.1);
        }
        .discussion-stats {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #6c757d;
            font-size: 1rem;
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 20px;
            font-weight: 500;
        }
        .section-header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.1);
        }
        .section-title {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
        }
        @media (max-width: 768px) {
            .container { margin-top: 20px; }
            .header-section, .post, #postForm { padding: 1.5rem; margin-bottom: 1.5rem; }
            .forum-title { font-size: 2rem; }
            .reply { margin-left: 1rem; }
            .discussion-stats { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <!-- Faculty Navigation Structure -->
    <nav class="navbar navbar-expand-lg navbar-light py-3">
        <div class="container">
            <a class="navbar-brand" href="faculty_dashboard.php">
                <div class="brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                University Smart Portal
                <span class="badge bg-primary ms-2">Faculty</span>
            </a>
            <div class="navbar-nav ms-auto d-none d-lg-flex">
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_dashboard.php') echo ' active'; ?>" href="faculty_dashboard.php">
                    <i class="bi bi-house-door me-1"></i>Dashboard
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_course_planner.php') echo ' active'; ?>" href="faculty_course_planner.php">
                    <i class="bi bi-calendar2-week me-1"></i>Courses
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_attendance.php') echo ' active'; ?>" href="faculty_attendance.php">
                    <i class="bi bi-clipboard-check me-1"></i>Attendance
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='leave_application.php') echo ' active'; ?>" href="leave_application.php">
                    <i class="bi bi-calendar-check me-1"></i>Leave
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_forum.php') echo ' active'; ?>" href="faculty_forum.php">
                    <i class="bi bi-chat-dots me-1"></i>Forum
                </a>
                <a class="nav-link<?php if(basename($_SERVER['PHP_SELF'])=='faculty_profile.php') echo ' active'; ?>" href="faculty_profile.php">
                    <i class="bi bi-person-circle me-1"></i>Profile
                </a>
            </div>
            <div class="d-flex align-items-center">
                <a href="logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container py-4">
        <div class="faculty-card">
            <!-- Forum Header -->
            <div class="forum-header">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="user-avatar">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-2 fw-bold">Discussion Forum</h2>
                        <p class="mb-0 opacity-75">
                            <i class="bi bi-people-fill me-2"></i>Connect, share ideas, and engage with the community
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

            <!-- Discussion Form Toggle -->
            <div class="text-end" id="toggleFormButtons">
                <button class="btn btn-primary" id="showDiscussionBtn">
                    <i class="bi bi-plus-circle-fill"></i> Start New Discussion
                </button>
                <button class="btn btn-danger d-none" id="hideDiscussionBtn">
                    <i class="bi bi-x-circle-fill"></i> Cancel Discussion
                </button>
            </div>

            <!-- New Post Form -->
            <div id="postForm" class="d-none">
                <h5 class="mb-4"><i class="bi bi-chat-square-text"></i> Create New Discussion</h5>
                <form method="post">
                    <div class="mb-4">
                        <label class="form-label">📝 Discussion Topic</label>
                        <input type="text" name="topic" id="topicField" class="form-control" placeholder="Enter an engaging discussion topic..." required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">💭 Your Message</label>
                        <textarea name="message" class="form-control" placeholder="Share your thoughts, questions, or ideas with the community..." rows="5" required></textarea>
                    </div>
                    <div class="d-flex gap-3">
                        <button type="submit" name="new_post" class="btn btn-success">
                            <i class="bi bi-send-fill"></i> Post Discussion
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="clearForm">
                            <i class="bi bi-arrow-clockwise"></i> Clear Form
                        </button>
                    </div>
                </form>
            </div>

            <!-- All Posts -->
            <div class="section-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="section-title mb-0"><i class="bi bi-chat-dots-fill"></i> Community Discussions</h4>
                    <div class="discussion-stats">
                        <?php 
                        $total_posts = $conn->query("SELECT COUNT(*) as count FROM discussion_posts")->fetch_assoc()['count'];
                        $total_replies = $conn->query("SELECT COUNT(*) as count FROM discussion_replies")->fetch_assoc()['count'];
                        ?>
                        <div class="stat-item">
                            <i class="bi bi-chat-square-fill"></i>
                            <span><?= $total_posts ?> Posts</span>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-reply-fill"></i>
                            <span><?= $total_replies ?> Replies</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            $posts = $conn->query("SELECT p.*, u.name, u.role FROM discussion_posts p JOIN users u ON p.user_id = u.id ORDER BY p.posted_on DESC");
            while ($post = $posts->fetch_assoc()):
                $post_id = $post['id'];
            ?>
            <div class="post" id="post-<?= $post_id ?>">
                <div class="post-header">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="post-title mb-0"><?= htmlspecialchars($post['topic']) ?></h5>
                            <div class="meta">
                                <i class="bi bi-person"></i>
                                <span><?= htmlspecialchars($post['name']) ?></span>
                                <span class="user-badge"><?= ucfirst($post['role']) ?></span>
                                <i class="bi bi-clock"></i>
                                <span><?= date('M d, Y \a\t g:i A', strtotime($post['posted_on'])) ?></span>
                            </div>
                        </div>
                        <?php if ($logged_in_user_id == $post['user_id']): ?>
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-post-btn" data-post-id="<?= $post_id ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <form method="post" class="d-inline delete-post-form">
                                <input type="hidden" name="post_id" value="<?= $post_id ?>">
                                <input type="hidden" name="delete_post" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="post-content"><?= nl2br(htmlspecialchars($post['message'])) ?></div>
                <div class="edit-post-form-wrapper d-none">
                    <form class="edit-post-form" onsubmit="return false;">
                        <textarea name="message" class="form-control"><?= htmlspecialchars($post['message']) ?></textarea>
                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                        <div class="mt-3 d-flex gap-2">
                            <button type="button" class="btn btn-warning btn-sm update-post-btn">Update Post</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit-post-btn">Cancel</button>
                        </div>
                    </form>
                </div>
                <div class="actions-container">
                    <div class="collapse-toggle" data-bs-toggle="collapse" data-bs-target="#replies-<?= $post_id ?>">
                        <i class="bi bi-chat-left-dots-fill"></i>
                        <span>View Replies & Discussions</span>
                        <i class="bi bi-chevron-down arrow-icon" id="arrow-<?= $post_id ?>"></i>
                    </div>
                </div>
                <div class="collapse mt-2" id="replies-<?= $post_id ?>">
                    <?php
                    $replies = $conn->query("SELECT r.*, u.name, u.role FROM discussion_replies r JOIN users u ON r.user_id = u.id WHERE r.post_id = $post_id ORDER BY r.replied_on ASC");
                    while ($reply = $replies->fetch_assoc()):
                    ?>
                    <div class="reply mt-3" id="reply-<?= $reply['id'] ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="meta"><?= $reply['name'] ?> (<?= $reply['role'] ?>) on <?= $reply['replied_on'] ?></div>
                            <?php if ($logged_in_user_id == $reply['user_id']): ?>
                            <div class="action-buttons">
                                <button type="button" class="btn btn-sm btn-outline-secondary edit-reply-btn" data-reply-id="<?= $reply['id'] ?>">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <form method="post" class="d-inline delete-reply-form">
                                    <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                                    <input type="hidden" name="delete_reply" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="reply-content-view">
                            <p class="mt-2"><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
                        </div>
                        <div class="edit-reply-form-wrapper d-none">
                            <form class="edit-reply-form" onsubmit="return false;">
                                <textarea name="message" class="form-control mt-2"><?= htmlspecialchars($reply['message']) ?></textarea>
                                <input type="hidden" name="reply_id" value="<?= $reply['id'] ?>">
                                <div class="mt-2 d-flex gap-2">
                                    <button type="button" class="btn btn-warning btn-sm update-reply-btn">Update</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm cancel-edit-reply-btn">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <form method="post" class="mt-3 add-reply-form">
                        <input type="hidden" name="new_reply" value="1">
                        <div class="reply-form">
                            <textarea name="reply_message" class="form-control" placeholder="💬 Write a thoughtful reply to this discussion..." rows="3" required></textarea>
                            <input type="hidden" name="post_id" value="<?= $post_id ?>">
                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-reply-fill"></i> Add Reply
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

<script>
function initReplyActions() {
    // Edit reply
    document.querySelectorAll('.edit-reply-btn').forEach(btn => {
        if (!btn.dataset.listener) {
            btn.addEventListener('click', function() {
                const replyId = btn.getAttribute('data-reply-id');
                const replyElem = document.getElementById('reply-' + replyId);
                if (replyElem) {
                    replyElem.querySelector('.reply-content-view').classList.add('d-none');
                    replyElem.querySelector('.edit-reply-form-wrapper').classList.remove('d-none');
                    replyElem.querySelector('textarea[name="message"]').focus();
                }
            });
            btn.dataset.listener = "1";
        }
    });

    // Cancel edit reply
    document.querySelectorAll('.cancel-edit-reply-btn').forEach(btn => {
        if (!btn.dataset.listener) {
            btn.addEventListener('click', function() {
                const replyElem = btn.closest('.reply');
                if (replyElem) {
                    replyElem.querySelector('.edit-reply-form-wrapper').classList.add('d-none');
                    replyElem.querySelector('.reply-content-view').classList.remove('d-none');
                }
            });
            btn.dataset.listener = "1";
        }
    });

    // Update reply
    document.querySelectorAll('.update-reply-btn').forEach(btn => {
        if (!btn.dataset.listener) {
            btn.addEventListener('click', function() {
                const form = btn.closest('.edit-reply-form');
                const replyElem = btn.closest('.reply');
                const replyId = form.querySelector('input[name="reply_id"]').value;
                const message = form.querySelector('textarea[name="message"]').value;
                btn.disabled = true;
                btn.textContent = 'Updating...';
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        ajax_update_reply: 1,
                        reply_id: replyId,
                        message: message
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        replyElem.querySelector('.reply-content-view p').textContent = data.message;
                        replyElem.querySelector('.edit-reply-form-wrapper').classList.add('d-none');
                        replyElem.querySelector('.reply-content-view').classList.remove('d-none');
                    } else {
                        alert('Failed to update reply.');
                    }
                })
                .catch(() => {
                    alert('Error updating reply.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Update';
                });
            });
            btn.dataset.listener = "1";
        }
    });

    // Delete reply
    document.querySelectorAll('.delete-reply-form').forEach(form => {
        if (!form.dataset.listener) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this reply?')) return;
                const replyElem = form.closest('.reply');
                const rect = replyElem.getBoundingClientRect();
                // Blur active element if it's inside the reply (prevents jump)
                if (replyElem.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                const formData = new FormData(form);
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        let anchorElem = replyElem.nextElementSibling || replyElem.parentElement;
                        let anchorRect = anchorElem ? anchorElem.getBoundingClientRect().top : 0;
                        replyElem.remove();
                        let newScrollY = window.scrollY + (anchorRect - (rect.top + rect.height));
                        window.scrollTo({ top: newScrollY });
                    }
                });
            });
            form.dataset.listener = "1";
        }
    });
}

// --- Other event listeners (unchanged) ---
document.querySelectorAll('.collapse-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
        const icon = toggle.querySelector('.arrow-icon');
        icon.classList.toggle('rotate');
    });
});

const showBtn = document.getElementById('showDiscussionBtn');
const hideBtn = document.getElementById('hideDiscussionBtn');
const postForm = document.getElementById('postForm');

showBtn.addEventListener('click', () => {
    postForm.classList.remove('d-none');
    showBtn.classList.add('d-none');
    hideBtn.classList.remove('d-none');
    document.getElementById('topicField').focus();
});

hideBtn.addEventListener('click', () => {
    postForm.classList.add('d-none');
    showBtn.classList.remove('d-none');
    hideBtn.classList.add('d-none');
    document.querySelector('#postForm form').reset();
});

document.getElementById('clearForm').addEventListener('click', () => {
    document.querySelector('#postForm form').reset();
    document.getElementById('topicField').focus();
});

// JS-based inline edit/cancel for posts
document.querySelectorAll('.edit-post-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const postId = btn.getAttribute('data-post-id');
        const postElem = document.getElementById('post-' + postId);
        postElem.querySelector('.post-content').classList.add('d-none');
        postElem.querySelector('.edit-post-form-wrapper').classList.remove('d-none');
        postElem.querySelector('textarea[name="message"]').focus();
    });
});

document.querySelectorAll('.cancel-edit-post-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const postElem = btn.closest('.post');
        postElem.querySelector('.edit-post-form-wrapper').classList.add('d-none');
        postElem.querySelector('.post-content').classList.remove('d-none');
    });
});

// AJAX update for post
document.querySelectorAll('.update-post-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const form = btn.closest('.edit-post-form');
        const postElem = btn.closest('.post');
        const postId = form.querySelector('input[name="post_id"]').value;
        const message = form.querySelector('textarea[name="message"]').value;
        btn.disabled = true;
        btn.textContent = 'Updating...';
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                ajax_update_post: 1,
                post_id: postId,
                message: message
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                postElem.querySelector('.post-content').innerHTML = data.message_html;
                postElem.querySelector('.edit-post-form-wrapper').classList.add('d-none');
                postElem.querySelector('.post-content').classList.remove('d-none');
            } else {
                alert('Failed to update post.');
            }
        })
        .catch(() => {
            alert('Error updating post.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Update Post';
        });
    });
});

// 1. AJAX Delete Post
document.querySelectorAll('.delete-post-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this post?')) return;
        const postElem = form.closest('.post');
        const formData = new FormData(form);
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) postElem.remove();
        });
    });
});

// 3. AJAX Add Reply (for each reply form)
document.querySelectorAll('.add-reply-form').forEach(replyForm => {
    replyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(replyForm);
        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.reply_html && data.post_id) {
                // Find the replies container
                const repliesCollapse = document.getElementById('replies-' + data.post_id);
                // If collapsed, open it
                if (repliesCollapse && !repliesCollapse.classList.contains('show')) {
                    new bootstrap.Collapse(repliesCollapse, {toggle: true});
                }
                // Insert the new reply at the end, before the reply form
                const forms = repliesCollapse.querySelectorAll('.add-reply-form');
                if (forms.length > 0) {
                    forms[forms.length-1].insertAdjacentHTML('beforebegin', data.reply_html);
                }
                // Reset the reply form
                replyForm.reset();
                // Re-initialize actions for new reply
                initReplyActions();
            }
        });
    });
});

// Initialize reply actions on page load
initReplyActions();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>