<?php
require_once 'connection.php';

if (isset($_POST['truck_id'])) {
    $id = $_POST['truck_id'];
    $query = "SELECT * FROM truck WHERE truck_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $truck = mysqli_fetch_assoc($result);
    
    echo json_encode($truck);
}
?>
