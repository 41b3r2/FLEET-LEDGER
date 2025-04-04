<?php
require_once 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['truck_id'];
    $model = $_POST['model'];
    $truck_plate = $_POST['truck_plate'];
    $status = $_POST['status'];
    $truck_type = $_POST['truck_type'];

    $query = "UPDATE truck SET model=?, truck_plate=?, status=?, truck_type=? WHERE truck_id=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $model, $truck_plate, $status, $truck_type, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>
                alert('Truck details successfully updated!');
                window.location.href = 'trucks.php';
              </script>";
    } else {
        echo "<script>
                alert('Error updating record. Please try again.');
                window.location.href = 'trucks.php';
              </script>";
    }
}
?>
