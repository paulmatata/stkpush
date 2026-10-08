<?php
// stk_push.php
require_once 'config.php';

$phone = '2547XXXXXXXX'; // Customer phone number in 2547XXXXXXXX format
$amount = 1;             // Send 1 shilling for testing

$timestamp = date('YmdHis');
$password = base64_encode(BUSINESS_SHORTCODE . PASSKEY . $timestamp);
$accessToken = getAccessToken();

// Callback URL must be HTTPS and publicly accessible (Use Ngrok for localhost)
$callbackUrl = 'https://your-domain.com/callback.php'; 

if (!$accessToken) {
    die("Failed to retrieve access token.");
}

$stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$payload = [
    'BusinessShortCode' => BUSINESS_SHORTCODE,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerBuyGoodsOnline', // Specific for Till Number
    'Amount'            => $amount,
    'PartyA'            => $phone,
    'PartyB'            => BUSINESS_SHORTCODE,       // In sandbox, PartyB matches shortcode
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

    // Insert initial record into DB
    $stmt = $conn->prepare("INSERT INTO stk_payments (checkout_request_id, merchant_request_id, phone_number, amount) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssd", $checkoutRequestId, $merchantRequestId, $phone, $amount);
    $stmt->execute();

    echo "STK Push sent successfully. Check your phone to enter PIN.";
} else {
    echo "STK Push failed: " . ($resData['errorMessage'] ?? 'Unknown error');
}
?>