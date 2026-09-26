<?php
// Path to log file
$logFile = __DIR__ . '/blacklist_attempts.json';

// Get client IP
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

// Parse matched flag ("1" = true, "0" = false)
$matched = (isset($_POST['matched']) && $_POST['matched'] === '1');

// Collect POST data
$data = [
    'timestamp'        => date('c'),
    'ip'               => $ip,
    'email'            => isset($_POST['email']) ? $_POST['email'] : '',
    'billing_phone'    => isset($_POST['billing_phone']) ? $_POST['billing_phone'] : '',
    'shipping_phone'   => isset($_POST['shipping_phone']) ? $_POST['shipping_phone'] : '',
    'billing_address1' => isset($_POST['billing_address1']) ? $_POST['billing_address1'] : '',
    'billing_address2' => isset($_POST['billing_address2']) ? $_POST['billing_address2'] : '',
    'billing_city'     => isset($_POST['billing_city']) ? $_POST['billing_city'] : '',
    'billing_postcode' => isset($_POST['billing_postcode']) ? $_POST['billing_postcode'] : '',
    'shipping_address1'=> isset($_POST['shipping_address1']) ? $_POST['shipping_address1'] : '',
    'shipping_address2'=> isset($_POST['shipping_address2']) ? $_POST['shipping_address2'] : '',
    'shipping_city'    => isset($_POST['shipping_city']) ? $_POST['shipping_city'] : '',
    'shipping_postcode'=> isset($_POST['shipping_postcode']) ? $_POST['shipping_postcode'] : '',
    'matched'          => $matched,
];

// Encode as JSON
$json = json_encode($data, JSON_UNESCAPED_UNICODE);

// Append to log file with locking
$fp = fopen($logFile, 'a');
if ($fp) {
    flock($fp, LOCK_EX);
    fwrite($fp, $json . PHP_EOL);
    flock($fp, LOCK_UN);
    fclose($fp);
}

// Return success response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
