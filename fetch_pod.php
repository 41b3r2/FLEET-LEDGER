<?php
// fetch_pod.php
require_once 'connection.php';

// Function to get trips with Complete POD status and AR remarks = 'Done'
function getPendingTrips() {
    global $conn;
    $trips = array();
    
    try {
        // Modified query to select trips with Complete POD status and include AR data
        $sql = "SELECT cs.*, 
                p.pod_status, p.date_received, p.Remarks, p.pod_transmittal, p.date_transmitted,
                p.odo_out, p.odo_in, p.odo_total,
                ar.invoice_number, ar.date_received AS ar_date_received, ar.remarks AS ar_remarks,
                CONCAT(t.model, ', ', t.truck_plate, ', ', t.truck_type) AS truck_details,
                d.fullname AS driver_name,
                h1.fullname AS helper1_name,
                h2.fullname AS helper2_name
                FROM customerservice cs
                INNER JOIN pod p ON cs.cs_id = p.cs_id
                LEFT JOIN ar ON cs.cs_id = ar.cs_id
                LEFT JOIN truck t ON cs.truck_id = t.truck_id
                LEFT JOIN driver d ON cs.driver = d.driver_id
                LEFT JOIN helper1 h1 ON cs.helper1 = h1.helper1_id
                LEFT JOIN helper2 h2 ON cs.helper2 = h2.helper2_id
                WHERE p.pod_status = 'Complete'
                ORDER BY cs.date DESC";

        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Organize the data into a structured array
                $trip = [
                    'cs_id' => $row['cs_id'],
                    'ts_id' => $row['ts_id'],
                    'waybill' => $row['waybill'],
                    'date' => $row['date'],
                    'status' => $row['status'],
                    'delivery_type' => $row['delivery_type'],
                    'amount' => $row['amount'],
                    'source' => $row['source'],
                    'pickup' => $row['pickup'],
                    'dropoff' => $row['dropoff'],
                    'rate' => $row['rate'],
                    'call_time' => $row['call_time'],
                    'truck_id' => $row['truck_id'],
                    'driver' => $row['driver_name'],
                    'helper1' => $row['helper1_name'],
                    'helper2' => $row['helper2_name'],
                    'pod_status' => $row['pod_status'],
                    'pod' => [
                        'date_received' => $row['date_received'],
                        'Remarks' => $row['Remarks'],
                        'pod_transmittal' => $row['pod_transmittal'],
                        'date_transmitted' => $row['date_transmitted'],
                        'odo_out' => $row['odo_out'],
                        'odo_in' => $row['odo_in'],
                        'odo_total' => $row['odo_total']
                    ],
                    'ar' => [
                        'invoice_number' => $row['invoice_number'],
                        'date_received' => $row['ar_date_received'],
                        'remarks' => $row['ar_remarks']
                    ]
                ];
                
                $trips[] = $trip;
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
    header('Content-Type: application/json');
    echo json_encode($trips);
    exit;
}
?>