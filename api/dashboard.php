<?php
// =====================================================
// dashboard.php - Student Dashboard (Main Page)
// Shows food items, reviews, search, and submit review
// =====================================================
session_start();
require_once 'db_connect.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['student_name'];
$student_id = $_SESSION['student_id'];

// Handle search query
$search = trim($_GET['search'] ?? '');

// Get food items with average ratings
$food_query = "SELECT f.food_id, f.food_name, f.price,
               ROUND(AVG(r.rating), 1) as avg_rating,
               COUNT(r.review_id) as review_count
               FROM food_items f
               LEFT JOIN reviews r ON f.food_id = r.food_id";
if ($search !== '') {
    $food_query .= " WHERE f.food_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'";
}
$food_query .= " GROUP BY f.food_id ORDER BY avg_rating DESC";
$food_result = mysqli_query($conn, $food_query);

// Get all reviews (with optional food filter)
$food_filter = intval($_GET['food_id'] ?? 0);
$review_query = "SELECT r.*, s.name as student_name, f.food_name
                 FROM reviews r
                 JOIN students s ON r.student_id = s.student_id
                 JOIN food_items f ON r.food_id = f.food_id";
if ($food_filter > 0) {
    $review_query .= " WHERE r.food_id = $food_filter";
}
$review_query .= " ORDER BY r.created_at DESC";
$review_result = mysqli_query($conn, $review_query);

// Get stats
$total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reviews"))['c'];
$total_foods = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM food_items"))['c'];
$my_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reviews WHERE student_id = $student_id"))['c'];

// Get all food items for the review form dropdown
$all_foods = mysqli_query($conn, "SELECT food_id, food_name FROM food_items ORDER BY food_name");

