<?php
session_start();
require_once 'connection.php';

header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        throw new Exception("Invalid request method");
    }

    $email = trim($_POST["email"] ?? '');
    $newPassword = trim($_POST["newPassword"] ?? '');
    $confirmPassword = trim($_POST["confirmPassword"] ?? '');

    if (empty($newPassword) || empty($confirmPassword)) {
        throw new Exception("All fields are required");
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception("Passwords do not match");
    }

    // Additional password strength validation (optional)
    if (strlen($newPassword) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE employee SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
    $stmt->bind_param("ss", $hashedPassword, $email);

    if (!$stmt->execute()) {
        throw new Exception("Error updating password: " . $stmt->error);
    }

    echo json_encode(["success" => true, "message" => "Password updated successfully"]);

} catch (Exception $e) {
    // Log the error (optional)
    error_log("Password Update Error: " . $e->getMessage());
    
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage(),
        "error_details" => $e->getMessage() // Add this for more detailed debugging
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>