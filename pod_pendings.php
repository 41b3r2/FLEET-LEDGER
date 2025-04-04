<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$u_id = $_SESSION["u_id"];
$permissions = [
    1 => ["all_access" => true],
    2 => ["waybill.php" => true, "dispatcher.php" => true, "viewsheet.php" => true],
    3 => ["pod.php" => true],
    4 => ["pod.php" => true, "ar.php" => true, "viewsheet.php" => true],
    5 => ["queries.php" => true, "viewsheet.php" => true],
    6 => ["budget.php" => true, "viewsheet.php" => true],
    7 => ["waybill.php" => true, "dispatcher.php" => true, "viewsheet.php" => true],
    8 => ["dispatcher.php" => true],
    9 => ["pod.php" => true]
];

function hasAccess($u_id, $page, $permissions) {
    return isset($permissions[$u_id]["all_access"]) || 
           (isset($permissions[$u_id][$page]) && $permissions[$u_id][$page]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - PENDING PODs</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/waybill.css">
    <script src="assets/js/budget.js"></script>
    <style>
        #updateTripModal {
            display: none !important;
        }
        .budgeted-row {
            background-color: #f0f0f0;
            color: #666;
        }
        .budgeted-row .update-btn {
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="mobile-toggle">☰</div>
    <div class="overlay"></div>
    
    <div class="loading-screen" id="loading-screen">
        <div class="loader"></div>
        <span>Loading...</span>
    </div>
    
    <div class="logo-wrapper">
        <img src="assets/img/logo.png" alt="PCL Logo" style="width: 150px; height: auto;">
    </div>
    
    <div class="sidebar">
        <div class="user-info">
            <div class="name"><?php echo htmlspecialchars($_SESSION["fullname"]); ?></div>
            <div class="role">Role ID: <?php echo htmlspecialchars($_SESSION["u_id"]); ?></div>
        </div>
        <div>
            <div class="metric-section" data-href="landingPage.php">
                <div class="chart-container">
                    <div class="pie-chart">
                        <div class="pie-slice"></div>
                    </div>
                </div>
                <div class="metric-title">UTILIZATION</div>
            </div>
            <div class="metric-section" data-href="available.php">
                <div class="bar-container">
                    <div class="bar bar-1"></div>
                    <div class="bar bar-2"></div>
                    <div class="bar bar-3"></div>
                </div>
                <div class="metric-title">AVAILABLE TDH</div>
            </div>
            <div class="metric-section" data-href="references.php">
                <div class="chart-container">
                    <div class="people-icon">
                        <div class="people-head"></div>
                        <div class="people-body"></div>
                    </div>
                </div>
                <div class="metric-title">REFERENCES</div>
            </div>
        </div>
        
        <a href="logout.php" class="logout-link" id="logout-link">
            <div class="logout-section">
                <div class="logout-icon">←</div>
                <span>Log Out</span>
            </div>
        </a>
    </div>
    
    <div class="main-content">
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="PCL Logo" style="width: 200px; height: auto;">
        </div>
        
        <div class="table-grid">
            <div class="pending-trip-section">
                <div class="trip-header">
                    <h2>LIST OF PENDING PODs</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="trip-table">
                        <thead>
                            <tr>
                                <th>Waybill No.</th>
                                <th>Date</th>
                                <th>FO/PO/STO</th>
                                <th>Delivery Type</th>
                                <th>Amount</th>
                                <th>Source</th>
                                <th>Pick Up</th>
                                <th>Drop Off</th>
                                <th>Rate</th>
                                <th>Call Time</th>
                                <th>Situation</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tripTableBody">
                        <?php
                            require_once 'fetch_pod_pendings.php';
                            $trips = getPendingTrips();

                            if (count($trips) > 0) {
                                foreach ($trips as $trip) {
                                    echo "<tr data-id='" . $trip['cs_id'] . "'>";
                                    echo "<td>" . $trip['waybill'] . "</td>";
                                    echo "<td>" . date("F j, Y", strtotime($trip['date'])) . "</td>";
                                    echo "<td>" . $trip['status'] . "</td>";
                                    echo "<td>" . $trip['delivery_type'] . "</td>";
                                    echo "<td> ₱ " . (isset($trip['amount']) ? $trip['amount'] : '') . "</td>";
                                    echo "<td>" . $trip['source'] . "</td>";
                                    echo "<td>" . $trip['pickup'] . "</td>";
                                    echo "<td>" . $trip['dropoff'] . "</td>";
                                    echo "<td>" . $trip['rate'] . "</td>";
                                    echo "<td>" . date("h:i A", strtotime($trip['call_time'])) . "</td>";
                                    echo "<td style='color: red; font-weight: bold;'>" . $trip['pod_status'] . "</td>";
                                    echo "<td><button class='update-btn' data-id='" . $trip['cs_id'] . "'>View Details</button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='16'>No trips with complete POD found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="pagination">
                    <a href="#" class="prev">Previous</a>
                    <a href="#" class="page-num active">1</a>
                    <a href="#" class="page-num">2</a>
                    <a href="#" class="page-num">3</a>
                    <a href="#" class="next">Next</a>
                </div>
            
            </div>
        </div>
    </div>


<!-- Add this JavaScript to your existing script tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Existing modal and button selectors
    const updateButtons = document.querySelectorAll('.update-btn');
    const updateTripModal = document.getElementById('updateTripModal');
    const closeModalButtons = document.querySelectorAll('.close-modal, .cancel-btn');

    // Budget calculation inputs
    const fuelFeeInput = document.getElementById('fuelfee');
    const tollFeeInput = document.getElementById('tollfee');
    const parkingFeeInput = document.getElementById('parkingfee');
    const roroFareInput = document.getElementById('rorofarefee');
    const budgetReleaseInput = document.getElementById('budgetrelease');

    // Calculate total budget release
    function calculateBudgetRelease() {
        const fuelfee = parseFloat(fuelFeeInput.value) || 0;
        const tollfee = parseFloat(tollFeeInput.value) || 0;
        const parkingfee = parseFloat(parkingFeeInput.value) || 0;
        const rorofarefee = parseFloat(roroFareInput.value) || 0;

        const total = fuelfee + tollfee + parkingfee + rorofarefee;
        budgetReleaseInput.value = total.toFixed(2);
    }

    // Add event listeners for budget inputs
    [fuelFeeInput, tollFeeInput, parkingFeeInput, roroFareInput].forEach(input => {
        input.addEventListener('input', calculateBudgetRelease);
    });

    // Handle Update Trip Form Submission
    const updateTripForm = document.getElementById('updateTripForm');
    updateTripForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading screen
        document.getElementById("loading-screen").style.display = "flex";

        // Gather all form data manually
        const formData = new FormData();
        
        // Add all form inputs to FormData
        const inputs = updateTripForm.querySelectorAll('input, select');
        inputs.forEach(input => {
            // Only add inputs that have a name and a value
            if (input.name && (input.value || input.value === '0')) {
                formData.append(input.name, input.value);
                console.log(`Adding to FormData: ${input.name} = ${input.value}`);
            }
        });

        // Send AJAX request to insert budget and POD
        fetch('insert_ar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            // Hide loading screen
            document.getElementById("loading-screen").style.display = "none";

            // Log the full response data
            console.log('Full response data:', data);

            if (data.success) {
                alert('Trip details saved successfully!');
                
                // Close the modal
                updateTripModal.style.display = 'none';
                
                // Refresh the table to remove the processed trip
                location.reload();
            } else {
                // More detailed error message
                console.error('Submission Error Details:', data);
                alert('Error: ' + (data.message || 'Unknown error occurred'));
            }
        })
        .catch(error => {
            // Hide loading screen
            document.getElementById("loading-screen").style.display = "none";
            
            // Log the full error
            console.error('Caught Error:', error);
            
            // Try to get more information about the error
            if (error instanceof TypeError) {
                console.error('TypeError details:', {
                    message: error.message,
                    name: error.name,
                    stack: error.stack
                });
            }
            
            alert('An error occurred while submitting trip details. Check console for more information.');
        });
    });

    // Existing modal open logic for update buttons
    updateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const csId = this.getAttribute('data-id');
            
            // Show loading screen
            document.getElementById("loading-screen").style.display = "flex";

            // Fetch trip details for the selected CS ID
            fetch(`get_trip_details.php?cs_id=${csId}`)
            .then(response => response.json())
            .then(trip => {
                // Hide loading screen
                document.getElementById("loading-screen").style.display = "none";

                // Populate form fields
                document.getElementById('update_id').value = trip.cs_id;
                document.getElementById('update_topsheet').value = trip.topsheet || '';
                document.getElementById('update_waybill').value = trip.waybill || '';
                document.getElementById('update_date').value = trip.date || '';
                document.getElementById('update_status').value = trip.status || '';
                document.getElementById('update_delivery_type').value = trip.delivery_type || '';
                document.getElementById('update_amount').value = trip.amount || '';
                document.getElementById('update_source').value = trip.source || '';
                document.getElementById('update_pickup').value = trip.pickup || '';
                document.getElementById('update_dropoff').value = trip.dropoff || '';
                document.getElementById('update_rate').value = trip.rate || '';
                document.getElementById('update_call_time').value = trip.call_time || '';
                document.getElementById('update_truck_id').value = trip.truck_id || '';
                document.getElementById('update_driver').value = trip.driver || '';
                document.getElementById('update_helper1').value = trip.helper1 || '';
                document.getElementById('update_helper2').value = trip.helper2 || '';

                // Check if budget details exist and pre-fill
                if (trip.budget) {
                    document.getElementById('fuelfee').value = trip.budget.fuelfee || '';
                    document.getElementById('tollfee').value = trip.budget.tollfee || '';
                    document.getElementById('parkingfee').value = trip.budget.parkingfee || '';
                    document.getElementById('rorofarefee').value = trip.budget.rorofarefee || '';
                    document.getElementById('budgetrelease').value = trip.budget.budgetrelease || '';
                } else {
                    // Reset budget fields
                    ['fuelfee', 'tollfee', 'parkingfee', 'rorofarefee', 'budgetrelease'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                }

                if (trip.pod) {
                    document.getElementById('pod_status').value = trip.pod.pod_status || '';
                    document.getElementById('date_received').value = trip.pod.date_received || '';
                    document.getElementById('Remarks').value = trip.pod.Remarks || '';
                    document.getElementById('pod_transmittal').value = trip.pod.pod_transmittal || '';
                    document.getElementById('date_transmitted').value = trip.pod.date_transmitted || '';
                } else {
                    // Reset POD fields if no existing data
                    ['pod_status', 'date_received', 'Remarks', 'pod_transmittal', 'date_transmitted'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                }

                if (trip.pod) {
                    document.getElementById('odo_out').value = trip.pod.odo_out || '';
                    document.getElementById('odo_in').value = trip.pod.odo_in || '';
                    document.getElementById('odo_total').value = trip.pod.odo_total || '';
                } else {
                    // Reset odometer fields if no existing data
                    ['odo_out', 'odo_in', 'odo_total'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                }

                if (trip.ar) {
                    document.getElementById('invoice_number').value = trip.ar.invoice_number || '';
                    document.getElementById('ar_date_received').value = trip.ar.date_received || '';
                    document.getElementById('remarks').value = trip.ar.remarks || '';
                } else {
                    // Reset AR fields if no existing data
                    ['invoice_number', 'date_received', 'remarks'].forEach(id => {
                        document.getElementById(id).value = '';
                    });
                }

                // Show the modal
                updateTripModal.style.display = 'flex';
            })
            .catch(error => {
                // Hide loading screen
                document.getElementById("loading-screen").style.display = "none";
                console.error('Error:', error);
                alert('Failed to fetch trip details.');
            });
        });
    });

    // Close modal buttons
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function() {
            updateTripModal.style.display = 'none';
        });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === updateTripModal) {
            updateTripModal.style.display = 'none';
        }
    });
});













































