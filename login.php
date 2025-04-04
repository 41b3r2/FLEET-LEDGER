<?php
session_start();
require_once 'connection.php';

$email = $password = "";
$emailErr = $passwordErr = $loginErr = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }
    
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if (empty($emailErr) && empty($passwordErr)) {
        $stmt = $conn->prepare("SELECT emp_id, fullname, email, password, u_id FROM employee WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            // Check if password is hashed (assuming bcrypt hash starts with $2y$)
            if (strpos($row["password"], '$2y$') === 0) {
                // Verify with password_verify if it's a hashed password
                $passwordMatch = password_verify($password, $row["password"]);
            } else {
                // Plain text comparison if not hashed
                $passwordMatch = ($password === $row["password"]);
            }
            
            if ($passwordMatch) {
                session_regenerate_id(true); 
                
                $_SESSION["loggedin"] = true;
                $_SESSION["emp_id"] = $row["emp_id"];
                $_SESSION["fullname"] = $row["fullname"];
                $_SESSION["email"] = $row["email"];
                $_SESSION["u_id"] = $row["u_id"];
                
                $success = true;
            } else {
                $loginErr = "Invalid email or password";
            }
        } else {
            $loginErr = "Invalid email or password";
        }
        
        $stmt->close();
    }
}

if (isset($conn) && $conn) {
    $conn->close();
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - Login</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">
    
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
            position: relative;
        }
        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .modal-content button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <video autoplay muted loop class="video-background">
        <source src="assets/vid/clip1.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="loading-screen" id="loading-screen">
        <div class="loader"></div>
        <span>Loading...</span>
    </div>

    <div class="container">
        <div class="logo-container">
            <img src="assets/img/pcl.png" alt="PCL Logo">
            <h1>FLEET LEDGER</h1>
        </div>
        
        <?php if (!empty($loginErr)): ?>
            <div class="alert"><?php echo htmlspecialchars($loginErr); ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" autocomplete="off">
            <div class="login-container">
                <div class="input-container">
                    <div class="input-wrapper">
                        <input type="email" placeholder="Email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <img src="assets/img/log_icon.png" class="input-icon" alt="Email icon">
                    </div>
                    <span class="error"><?php echo $emailErr; ?></span>
                </div>

                <div class="input-container">
                    <div class="input-wrapper">
                        <input type="password" placeholder="Password" id="password" name="password">
                        <img src="assets/img/pas_icon.png" class="input-icon" alt="Password icon">
                    </div>
                    <span class="error"><?php echo $passwordErr; ?></span>
                </div>

                <a href="forgot-password.php" id="forgotLink">Forgot Password?</a>
                <button class="login-button" type="submit">Login</button>
            </div>
        </form>
    </div>

    <?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById("loading-screen").style.display = "flex";
            
            setTimeout(function() {
                window.location.href = "landingPage.php";
            }, 1000);
        });
    </script>
    <?php endif; ?>

<!-- Modify your existing Forgot Password Modal -->
<div id="forgotPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('forgotPasswordModal')">&times;</span>
        <h2>Forgot Password</h2>
        <form id="forgotPasswordForm">
            <input type="email" id="forgotEmail" name="email" placeholder="Enter your email" required>
            <button type="submit">Send OTP</button>
        </form>
    </div>
</div>

    <!-- OTP Verification Modal -->
    <div id="otpModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('otpModal')">&times;</span>
            <h2>OTP Verification</h2>
            <form id="otpVerificationForm">
                <input type="text" id="otpInput" placeholder="Enter OTP" required maxlength="6">
                <button type="submit">Verify OTP</button>
            </form>
        </div>
    </div>
    

<!-- New Password Modal -->
<div id="newPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('newPasswordModal')">&times;</span>
        <h2>Reset Password</h2>
        <form id="resetPasswordForm">
            <input type="hidden" id="resetEmail" name="email">
            <input type="password" id="newPassword" placeholder="New Password" required>
            <input type="password" id="confirmPassword" placeholder="Confirm New Password" required>
            <button type="submit">Reset Password</button>
        </form>
    </div>
</div>





    <script>

document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    fetch('update-password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'newPassword=' + encodeURIComponent(newPassword) + 
              '&confirmPassword=' + encodeURIComponent(confirmPassword)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal('newPasswordModal');
            // Optionally redirect to login or show success message
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Event Listeners
        document.getElementById('forgotLink').addEventListener('click', function(e) {
            e.preventDefault();
            openModal('forgotPasswordModal');
        });

        // Forgot Password Form Submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('forgotEmail').value;
        
        // Store email in a global variable or hidden input that persists across modals
        localStorage.setItem('resetEmail', email);
        
        fetch('send-otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('forgotPasswordModal');
                openModal('otpModal');
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });

    document.getElementById('otpVerificationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const otp = document.getElementById('otpInput').value;
    
    // Retrieve email from localStorage or input
    const email = localStorage.getItem('resetEmail') || 
                  document.getElementById('forgotEmail').value;

    console.log('OTP Verification - Email:', email);
    console.log('OTP:', otp);

    if (!email) {
        alert('Email is missing. Please start the process again.');
        return;
    }

    fetch('verify-otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'otp=' + encodeURIComponent(otp) + '&email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server Response:', data);

        if (data.success) {
            closeModal('otpModal');
            
            // Set the email in the reset password modal
            document.getElementById('resetEmail').value = email;
            openModal('newPasswordModal');
        } else {
            alert(data.message || 'An error occurred during OTP verification');
        }
    })
    .catch(error => {
        console.error('Network Error:', error);
        alert('Network error. Please check your connection.');
    });
});

// Reset Password Form Submission
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('resetEmail').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        fetch('update-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent(email) + 
                  '&newPassword=' + encodeURIComponent(newPassword) + 
                  '&confirmPassword=' + encodeURIComponent(confirmPassword)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                closeModal('newPasswordModal');
                localStorage.removeItem('resetEmail'); // Clear stored email
                window.location.href = 'login.php'; // Redirect to login page
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
    </script>
</body>
</html>