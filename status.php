<?php
// stk_push.php
require_once 'config.php';

$phone = $_POST['phone'] ?? '2547XXXXXXXX'; // Get phone dynamically or fallback
$amount = 1;

$timestamp = date('YmdHis');
$password = base64_encode(BUSINESS_SHORTCODE . PASSKEY . $timestamp);
$accessToken = getAccessToken();

// Dynamic callback using Render URL
$callbackUrl = rtrim(APP_URL, '/') . '/callback.php'; 

if (!$accessToken) {
    die(json_encode(["status" => "error", "message" => "Failed to retrieve access token."]));
}

$stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$payload = [
    'BusinessShortCode' => BUSINESS_SHORTCODE,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerBuyGoodsOnline',
    'Amount'            => $amount,
    'PartyA'            => $phone,
    'PartyB'            => BUSINESS_SHORTCODE,
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $callbackUrl,
    'AccountReference'  => 'Test Payment',
    'TransactionDesc'   => 'Testing 1 KES STK Push'
];

$ch = curl_init($stkUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode($response, true);

if (isset($resData['ResponseCode']) && $resData['ResponseCode'] == '0') {
    $checkoutRequestId = $resData['CheckoutRequestID'];
    $merchantRequestId = $resData['MerchantRequestID'];

    $stmt = $conn->prepare("INSERT INTO stk_payments (checkout_request_id, merchant_request_id, phone_number, amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $checkoutRequestId, $merchantRequestId, $phone, $amount);
    $stmt->execute();

    echo json_encode([
        "status" => "success",
        "message" => "STK Push sent. Check your phone.",
        "checkout_id" => $checkoutRequestId
    ]);
} else {
    echo json_encode([
        "status" => "error", 
        "message" => $resData['errorMessage'] ?? 'Unknown error'
    ]);
}
?>