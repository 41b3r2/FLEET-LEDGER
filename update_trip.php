<?php
// update_trip.php
ini_set('display_errors', 0); // Disable displaying errors directly in the response
ini_set('log_errors', 1); // Enable logging errors
ini_set('error_log', 'error.log'); // Specify error log file in current directory

require_once 'connection.php'; // Ensure this path is correct

// Make sure we output clean JSON with no whitespace before/after
ob_start();

$response = array('success' => false, 'message' => '');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['cs_id'])) {
        // Existing GET method code remains unchanged
        $cs_id = filter_var($_GET['cs_id'], FILTER_SANITIZE_NUMBER_INT);

        $sql = "SELECT cs.*, t.model, t.truck_plate, t.truck_type,
                d.fullname AS driver_name, h1.fullname AS helper1_name, h2.fullname AS helper2_name
                FROM customerservice cs
                LEFT JOIN truck t ON cs.truck_id = t.truck_id
                LEFT JOIN driver d ON cs.driver = d.driver_id  
                LEFT JOIN helper1 h1 ON cs.helper1 = h1.helper1_id
                LEFT JOIN helper2 h2 ON cs.helper2 = h2.helper2_id
                WHERE cs.cs_id = ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cs_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['success'] = true;
            $response['data'] = $result->fetch_assoc();
        } else {
            throw new Exception("Trip not found");
        }

        $stmt->close();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cs_id'])) {
        $cs_id = filter_var($_POST['cs_id'], FILTER_SANITIZE_NUMBER_INT);
        
        // First, get the current record to preserve existing values
        $getCurrentSql = "SELECT * FROM customerservice WHERE cs_id = ?";
        $getCurrentStmt = $conn->prepare($getCurrentSql);
        $getCurrentStmt->bind_param("i", $cs_id);
        $getCurrentStmt->execute();
        $currentResult = $getCurrentStmt->get_result();
        
        if ($currentResult->num_rows === 0) {
            throw new Exception("Trip not found");
        }
        
        $currentData = $currentResult->fetch_assoc();
        $getCurrentStmt->close();
        
        // IMPORTANT: For ts_id, we should preserve the original value and not allow changes
        // since it's linked by a foreign key constraint
        $ts_id = $currentData['ts_id']; // Always use the existing ts_id
            
        $waybill = isset($_POST['waybill']) && $_POST['waybill'] !== '' ? 
            filter_var($_POST['waybill'], FILTER_SANITIZE_NUMBER_INT) : $currentData['waybill'];
            
        $date = isset($_POST['date']) && $_POST['date'] !== '' ? 
            filter_var($_POST['date'], FILTER_SANITIZE_STRING) : $currentData['date'];
            
        $status = isset($_POST['status']) && $_POST['status'] !== '' ? 
            filter_var($_POST['status'], FILTER_SANITIZE_STRING) : $currentData['status'];
            
        $delivery_type = isset($_POST['delivery_type']) && $_POST['delivery_type'] !== '' ? 
            filter_var($_POST['delivery_type'], FILTER_SANITIZE_STRING) : $currentData['delivery_type'];
            
        $amount = isset($_POST['amount']) && $_POST['amount'] !== '' ? 
            filter_var($_POST['amount'], FILTER_SANITIZE_STRING) : $currentData['amount'];
            
        $source = isset($_POST['source']) && $_POST['source'] !== '' ? 
            filter_var($_POST['source'], FILTER_SANITIZE_STRING) : $currentData['source'];
            
        $pickup = isset($_POST['pickup']) && $_POST['pickup'] !== '' ? 
            filter_var($_POST['pickup'], FILTER_SANITIZE_STRING) : $currentData['pickup'];
            
        $dropoff = isset($_POST['dropoff']) && $_POST['dropoff'] !== '' ? 
            filter_var($_POST['dropoff'], FILTER_SANITIZE_STRING) : $currentData['dropoff'];
            
        $rate = isset($_POST['rate']) && $_POST['rate'] !== '' ? 
            filter_var($_POST['rate'], FILTER_SANITIZE_STRING) : $currentData['rate'];
            
        $call_time = isset($_POST['call_time']) && $_POST['call_time'] !== '' ? 
            filter_var($_POST['call_time'], FILTER_SANITIZE_STRING) : $currentData['call_time'];
            
        $truck_id = isset($_POST['truck_id']) && $_POST['truck_id'] !== '' ? 
            filter_var($_POST['truck_id'], FILTER_SANITIZE_STRING) : $currentData['truck_id'];
            
        $driver = isset($_POST['driver']) && $_POST['driver'] !== '' ? 
            filter_var($_POST['driver'], FILTER_SANITIZE_STRING) : $currentData['driver'];
            
        $helper1 = isset($_POST['helper1']) && $_POST['helper1'] !== '' ? 
            filter_var($_POST['helper1'], FILTER_SANITIZE_STRING) : $currentData['helper1'];
            
        $helper2 = isset($_POST['helper2']) && $_POST['helper2'] !== '' ? 
            filter_var($_POST['helper2'], FILTER_SANITIZE_STRING) : $currentData['helper2'];

        // Determine the situation based on filled fields (using the updated values)
        $situation = ((empty($truck_id) || empty($driver) || empty($helper1))) ? "Pending" : "Ready for budgeting";

        // Update SQL query
        $sql = "UPDATE customerservice SET 
                    waybill = ?, 
                    date = ?, 
                    status = ?, 
                    delivery_type = ?, 
                    amount = ?, 
                    source = ?, 
                    pickup = ?, 
                    dropoff = ?, 
                    rate = ?, 
                    call_time = ?,
                    truck_id = ?,
                    driver = ?,
                    helper1 = ?,
                    helper2 = ?,
                    situation = ?
                WHERE cs_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssssssssssi", 
            $waybill, $date, $status, $delivery_type, 
            $amount, $source, $pickup, $dropoff, $rate, 
            $call_time, $truck_id, $driver, $helper1, $helper2, 
            $situation, $cs_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Trip updated successfully';
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }

        $stmt->close();
    } else {
        throw new Exception("Invalid request");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in update_trip.php: " . $e->getMessage()); // Log the error
}

$conn->close();

// Clear any previous output
ob_end_clean();

// Ensure proper headers are sent
header('Content-Type: application/json');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Output the JSON with proper flags for safety
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
exit;
?>