// Handle review submission (POST)
$msg = "";
$msg_type = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $food_id = intval($_POST['food_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    if ($food_id <= 0 || $rating < 1 || $rating > 5 || empty($review_text)) {
        $msg = "Please fill all fields correctly! Rating must be 1-5.";
        $msg_type = "error";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO reviews (student_id, food_id, rating, review_text) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiis", $student_id, $food_id, $rating, $review_text);
        if (mysqli_stmt_execute($stmt)) {
            // Get food name for log
            $fn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT food_name FROM food_items WHERE food_id = $food_id"));
            log_db_operation("INSERT", "Review added by $student_name for '{$fn['food_name']}' - Rating: $rating/5");
            $msg = "Review submitted successfully!";
            $msg_type = "success";
            // Refresh data
            header("Location: dashboard.php?msg=review_added");
            exit();
        } else {
            $msg = "Error submitting review. Please try again.";
            $msg_type = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle delete own review
if (isset($_GET['delete_review'])) {
    $rid = intval($_GET['delete_review']);
    // Only allow deleting own reviews
    $del_stmt = mysqli_prepare($conn, "DELETE FROM reviews WHERE review_id = ? AND student_id = ?");
    mysqli_stmt_bind_param($del_stmt, "ii", $rid, $student_id);
    if (mysqli_stmt_execute($del_stmt) && mysqli_stmt_affected_rows($conn) > 0) {
        log_db_operation("DELETE", "Review #$rid deleted by student $student_name");
        header("Location: dashboard.php?msg=review_deleted");
        exit();
    }
    mysqli_stmt_close($del_stmt);
}

// Flash messages from redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'review_added') { $msg = "Review submitted successfully!"; $msg_type = "success"; }
    if ($_GET['msg'] == 'review_deleted') { $msg = "Review deleted successfully!"; $msg_type = "success"; }
}

// Helper: render stars
function render_stars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rating) ? '★' : '☆';
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Canteen Review System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🍽️ Canteen<span>Review</span></div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="#food-section">Food Items</a>
            <a href="#review-section">Reviews</a>
            <a href="#submit-section">Add Review</a>
            <a href="logout.php" class="btn-logout">Logout (<?php echo htmlspecialchars($student_name); ?>)</a>
        </div>
    </nav>

    <div class="container">
        <!-- Welcome & Stats -->
        <div class="card" style="text-align:center; background: linear-gradient(135deg, #fff5f0, #fff);">
            <h2 style="text-align:center;">Welcome, <?php echo htmlspecialchars($student_name); ?>! 👋</h2>
        </div>

        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_foods; ?></div>
                <div class="stat-label">Food Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_reviews; ?></div>
                <div class="stat-label">Total Reviews</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $my_reviews; ?></div>
                <div class="stat-label">My Reviews</div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card" id="food-section">
            <h2>🔍 Search Food Items</h2>
            <form method="GET" action="api/dashboard.php" class="search-bar">
                <input type="text" name="search" placeholder="Search for a food item..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="dashboard.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Food Items Grid -->
            <div class="food-grid">
                <?php if (mysqli_num_rows($food_result) > 0): ?>
                    <?php while ($food = mysqli_fetch_assoc($food_result)): ?>
                        <a href="dashboard.php?food_id=<?php echo $food['food_id']; ?>#review-section" style="text-decoration:none;">
                            <div class="food-card">
                                <h4><?php echo htmlspecialchars($food['food_name']); ?></h4>
                                <div class="price">₹<?php echo number_format($food['price'], 2); ?></div>
                                <div class="avg-rating">
                                    <span><?php echo $food['avg_rating'] ? render_stars(round($food['avg_rating'])) : '☆☆☆☆☆'; ?></span>
                                    <br><?php echo $food['avg_rating'] ?? 'No'; ?> avg
                                    (<?php echo $food['review_count']; ?> reviews)
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1/-1; text-align:center; color:#636e72;">No food items found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Submit Review Form -->
        <div class="card" id="submit-section">
            <h2>📝 Submit a Review</h2>
            <form method="POST" action="dashboard.php">
                <div class="form-group">
                    <label for="food_id">Select Food Item</label>
                    <select id="food_id" name="food_id" required>
                        <option value="">-- Choose a food item --</option>
                        <?php
                        // Reset the result pointer
                        $all_foods = mysqli_query($conn, "SELECT food_id, food_name FROM food_items ORDER BY food_name");
                        while ($f = mysqli_fetch_assoc($all_foods)):
                        ?>
                            <option value="<?php echo $f['food_id']; ?>"><?php echo htmlspecialchars($f['food_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="rating">Rating (1-5)</label>
                    <select id="rating" name="rating" required>
                        <option value="">-- Select rating --</option>
                        <option value="1">⭐ 1 - Poor</option>
                        <option value="2">⭐⭐ 2 - Below Average</option>
                        <option value="3">⭐⭐⭐ 3 - Average</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Good</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Excellent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="review_text">Your Review</label>
                    <textarea id="review_text" name="review_text" placeholder="Write your review here..." required></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
            </form>
        </div>

        <!-- All Reviews -->
        <div class="card" id="review-section">
            <h2>📋 <?php echo $food_filter ? 'Filtered' : 'All'; ?> Reviews
                <?php if ($food_filter): ?>
                    <a href="dashboard.php#review-section" class="btn btn-secondary btn-sm" style="font-size:0.8rem; margin-left:10px;">Show All</a>
                <?php endif; ?>
            </h2>

            <?php if (mysqli_num_rows($review_result) > 0): ?>
                <?php while ($rev = mysqli_fetch_assoc($review_result)): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <h3><?php echo htmlspecialchars($rev['food_name']); ?></h3>
                            <div class="review-stars"><?php echo render_stars($rev['rating']); ?></div>
                        </div>
                        <div class="review-meta">
                            <span>👤 <?php echo htmlspecialchars($rev['student_name']); ?></span>
                            <span>📅 <?php echo date('d M Y, h:i A', strtotime($rev['created_at'])); ?></span>
                        </div>
                        <p class="review-text"><?php echo htmlspecialchars($rev['review_text']); ?></p>
                        <?php if ($rev['student_id'] == $student_id): ?>
                            <div style="margin-top:10px;">
                                <a href="dashboard.php?delete_review=<?php echo $rev['review_id']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this review?');">
                                    🗑️ Delete My Review
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align:center; color:#636e72; padding:20px;">No reviews yet. Be the first to review!</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2026 Canteen Review System | DBMS Mini Project</p>
    </div>
</body>
</html>