</script>
    
    <!-- Update Trip Modal - with forced hide -->
    <div class="modal-overlay" id="updateTripModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Update Trip</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="updateTripForm" method="post">
                    <input type="hidden" id="update_id" name="cs_id">
                    
                    <!-- Row 1 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_topsheet">Topsheet No:</label>
                            <input type="number" id="update_topsheet" name="topsheet" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_waybill">Waybill No:</label>
                            <input type="number" id="update_waybill" name="waybill" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_date">Date:</label>
                            <input type="date" id="update_date" name="date" readonly>
                        </div>
                    </div>
                    
                    <!-- Row 2 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_status">FO/PO/STO:</label>
                            <select id="update_status" name="status" readonly>
                                <option value="">Select Type</option>
                                <option value="Freight Order">Freight Order</option>
                                <option value="Purchase Order">Purchase Order</option>
                                <option value="Stock Transfer Order">Stock Transfer Order</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_delivery_type">Delivery Type:</label>
                            <input type="text" id="update_delivery_type" name="delivery_type" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_amount">Amount:</label>
                            <input type="text" id="update_amount" name="amount" readonly>
                        </div>
                    </div>
                    
                    <!-- Row 3 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_source">Source:</label>
                            <input type="text" id="update_source" name="source" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_pickup">Pick Up:</label>
                            <input type="text" id="update_pickup" name="pickup" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_dropoff">Drop Off:</label>
                            <input type="text" id="update_dropoff" name="dropoff" readonly>
                        </div>
                    </div>
                    
                    <!-- Row 4 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_rate">Rate:</label>
                            <input type="text" id="update_rate" name="rate" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_call_time">Call Time:</label>
                            <input type="time" id="update_call_time" name="call_time" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_truck_id">Truck Type:</label>
                            <select id="update_truck_id" name="truck_id" readonly>
                                <option value="">Select Truck</option>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                    </div>
                    
                    <!-- Row 5 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_driver">Driver:</label>
                            <select id="update_driver" name="driver_name" readonly>
                                <option value="">Select Driver</option>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_helper1">Helper 1:</label>
                            <select id="update_helper1" name="helper1_name" readonly>
                                <option value="">Select Helper 1</option>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_helper2">Helper 2:</label>
                            <select id="update_helper2" name="helper2_name" readonly>
                                <option value="">Select Helper 2</option>
                                <!-- Options will be loaded dynamically -->
                            </select>
                        </div>
                    </div>
                    <hr style="margin-bottom: 15px;">

                    <!-- Row 6 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label style="color: maroon; font-weight: bold;">Budget</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fuelfee">Fuel Fee:</label>
                            <input type="number" id="fuelfee" name="fuelfee"readonly>
                        </div>
                        <div class="form-group">
                            <label for="tollfee">Toll Fee:</label>
                            <input type="number" id="tollfee" name="tollfee"readonly>
                        </div>
                        <div class="form-group">
                            <label for="parkingfee">Parking Fee:</label>
                            <input type="number" id="parkingfee" name="parkingfee"readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rorofarefee">Roro Fare:</label>
                            <input type="number" id="rorofarefee" name="rorofarefee"readonly>
                        </div>
                        <div class="form-group">
                            <label for="budgetrelease">Total Budget Release:</label>
                            <input type="number" id="budgetrelease" name="budgetrelease" readonly>
                        </div>
                    </div>




                    <hr style="margin-bottom: 15px;">

                    <!-- Row 7 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label style="color: maroon; font-weight: bold;">Proof of Delivery</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pod_status">POD Status:</label>
                            <select id="pod_status" name="pod_status" readonly>
                                <option value="">Select Status</option>
                                <option value="Complete">Complete</option>
                                <option value="Incomplete">Incomplete</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_received">Date Received:</label>
                            <input type="date" id="date_received" name="date_received" readonly>
                        </div>
                        <div class="form-group">
                            <label for="Remarks">Remarks:</label>
                            <select id="Remarks" name="Remarks" readonly>
                                <option value="">Select Remarks</option>
                                <option value="No Stamp">No Stamp</option>
                                <option value="No Sign">No Sign</option>
                                <option value="No Counter">No Counter</option>
                                <option value="Missing Docs">Missing Docs</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pod_transmittal">POD Transmittal:</label>
                            <input type="text" id="pod_transmittal" name="pod_transmittal" readonly>
                        </div>
                        <div class="form-group">
                            <label for="date_transmitted">Date Transmitted:</label>
                            <input type="date" id="date_transmitted" name="date_transmitted" readonly>
                        </div>
                    </div>


                    <!-- Row 8 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label style="color: maroon; font-weight: bold;">Mileage</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="odo_out">Mileage Out:</label>
                            <input type="number" id="odo_out" name="odo_out" readonly>
                        </div>
                        <div class="form-group">
                            <label for="odo_in">Mileage In:</label>
                            <input type="number" id="odo_in" name="odo_in" readonly>
                        </div>
                        <div class="form-group">
                            <label for="odo_total">Total Mileage:</label> 
                            <input type="number" id="odo_total" name="odo_total" readonly>
                        </div>
                    </div>


                    <hr style="margin-bottom: 15px;">

                    <!-- Row 9 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label style="color: maroon; font-weight: bold;">A R</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="invoice_number">Invoice No.:</label>
                            <input type="number" id="invoice_number" name="invoice_number" readonly>
                        </div>
                        <div class="form-group">
                            <label for="date_received">Date Received:</label>
                            <input type="date" id="ar_date_received" name="date_received" readonly>
                        </div>
                        <div class="form-group">
                            <label for="remarks">Remarks:</label>
                            <select id="remarks" name="remarks" readonly>
                                <option value="">Select Remarks</option>
                                <option value="Waiting for approval (client)">Waiting for approval (client)</option>
                                <option value="Missing Docs">Missing Docs</option>
                            </select>
                        </div>
                    </div>






                    <div class="form-actions">
                        <button type="button" class="cancel-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>