<?php
// =====================================================
// login.php - Student Login Page
// =====================================================
session_start();
require_once 'db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {
        // Check credentials
        $stmt = mysqli_prepare($conn, "SELECT student_id, name, email, password FROM students WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
          
            if (password_verify($password, $row['password'])) {
               
                $_SESSION['student_id'] = $row['student_id'];
                $_SESSION['student_name'] = $row['name'];
                $_SESSION['student_email'] = $row['email'];

                log_db_operation("LOGIN", "Student logged in: " . $row['name'] . " (" . $row['email'] . ")");
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "No account found with this email!";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Canteen Review System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🍽️ Canteen<span>Review</span></div>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="login.php" class="active">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>🔐 Login</h2>
            <p class="subtitle">Welcome back, student!</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="api/login.php" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Login</button>
            </form>

            <p class="switch-link">Don't have an account? <a href="register.php">Register here</a></p>

            <hr style="margin: 20px 0; border: 1px solid #eee;">
            <p class="switch-link"><a href="admin.php">🛡️ Admin Login</a></p>
        </div>
    </div>
</body>
</html>
