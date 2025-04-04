<?php
require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['driver_id'];
    $fullname = $_POST['fullname'];
    $contact = $_POST['contact'];
    $status = $_POST['status'];

    // Validate contact number
    if (!preg_match('/^09\d{9}$/', $contact)) {
        echo "<script>
        alert('Invalid contact number. Please enter a number starting with 09 and exactly 11 digits.');
        window.location.href = 'drivers.php';
        </script>";
        exit;
    }

    $query = "UPDATE driver SET fullname=?, contact=?, status=? WHERE driver_id=?";
    $stmt = mysqli_prepare($conn, $query);
    // Change 'i' to 's' for contact to treat it as a string
    mysqli_stmt_bind_param($stmt, "sssi", $fullname, $contact, $status, $id);

    if (mysqli_stmt_execute($stmt)){
        echo "<script>
        alert('Driver details successfully updated!');
        window.location.href = 'drivers.php';
        </script>";
    } else {
        echo "<script>
        alert('Error updating record: " . mysqli_error($conn) . "');
        window.location.href = 'drivers.php';
        </script>";
    }
}
?>