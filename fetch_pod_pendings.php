<?php
// fetch_pod_pendings.php
require_once 'connection.php';

// Function to get trips with Complete POD status
function getPendingTrips() {
    global $conn;
    $trips = array();
    
    try {
        // Modified query to only select trips with Complete POD status
        $sql = "SELECT cs.*, 
        p.pod_status,
        CONCAT(t.model, ', ', t.truck_plate, ', ', t.truck_type) AS truck_details,
        d.fullname AS driver_name,
        h1.fullname AS helper1_name,
        h2.fullname AS helper2_name
        FROM customerservice cs
        INNER JOIN pod p ON cs.cs_id = p.cs_id
        LEFT JOIN truck t ON cs.truck_id = t.truck_id
        LEFT JOIN driver d ON cs.driver = d.driver_id
        LEFT JOIN helper1 h1 ON cs.helper1 = h1.helper1_id
        LEFT JOIN helper2 h2 ON cs.helper2 = h2.helper2_id
        WHERE p.pod_status = 'Incomplete'
        ORDER BY cs.date DESC";

        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $trips[] = $row;
            }
        }
    } catch (Exception $e) {
        // Handle exception
        error_log("Error fetching trips: " . $e->getMessage());
    }
    
    return $trips;
}

// Return data as JSON if it's an AJAX request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $trips = getPendingTrips();
    echo json_encode($trips);
    exit;
}
?>