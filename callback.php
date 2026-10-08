<?php
// callback.php
require_once 'config.php';

header("Content-Type: application/json");

// 1. Read raw JSON body from Safaricom
$stkCallbackResponse = file_get_contents('php://input');

// Log callback to a file if local, or error log in cloud
error_log("Daraja Callback Received: " . $stkCallbackResponse);

$data = json_decode($stkCallbackResponse, true);

if (!$data || !isset($data['Body']['stkCallback'])) {
    echo json_encode(["ResultCode" => 1, "ResultDesc" => "Invalid Payload"]);
    exit;
}

$callback = $data['Body']['stkCallback'];
$resultCode = $callback['ResultCode'];
$resultDesc = $callback['ResultDesc'] ?? '';
$checkoutRequestId = $callback['CheckoutRequestID'];

if ($resultCode == 0) {
    // Payment Successful
    $mpesaReceiptNumber = null;
    
    if (isset($callback['CallbackMetadata']['Item'])) {
        foreach ($callback['CallbackMetadata']['Item'] as $item) {
            if (isset($item['Name']) && $item['Name'] === 'MpesaReceiptNumber') {
                $mpesaReceiptNumber = $item['Value'] ?? null;
            }
        }
    }

    // Update database status to SUCCESS
    $stmt = $conn->prepare("UPDATE stk_payments SET status = 'SUCCESS', mpesa_receipt_number = ?, result_desc = ? WHERE checkout_request_id = ?");
    $stmt->bind_param("sss", $mpesaReceiptNumber, $resultDesc, $checkoutRequestId);
    $stmt->execute();

} else {
    // Payment Failed or Cancelled by User
    $stmt = $conn->prepare("UPDATE stk_payments SET status = 'FAILED', result_desc = ? WHERE checkout_request_id = ?");
    $stmt->bind_param("ss", $resultDesc, $checkoutRequestId);
    $stmt->execute();
}

// Acknowledge receipt to Safaricom
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
?>