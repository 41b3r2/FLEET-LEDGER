<?php
session_start();
require_once 'connection.php';

// Ensure email is passed via GET
if (!isset($_GET['email'])) {
    header('Location: login.php');
    exit;
}

$email = $_GET['email'];
$passwordErr = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Validate passwords
    if (empty($new_password)) {
        $passwordErr = "Please enter a new password";
    } elseif (strlen($new_password) < 8) {
        $passwordErr = "Password must be at least 8 characters long";
    } elseif ($new_password !== $confirm_password) {
        $passwordErr = "Passwords do not match";
    }

    if (empty($passwordErr)) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update password in database
        $stmt = $conn->prepare("UPDATE employee SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Redirect to login with success message
            header('Location: login.php?reset=success');
            exit;
        } else {
            $passwordErr = "Something went wrong. Please try again.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <img src="assets/img/pcl.png" alt="PCL Logo">
            <h1>RESET PASSWORD</h1>
        </div>
        
        <?php if (!empty($passwordErr)): ?>
            <div class="alert"><?php echo htmlspecialchars($passwordErr); ?></div>
        <?php endif; ?>
        
        <form action="" method="post">
            <div class="login-container">
                <div class="input-container">
                    <div class="input-wrapper">
                        <input type="password" placeholder="New Password" name="new_password" required>
                        <img src="assets/img/pas_icon.png" class="input-icon" alt="Password icon">
                    </div>
                </div>

                <div class="input-container">
                    <div class="input-wrapper">
                        <input type="password" placeholder="Confirm New Password" name="confirm_password" required>
                        <img src="assets/img/pas_icon.png" class="input-icon" alt="Password icon">
                    </div>
                </div>

                <button class="login-button" type="submit">Reset Password</button>
            </div>
        </form>
    </div>
</body>
</html>