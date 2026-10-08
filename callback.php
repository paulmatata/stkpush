<?php
// callback.php
require_once 'config.php';

header("Content-Type: application/json");

// Read raw JSON response from Safaricom
$stkCallbackResponse = file_get_contents('php://input');
$logFile = "stkPushCallbackResponse.json";
file_put_contents($logFile, $stkCallbackResponse, FILE_APPEND);

$data = json_decode($stkCallbackResponse, true);

if (!$data) {
    exit;
}

$callback = $data['Body']['stkCallback'];
$resultCode = $callback['ResultCode'];
$resultDesc = $callback['ResultDesc'];
$checkoutRequestId = $callback['CheckoutRequestID'];

if ($resultCode == 0) {
    // Payment was Successful
    $mpesaReceiptNumber = null;
    
    foreach ($callback['CallbackMetadata']['Item'] as $item) {
        if ($item['Name'] == 'MpesaReceiptNumber') {
            $mpesaReceiptNumber = $item['Value'];
        }
    }

    $stmt = $conn->prepare("UPDATE stk_payments SET status = 'SUCCESS', mpesa_receipt_number = ?, result_desc = ? WHERE checkout_request_id = ?");
    $stmt->bind_param("sss", $mpesaReceiptNumber, $resultDesc, $checkoutRequestId);
    $stmt->execute();
} else {
    // Payment failed or was canceled by user
    $stmt = $conn->prepare("UPDATE stk_payments SET status = 'FAILED', result_desc = ? WHERE checkout_request_id = ?");
    $stmt->bind_param("ss", $resultDesc, $checkoutRequestId);
    $stmt->execute();
}

// Acknowledge Safaricom
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
?>