<?php
// config.php - Reads directly from Render Environment Variables

define('CONSUMER_KEY', getenv('lr39RyCcalAc3XSHnOX4XAnuRizTXutYdRvHsdiKRdliF6oR'));
define('CONSUMER_SECRET', getenv('5hlKNFfGKT5ihZu2Mw3ss5VNx40AWc6SYzDzEvYhOo3mE9pF9vHKBIqlfeByBBla'));
define('PASSKEY', getenv('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'));
define('BUSINESS_SHORTCODE', getenv('DARAJA_SHORTCODE') ?: '174379');
define('APP_URL', getenv('APP_URL')); // e.g., https://your-app.onrender.com

// Database connection details from Aiven
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT') ?: 3306;
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');
$db_name = getenv('DB_NAME');

$conn = mysqli_init();

// Aiven requires SSL connection
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL); 
$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, MYSQLI_CLIENT_SSL);

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