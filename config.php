<?php
// config.php

define('CONSUMER_KEY', 'lr39RyCcalAc3XSHnOX4XAnuRizTXutYdRvHsdiKRdliF6oR');
define('CONSUMER_SECRET', '5hlKNFfGKT5ihZu2Mw3ss5VNx40AWc6SYzDzEvYhOo3mE9pF9vHKBIqlfeByBBla');
define('PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('BUSINESS_SHORTCODE', '174379'); // Sandbox shortcode

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'tenda_monitor';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

function getAccessToken() {
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response);
    return $result->access_token ?? null;
}
?>