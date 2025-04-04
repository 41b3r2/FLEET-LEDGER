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

require_once 'fetch_ready_for_budget.php';
$trips = getPendingTrips();

$topsheets = [];
foreach ($trips as $trip) {
    $ts_id = $trip['ts_id'] ? $trip['ts_id'] : "No topsheet";
    if (!isset($topsheets[$ts_id])) {
        $topsheets[$ts_id] = 0;
    }
    $topsheets[$ts_id]++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - BUDGET</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/waybill.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-control label {
            font-weight: bold;
            white-space: nowrap;
        }
        
        select.filter-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 200px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-ready {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-budgeted {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .budget-summary {
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
        
        .trip-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .trip-table th {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .trip-table tr.group-header {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        
        .trip-table tr.group-header td {
            padding: 10px 8px;
        }
        
        .trip-table tr.hidden {
            display: none;
        }
        
        .toggle-group {
            cursor: pointer;
            color: #1976d2;
        }
        
        .no-topsheet {
            font-style: italic;
            color: #757575;
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
                    <h2>BUDGETING</h2>
                </div>
                
                <div class="budget-summary">
                    <div class="summary-item">
                        <span>Total Trips:</span>
                        <span class="summary-value"><?php echo count($trips); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Ready for Budget:</span>
                        <span class="summary-value"><?php echo count(array_filter($trips, fn($t) => $t['situation'] == 'Ready for budgeting')); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Budgeted:</span>
                        <span class="summary-value"><?php echo count(array_filter($trips, fn($t) => $t['situation'] == 'Budgeted')); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Unique Topsheets:</span>
                        <span class="summary-value"><?php echo count($topsheets); ?></span>
                    </div>
                </div>
                
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
                    
                    <div class="filter-control">
                        <label for="status-filter"><i class="fas fa-filter"></i> Filter by Status:</label>
                        <select id="status-filter" class="filter-select">
                            <option value="all">All Statuses</option>
                            <option value="Ready for budgeting">Ready for budgeting</option>
                            <option value="Budgeted">Budgeted</option>
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
                            <?php foreach ($trips as $trip): ?>
                                <tr class="trip-row <?php echo $trip['situation'] == 'Budgeted' ? 'budgeted-row' : ''; ?>" 
                                    data-id="<?php echo $trip['cs_id']; ?>"
                                    data-topsheet="<?php echo htmlspecialchars($trip['ts_id'] ? $trip['ts_id'] : "No topsheet"); ?>"
                                    data-status="<?php echo htmlspecialchars($trip['situation']); ?>">
                                    <td><?php echo htmlspecialchars($trip['waybill']); ?></td>
                                    <td><?php echo date("F j, Y", strtotime($trip['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($trip['status']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['delivery_type']); ?></td>
                                    <td>₱ <?php echo isset($trip['amount']) ? number_format($trip['amount'], 2) : ''; ?></td>
                                    <td><?php echo htmlspecialchars($trip['source']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['pickup']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['dropoff']); ?></td>
                                    <td><?php echo htmlspecialchars($trip['rate']); ?></td>
                                    <td><?php echo date("h:i A", strtotime($trip['call_time'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $trip['situation'] == 'Budgeted' ? 'status-budgeted' : 'status-ready'; ?>">
                                            <?php echo htmlspecialchars($trip['situation']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="update-btn" 
                                                data-id="<?php echo $trip['cs_id']; ?>" 
                                                data-topsheet="<?php echo htmlspecialchars($trip['ts_id'] ? $trip['ts_id'] : "No topsheet"); ?>">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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

    <div class="modal-overlay" id="updateTripModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Update Trip</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="topsheet-display" id="topsheet-display">
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
                            <label style="color: maroon; font-weight: bold;">Insert Budget:</label>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fuelfee">Fuel Fee:</label>
                            <input type="number" id="fuelfee" name="fuelfee" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="tollfee">Toll Fee:</label>
                            <input type="number" id="tollfee" name="tollfee" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="parkingfee">Parking Fee:</label>
                            <input type="number" id="parkingfee" name="parkingfee" step="0.01">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rorofarefee">Roro Fare:</label>
                            <input type="number" id="rorofarefee" name="rorofarefee" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="budgetrelease">Total Budget Release:</label>
                            <input type="number" id="budgetrelease" name="budgetrelease" readonly step="0.01">
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
        const topsheetFilter = document.getElementById('topsheet-filter');
        const statusFilter = document.getElementById('status-filter');
        const resetFilters = document.getElementById('reset-filters');
        
        function applyFilters() {
            const selectedTopsheet = topsheetFilter.value;
            const selectedStatus = statusFilter.value;
            
            document.querySelectorAll('.trip-row').forEach(row => {
                const rowTopsheet = row.dataset.topsheet;
                const rowStatus = row.dataset.status;
                
                const matchesTopsheet = selectedTopsheet === 'all' || rowTopsheet === selectedTopsheet;
                const matchesStatus = selectedStatus === 'all' || rowStatus === selectedStatus;
                
                row.style.display = (matchesTopsheet && matchesStatus) ? '' : 'none';
            });
        }
        
        topsheetFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
        
        resetFilters.addEventListener('click', function() {
            topsheetFilter.value = 'all';
            statusFilter.value = 'all';
            applyFilters();
        });
        
        applyFilters();
        
        function calculateBudgetRelease() {
            const fuelfee = parseFloat(document.getElementById('fuelfee').value) || 0;
            const tollfee = parseFloat(document.getElementById('tollfee').value) || 0;
            const parkingfee = parseFloat(document.getElementById('parkingfee').value) || 0;
            const rorofarefee = parseFloat(document.getElementById('rorofarefee').value) || 0;
            
            const total = fuelfee + tollfee + parkingfee + rorofarefee;
            document.getElementById('budgetrelease').value = total.toFixed(2);
        }
        
        ['fuelfee', 'tollfee', 'parkingfee', 'rorofarefee'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', calculateBudgetRelease);
            }
        });
        
        const updateTripForm = document.getElementById('updateTripForm');
        if (updateTripForm) {
            updateTripForm.addEventListener('submit', function(e) {
                e.preventDefault();
                document.getElementById("loading-screen").style.display = "flex";
                
                const formData = new FormData(updateTripForm);
                
                fetch('insert_budget.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("loading-screen").style.display = "none";
                    if (data.success) {
                        alert('Budget details saved successfully!');
                        updateTripModal.style.display = 'none';
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById("loading-screen").style.display = "none";
                    console.error('Error:', error);
                    alert('An error occurred while submitting budget details.');
                });
            });
        }
        
        document.querySelectorAll('.update-btn').forEach(button => {
        button.addEventListener('click', function() {
        const csId = this.getAttribute('data-id');
        const topsheet = this.getAttribute('data-topsheet');
        
        const topsheetValue = document.getElementById('topsheet-value');
        if (topsheetValue) {
            topsheetValue.textContent = topsheet;
        }
        
        loadDropdownOptions(csId).then(() => {
            document.getElementById("loading-screen").style.display = "flex";
            
            fetch(`get_trip_details.php?cs_id=${csId}`)
            .then(response => response.json())
            .then(trip => {
                document.getElementById("loading-screen").style.display = "none";
                
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
                
                if (trip.truck_id) setValue('update_truck_id', trip.truck_id);
                if (trip.driver) setValue('update_driver', trip.driver);
                if (trip.helper1) setValue('update_helper1', trip.helper1);
                if (trip.helper2) setValue('update_helper2', trip.helper2);
                
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
        

    document.querySelectorAll('.close-modal, .cancel-btn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('updateTripModal').style.display = 'none';
            });
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('updateTripModal')) {
                document.getElementById('updateTripModal').style.display = 'none';
            }
        });

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
        
        document.querySelectorAll('.metric-section').forEach(section => {
            section.addEventListener('click', function(event) {
                event.preventDefault();
                let link = this.getAttribute('data-href');
                if (link) {
                    document.getElementById("loading-screen").style.display = "flex";
                    setTimeout(() => {
                        window.location.href = link;
                    }, 2000);
                }
            });
        });
        
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
        
        const pieSlice = document.querySelector('.pie-slice');
        setTimeout(() => {
            pieSlice.style.transition = 'transform 1s ease-out';
            pieSlice.style.transform = 'rotate(135deg)';
        }, 500);
        
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