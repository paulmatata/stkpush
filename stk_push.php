<?php
// stk_push.php
require_once 'config.php';

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json");

$rawPhone = $_POST['phone'] ?? '';

// Format phone number to 254XXXXXXXXX
$phone = preg_replace('/[^0-9]/', '', $rawPhone);
if (substr($phone, 0, 1) === '0') {
    $phone = '254' . substr($phone, 1);
} elseif (substr($phone, 0, 3) !== '254') {
    $phone = '254' . $phone;
}

if (strlen($phone) !== 12) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid phone number length. Use format 07XXXXXXXX or 01XXXXXXXX."
    ]);
    exit;
}

$amount = 1;
$timestamp = date('YmdHis');
$password = base64_encode(BUSINESS_SHORTCODE . PASSKEY . $timestamp);

try {
    $accessToken = getAccessToken();

    if (!$accessToken) {
        echo json_encode(["status" => "error", "message" => "Failed to fetch Daraja Access Token."]);
        exit;
    }

    $callbackUrl = rtrim(APP_URL, '/') . '/callback.php'; 
    $stkUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    $payload = [
        'BusinessShortCode' => BUSINESS_SHORTCODE,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline', // Changed from CustomerBuyGoodsOnline
        'Amount'            => $amount,
        'PartyA'            => $phone,
        'PartyB'            => BUSINESS_SHORTCODE,       // Must match BusinessShortCode in Sandbox
        'PhoneNumber'       => $phone,
        'CallBackURL'       => $callbackUrl,
        'AccountReference'  => 'CloudP Tech',
        'TransactionDesc'   => 'Testing 1 KES STK Push'
    ];

    $ch = curl_init($stkUrl);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ],
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception("cURL Error: " . curl_error($ch));
    }
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
            "message" => "STK Push sent successfully. Check your phone.",
            "checkout_id" => $checkoutRequestId
        ]);
    } else {
        echo json_encode([
            "status" => "error", 
            "message" => $resData['errorMessage'] ?? $resData['ResponseDescription'] ?? 'Daraja request failed.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Server Error: " . $e->getMessage()
    ]);
}
?>