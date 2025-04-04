<?php
// get_ready_for_budgeting.php
require_once 'connection.php';

$response = [];

try {
    // Revised query to specifically filter for "Ready for Budgeting" trips
    $sql = "SELECT cs.cs_id, 
            cs.waybill, 
            cs.date, 
            cs.status, 
            cs.delivery_type, 
            cs.amount, 
            cs.source, 
            cs.pickup, 
            cs.dropoff, 
            cs.rate, 
            cs.call_time,
            cs.situation,
            CONCAT(t.model, ', ', t.truck_plate, ', ', t.truck_type) AS truck_details,
            d.fullname AS driver_name,
            h1.fullname AS helper1_name,
            h2.fullname AS helper2_name
            FROM customerservice cs
            LEFT JOIN truck t ON cs.truck_id = t.truck_id
            LEFT JOIN driver d ON cs.driver = d.driver_id
            LEFT JOIN helper1 h1 ON cs.helper1 = h1.helper1_id
            LEFT JOIN helper2 h2 ON cs.helper2 = h2.helper2_id
            WHERE LOWER(cs.situation) = 'Budgeted'
            ORDER BY cs.date DESC";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Sanitize and format data if needed
            $row['amount'] = number_format($row['amount'], 2, '.', ',');
            $response[] = $row;
        }
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error fetching ready for budgeting trips: " . $e->getMessage());
    
    // Return an error response
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch trips']);
    exit;
}

// Ensure proper JSON headers
header('Content-Type: application/json');
echo json_encode($response);
?>