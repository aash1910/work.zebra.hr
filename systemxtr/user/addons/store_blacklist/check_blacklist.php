<?php
$logFile = __DIR__ . '/blacklist_attempts.json';

$logs = [];
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $logs[] = json_decode($line, true);
    }
}

// Sort by timestamp descending
usort($logs, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blacklist Log Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .matched { background-color: #f8d7da; }
    </style>
</head>
<body>
    <h1>Blacklist Log</h1>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Timestamp</th>
                <th>IP</th>
                <th>Email</th>
                <th>Billing Phone</th>
                <th>Shipping Phone</th>
                <th>Billing Address</th>
                <th>Shipping Address</th>
                <th>Billing City</th>
                <th>Shipping City</th>
                <th>Billing Postcode</th>
                <th>Shipping Postcode</th>
                <th>Matched</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $i => $entry): ?>
                <tr class="<?php echo (!empty($entry['matched']) ? 'matched' : ''); ?>">
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($entry['timestamp']); ?></td>
                    <td><?php echo htmlspecialchars($entry['ip']); ?></td>
                    <td><?php echo htmlspecialchars($entry['email']); ?></td>
                    <td><?php echo htmlspecialchars($entry['billing_phone']); ?></td>
                    <td><?php echo htmlspecialchars($entry['shipping_phone']); ?></td>
                    <td><?php echo htmlspecialchars(trim($entry['billing_address1'] . ' ' . $entry['billing_address2'])); ?></td>
                    <td><?php echo htmlspecialchars(trim($entry['shipping_address1'] . ' ' . $entry['shipping_address2'])); ?></td>
                    <td><?php echo htmlspecialchars($entry['billing_city']); ?></td>
                    <td><?php echo htmlspecialchars($entry['shipping_city']); ?></td>
                    <td><?php echo htmlspecialchars($entry['billing_postcode']); ?></td>
                    <td><?php echo htmlspecialchars($entry['shipping_postcode']); ?></td>
                    <td><?php echo (!empty($entry['matched']) ? 'Yes' : 'No'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
