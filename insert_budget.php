<?php
// insert_budget.php
require_once 'connection.php';

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $cs_id = $_POST['cs_id'] ?? null;
    $fuelfee = $_POST['fuelfee'] ?? 0;
    $tollfee = $_POST['tollfee'] ?? 0;
    $parkingfee = $_POST['parkingfee'] ?? 0;
    $rorofarefee = $_POST['rorofarefee'] ?? 0;
    
    // Calculate total budget release
    $budgetrelease = $fuelfee + $tollfee + $parkingfee + $rorofarefee;

    // Validate required fields
    if ($cs_id === null) {
        echo json_encode(['success' => false, 'message' => 'CS ID is required']);
        exit;
    }

    try {
        // Check if budget already exists for this cs_id
        $checkStmt = $conn->prepare("SELECT * FROM budget WHERE cs_id = ?");
        $checkStmt->bind_param("i", $cs_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing budget record
            $stmt = $conn->prepare("UPDATE budget SET 
                fuelfee = ?, 
                tollfee = ?, 
                parkingfee = ?, 
                rorofarefee = ?, 
                budgetrelease = ?, 
                updated_at = NOW() 
                WHERE cs_id = ?");

            $stmt->bind_param(
                "dddddi", 
                $fuelfee, 
                $tollfee, 
                $parkingfee, 
                $rorofarefee, 
                $budgetrelease,
                $cs_id
            );
        } else {
            // Insert new budget record
            $stmt = $conn->prepare("INSERT INTO budget (
                cs_id, 
                fuelfee, 
                tollfee, 
                parkingfee, 
                rorofarefee, 
                budgetrelease, 
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, NOW()
            )");

            $stmt->bind_param(
                "iddddi", 
                $cs_id, 
                $fuelfee, 
                $tollfee, 
                $parkingfee, 
                $rorofarefee, 
                $budgetrelease
            );
        }

        // Execute the statement
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Budget details saved successfully',
                'budgetId' => $stmt->insert_id
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to save budget details: ' . $stmt->error
            ]);
        }

        // Close statements
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    exit;
} else {
    // Handle invalid request method
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
    exit;
}
?>