<?php
// get_dropdown_data.php
ini_set('display_errors', 0); // Disable displaying errors directly in the response
ini_set('log_errors', 1); // Enable logging errors
ini_set('error_log', 'error.log'); // Specify error log file

require_once 'connection.php';

// Create a response array
$response = array('success' => false, 'message' => '', 'data' => array());

try {
    // Get the requested data type
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    // Get the current trip ID (if updating)
    $current_trip_id = isset($_GET['trip_id']) ? intval($_GET['trip_id']) : 0;
    
    if (empty($type)) {
        throw new Exception("Data type not specified");
    }
    
    switch ($type) {
        case 'trucks':
            // Fetch available trucks (exclude those already assigned to other trips)
            $sql = "SELECT t.truck_id, t.model, t.truck_plate, t.truck_type 
                    FROM truck t
                    WHERE t.truck_id NOT IN (
                        SELECT cs.truck_id 
                        FROM customerservice cs 
                        WHERE cs.truck_id IS NOT NULL";
            
            // If updating, exclude the current trip's truck to make it available for selection
            if ($current_trip_id > 0) {
                $sql .= " AND cs.cs_id != $current_trip_id";
            }
            
            $sql .= ")";
            
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $response['data'][] = $row;
                }
                $response['success'] = true;
            } else {
                // Return success but with empty array
                $response['success'] = true;
                $response['message'] = "No available trucks found";
            }
            break;
            
        case 'drivers':
            // Fetch available drivers (exclude those already assigned to other trips)
            $sql = "SELECT d.driver_id, d.fullname 
                    FROM driver d
                    WHERE d.driver_id NOT IN (
                        SELECT cs.driver 
                        FROM customerservice cs 
                        WHERE cs.driver IS NOT NULL";
            
            // If updating, exclude the current trip's driver to make it available for selection
            if ($current_trip_id > 0) {
                $sql .= " AND cs.cs_id != $current_trip_id";
            }
            
            $sql .= ")";
            
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $response['data'][] = $row;
                }
                $response['success'] = true;
            } else {
                // Return success but with empty array
                $response['success'] = true;
                $response['message'] = "No available drivers found";
            }
            break;
            
        case 'helper1':
            // Fetch available helper1 (exclude those already assigned to other trips)
            $sql = "SELECT h.helper1_id, h.fullname 
                    FROM helper1 h
                    WHERE h.helper1_id NOT IN (
                        SELECT cs.helper1 
                        FROM customerservice cs 
                        WHERE cs.helper1 IS NOT NULL";
            
            // If updating, exclude the current trip's helper1 to make it available for selection
            if ($current_trip_id > 0) {
                $sql .= " AND cs.cs_id != $current_trip_id";
            }
            
            $sql .= ")";
            
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $response['data'][] = $row;
                }
                $response['success'] = true;
            } else {
                // Return success but with empty array
                $response['success'] = true;
                $response['message'] = "No available helper1 found";
            }
            break;
            
        case 'helper2':
            // Fetch available helper2 (exclude those already assigned to other trips)
            $sql = "SELECT h.helper2_id, h.fullname 
                    FROM helper2 h
                    WHERE h.helper2_id NOT IN (
                        SELECT cs.helper2 
                        FROM customerservice cs 
                        WHERE cs.helper2 IS NOT NULL";
            
            // If updating, exclude the current trip's helper2 to make it available for selection
            if ($current_trip_id > 0) {
                $sql .= " AND cs.cs_id != $current_trip_id";
            }
            
            $sql .= ")";
            
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $response['data'][] = $row;
                }
                $response['success'] = true;
            } else {
                // Return success but with empty array
                $response['success'] = true;
                $response['message'] = "No available helper2 found";
            }
            break;
            
        default:
            throw new Exception("Invalid data type specified");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in get_dropdown_data.php: " . $e->getMessage());
}

// Set appropriate headers for JSON response
header('Content-Type: application/json');

// Output JSON response
echo json_encode($response);

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>