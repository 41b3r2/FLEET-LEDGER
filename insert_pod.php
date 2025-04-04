<?php
// insert_pod.php
require_once 'connection.php';

// Enhanced error logging function
function logError($message, $file = 'pod_error_log.txt') {
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

    // Retrieve form data for POD with more robust checking
    $cs_id = filter_input(INPUT_POST, 'cs_id', FILTER_VALIDATE_INT);
    $pod_status = filter_input(INPUT_POST, 'pod_status', FILTER_SANITIZE_STRING);
    $date_received = filter_input(INPUT_POST, 'date_received', FILTER_SANITIZE_STRING);
    $remarks = filter_input(INPUT_POST, 'Remarks', FILTER_SANITIZE_STRING);
    $pod_transmittal = filter_input(INPUT_POST, 'pod_transmittal', FILTER_SANITIZE_STRING);
    $date_transmitted = filter_input(INPUT_POST, 'date_transmitted', FILTER_SANITIZE_STRING);

    // New odometer-related inputs
    $odo_out = filter_input(INPUT_POST, 'odo_out', FILTER_VALIDATE_INT);
    $odo_in = filter_input(INPUT_POST, 'odo_in', FILTER_VALIDATE_INT);
    $odo_total = filter_input(INPUT_POST, 'odo_total', FILTER_VALIDATE_INT);

    // Comprehensive validation
    $errors = [];
    if ($cs_id === false || $cs_id === null) {
        $errors[] = 'Invalid or missing CS ID';
        logError('Validation Error: Invalid CS ID - ' . $_POST['cs_id']);
    }

    if (empty($pod_status)) {
        $errors[] = 'POD Status is required';
    }

    if (empty($date_received)) {
        $errors[] = 'Date Received is required';
    }

    if (empty($remarks)) {
        $errors[] = 'Remarks are required';
    }

    if (empty($pod_transmittal)) {
        $errors[] = 'POD Transmittal is required';
    }

    if (empty($date_transmitted)) {
        $errors[] = 'Date Transmitted is required';
    }

    // Validate odometer readings
    if ($odo_out === false || $odo_out === null) {
        $errors[] = 'Invalid Odometer Out reading';
    }

    if ($odo_in === false || $odo_in === null) {
        $errors[] = 'Invalid Odometer In reading';
    }

    if ($odo_in < $odo_out) {
        $errors[] = 'Odometer In reading must be greater than Odometer Out reading';
    }

    // Calculate total mileage if not provided
    $odo_total = $odo_in - $odo_out;

    if (!empty($errors)) {
        sendErrorResponse('Validation failed', $errors);
    }

    try {
        // Start a transaction
        $conn->begin_transaction();

        // Use prepared statement to insert POD and mileage details
        $stmt = $conn->prepare("
            INSERT INTO pod (
                cs_id, 
                pod_status, 
                date_received, 
                Remarks, 
                pod_transmittal, 
                date_transmitted,
                odo_out,
                odo_in,
                odo_total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                pod_status = VALUES(pod_status), 
                date_received = VALUES(date_received), 
                Remarks = VALUES(Remarks), 
                pod_transmittal = VALUES(pod_transmittal), 
                date_transmitted = VALUES(date_transmitted),
                odo_out = VALUES(odo_out),
                odo_in = VALUES(odo_in),
                odo_total = VALUES(odo_total)
        ");

        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $bindResult = $stmt->bind_param(
            "issssssii", 
            $cs_id, 
            $pod_status, 
            $date_received, 
            $remarks, 
            $pod_transmittal, 
            $date_transmitted,
            $odo_out,
            $odo_in,
            $odo_total
        );

        if ($bindResult === false) {
            throw new Exception('Bind param failed: ' . $stmt->error);
        }

        $executeResult = $stmt->execute();
        if ($executeResult === false) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        // Optional: Log successful insertion
        logError('POD Insertion Successful for CS ID: ' . $cs_id, 'pod_success_log.txt');

        // Commit the transaction
        $conn->commit();

        $response = [
            'success' => true, 
            'message' => 'POD details saved successfully',
            'cs_id' => $cs_id,
            'odo_total' => $odo_total,
            'pod_status' => $pod_status
        ];

        echo json_encode($response);

        $stmt->close();

    } catch (Exception $e) {
        // Rollback the transaction
        $conn->rollback();

        // Detailed error logging
        logError('POD Insertion Error: ' . $e->getMessage());

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