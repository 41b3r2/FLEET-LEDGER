<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Database connection
$host = "localhost"; // or your database host
$username = "root";  // or your database username
$password = "";      // or your database password
$database = "pcldb";   // your database name

// Create connection
$mysqli = new mysqli($host, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$u_id = $_SESSION["u_id"];
$emp_id = $_SESSION["emp_id"]; // Assuming emp_id is stored in session

// Define variables and initialize with empty values
$fullname = $email = $emp_num = "";
$fullname_err = $email_err = $emp_num_err = "";
$password = $new_password = $confirm_password = "";
$password_err = $new_password_err = $confirm_password_err = "";
$success_message = $error_message = "";

// Processing form data when form is submitted for profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
    
    // Validate fullname
    if (empty(trim($_POST["fullname"]))) {
        $fullname_err = "Please enter your full name.";
    } else {
        $fullname = trim($_POST["fullname"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        }
    }
    
    // Validate employee number
    if (empty(trim($_POST["emp_num"]))) {
        $emp_num_err = "Please enter your employee number.";
    } else {
        $emp_num = trim($_POST["emp_num"]);
    }
    
    // Check input errors before updating the database
    if (empty($fullname_err) && empty($email_err) && empty($emp_num_err)) {
        // Prepare an update statement
        $sql = "UPDATE employee SET fullname = ?, email = ?, emp_num = ? WHERE emp_id = ?";
        
        if ($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sssi", $param_fullname, $param_email, $param_emp_num, $param_emp_id);
            
            // Set parameters
            $param_fullname = $fullname;
            $param_email = $email;
            $param_emp_num = $emp_num;
            $param_emp_id = $emp_id;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Update session variables
                $_SESSION["fullname"] = $fullname;
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    
    // Validate current password
    if (empty(trim($_POST["current_password"]))) {
        $password_err = "Please enter your current password.";
    } else {
        $password = trim($_POST["current_password"]);
    }
    
    // Validate new password
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter the new password.";     
    } elseif (strlen(trim($_POST["new_password"])) < 6) {
        $new_password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before updating the database
    if (empty($password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Prepare a select statement to verify current password
        $sql = "SELECT password FROM employee WHERE emp_id = ?";
        
        if ($stmt = $mysqli->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("i", $param_emp_id);
            
            // Set parameters
            $param_emp_id = $emp_id;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if employee exists
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($stored_password);
                    if ($stmt->fetch()) {
                        // Check if the password is hashed (bcrypt starts with $2y$)
                        $passwordMatches = false;
                        
                        if (strpos($stored_password, '$2y$') === 0) {
                            // Verify using password_verify if it's a hashed password
                            $passwordMatches = password_verify($password, $stored_password);
                        } else {
                            // Direct comparison if it's not hashed
                            $passwordMatches = ($password === $stored_password);
                        }
                        
                        if ($passwordMatches) {
                            // Password is correct, update the new password
                            $sql = "UPDATE employee SET password = ? WHERE emp_id = ?";
                            
                            if ($stmt_update = $mysqli->prepare($sql)) {
                                // Bind variables to the prepared statement as parameters
                                $stmt_update->bind_param("si", $param_password, $param_emp_id);
                                
                                // Set parameters - store the new password as is
                                // Comment out the line below if you don't want to hash passwords
                                $param_password = password_hash($new_password, PASSWORD_DEFAULT);
                                
                                // Uncomment the line below if you want to store passwords as plain text
                                // $param_password = $new_password;
                                
                                // Attempt to execute the prepared statement
                                if ($stmt_update->execute()) {
                                    $success_message = "Password changed successfully!";
                                    // Clear the password fields
                                    $password = $new_password = $confirm_password = "";
                                } else {
                                    $error_message = "Oops! Something went wrong. Please try again later.";
                                }
                                
                                // Close statement
                                $stmt_update->close();
                            }
                        } else {
                            // Display an error message if password is not valid
                            $password_err = "The current password you entered is not valid.";
                        }
                    }
                } else {
                    // Display an error message if employee doesn't exist
                    $error_message = "No account found with that employee ID.";
                }
            } else {
                $error_message = "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            $stmt->close();
        }
    }
}

// Fetch current user data to display in the form
$sql = "SELECT fullname, email, emp_num FROM employee WHERE emp_id = ?";
if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("i", $emp_id);
    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows == 1) {
            $stmt->bind_result($fullname, $email, $emp_num);
            $stmt->fetch();
        }
    }
    $stmt->close();
}

