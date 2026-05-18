<?php
// =====================================================
// submit_review.php - Standalone Review Submission
// (Also handled in dashboard.php, this is an alternative)
// =====================================================
session_start();
require_once 'db_connect.php';

// Check if logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_SESSION['student_id'];
    $student_name = $_SESSION['student_name'];
    $food_id = intval($_POST['food_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $review_text = trim($_POST['review_text'] ?? '');

    // Validate inputs
    if ($food_id <= 0 || $rating < 1 || $rating > 5 || empty($review_text)) {
        header("Location: dashboard.php?msg=error");
        exit();
    }

    // Insert the review
    $stmt = mysqli_prepare($conn, "INSERT INTO reviews (student_id, food_id, rating, review_text) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiis", $student_id, $food_id, $rating, $review_text);

    if (mysqli_stmt_execute($stmt)) {
        $fn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT food_name FROM food_items WHERE food_id = $food_id"));
        log_db_operation("INSERT", "Review added by $student_name for '{$fn['food_name']}' - Rating: $rating/5");
        header("Location: dashboard.php?msg=review_added");
    } else {
        header("Location: dashboard.php?msg=error");
    }
    mysqli_stmt_close($stmt);
    exit();
}

// If accessed via GET, redirect to dashboard
header("Location: dashboard.php#submit-section");
exit();
?>
