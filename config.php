<?php
// config.php - Reads directly from Render Environment Variables

define('CONSUMER_KEY', getenv('DARAJA_CONSUMER_KEY'));
define('CONSUMER_SECRET', getenv('DARAJA_CONSUMER_SECRET'));
define('PASSKEY', getenv('DARAJA_PASSKEY'));
define('BUSINESS_SHORTCODE', getenv('DARAJA_SHORTCODE') ?: '174379');
define('APP_URL', getenv('APP_URL'));

// Database connection details from Aiven
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL); 
$conn->real_connect($db_host, $db_user, $db_pass, $db_name, (int)$db_port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    error_log("Database Connection Failed: " . $conn->connect_error);
}

function getAccessToken() {
    $consumerKey = trim(CONSUMER_KEY);
    $consumerSecret = trim(CONSUMER_SECRET);

    if (empty($consumerKey) || empty($consumerSecret)) {
        error_log("Daraja Keys Missing in Environment Variables!");
        return null;
    }

    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false, // Fixes Docker/Render SSL handshake issues
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $result = json_decode($response);
    return $result->access_token ?? null;
}
?>