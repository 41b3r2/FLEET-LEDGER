<?php
session_start();

require_once 'connection.php';

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

$query = "SELECT * FROM driver";
$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - DRIVERS</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/landingPage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="assets/js/landingPage.js"></script>
    <style>
        /* Table specific styles */
        .driver-table-container {
            margin: 20px;
            width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            background-color: #fff;
        }
        
        .table-header {
            background-color:rgba(106, 0, 11, 0.79);
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .driver-table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
        }
        
        .driver-table th {
            background-color: maroon;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 12px 15px;
        }
        
        .driver-table td {
            padding: 10px 15px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .driver-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .driver-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
        }
        
        .action-btn {
            margin: 2px;
        }
        
        .table-responsive {
            max-height: 900px; /* Adjust height as needed */
            overflow-y: auto;
            border-radius: 0px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .driver-table thead {
            position: sticky;
            top: 0;
            background-color: maroon;
            color: white;
            z-index: 10;
        }

        .driver-table th {
            padding: 12px;
            border-bottom: 2px solid #ddd;
        }

        /* Make the table responsive */
        @media screen and (max-width: 768px) {
            .driver-table {
                display: block;
                overflow-x: auto;
            }
            
            .driver-table-container {
                width: 95%;
            }
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
        
        <div class="driver-table-container">
            <div class="table-header">
                Driver Information
            </div>
            <div class="table-responsive">
                <table class="driver-table">
                    <thead>
                        <tr>
                            <th>Driver ID</th>
                            <th>Full Name</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['driver_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                              <button type="button" class="btn btn-success editbtn"><i class = "fas fa-edit"></i> Edit</button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class ="modal fade" id="editmodal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" area-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <form action="update_driver.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalLabel">Edit Driver Information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="driver_id" id="driver_id">
                        
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" name="fullname" id="fullname" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="contact">Contact</label>
                            <input type="number" name="contact" id="contact" class="form-control" 
                                pattern="^09\d{9}$" 
                                title="Please enter a number starting with 09 and exactly 11 digits" 
                                required>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <input type="text" name="status" id="status" class="form-control">
                        </div>
                    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" name="updatedata">Save Changes</button>
                </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>

$(document).ready(function(){
    $('#contact').on('input', function() {
        // Remove any non-digit characters
        var value = $(this).val().replace(/\D/g, '');
        
        // Ensure it starts with 09
        if (!value.startsWith('09')) {
            value = '09' + value.replace(/^0*/, '');
        }
        
        // Limit to 11 digits
        value = value.substring(0, 11);
        
        $(this).val(value);
    });

    $('form').on('submit', function(e) {
        var contact = $('#contact').val();
        
        // Validate contact number
        if (!/^09\d{9}$/.test(contact)) {
            e.preventDefault();
            alert('Please enter a valid contact number starting with 09 and exactly 11 digits');
            $('#contact').focus();
        }
    });
});
        $(document).ready(function(){
            $('.editbtn').on('click', function(){
                $('#editmodal').modal('show');

                $tr = $(this).closest('tr');
                var data = $tr.children("td").map(function(){
                    return $(this).text();
                }).get();
                console.log(data);

                $('#driver_id').val(data[0]);
                $('#fullname').val(data[1]);
                $('#contact').val(data[2]);
                $('#status').val(data[3]);


            });
        });
    </script>                        

</body>
</html>