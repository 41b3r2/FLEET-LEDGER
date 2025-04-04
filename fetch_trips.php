<?php
// fetch_trips.php
require_once 'connection.php';

/**
 * Gets all pending trips with their details
 * @return array Array containing topsheets and trips data
 */
function getPendingTrips() {
    global $conn;
    $trips = [];
    $topsheets = [];
    
    try {
        // Get unique topsheets with count of waybills
        $sql = "SELECT cs.ts_id, 
                COUNT(cs.waybill) as waybill_count,
                MIN(cs.date) as first_date,
                GROUP_CONCAT(DISTINCT cs.source) as sources,
                GROUP_CONCAT(DISTINCT cs.pickup) as pickups,
                GROUP_CONCAT(DISTINCT cs.dropoff) as dropoffs
                FROM customerservice cs
                WHERE cs.ts_id IS NOT NULL AND cs.ts_id != ''
                GROUP BY cs.ts_id
                ORDER BY cs.ts_id DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $topsheets[] = $row;
            }
        }
        
        // Get all trip details with related data
        $detailSql = "SELECT cs.*, 
                CONCAT(t.model, ', ', t.truck_plate, ', ', t.truck_type) AS truck_details,
                d.fullname AS driver_name,
                h1.fullname AS helper1_name,
                h2.fullname AS helper2_name
                FROM customerservice cs
                LEFT JOIN truck t ON cs.truck_id = t.truck_id
                LEFT JOIN driver d ON cs.driver = d.driver_id
                LEFT JOIN helper1 h1 ON cs.helper1 = h1.helper1_id
                LEFT JOIN helper2 h2 ON cs.helper2 = h2.helper2_id
                ORDER BY cs.ts_id DESC, cs.date DESC";
        
        $detailResult = $conn->query($detailSql);
        
        if ($detailResult && $detailResult->num_rows > 0) {
            while($row = $detailResult->fetch_assoc()) {
                $trips[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching trips: " . $e->getMessage());
    }
    
    return ['topsheets' => $topsheets, 'trips' => $trips];
}

// Handle AJAX request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(getPendingTrips());
    exit;
}
?>