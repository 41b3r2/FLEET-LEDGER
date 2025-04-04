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

require_once 'fetch_pending.php';
$trips = getPendingTrips();

$topsheets = [];
foreach ($trips as $trip) {
    $ts_id = $trip['ts_id'] ? $trip['ts_id'] : "No topsheet";
    if (!isset($topsheets[$ts_id])) {
        $topsheets[$ts_id] = 1;
    } else {
        $topsheets[$ts_id]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - DISPATCH</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/waybill.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #updateTripModal {
            display: none;
        }
        
        /* Filter styles */
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
        
        /* Modal for view sheet */
        .wide-modal {
            width: 95%;
            height: 70%;
            max-width: 1900px;
        }
        
        #viewSheetModal {
            display: none;
        }
        
        /* Highlight ready for budgeting rows */
        .ready-row {
            background-color: rgba(236, 255, 224, 0.3);
        }
        
        /* Make the monitoring button more visible */
        .monitoring-btn {
            background-color: rgb(175, 76, 76);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .monitoring-btn:hover {
            background-color: rgb(160, 69, 69);
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
                    <h2>DISPATCHER</h2>
                </div>
                
                <!-- Filter controls -->
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
                            <option value="Pending">Pending</option>
                            <option value="Dispatched">Dispatched</option>
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
                                    echo "<tr class='trip-row' data-id='" . $trip['cs_id'] . "' data-topsheet='" . ($trip['ts_id'] ? htmlspecialchars($trip['ts_id']) : "No topsheet") . "' data-status='" . htmlspecialchars($trip['situation']) . "'>";
                                    echo "<td>" . htmlspecialchars($trip['waybill']) . "</td>";
                                    echo "<td>" . date("F j, Y", strtotime($trip['date'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($trip['status']) . "</td>";
                                    echo "<td>" . htmlspecialchars($trip['delivery_type']) . "</td>";
                                    echo "<td>₱ " . (isset($trip['amount']) ? htmlspecialchars($trip['amount']) : '') . "</td>";
                                    echo "<td>" . htmlspecialchars($trip['source']) . "</td>";
                                    echo "<td>" . htmlspecialchars($trip['pickup']) . "</td>";
                                    echo "<td>" . htmlspecialchars($trip['dropoff']) . "</td>";
                                    echo "<td>" . htmlspecialchars($trip['rate']) . "</td>";
                                    echo "<td>" . date("h:i A", strtotime($trip['call_time'])) . "</td>";
                                    echo "<td style='color: red; font-weight: bold;'>" . htmlspecialchars($trip['situation']) . "</td>";
                                    echo "<td><button class='update-btn' data-id='" . $trip['cs_id'] . "'>View | Update</button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='12'>No pending trips found</td></tr>";
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
                
                <div class="monitoring-section">
                    <button class="monitoring-btn">Ready For Budgeting</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Sheet Modal -->
    <div class="modal-overlay" id="viewSheetModal">
        <div class="modal-container wide-modal">
            <div class="modal-header">
                <h3>Ready for Budgeting</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
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
                                <th>Truck Type</th>
                                <th>Driver</th>
                                <th>Helper 1</th>
                                <th>Helper 2</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="readyForBudgetingTableBody">
                            <!-- Data will be loaded here dynamically -->
                        </tbody>
                    </table>
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
                <form id="updateTripForm" method="post">
                    <input type="hidden" id="update_id" name="cs_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_topsheet">Topsheet No:</label>
                            <input type="text" id="update_topsheet" name="topsheet" readonly>
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
                            <select id="update_truck_id" name="truck_id">
                                <option value="">Select Truck</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_driver">Driver:</label>
                            <select id="update_driver" name="driver">
                                <option value="">Select Driver</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_helper1">Helper 1:</label>
                            <select id="update_helper1" name="helper1">
                                <option value="">Select Helper 1</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_helper2">Helper 2:</label>
                            <select id="update_helper2" name="helper2">
                                <option value="">Select Helper 2</option>
                            </select>
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

    const viewSheetBtn = document.querySelector('.monitoring-btn');
    const viewSheetModal = document.getElementById('viewSheetModal');
    
    if (viewSheetBtn && viewSheetModal) {
        viewSheetBtn.addEventListener('click', function() {
            document.getElementById("loading-screen").style.display = "flex";
            
            fetch('get_ready_for_budgeting.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById("loading-screen").style.display = "none";
                    
                    const tableBody = document.getElementById('readyForBudgetingTableBody');
                    tableBody.innerHTML = '';
                    
                    if (data && data.length > 0) {
                        data.forEach(trip => {
                            const row = document.createElement('tr');
                            row.className = 'ready-row';
                            row.setAttribute('data-id', trip.cs_id);
                            
                            row.innerHTML = `
                                <td>${trip.waybill || ''}</td>
                                <td>${formatDate(trip.date)}</td>
                                <td>${trip.status || ''}</td>
                                <td>${trip.delivery_type || ''}</td>
                                <td>₱ ${trip.amount || ''}</td>
                                <td>${trip.source || ''}</td>
                                <td>${trip.pickup || ''}</td>
                                <td>${trip.dropoff || ''}</td>
                                <td>${trip.rate || ''}</td>
                                <td>${formatTime(trip.call_time)}</td>
                                <td style="background-color:rgba(236, 255, 224, 0.62);">${trip.truck_details || ''}</td>
                                <td style="background-color:rgba(236, 255, 224, 0.62);">${trip.driver_name || ''}</td>
                                <td style="background-color:rgba(236, 255, 224, 0.62);">${trip.helper1_name || ''}</td>
                                <td style="background-color:rgba(236, 255, 224, 0.62);">${trip.helper2_name || ''}</td>
                                <td><button class="update-btn" data-id="${trip.cs_id}">View | Update</button></td>
                            `;
                            
                            tableBody.appendChild(row);
                        });
                    } else {
                        tableBody.innerHTML = '<tr><td colspan="15">No trips ready for budgeting found</td></tr>';
                    }
                    
                    viewSheetModal.style.display = 'flex';
                })
                .catch(error => {
                    document.getElementById("loading-screen").style.display = "none";
                    console.error('Error:', error);
                    const tableBody = document.getElementById('readyForBudgetingTableBody');
                    tableBody.innerHTML = `<tr><td colspan="15">Error loading trips: ${error.message}</td></tr>`;
                    viewSheetModal.style.display = 'flex';
                });
        });
        
        const closeBtn = viewSheetModal.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                viewSheetModal.style.display = 'none';
            });
        }
        
        window.addEventListener('click', function(event) {
            if (event.target === viewSheetModal) {
                viewSheetModal.style.display = 'none';
            }
        });
    }
    
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    function formatTime(timeString) {
        if (!timeString) return '';
        const time = new Date(`2000-01-01T${timeString}`);
        return time.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
    }

    const updateModal = document.getElementById('updateTripModal');
    if (updateModal) {
        updateModal.style.display = 'none';
        
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
        
        const updateForm = document.getElementById('updateTripForm');
        if (updateForm) {
            const updateCloseBtn = updateModal.querySelector('.close-modal');
            const updateCancelBtn = updateModal.querySelector('.cancel-btn');
            
            function loadDropdownOptions(tripId = 0) {
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
            
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('update-btn')) {
                    const tripId = e.target.getAttribute('data-id');
                    loadDropdownOptions(tripId).then(() => {
                        fetchTripDetails(tripId);
                    });
                }
            });
            
            function fetchTripDetails(id) {
                document.getElementById("loading-screen").style.display = "flex";
                
                fetch('update_trip.php?cs_id=' + id)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById("loading-screen").style.display = "none";
                        
                        if (data.success) {
                            document.getElementById('update_id').value = data.data.cs_id;
                            document.getElementById('update_topsheet').value = data.data.ts_id;
                            document.getElementById('update_waybill').value = data.data.waybill;
                            document.getElementById('update_date').value = data.data.date;
                            document.getElementById('update_status').value = data.data.status;
                            document.getElementById('update_delivery_type').value = data.data.delivery_type;
                            document.getElementById('update_amount').value = data.data.amount;
                            document.getElementById('update_source').value = data.data.source;
                            document.getElementById('update_pickup').value = data.data.pickup;
                            document.getElementById('update_dropoff').value = data.data.dropoff;
                            document.getElementById('update_rate').value = data.data.rate;
                            document.getElementById('update_call_time').value = data.data.call_time;
                            
                            if (data.data.truck_id) {
                                document.getElementById('update_truck_id').value = data.data.truck_id;
                            }
                            
                            if (data.data.driver) {
                                document.getElementById('update_driver').value = data.data.driver;
                            }
                            
                            if (data.data.helper1) {
                                document.getElementById('update_helper1').value = data.data.helper1;
                            }
                            
                            if (data.data.helper2) {
                                document.getElementById('update_helper2').value = data.data.helper2;
                            }
                            
                            updateModal.style.display = 'flex';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        document.getElementById("loading-screen").style.display = "none";
                        alert('An error occurred: ' + error.message);
                    });
            }
            
            if (updateCloseBtn) {
                updateCloseBtn.addEventListener('click', function() {
                    updateModal.style.display = 'none';
                });
            }
            
            if (updateCancelBtn) {
                updateCancelBtn.addEventListener('click', function() {
                    updateModal.style.display = 'none';
                    updateForm.reset();
                });
            }
            
            window.addEventListener('click', function(event) {
                if (event.target === updateModal) {
                    updateModal.style.display = 'none';
                }
            });
            
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                document.getElementById("loading-screen").style.display = "flex";
                
                const formData = new FormData(updateForm);
                
                fetch('update_trip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    document.getElementById("loading-screen").style.display = "none";
                    
                    if (data.success) {
                        alert('Trip updated successfully!');
                        updateModal.style.display = 'none';
                        updateForm.reset();
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    document.getElementById("loading-screen").style.display = "none";
                    alert('An error occurred: ' + error.message);
                });
            });
        }
    }
});
    </script>
</body>
</html>