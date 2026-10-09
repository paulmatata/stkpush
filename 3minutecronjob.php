<?php
// keep_alive.php - Keeps Render web service awake

$targetUrl = 'https://stkpush-paulmatata.onrender.com/stk_push.php';
$intervalSeconds = 180; // 3 minutes

echo "==========================================" . PHP_EOL;
echo " Render Keep-Alive Ping Started" . PHP_EOL;
echo " Target: {$targetUrl}" . PHP_EOL;
echo " Interval: Every 3 minutes" . PHP_EOL;
echo " Press Ctrl+C to stop" . PHP_EOL;
echo "==========================================" . PHP_EOL . PHP_EOL;

while (true) {
    $timestamp = date('Y-m-d H:i:s');
    
    $ch = curl_init($targetUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'RenderKeepAliveBot/1.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 400) {
        echo "[{$timestamp}] PING SUCCESS | HTTP Code: {$httpCode}" . PHP_EOL;
    } else {
        echo "[{$timestamp}] PING FAILED  | HTTP Code: {$httpCode} | Error: {$curlError}" . PHP_EOL;
    }

    // Wait 3 minutes before next ping
    sleep($intervalSeconds);
}
?>