// Permissions array from landing page
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
    <title>PCL - MY PROFILE</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/landingPage.css">
    <style>
        /* General Styles */
        :root {
            --primary-color:rgb(103, 0, 0);
            --primary-hover:rgb(179, 0, 0);
            --text-color: #333;
            --border-color: #ddd;
            --light-gray: #f8f9fa;
            --error-color: #dc3545;
            --success-color: #28a745;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }
        
        /* Profile Container */
        .profile-container {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 10px;
            margin: 20px auto;
            width: 90%;
        }
        
        
        /* Row Layout */
        .profile-sections {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .profile-section {
            flex: 1;
            min-width: 300px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .profile-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }
        
        .section-header h2 {
            font-size: 1.4rem;
            color: var(--primary-color);
            margin: 0;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            position: relative;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        
        .form-group .invalid-feedback {
            color: var(--error-color);
            font-size: 14px;
            margin-top: 5px;
        }
        
        .btn {
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s, transform 0.2s;
            width: 100%;
        }
        
        .btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* User Info Table */
        .user-info-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-info-table tr {
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-info-table tr:last-child {
            border-bottom: none;
        }
        
        .user-info-table th, .user-info-table td {
            padding: 12px 8px;
            text-align: left;
        }
        
        .user-info-table th {
            color: #555;
            font-weight: 600;
            width: 40%;
        }
        
        .user-info-table td {
            color: var(--text-color);
        }
        
        /* Responsiveness */
        @media (max-width: 992px) {
            .profile-sections {
                flex-direction: column;
            }
            
            .profile-section {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 20px 15px;
                margin: 10px;
            }
            
            
            .section-header h2 {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            .form-group input, .btn {
                padding: 10px;
                font-size: 14px;
            }
            
        }
        
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script src="assets/js/landingPage.js"></script>
</head>
<body>
    <div class="mobile-toggle">☰</div>
    <div class="overlay"></div>
    
    <!-- Updated Loading Screen to match the login page -->
    <div class="loading-screen" id="loading-screen">
        <div class="loader"></div>
        <span>Loading...</span>
    </div>
    
    
    <!-- Sidebar from landing page -->
    <div class="sidebar">
        <div class="user-info">
            <div class="name"><?php echo htmlspecialchars($_SESSION["fullname"]); ?></div>
            <div class="role">Role ID: <?php echo htmlspecialchars($_SESSION["u_id"]); ?></div>
        </div>
        
        <div>
            <!-- Metrics Sections -->
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
            <img src="assets/img/logo.png" alt="PCL Logo" style="margin-right: 10px; width: 320px; height: auto;">
        </div>
        
        <div class="profile-container">
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Row-based layout for the three sections -->
            <div class="profile-sections">
                <!-- Current User Information -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Current Information</h2>
                    </div>
                    <table class="user-info-table">
                        <tr>
                            <th>Employee ID</th>
                            <td><?php echo htmlspecialchars($emp_id); ?></td>
                        </tr>
                        <tr>
                            <th>Full Name</th>
                            <td><?php echo htmlspecialchars($fullname); ?></td>
                        </tr>
                        <tr>
                            <th>Employee Number</th>
                            <td><?php echo htmlspecialchars($emp_num); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($email); ?></td>
                        </tr>
                        <tr>
                            <th>Role ID</th>
                            <td><?php echo htmlspecialchars($u_id); ?></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Update Profile Form -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Update Profile</h2>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>">
                            <span class="invalid-feedback"><?php echo $fullname_err; ?></span>
                        </div>    
                        <div class="form-group">
                            <label>Employee Number</label>
                            <input type="text" name="emp_num" value="<?php echo htmlspecialchars($emp_num); ?>">
                            <span class="invalid-feedback"><?php echo $emp_num_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>
                        <div class="form-group">
                            <input type="submit" class="btn" name="update_profile" value="Update Profile">
                        </div>
                    </form>
                </div>
                
                <!-- Change Password Form -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Change Password</h2>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password">
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password">
                            <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password">
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        </div>
                        <div class="form-group">
                            <input type="submit" class="btn" name="change_password" value="Change Password">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Hide loading screen once page is loaded
        window.addEventListener('load', function() {
            document.getElementById('loading-screen').style.display = 'none';
        });
        
        // Mobile sidebar toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.overlay').classList.toggle('active');
        });
        
        document.querySelector('.overlay').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.remove('active');
            document.querySelector('.overlay').classList.remove('active');
        });
        
        // Add smooth animation to success message
        const alertSuccess = document.querySelector('.alert-success');
        if (alertSuccess) {
            setTimeout(function() {
                alertSuccess.style.opacity = '0';
                alertSuccess.style.transition = 'opacity 1s';
                setTimeout(function() {
                    alertSuccess.style.display = 'none';
                }, 1000);
            }, 3000);
        }
        
        // Add focus effect for form inputs
        const formInputs = document.querySelectorAll('.form-group input');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('label').style.color = '#007bff';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.querySelector('label').style.color = '#555';
            });
        });
    </script>
</body>
</html>