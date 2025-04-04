<?php
session_start();
require_once 'connection.php';

// Set header to ensure JSON response
header('Content-Type: application/json');

// Error logging function
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, 'otp_verification_errors.log');
}

try {
    // Check request method
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    // Log all received POST data for debugging
    logError("Received POST data: " . print_r($_POST, true));

    // Check if OTP is provided
    if (!isset($_POST["otp"]) || empty($_POST["otp"])) {
        throw new Exception("OTP is required");
    }

    $otp = trim($_POST["otp"]);
    $email = trim($_POST["email"]);

    // Additional email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Prepare and execute database query
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM employee WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if email exists
    if ($result->num_rows === 0) {
        throw new Exception("Email not found in database");
    }

    // Fetch OTP details
    $row = $result->fetch_assoc();
    $storedOtp = $row["otp"];
    $otpExpiry = strtotime($row["otp_expiry"]);
    $currentTime = time();

    // Check OTP expiration
    if ($currentTime > $otpExpiry) {
        throw new Exception("OTP has expired");
    }

    // Verify OTP
    if ($otp != $storedOtp) {
        throw new Exception("Incorrect OTP");
    }

    // Store email in session for next step
    $_SESSION['reset_email'] = $email;

    // Successful verification
    echo json_encode([
        "success" => true, 
        "message" => "OTP verified successfully"
    ]);

} catch (Exception $e) {
    // Log the specific error
    logError("Verification Error: " . $e->getMessage());

    // Return error response
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage(),
        "error_details" => $e->getMessage()
    ]);
} finally {
    // Close database connections
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>