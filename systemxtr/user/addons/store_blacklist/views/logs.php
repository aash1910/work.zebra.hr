<style>
.blacklist-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.blacklist-table th, .blacklist-table td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; overflow:hidden;}
.blacklist-table th { background-color: #f4f4f4; }
.blacklist-table tr:nth-child(even) { background-color: #f9f9f9; }
.blacklist-table tr.matched td { background-color: #f8d7da; }
.col-num       { width: 50px; }
.col-timestamp { width: 170px; }
.col-ip        { width: 170px; }
.col-email     { width: 170px; }
.col-phone     { width: 170px; }
.col-address   { width: 170px; }
.col-city      { width: 120px; }
.col-postcode  { width: 120px; }
.col-matched   { width: 50px; }
.col-reason    { width: 170px; }
.col-value     { width: 170px; }
</style>

<div class="panel-heading"><a class="button button--primary" href="<?php echo ee('CP/URL')->make('addons/settings/store_blacklist'); ?>">Povratak</a></div>

<h2 class="mb">Blacklist Log</h2>

<table class="blacklist-table">
    <thead>
        <tr>
            <th class="col-num">#</th>
            <th class="col-timestamp">Timestamp</th>
            <th class="col-ip">IP</th>
            <th class="col-email">Email</th>
            <th class="col-phone">Billing Phone</th>
            <th class="col-phone">Shipping Phone</th>
            <th class="col-address">Billing Address</th>
            <th class="col-address">Shipping Address</th>
            <th class="col-city">Billing City</th>
            <th class="col-city">Shipping City</th>
            <th class="col-postcode">Billing Postcode</th>
            <th class="col-postcode">Shipping Postcode</th>
            <th class="col-matched">Matched</th>
            <th class="col-reason">Reason</th>
            <th class="col-value">Matched Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logs as $i => $log): ?>
        <tr class="<?php echo (!empty($log['matched']) ? 'matched' : ''); ?>">
            <td><?php echo $i + 1; ?></td>
            <td><?php echo htmlspecialchars(isset($log['timestamp']) ? $log['timestamp'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['ip']) ? $log['ip'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['email']) ? $log['email'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['billing_phone']) ? $log['billing_phone'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['shipping_phone']) ? $log['shipping_phone'] : ''); ?></td>
            <td><?php echo htmlspecialchars(trim((isset($log['billing_address1']) ? $log['billing_address1'] : '') . ' ' . (isset($log['billing_address2']) ? $log['billing_address2'] : ''))); ?></td>
            <td><?php echo htmlspecialchars(trim((isset($log['shipping_address1']) ? $log['shipping_address1'] : '') . ' ' . (isset($log['shipping_address2']) ? $log['shipping_address2'] : ''))); ?></td>
            <td><?php echo htmlspecialchars(isset($log['billing_city']) ? $log['billing_city'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['shipping_city']) ? $log['shipping_city'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['billing_postcode']) ? $log['billing_postcode'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['shipping_postcode']) ? $log['shipping_postcode'] : ''); ?></td>
            <td><?php echo !empty($log['matched']) ? 'Yes' : 'No'; ?></td>
            <td><?php echo htmlspecialchars(isset($log['match_reason']) ? $log['match_reason'] : ''); ?></td>
            <td><?php echo htmlspecialchars(isset($log['matched_value']) ? $log['matched_value'] : ''); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
