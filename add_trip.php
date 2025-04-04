<?php
// add_trip.php
ini_set('display_errors', 0); 
ini_set('log_errors', 1); 
ini_set('error_log', 'error.log');

require_once 'connection.php'; 

$response = array('success' => false, 'message' => '', 'ts_id' => '');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $conn->begin_transaction();
        
        $query = "SELECT ts_id FROM topsheet WHERE ts_id LIKE 'TS-%' ORDER BY CAST(SUBSTRING(ts_id, 4) AS UNSIGNED) DESC LIMIT 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $lastTopsheet = $row['ts_id'];
            $lastNumber = intval(substr($lastTopsheet, 3));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        
        $ts_id = 'TS-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        
        $required_fields = [
            'waybill', 'date', 'status', 'delivery_type', 
            'amount', 'source', 'pickup', 'dropoff', 
            'rate', 'call_time'
        ];
        
        $data = [];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("The field $field is required.");
            }
            $data[$field] = $conn->real_escape_string($_POST[$field]);
        }

        $topsheetSql = "INSERT INTO topsheet (ts_id, odo_out) VALUES (?, 0)";
        $topsheetStmt = $conn->prepare($topsheetSql);
        $topsheetStmt->bind_param("s", $ts_id);
        
        if (!$topsheetStmt->execute()) {
            throw new Exception("Error in topsheet: " . $topsheetStmt->error);
        }
        $topsheetStmt->close();
        
        $customerSql = "INSERT INTO customerservice 
                        (ts_id, waybill, date, status, delivery_type, amount, 
                         source, pickup, dropoff, rate, call_time, situation) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";

        $customerStmt = $conn->prepare($customerSql);
        $customerStmt->bind_param("sisssssssss", 
            $ts_id, // Foreign key to topsheet
            $data['waybill'], 
            $data['date'], 
            $data['status'], 
            $data['delivery_type'],
            $data['amount'], 
            $data['source'], 
            $data['pickup'], 
            $data['dropoff'], 
            $data['rate'], 
            $data['call_time']
        );

        if (!$customerStmt->execute()) {
            throw new Exception("Error in customerservice: " . $customerStmt->error);
        }
        $customerStmt->close();
        
        // Commit transaction
        $conn->commit();
            
        $response['success'] = true;
        $response['message'] = 'Trip Successfully Added!';
        $response['ts_id'] = $ts_id;
        
    } else {
        throw new Exception("Wrong request method");
    }
} catch (Exception $e) {
    if ($conn && $conn->ping()) {
        $conn->rollback();
    }
    
    $response['message'] = $e->getMessage();
    error_log("Error in add_trip.php: " . $e->getMessage());
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>