<?php
// pod.php
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

require_once 'fetch_budgeted.php';
$trips = getPendingTrips();

// Calculate statistics
$topsheets = [];
$waybillCount = 0;
$sources = [];

foreach ($trips as $trip) {
    // Group by topsheet
    $ts_id = $trip['ts_id'] ? $trip['ts_id'] : "No topsheet";
    if (!isset($topsheets[$ts_id])) {
        $topsheets[$ts_id] = 0;
    }
    if (isset($trip['pod_status']) && $trip['pod_status'] == 'Complete') {
        continue; // Skip this iteration of the loop (don't display this row)
    }
    $topsheets[$ts_id]++;
    
    // Count waybills
    $waybillCount++;
    
    // Track unique sources
    if (!empty($trip['source'])) {
        $sources[$trip['source']] = true;
    }
}

$uniqueTopsheets = count($topsheets);
$uniqueSources = count($sources);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - PROOF OF DELIVERY</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/waybill.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #updateTripModal {
            display: none;
        }
        .budgeted-row {
            background-color:rgb(255, 255, 255);
            color: #666;
        }
        .filter-section {
            margin-bottom: 15px;
            padding: 0px;
            border-radius: 5px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-control label {
            font-weight: bold;
            white-space: nowrap;
        }
        .filter-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 200px;
        }
        .topsheet-display {
            background-color: #f7f7f7;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
        }
        .topsheet-label {
            color: #333;
            margin-right: 10px;
        }
        .topsheet-value {
            color: #c01818;
            font-size: 18px;
        }
        .summary-card {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .summary-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .summary-value {
            font-weight: bold;
            font-size: 18px;
        }
        .reset-btn {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }
        .reset-btn:hover {
            background-color: #e0e0e0;
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
                    <h2>PROOF OF DELIVERY</h2>
                </div>
                
                <!-- Summary Statistics Section -->
                <div class="summary-card">
                    <div class="summary-item">
                        <span>Total Topsheets</span>
                        <span class="summary-value"><?php echo $uniqueTopsheets; ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Total Waybills</span>
                        <span class="summary-value"><?php echo $waybillCount; ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Unique Sources</span>
                        <span class="summary-value"><?php echo $uniqueSources; ?></span>
                    </div>
                </div>
                
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="filter-control">
                        <label for="topsheet-filter"><i class="fas fa-filter"></i> Filter by Topsheet:</label>
                        <select id="topsheet-filter" class="filter-select">
                            <option value="all">All Topsheets</option>
                            <?php foreach ($topsheets as $ts_id => $count): ?>
                                <option value="<?php echo htmlspecialchars($ts_id); ?>">
                                    <?php echo htmlspecialchars($ts_id); ?> 
                                    (<?php echo $count; ?> trips)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button id="reset-filters" class="submit-btn" style="padding: 8px 12px;">
                        <i class="fas fa-sync-alt"></i> Reset Filters
                    </button>
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
                        if (count($trips) > 0) {
                            foreach ($trips as $trip) {
                                // Skip rows with complete POD status
                                if (isset($trip['pod_status']) && $trip['pod_status'] === 'Complete') {
                                    continue;
                                }
                                
                                $rowClass = ($trip['situation'] == 'Budgeted') ? '' : '';
                                echo "<tr class='{$rowClass}' data-id='" . $trip['cs_id'] . "' data-topsheet='" . ($trip['ts_id'] ? htmlspecialchars($trip['ts_id']) : "No topsheet") . "'>";
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
                                
                                // Modified Situation display with POD status
                                $situationText = $trip['situation'];
                                if ($trip['situation'] == 'Budgeted') {
                                    $podStatus = isset($trip['pod_status']) ? $trip['pod_status'] : 'Incomplete';
                                    $situationText .= " - " . $podStatus;
                                }
                                
                                echo "<td style='" . 
                                ($trip['situation'] == 'Budgeted' ? 'color: green; font-weight: bold;' : 
                                ($trip['situation'] == 'Ready for budgeting' ? 'color: red; font-weight: bold;' : '')) . 
                                "'>" . $situationText . "</td>";
                                echo "<td><button class='update-btn' data-id='" . $trip['cs_id'] . "'>View | Update</button></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='16'>No pending trips found</td></tr>";
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

    <!-- Update Trip Modal -->
    <div class="modal-overlay" id="updateTripModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Update Trip</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="topsheet-display">
                    <span class="topsheet-label">TOPSHEET:</span>
                    <span class="topsheet-value" id="topsheet-value"></span>
                </div>
                
                <form id="updateTripForm" method="post">
                    <input type="hidden" id="update_id" name="cs_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_waybill">Waybill No:</label>
                            <input type="number" id="update_waybill" name="waybill" readonly>
                        </div>
                        <div class="form-group">
                            <label for="update_date">Date:</label>
                            <input type="date" id="update_date" name="date" readonly>
                        </div>
                    </div>
                    
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
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_driver">Driver:</label>
                            <select id="update_driver" name="driver" readonly>
                                <option value="">Select Driver</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_helper1">Helper 1:</label>
                            <select id="update_helper1" name="helper1" readonly>
                                <option value="">Select Helper 1</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_helper2">Helper 2:</label>
                            <select id="update_helper2" name="helper2" readonly>
                                <option value="">Select Helper 2</option>
                            </select>
                        </div>
                    </div>
                    <hr style="margin-bottom: 15px;">

                    <div class="form-row">
                        <div class="form-group">
                            <label style="color: maroon; font-weight: bold;">Budget</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fuelfee">Fuel Fee:</label>
                            <input type="number" id="fuelfee" name="fuelfee" readonly>
                        </div>
                        <div class="form-group">
                            <label for="tollfee">Toll Fee:</label>
                            <input type="number" id="tollfee" name="tollfee" readonly>
                        </div>
                        <div class="form-group">
                            <label for="parkingfee">Parking Fee:</label>
                            <input type="number" id="parkingfee" name="parkingfee" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rorofarefee">Roro Fare:</label>
                            <input type="number" id="rorofarefee" name="rorofarefee" readonly>
                        </div>
                        <div class="form-group">
                            <label for="budgetrelease">Total Budget Release:</label>
                            <input type="number" id="budgetrelease" name="budgetrelease" readonly>
                        </div>
                    </div>

                    <hr style="margin-bottom: 15px;">

                    <div class="form-row">
                        <div class="form-group">
                            <label style="color: maroon; font-weight: bold;">Proof of Delivery</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pod_status">POD Status:</label>
                            <select id="pod_status" name="pod_status">
                                <option value="">Select Status</option>
                                <option value="Complete">Complete</option>
                                <option value="Incomplete">Incomplete</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_received">Date Received:</label>
                            <input type="date" id="date_received" name="date_received">
                        </div>
                        <div class="form-group">
                            <label for="Remarks">Remarks:</label>
                            <select id="Remarks" name="Remarks">
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
                            <input type="text" id="pod_transmittal" name="pod_transmittal">
                        </div>
                        <div class="form-group">
                            <label for="date_transmitted">Date Transmitted:</label>
                            <input type="date" id="date_transmitted" name="date_transmitted">
                        </div>
                    </div>

                    <hr style="margin-bottom: 15px;">

                    <div class="form-row">
                        <div class="form-group">
                            <label style="color: maroon; font-weight: bold;">Mileage</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="odo_in">Mileage PCL In:</label>
                            <input type="number" id="odo_in" name="odo_in">
                        </div>
                        <div class="form-group">
                            <label for="odo_out">Mileage PCL Out:</label>
                            <input type="number" id="odo_out" name="odo_out">
                        </div>
                        <div class="form-group">
                            <label for="odo_total">Total Mileage:</label> 
                            <input type="number" id="odo_total" name="odo_total" readonly>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="submit-btn">Update Trip</button>
                        <button type="button" class="cancel-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Topsheet filter functionality
        const topsheetFilter = document.getElementById('topsheet-filter');
        const resetFiltersBtn = document.getElementById('reset-filters');
        
        function applyFilters() {
            const selectedTopsheet = topsheetFilter.value;
            
            document.querySelectorAll('#tripTableBody tr').forEach(row => {
                const rowTopsheet = row.getAttribute('data-topsheet');
                
                if (selectedTopsheet === 'all' || rowTopsheet === selectedTopsheet) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        topsheetFilter.addEventListener('change', applyFilters);
        
        // Reset filters functionality
        resetFiltersBtn.addEventListener('click', function() {
            topsheetFilter.value = 'all';
            applyFilters();
        });

        // Existing modal and button selectors
        const updateButtons = document.querySelectorAll('.update-btn');
        const updateTripModal = document.getElementById('updateTripModal');
        const closeModalButtons = document.querySelectorAll('.close-modal, .cancel-btn');

        // Handle Update Trip Form Submission
        const updateTripForm = document.getElementById('updateTripForm');
        updateTripForm.addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById("loading-screen").style.display = "flex";

    const formData = new FormData();
    const inputs = updateTripForm.querySelectorAll('input, select');
    inputs.forEach(input => {
        if (input.name && (input.value || input.value === '0')) {
            formData.append(input.name, input.value);
        }
    });

    fetch('insert_pod.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById("loading-screen").style.display = "none";
        if (data.success) {
            alert('Trip details saved successfully!');
            updateTripModal.style.display = 'none';
            
            // If POD status was set to Complete, hide the row
            if (document.getElementById('pod_status').value === 'Complete') {
                const row = document.querySelector(`tr[data-id="${formData.get('cs_id')}"]`);
                if (row) row.style.display = 'none';
            } else {
                // Otherwise just reload
                location.reload();
            }
        } else {
            alert('Error: ' + (data.message || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        document.getElementById("loading-screen").style.display = "none";
        console.error('Error:', error);
        alert('An error occurred while submitting trip details.');
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

                    // Update topsheet display
                    document.getElementById('topsheet-value').textContent = trip.ts_id || 'No topsheet';

                    // Populate form fields
                    document.getElementById('update_id').value = trip.cs_id;
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

                    // Fetch and populate truck options
                    fetch('get_trucks.php')
                        .then(response => response.json())
                        .then(trucks => {
                            const truckSelect = document.getElementById('update_truck_id');
                            truckSelect.innerHTML = '<option value="">Select Truck</option>';
                            trucks.forEach(truck => {
                                const option = document.createElement('option');
                                option.value = truck.id;
                                option.textContent = truck.name;
                                option.selected = truck.id == trip.truck_id;
                                truckSelect.appendChild(option);
                            });
                        });

                    // Fetch and populate driver options
                    fetch('get_drivers.php')
                        .then(response => response.json())
                        .then(drivers => {
                            const driverSelect = document.getElementById('update_driver');
                            driverSelect.innerHTML = '<option value="">Select Driver</option>';
                            drivers.forEach(driver => {
                                const option = document.createElement('option');
                                option.value = driver.id;
                                option.textContent = driver.name;
                                option.selected = driver.id == trip.driver;
                                driverSelect.appendChild(option);
                            });
                        });

                    // Fetch and populate helper options
                    fetch('get_helpers.php')
                        .then(response => response.json())
                        .then(helpers => {
                            const helper1Select = document.getElementById('update_helper1');
                            const helper2Select = document.getElementById('update_helper2');
                            helper1Select.innerHTML = '<option value="">Select Helper 1</option>';
                            helper2Select.innerHTML = '<option value="">Select Helper 2</option>';
                            
                            helpers.forEach(helper => {
                                const option1 = document.createElement('option');
                                option1.value = helper.id;
                                option1.textContent = helper.name;
                                option1.selected = helper.id == trip.helper1;
                                helper1Select.appendChild(option1);
                                
                                const option2 = document.createElement('option');
                                option2.value = helper.id;
                                option2.textContent = helper.name;
                                option2.selected = helper.id == trip.helper2;
                                helper2Select.appendChild(option2);
                            });
                        });

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









        function loadDropdownOptions(tripId) {
    document.getElementById("loading-screen").style.display = "flex";
    
    return Promise.all([
        fetch(`get_dropdown_data.php?type=trucks&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const truckDropdown = document.getElementById('update_truck_id');
                truckDropdown.innerHTML = '<option value="">Select Truck</option>';
                
                if (data.success) {
                    data.data.forEach(truck => {
                        truckDropdown.innerHTML += `<option value="${truck.truck_id}">${truck.model} - ${truck.truck_plate} (${truck.truck_type})</option>`;
                    });
                }
            }),
        
        fetch(`get_dropdown_data.php?type=drivers&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const driverDropdown = document.getElementById('update_driver');
                driverDropdown.innerHTML = '<option value="">Select Driver</option>';
                
                if (data.success) {
                    data.data.forEach(driver => {
                        driverDropdown.innerHTML += `<option value="${driver.driver_id}">${driver.fullname}</option>`;
                    });
                }
            }),
        
        fetch(`get_dropdown_data.php?type=helper1&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const helper1Dropdown = document.getElementById('update_helper1');
                helper1Dropdown.innerHTML = '<option value="">Select Helper 1</option>';
                
                if (data.success) {
                    data.data.forEach(helper => {
                        helper1Dropdown.innerHTML += `<option value="${helper.helper1_id}">${helper.fullname}</option>`;
                    });
                }
            }),
        
        fetch(`get_dropdown_data.php?type=helper2&trip_id=${tripId}`)
            .then(response => response.json())
            .then(data => {
                const helper2Dropdown = document.getElementById('update_helper2');
                helper2Dropdown.innerHTML = '<option value="">Select Helper 2</option>';
                
                if (data.success) {
                    data.data.forEach(helper => {
                        helper2Dropdown.innerHTML += `<option value="${helper.helper2_id}">${helper.fullname}</option>`;
                    });
                }
            })
    ]).then(() => {
        document.getElementById("loading-screen").style.display = "none";
    });
}

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

        // Odometer calculation
        const odoOutInput = document.getElementById('odo_out');
        const odoInInput = document.getElementById('odo_in');
        const odoTotalInput = document.getElementById('odo_total');

        function calculateTotalMileage() {
            const odoOut = parseFloat(odoOutInput.value) || 0;
            const odoIn = parseFloat(odoInInput.value) || 0;
            
            if (odoIn >= odoOut) {
                odoTotalInput.value = odoIn - odoOut;
            } else {
                odoTotalInput.value = 0;
                alert('Odometer In reading must be greater than Odometer Out reading');
            }
        }

        odoOutInput.addEventListener('input', calculateTotalMileage);
        odoInInput.addEventListener('input', calculateTotalMileage);

        // Sidebar toggle functionality
        const mobileToggle = document.querySelector('.mobile-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');
        const mainContent = document.querySelector('.main-content');
        
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        function checkMobile() {
            if (window.innerWidth <= 768) {
                mainContent.style.marginLeft = '0';
                sidebar.classList.remove('active');
            } else {
                mainContent.style.marginLeft = '-20px';
            }
        }
        
        checkMobile();
        window.addEventListener('resize', checkMobile);
        
        // Navigation functionality
        document.querySelectorAll('.update-btn').forEach(button => {
    button.addEventListener('click', function() {
        const csId = this.getAttribute('data-id');
        const topsheet = this.getAttribute('data-topsheet');
        
        // Update topsheet display immediately
        const topsheetValue = document.getElementById('topsheet-value');
        if (topsheetValue) {
            topsheetValue.textContent = topsheet;
        }
        
        // Load dropdown options first
        loadDropdownOptions(csId).then(() => {
            // Then fetch trip details
            document.getElementById("loading-screen").style.display = "flex";
            
            fetch(`get_trip_details.php?cs_id=${csId}`)
            .then(response => response.json())
            .then(trip => {
                document.getElementById("loading-screen").style.display = "none";
                
                // Set form values
                const setValue = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.value = value || '';
                };
                
                setValue('update_id', trip.cs_id);
                setValue('update_waybill', trip.waybill);
                setValue('update_date', trip.date);
                setValue('update_status', trip.status);
                setValue('update_delivery_type', trip.delivery_type);
                setValue('update_amount', trip.amount);
                setValue('update_source', trip.source);
                setValue('update_pickup', trip.pickup);
                setValue('update_dropoff', trip.dropoff);
                setValue('update_rate', trip.rate);
                setValue('update_call_time', trip.call_time);
                
                // Set dropdown values if they exist
                if (trip.truck_id) setValue('update_truck_id', trip.truck_id);
                if (trip.driver) setValue('update_driver', trip.driver);
                if (trip.helper1) setValue('update_helper1', trip.helper1);
                if (trip.helper2) setValue('update_helper2', trip.helper2);
                
                // Update budget fields
                if (trip.budget) {
                    setValue('fuelfee', trip.budget.fuelfee);
                    setValue('tollfee', trip.budget.tollfee);
                    setValue('parkingfee', trip.budget.parkingfee);
                    setValue('rorofarefee', trip.budget.rorofarefee);
                    setValue('budgetrelease', trip.budget.budgetrelease);
                }
                
                document.getElementById('updateTripModal').style.display = 'flex';
            });
        });
    });
});
        
        // Logout functionality
        const logoutLink = document.getElementById('logout-link');
        
        if (logoutLink) {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                const logoutButton = document.querySelector('.logout-section');
                logoutButton.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                document.getElementById("loading-screen").style.display = "flex";
                setTimeout(() => {
                    logoutButton.style.backgroundColor = '';
                    window.location.href = this.getAttribute('href');
                }, 2000);
            });
        }
        
        // Animation for pie chart
        const pieSlice = document.querySelector('.pie-slice');
        setTimeout(() => {
            pieSlice.style.transition = 'transform 1s ease-out';
            pieSlice.style.transform = 'rotate(135deg)';
        }, 500);
        
        // Animation for bars
        const bars = document.querySelectorAll('.bar');
        bars.forEach((bar, index) => {
            const heights = ['30%', '70%', '50%'];
            bar.style.height = '0';
            setTimeout(() => {
                bar.style.transition = 'height 1s ease-out';
                bar.style.height = heights[index % 3];
            }, 300 + (index * 100));
        });
    });
    </script>
</body>
</html>