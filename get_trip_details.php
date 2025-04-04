<?php
// get_trip_details.php
require_once 'connection.php';

// Enhanced error logging function
function logError($message, $file = 'trip_details_error_log.txt') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    file_put_contents($file, $logMessage, FILE_APPEND);
    error_log($message);
}

// Check if CS ID is provided
if (!isset($_GET['cs_id']) || empty($_GET['cs_id'])) {
    logError('No CS ID provided');
    echo json_encode([
        'error' => 'CS ID is required', 
        'details' => 'No CS ID was sent in the request'
    ]);
    exit;
}

// Sanitize and validate CS ID
$cs_id = filter_input(INPUT_GET, 'cs_id', FILTER_VALIDATE_INT);

if ($cs_id === false || $cs_id === null) {
    logError('Invalid CS ID: ' . $_GET['cs_id']);
    echo json_encode([
        'error' => 'Invalid CS ID', 
        'details' => 'The provided CS ID is not a valid integer'
    ]);
    exit;
}

try {
    // Updated query to include odometer details from pod table
    $stmt = $conn->prepare("
        SELECT 
            cs.cs_id, cs.ts_id, cs.waybill, cs.date, cs.status, 
            cs.delivery_type, cs.amount, cs.source, cs.pickup, 
            cs.dropoff, cs.rate, cs.call_time, cs.truck_id, 
            cs.driver, cs.helper1, cs.helper2, 
            
            b.fuelfee, b.tollfee, b.parkingfee, b.rorofarefee, b.budgetrelease,
            
            p.pod_status, 
            p.date_received AS pod_date_received, 
            p.Remarks AS pod_remarks, 
            p.pod_transmittal, 
            p.date_transmitted AS pod_date_transmitted,
            p.odo_out,
            p.odo_in,
            p.odo_total,
            
            ar.invoice_number, 
            ar.date_received AS ar_date_received, 
            ar.remarks AS ar_remarks
        FROM 
            customerservice cs
        LEFT JOIN 
            budget b ON cs.cs_id = b.cs_id
        LEFT JOIN 
            pod p ON cs.cs_id = p.cs_id
        LEFT JOIN 
            ar ON cs.cs_id = ar.cs_id
        WHERE 
            cs.cs_id = ?
    ");
    
    // Log the actual query being executed
    logError('Executing query for CS ID: ' . $cs_id);

    $stmt->bind_param("i", $cs_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $trip = $result->fetch_assoc();
        
        // Organize budget details
        if ($trip['fuelfee'] !== null) {
            $trip['budget'] = [
                'fuelfee' => $trip['fuelfee'] ?? 0,
                'tollfee' => $trip['tollfee'] ?? 0,
                'parkingfee' => $trip['parkingfee'] ?? 0,
                'rorofarefee' => $trip['rorofarefee'] ?? 0,
                'budgetrelease' => $trip['budgetrelease'] ?? 0
            ];
        }

        // Organize POD details
        if ($trip['pod_status'] !== null) {
            $trip['pod'] = [
                'pod_status' => $trip['pod_status'] ?? '',
                'date_received' => $trip['pod_date_received'] ?? '',
                'Remarks' => $trip['pod_remarks'] ?? '',
                'pod_transmittal' => $trip['pod_transmittal'] ?? '',
                'date_transmitted' => $trip['pod_date_transmitted'] ?? '',
                'odo_out' => $trip['odo_out'] ?? '',
                'odo_in' => $trip['odo_in'] ?? '',
                'odo_total' => $trip['odo_total'] ?? ''
            ];
        }

        // Organize AR details
        if ($trip['invoice_number'] !== null) {
            $trip['ar'] = [
                'invoice_number' => $trip['invoice_number'] ?? '',
                'date_received' => $trip['ar_date_received'] ?? '',
                'remarks' => $trip['ar_remarks'] ?? ''
            ];
        }

        // Remove redundant columns
        $columns_to_unset = [
            'fuelfee', 'tollfee', 'parkingfee', 'rorofarefee', 'budgetrelease',
            'pod_status', 'pod_date_received', 'pod_remarks', 'pod_transmittal', 'pod_date_transmitted',
            'odo_out', 'odo_in', 'odo_total',
            'invoice_number', 'ar_date_received', 'ar_remarks'
        ];
        
        foreach ($columns_to_unset as $column) {
            unset($trip[$column]);
        }

        // Log successful fetch
        logError('Successfully fetched trip details for CS ID: ' . $cs_id, 'trip_details_success_log.txt');

        // Send the trip details as JSON
        header('Content-Type: application/json');
        echo json_encode($trip);
    } else {
        // No trip found with this CS ID
        logError('No trip found for CS ID: ' . $cs_id);
        echo json_encode([
            'error' => 'Trip not found', 
            'details' => 'No trip details exist for the given CS ID'
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    // Catch any unexpected errors
    logError('Unexpected error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Server error', 
        'details' => $e->getMessage()
    ]);
}
?>