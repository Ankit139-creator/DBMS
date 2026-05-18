<?php
// =====================================================
// register.php - Student Registration Page
// =====================================================
session_start();
require_once 'db_connect.php';

$error = "";
$success = "";

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $check = mysqli_prepare($conn, "SELECT student_id FROM students WHERE email = ?");
        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Email already registered! Please login.";
        } else {
            // Hash password and insert
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO students (name, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed);

            if (mysqli_stmt_execute($stmt)) {
                // Log the operation
                log_db_operation("INSERT", "New student registered: $name ($email)");
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Canteen Review System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🍽️ Canteen<span>Review</span></div>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php" class="active">Register</a>
        </div>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>📋 Register</h2>
            <p class="subtitle">Create your student account</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php" id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name"
                           value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Register</button>
            </form>

            <p class="switch-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</body>
</html>
