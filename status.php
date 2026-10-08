<?php
// status.php
require_once 'config.php';

// Disable HTML error displays to ensure pure JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json");

$checkoutRequestId = $_GET['checkout_id'] ?? '';

if (empty($checkoutRequestId)) {
    echo json_encode([
        "status" => "ERROR", 
        "message" => "Checkout Request ID is missing."
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT status, mpesa_receipt_number, result_desc FROM stk_payments WHERE checkout_request_id = ?");
    $stmt->bind_param("s", $checkoutRequestId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "status"  => $row['status'], // 'PENDING', 'SUCCESS', or 'FAILED'
            "receipt" => $row['mpesa_receipt_number'] ?? '',
            "message" => $row['result_desc'] ?? ''
        ]);
    } else {
        echo json_encode([
            "status"  => "NOT_FOUND",
            "message" => "Transaction not found in database."
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        "status"  => "ERROR",
        "message" => $e->getMessage()
    ]);
}
?>