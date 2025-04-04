<?php
// send-otp.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'connection.php';

header('Content-Type: application/json');

// Error logging function with improved logging
function logError($message, $context = []) {
    $logEntry = date('[Y-m-d H:i:s] ') . $message;
    if (!empty($context)) {
        $logEntry .= ' | ' . json_encode($context);
    }
    error_log($logEntry . PHP_EOL, 3, 'otp_errors.log');
}

// Secure OTP generation
function generateSecureOTP($length = 6) {
    $otp = '';
    $characters = '0123456789';
    $charLength = strlen($characters);
    
    for ($i = 0; $i < $length; $i++) {
        $otp .= $characters[random_int(0, $charLength - 1)];
    }
    
    return $otp;
}

// Create HTML email body
function createOTPEmailBody($otp, $fullname = 'User') {
    return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
        .container { background-color: #f4f4f4; padding: 20px; border-radius: 8px; }
        .header { text-align: center; background-color: #007bff; color: white; padding: 10px; border-radius: 8px 8px 0 0; }
        .content { background-color: white; padding: 20px; border-radius: 0 0 8px 8px; }
        .otp { 
            font-size: 24px; 
            font-weight: bold; 
            color: #007bff; 
            text-align: center; 
            margin: 20px 0; 
            padding: 10px; 
            background-color: #f0f0f0; 
            border-radius: 5px; 
            letter-spacing: 3px;
        }
        .footer { text-align: center; color: #666; margin-top: 20px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset</h1>
        </div>
        <div class="content">
            <p>Hello, ' . htmlspecialchars($fullname) . '</p>
            <p>You have requested to reset your password. Please use the following One-Time Password (OTP):</p>
            
            <div class="otp">' . htmlspecialchars($otp) . '</div>
            
            <p>This OTP is valid for <strong>15 minutes</strong>. Do not share this code with anyone.</p>
            
            <p>If you did not request a password reset, please ignore this email or contact our support team.</p>
        </div>
        <div class="footer">
            &copy; ' . date('Y') . ' PCL Fleet Ledger. All rights reserved.
        </div>
    </div>
</body>
</html>
    ';
}

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }

    // Validate and sanitize email
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format', 400);
    }

    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed', 500);
    }

    // Prepare and execute user check with full name retrieval
    $stmt = $conn->prepare("SELECT fullname FROM employee WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Email not found', 404);
    }

    // Fetch full name
    $userData = $result->fetch_assoc();
    $fullname = $userData['fullname'] ?? 'User';
    $stmt->close();

    // Generate secure OTP
    $otp = generateSecureOTP();
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    // Update OTP in database
    $stmt = $conn->prepare("UPDATE employee SET otp = ?, otp_expiry = ? WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $conn->error, 500);
    }
    
    $stmt->bind_param("sss", $otp, $otp_expiry, $email);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update OTP: ' . $stmt->error, 500);
    }

    // Configure PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';  
    $mail->SMTPAuth   = true;
    $mail->Username   = 'enanojra@gmail.com';  
    $mail->Password   = 'ovqgmiilcxbdoonj';     
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Set email details
    $mail->setFrom('enanojra@gmail.com', 'PCL Fleet Ledger');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset OTP';
    $mail->Body = createOTPEmailBody($otp, $fullname);

    // Send email
    $mail->send();

    // Successful response
    echo json_encode([
        'success' => true, 
        'message' => 'OTP sent successfully',
        'fullname' => $fullname
    ]);

} catch (Exception $e) {
    // Log and return error
    logError($e->getMessage(), [
        'email' => $email ?? 'N/A', 
        'code' => $e->getCode()
    ]);

    // Return error response
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);

} finally {
    // Close database statement and connection
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>