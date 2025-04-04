<?php
// waybill.php
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

require_once 'fetch_trips.php';
$data = getPendingTrips();
$topsheets = $data['topsheets'];

// Prepare data for filtering
$topsheetData = [];
foreach ($topsheets as $ts) {
    $topsheetData[$ts['ts_id']] = [
        'waybill_count' => $ts['waybill_count'],
        'sources' => $ts['sources'],
        'date' => date("F j, Y", strtotime($ts['first_date']))
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - WAYBILL</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/waybill.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        
        .trip-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-primary {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-success {
            background-color: #e8f5e9;
            color: #2e7d32;
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
                    <h2>List of Pending Trip</h2>
                    <button class="add-trip-btn">+ Add Trip</button>
                </div>

                <div class="summary-card">
                    <div class="summary-item">
                        <span>Total Topsheets:</span>
                        <span class="summary-value"><?php echo count($topsheets); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Total Waybills:</span>
                        <span class="summary-value"><?php echo array_sum(array_column($topsheets, 'waybill_count')); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Unique Sources:</span>
                        <span class="summary-value"><?php echo count(array_unique(array_column($topsheets, 'sources'))); ?></span>
                    </div>
                </div>

                <div class="filter-section">
                    <div class="filter-control">
                        <label for="topsheet-filter"><i class="fas fa-filter"></i> Filter by Topsheet:</label>
                        <select id="topsheet-filter" class="filter-select">
                            <option value="all">All Topsheets</option>
                            <?php foreach ($topsheets as $ts): ?>
                                <option value="<?php echo htmlspecialchars($ts['ts_id']); ?>">
                                    <?php echo htmlspecialchars($ts['ts_id']); ?> 
                                    (<?php echo $ts['waybill_count']; ?> waybills)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-control">
                        <label for="source-filter"><i class="fas fa-filter"></i> Filter by Source:</label>
                        <select id="source-filter" class="filter-select">
                            <option value="all">All Sources</option>
                            <?php 
                            $uniqueSources = array_unique(array_column($topsheets, 'sources'));
                            foreach ($uniqueSources as $source): ?>
                                <option value="<?php echo htmlspecialchars($source); ?>">
                                    <?php echo htmlspecialchars($source); ?>
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
                                <th>Topsheet No.</th>
                                <th>Date</th>
                                <th>Waybills</th>
                                <th>Source</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tripTableBody">
                            <?php if (count($topsheets) > 0): ?>
                                <?php foreach ($topsheets as $ts): ?>
                                    <tr class="trip-row" 
                                        data-ts_id="<?php echo htmlspecialchars($ts['ts_id']); ?>"
                                        data-source="<?php echo htmlspecialchars($ts['sources']); ?>">
                                        <td><?php echo htmlspecialchars($ts['ts_id']); ?></td>
                                        <td><?php echo date("F j, Y", strtotime($ts['first_date'])); ?></td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo $ts['waybill_count']; ?> waybills
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($ts['sources']); ?></td>
                                        <td>
                                            <button class="view-waybills-btn submit-btn" data-ts_id="<?php echo $ts['ts_id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7">No topsheets found</td></tr>
                            <?php endif; ?>
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

    <!-- Modals remain the same as in your original code -->
    <!-- waybillsModal -->
    <div class="modal-overlay" id="waybillsModal" style="display: none;">
        <div class="modal-container" style="width: 90%; max-width: 1200px;">
            <div class="modal-header">
                <h3>Waybills for Topsheet: <span id="currentTopsheet"></span></h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="trip-header" style="margin-bottom: 15px;">
                    <button class="add-waybill-btn submit-btn">+ Add Waybill</button>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="waybillsTableBody">
                            <!-- Waybills will be loaded here dynamically -->
                        </tbody>
                    </table>
                </div>
                <div class="form-row" style="margin-top: 20px;">
                    <div class="form-group" style="flex: 2"></div>
                    <div class="form-group" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" class="close-btn">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- addWaybillModal -->
    <div class="modal-overlay" id="addWaybillModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Add Waybill to Topsheet: <span id="addWaybillTopsheet"></span></h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addWaybillForm" method="post">
                    <input type="hidden" id="existing_topsheet" name="existing_topsheet">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="waybill">Waybill No:</label>
                            <input type="number" id="waybill" name="waybill" required>
                        </div>
                        <div class="form-group">
                            <label for="date">Date:</label>
                            <input type="date" id="date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label for="status">FO/PO/STO:</label>
                            <select id="status" name="status" required>
                                <option value="">Select Type</option>
                                <option value="Freight Order">Freight Order</option>
                                <option value="Purchase Order">Purchase Order</option>
                                <option value="Stock Transfer Order">Stock Transfer Order</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="delivery_type">Delivery Type:</label>
                            <input type="text" id="delivery_type" name="delivery_type" required>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount:</label>
                            <input type="number" id="amount" name="amount" required>
                        </div>
                        <div class="form-group">
                            <label for="source">Source:</label>
                            <select id="source" name="source" required>
                                <option value="">-- Select Client --</option>
                                <option value="URC">URC</option>
                                <option value="3M HUSTLING">3M HUSTLING</option>
                                <option value="NESTLE">NESTLE</option>
                                <option value="JWSL">JWSL</option>
                                <option value="ULP-MDC">ULP-MDC</option>
                                <option value="DELFI">DELFI</option>
                                <option value="ZEST-O">ZEST-O</option>
                                <option value="DELFI / JWSL">DELFI / JWSL</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="pickup">Pick Up:</label>
                            <input type="text" id="pickup" name="pickup" required>
                        </div>
                        <div class="form-group">
                            <label for="dropoff">Drop Off:</label>
                            <input type="text" id="dropoff" name="dropoff" required>
                        </div>
                        <div class="form-group">
                            <label for="rate">Rate:</label>
                            <input type="text" id="rate" name="rate" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="call_time">Call Time:</label>
                            <input type="time" id="call_time" name="call_time" required>
                        </div>
                        <div class="form-group" style="visibility: hidden;">
                            <label>Placeholder</label>
                            <input type="hidden">
                        </div>
                        <div class="form-group" style="visibility: hidden;">
                            <label>Placeholder</label>
                            <input type="hidden">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2"></div>
                        <div class="form-group" style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="submit" class="submit-btn">Add Waybill</button>
                            <button type="button" class="cancel-btn">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- updateTripModal -->
    <div class="modal-overlay" id="updateTripModal" style="display: none;">
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
                            <label for="update_ts_id">Topsheet No:</label>
                            <input type="text" id="update_ts_id" name="ts_id" readonly class="disabled-input">
                        </div>
                        <div class="form-group">
                            <label for="update_waybill">Waybill No:</label>
                            <input type="number" id="update_waybill" name="waybill" required>
                        </div>
                        <div class="form-group">
                            <label for="update_date">Date:</label>
                            <input type="date" id="update_date" name="date" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_status">FO/PO/STO:</label>
                            <select id="update_status" name="status" required>
                                <option value="">Select Type</option>
                                <option value="Freight Order">Freight Order</option>
                                <option value="Purchase Order">Purchase Order</option>
                                <option value="Stock Transfer Order">Stock Transfer Order</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_delivery_type">Delivery Type:</label>
                            <input type="text" id="update_delivery_type" name="delivery_type" required>
                        </div>
                        <div class="form-group">
                            <label for="update_amount">Amount:</label>
                            <input type="number" id="update_amount" name="amount" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_source">Source:</label>
                            <select id="update_source" name="source" required>
                                <option value="">-- Select Client --</option>
                                <option value="URC">URC</option>
                                <option value="3M HUSTLING">3M HUSTLING</option>
                                <option value="NESTLE">NESTLE</option>
                                <option value="JWSL">JWSL</option>
                                <option value="ULP-MDC">ULP-MDC</option>
                                <option value="DELFI">DELFI</option>
                                <option value="ZEST-O">ZEST-O</option>
                                <option value="DELFI / JWSL">DELFI / JWSL</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_pickup">Pick Up:</label>
                            <input type="text" id="update_pickup" name="pickup" required>
                        </div>
                        <div class="form-group">
                            <label for="update_dropoff">Drop Off:</label>
                            <input type="text" id="update_dropoff" name="dropoff" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_rate">Rate:</label>
                            <input type="text" id="update_rate" name="rate" required>
                        </div>
                        <div class="form-group">
                            <label for="update_call_time">Call Time:</label>
                            <input type="time" id="update_call_time" name="call_time" required>
                        </div>
                        <div class="form-group">
                            <label style="visibility: hidden;">Placeholder</label>
                            <input type="hidden">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2"></div>
                        <div class="form-group" style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="submit" class="submit-btn">Update Trip</button>
                            <button type="button" class="delete-btn" id="deleteTrip">Delete</button>
                            <button type="button" class="cancel-btn">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- addTripModal -->
    <div class="modal-overlay" id="addTripModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Add Trip</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addTripForm" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Topsheet No: <span class="auto-generate-text">(Auto-generated)</span></label>
                            <input type="text" disabled placeholder="TS-XXXXX" class="disabled-input">
                        </div>
                        <div class="form-group">
                            <label for="waybill">Waybill No:</label>
                            <input type="number" id="waybill" name="waybill" required>
                        </div>
                        <div class="form-group">
                            <label for="date">Date:</label>
                            <input type="date" id="date" name="date" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">FO/PO/STO:</label>
                            <select id="status" name="status" required>
                                <option value="">Select Type</option>
                                <option value="Freight Order">Freight Order</option>
                                <option value="Purchase Order">Purchase Order</option>
                                <option value="Stock Transfer Order">Stock Transfer Order</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="delivery_type">Delivery Type:</label>
                            <input type="text" id="delivery_type" name="delivery_type" required>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount:</label>
                            <input type="number" id="amount" name="amount" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="source">Source:</label>
                            <select id="source" name="source" required>
                                <option value="">-- Select Client --</option>
                                <option value="URC">URC</option>
                                <option value="3M HUSTLING">3M HUSTLING</option>
                                <option value="NESTLE">NESTLE</option>
                                <option value="JWSL">JWSL</option>
                                <option value="ULP-MDC">ULP-MDC</option>
                                <option value="DELFI">DELFI</option>
                                <option value="ZEST-O">ZEST-O</option>
                                <option value="DELFI / JWSL">DELFI / JWSL</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pickup">Pick Up:</label>
                            <input type="text" id="pickup" name="pickup" required>
                        </div>
                        <div class="form-group">
                            <label for="dropoff">Drop Off:</label>
                            <input type="text" id="dropoff" name="dropoff" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="rate">Rate:</label>
                            <input type="text" id="rate" name="rate" required>
                        </div>
                        <div class="form-group">
                            <label for="call_time">Call Time:</label>
                            <input type="time" id="call_time" name="call_time" required>
                        </div>
                        <div class="form-group">
                            <label style="visibility: hidden;">Placeholder</label>
                            <input type="hidden">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2"></div>
                        <div class="form-group" style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="submit" class="submit-btn">Add Trip</button>
                            <button type="button" class="cancel-btn">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Filter functionality
        const topsheetFilter = document.getElementById('topsheet-filter');
        const sourceFilter = document.getElementById('source-filter');
        const resetFilters = document.getElementById('reset-filters');
        
        function applyFilters() {
            const selectedTopsheet = topsheetFilter.value;
            const selectedSource = sourceFilter.value;
            
            document.querySelectorAll('.trip-row').forEach(row => {
                const rowTopsheet = row.dataset.ts_id;
                const rowSource = row.dataset.source;
                
                const matchesTopsheet = selectedTopsheet === 'all' || rowTopsheet === selectedTopsheet;
                const matchesSource = selectedSource === 'all' || rowSource === selectedSource;
                
                row.style.display = (matchesTopsheet && matchesSource) ? '' : 'none';
            });
        }
        
        topsheetFilter.addEventListener('change', applyFilters);
        sourceFilter.addEventListener('change', applyFilters);
        
        resetFilters.addEventListener('click', function() {
            topsheetFilter.value = 'all';
            sourceFilter.value = 'all';
            applyFilters();
        });

        // Mobile toggle and sidebar functionality
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

        // Metric section clicks
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

        // Animation for sidebar metrics
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

        // Add trip modal
        document.querySelector('.add-trip-btn').addEventListener('click', function() {
            document.getElementById('addTripForm').reset();
            const topsheetInput = document.querySelector('#addTripModal .disabled-input');
            topsheetInput.placeholder = "TS-#####";
            document.getElementById('addTripModal').style.display = 'flex';
        });

        document.getElementById('addTripForm').addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById("loading-screen").style.display = "flex";
            const formData = new FormData(this);

            fetch('add_trip.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', text);
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                document.getElementById("loading-screen").style.display = "none";
                if (data.success) {
                    alert('Trip added successfully! Topsheet: ' + data.ts_id);
                    document.getElementById('addTripModal').style.display = 'none';
                    this.reset();
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                document.getElementById("loading-screen").style.display = "none";
                console.error('Error details:', error);
                alert('An error occurred: ' + error.message);
            });
        });

        // View waybills functionality
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('view-waybills-btn')) {
                const ts_id = e.target.getAttribute('data-ts_id');
                fetchWaybillsForTopsheet(ts_id);
            }
        });

        function fetchWaybillsForTopsheet(ts_id) {
            document.getElementById("loading-screen").style.display = "flex";
            
            fetch('fetch_trips.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById("loading-screen").style.display = "none";
                
                const waybills = data.trips.filter(trip => trip.ts_id === ts_id);
                document.getElementById('currentTopsheet').textContent = ts_id;
                
                const waybillsTableBody = document.getElementById('waybillsTableBody');
                waybillsTableBody.innerHTML = '';
                
                if (waybills.length > 0) {
                    waybills.forEach(waybill => {
                        const row = document.createElement('tr');
                        row.setAttribute('data-id', waybill.cs_id);
                        
                        row.innerHTML = `
                            <td>${waybill.waybill}</td>
                            <td>${formatDate(waybill.date)}</td>
                            <td>${waybill.status}</td>
                            <td>${waybill.delivery_type}</td>
                            <td>₱ ${waybill.amount || ''}</td>
                            <td>${waybill.source}</td>
                            <td>${waybill.pickup}</td>
                            <td>${waybill.dropoff}</td>
                            <td>${waybill.rate}</td>
                            <td>${formatTime(waybill.call_time)}</td>
                            <td><button class='update-btn' data-id='${waybill.cs_id}'>Update</button></td>
                        `;
                        
                        waybillsTableBody.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td colspan="11">No waybills found for this ts_id</td>`;
                    waybillsTableBody.appendChild(row);
                }
                
                document.getElementById('waybillsModal').style.display = 'flex';
            })
            .catch(error => {
                document.getElementById("loading-screen").style.display = "none";
                console.error('Error:', error);
                alert('An error occurred while fetching waybills');
            });
        }

        // Helper functions for date/time formatting
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }

        // Close modals
        document.querySelectorAll('.close-modal, .cancel-btn, .close-btn').forEach(button => {
            button.addEventListener('click', function() {
                const modal = this.closest('.modal-overlay');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });

        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        });

        // Add waybill functionality
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('add-waybill-btn')) {
                const ts_id = document.getElementById('currentTopsheet').textContent;
                document.getElementById('existing_topsheet').value = ts_id;
                document.getElementById('addWaybillTopsheet').textContent = ts_id;
                document.getElementById('addWaybillForm').reset();
                document.getElementById('addWaybillModal').style.display = 'flex';
            }
        });

        document.getElementById('addWaybillForm').addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById("loading-screen").style.display = "flex";
            const formData = new FormData(this);

            fetch('add_waybill_to_topsheet.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', text);
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                document.getElementById("loading-screen").style.display = "none";
                if (data.success) {
                    alert('Waybill added to ts_id successfully!');
                    document.getElementById('addWaybillModal').style.display = 'none';
                    this.reset();
                    
                    const ts_id = document.getElementById('currentTopsheet').textContent;
                    fetchWaybillsForTopsheet(ts_id);
                } else {
                    alert('Error: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                document.getElementById("loading-screen").style.display = "none";
                console.error('Error details:', error);
                alert('An error occurred: ' + error.message);
            });
        });

        // Update trip functionality
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('update-btn')) {
                const tripId = e.target.getAttribute('data-id');
                fetchTripDetails(tripId);
            }
        });

        function fetchTripDetails(id) {
            document.getElementById("loading-screen").style.display = "flex";

            fetch('update_trip.php?cs_id=' + id)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON response:', text);
                    throw new Error('Invalid server response');
                }
            })
            .then(data => {
                document.getElementById("loading-screen").style.display = "none";

                if (data.success) {
                    document.getElementById('update_id').value = data.data.cs_id;
                    document.getElementById('update_ts_id').value = data.data.ts_id || '';
                    document.getElementById('update_waybill').value = data.data.waybill || '';
                    document.getElementById('update_date').value = data.data.date || '';
                    document.getElementById('update_status').value = data.data.status || '';
                    document.getElementById('update_delivery_type').value = data.data.delivery_type || '';
                    document.getElementById('update_amount').value = data.data.amount || '';
                    document.getElementById('update_source').value = data.data.source || '';
                    document.getElementById('update_pickup').value = data.data.pickup || '';
                        document.getElementById('update_dropoff').value = data.data.dropoff || '';
                        document.getElementById('update_rate').value = data.data.rate || '';
                        document.getElementById('update_call_time').value = data.data.call_time || '';
                        
                        document.getElementById('updateTripModal').style.display = 'flex';
                    } else {
                        alert('Error: ' + (data.message || 'Failed to fetch trip details'));
                    }
                })
                .catch(error => {
                    document.getElementById("loading-screen").style.display = "none";
                    console.error('Error details:', error);
                    alert('An error occurred: ' + error.message);
                });
        }

        // Update Trip form submission
        document.getElementById('updateTripForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                document.getElementById("loading-screen").style.display = "flex";
                const formData = new FormData(this);
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000);
                
                const response = await fetch('update_trip.php', {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`Server responded with status ${response.status}: ${errorText}`);
                }
                
                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    throw new Error('Invalid JSON response from server');
                }
                
                if (!data.success) {
                    throw new Error(data.message || 'Update failed without error message');
                }
                
                alert('Trip updated successfully!');
                document.getElementById('updateTripModal').style.display = 'none';
                location.reload();
                
            } catch (error) {
                console.error('Update error:', error);
                if (error.name === 'AbortError') {
                    alert('Request timed out. Please check your connection and try again.');
                } else {
                    alert(`Update failed: ${error.message}`);
                }
            } finally {
                document.getElementById("loading-screen").style.display = "none";
            }
        });

        // Delete Trip functionality
        document.getElementById('deleteTrip').addEventListener('click', function() {
            const tripId = document.getElementById('update_id').value;
            
            if (confirm('Are you sure you want to delete this trip? This action cannot be undone.')) {
                fetch('delete_trip.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'cs_id=' + encodeURIComponent(tripId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Trip deleted successfully');
                        document.getElementById('updateTripModal').style.display = 'none';
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the trip');
                });
            }
        });
    });
    </script>
</body>
</html>