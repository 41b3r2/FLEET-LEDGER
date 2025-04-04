<?php
// insert_ar.php
require_once 'connection.php';

// Enhanced error logging function
function logError($message, $file = 'ar_error_log.txt') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($file, $logMessage, FILE_APPEND);
    error_log($message);
}

// Detailed error response function
function sendErrorResponse($message, $details = []) {
    $response = [
        'success' => false,
        'message' => $message,
        'details' => $details
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extensive logging of all received data
    logError('RAW POST Data: ' . print_r($_POST, true));

    // Retrieve form data for A/R with more robust checking
    $cs_id = filter_input(INPUT_POST, 'cs_id', FILTER_VALIDATE_INT);
    $invoice_number = filter_input(INPUT_POST, 'invoice_number', FILTER_VALIDATE_INT);
    $date_received = filter_input(INPUT_POST, 'date_received', FILTER_SANITIZE_STRING);
    $remarks = filter_input(INPUT_POST, 'remarks', FILTER_SANITIZE_STRING);

    // Comprehensive validation
    $errors = [];
    if ($cs_id === false || $cs_id === null) {
        $errors[] = 'Invalid or missing CS ID';
        logError('Validation Error: Invalid CS ID - ' . $_POST['cs_id']);
    }

    if (empty($invoice_number)) {
        $errors[] = 'Invoice Number is required';
    }

    if (empty($date_received)) {
        $errors[] = 'Date Received is required';
    }

    // Check if remarks is one of the allowed enum values
    $allowed_remarks = ['Waiting for approval (client)', 'Missing Docs', 'Done'];
    if (!in_array($remarks, $allowed_remarks)) {
        $errors[] = 'Invalid remarks';
    }

    if (!empty($errors)) {
        sendErrorResponse('Validation failed', $errors);
    }

    try {
        // Start a transaction
        $conn->begin_transaction();

        // Check if an A/R record already exists for this CS ID
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM ar WHERE cs_id = ?");
        $check_stmt->bind_param("i", $cs_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        $check_stmt->close();

        // Prepare statement based on whether record exists
        if ($row['count'] > 0) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE ar 
                SET invoice_number = ?, 
                    date_received = ?, 
                    remarks = ? 
                WHERE cs_id = ?
            ");
            $stmt->bind_param("issi", $invoice_number, $date_received, $remarks, $cs_id);
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO ar (
                    invoice_number, 
                    date_received, 
                    remarks, 
                    cs_id
                ) VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("issi", $invoice_number, $date_received, $remarks, $cs_id);
        }

        // Execute the statement
        $executeResult = $stmt->execute();
        if ($executeResult === false) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        // Optional: Log successful update/insertion
        logError('AR Updated/Inserted Successfully for CS ID: ' . $cs_id, 'ar_success_log.txt');

        // Commit the transaction
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'A/R details saved successfully',
            'cs_id' => $cs_id,
            'invoice_number' => $invoice_number
        ]);

        $stmt->close();

    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();

        // Detailed error logging
        logError('AR Update/Insertion Error: ' . $e->getMessage());

        sendErrorResponse('Database Error', [
            'message' => $e->getMessage(),
            'cs_id' => $cs_id
        ]);
    }

    exit;
} else {
    sendErrorResponse('Invalid request method');
}
?>