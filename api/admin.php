<?php
// =====================================================
// admin.php - Admin Panel
// Admin can view and delete any review
// Default admin credentials: admin@canteen.com / admin123
// =====================================================
session_start();
require_once 'db_connect.php';

// Hardcoded admin credentials (simple for beginners)
$ADMIN_EMAIL = "admin@canteen.com";
$ADMIN_PASSWORD = "admin123";

$error = "";
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Handle admin login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === $ADMIN_EMAIL && $password === $ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        $is_admin = true;
        log_db_operation("LOGIN", "Admin logged in");
    } else {
        $error = "Invalid admin credentials!";
    }
}

// Handle admin logout
if (isset($_GET['admin_logout'])) {
    unset($_SESSION['is_admin']);
    header("Location: admin.php");
    exit();
}

// Handle delete review
if ($is_admin && isset($_GET['delete'])) {
    $rid = intval($_GET['delete']);
    // Get review details for logging
    $rev_info = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT r.review_id, s.name, f.food_name FROM reviews r
         JOIN students s ON r.student_id = s.student_id
         JOIN food_items f ON r.food_id = f.food_id
         WHERE r.review_id = $rid"));

    $del = mysqli_prepare($conn, "DELETE FROM reviews WHERE review_id = ?");
    mysqli_stmt_bind_param($del, "i", $rid);
    if (mysqli_stmt_execute($del) && mysqli_stmt_affected_rows($conn) > 0) {
        log_db_operation("DELETE", "Admin deleted review #$rid by {$rev_info['name']} for '{$rev_info['food_name']}'");
        header("Location: admin.php?msg=deleted");
        exit();
    }
    mysqli_stmt_close($del);
}

// Handle delete student
if ($is_admin && isset($_GET['delete_student'])) {
    $sid = intval($_GET['delete_student']);
    $stu_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name, email FROM students WHERE student_id = $sid"));
    $del = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
    mysqli_stmt_bind_param($del, "i", $sid);
    if (mysqli_stmt_execute($del) && mysqli_stmt_affected_rows($conn) > 0) {
        log_db_operation("DELETE", "Admin deleted student: {$stu_info['name']} ({$stu_info['email']})");
        header("Location: admin.php?msg=student_deleted");
        exit();
    }
    mysqli_stmt_close($del);
}

// Flash messages
$flash = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $flash = "Review deleted successfully!";
    if ($_GET['msg'] == 'student_deleted') $flash = "Student and their reviews deleted!";
}

// Fetch data for admin
if ($is_admin) {
    $reviews = mysqli_query($conn,
        "SELECT r.*, s.name as student_name, f.food_name
         FROM reviews r
         JOIN students s ON r.student_id = s.student_id
         JOIN food_items f ON r.food_id = f.food_id
         ORDER BY r.created_at DESC");

    $students = mysqli_query($conn,
        "SELECT s.*, COUNT(r.review_id) as review_count
         FROM students s
         LEFT JOIN reviews r ON s.student_id = r.student_id
         GROUP BY s.student_id ORDER BY s.student_id");

    $foods = mysqli_query($conn,
        "SELECT f.*, ROUND(AVG(r.rating),1) as avg_rating, COUNT(r.review_id) as review_count
         FROM food_items f
         LEFT JOIN reviews r ON f.food_id = r.food_id
         GROUP BY f.food_id ORDER BY f.food_id");

    // Stats
    $total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM students"))['c'];
    $total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reviews"))['c'];
    $total_foods = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM food_items"))['c'];
}

// Helper: render stars
function render_stars_admin($rating) {
    $s = '';
    for ($i = 1; $i <= 5; $i++) $s .= ($i <= $rating) ? '★' : '☆';
    return $s;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Canteen Review System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🛡️ Admin<span>Panel</span></div>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <?php if ($is_admin): ?>
                <a href="admin.php" class="active">Dashboard</a>
                <a href="admin.php?admin_logout=1" class="btn-logout">Logout Admin</a>
            <?php else: ?>
                <a href="login.php">Student Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php if (!$is_admin): ?>
            <!-- Admin Login Form -->
            <div class="auth-wrapper" style="min-height: calc(100vh - 200px);">
                <div class="auth-card">
                    <h2>🛡️ Admin Login</h2>
                    <p class="subtitle">Enter admin credentials</p>

                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="api/admin.php">
                        <div class="form-group">
                            <label for="email">Admin Email</label>
                            <input type="email" id="email" name="email" placeholder="admin@canteen.com" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Admin Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter admin password" required>
                        </div>
                        <button type="submit" name="admin_login" class="btn btn-primary" style="width:100%;">Login as Admin</button>
                    </form>

                    <div class="alert alert-info" style="margin-top:20px;">
                        <strong>Default Credentials:</strong><br>
                        Email: admin@canteen.com<br>
                        Password: admin123
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Admin Dashboard -->
            <div class="card" style="text-align:center; background: linear-gradient(135deg, #fff5f0, #fff);">
                <h2 style="text-align:center;">🛡️ Admin Dashboard</h2>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_foods; ?></div>
                    <div class="stat-label">Food Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_reviews; ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>

            <!-- All Reviews Table -->
            <div class="card">
                <h2>📋 All Reviews</h2>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Food Item</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = mysqli_fetch_assoc($reviews)): ?>
                            <tr>
                                <td><?php echo $r['review_id']; ?></td>
                                <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['food_name']); ?></td>
                                <td style="color:#f39c12;"><?php echo render_stars_admin($r['rating']); ?></td>
                                <td><?php echo htmlspecialchars(substr($r['review_text'], 0, 60)); ?>...</td>
                                <td><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
                                <td>
                                    <a href="admin.php?delete=<?php echo $r['review_id']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this review?');">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Food Items Table -->
            <div class="card">
                <h2>🍛 Food Items & Ratings</h2>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Food Name</th>
                                <th>Price</th>
                                <th>Avg Rating</th>
                                <th>Reviews</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($f = mysqli_fetch_assoc($foods)): ?>
                            <tr>
                                <td><?php echo $f['food_id']; ?></td>
                                <td><?php echo htmlspecialchars($f['food_name']); ?></td>
                                <td>₹<?php echo number_format($f['price'], 2); ?></td>
                                <td style="color:#f39c12;"><?php echo $f['avg_rating'] ? $f['avg_rating'] . '/5' : 'N/A'; ?></td>
                                <td><?php echo $f['review_count']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Students Table -->
            <div class="card">
                <h2>👤 Registered Students</h2>
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Reviews</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($s = mysqli_fetch_assoc($students)): ?>
                            <tr>
                                <td><?php echo $s['student_id']; ?></td>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td><?php echo $s['review_count']; ?></td>
                                <td>
                                    <a href="admin.php?delete_student=<?php echo $s['student_id']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete this student and all their reviews?');">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p>&copy; 2026 Canteen Review System | Admin Panel</p>
    </div>
</body>
</html>
