<script>
function addAddressRow() {
    var table = document.querySelector('.address-table tbody');
    var index = table.querySelectorAll('tr').length;
    var html = `
        <tr>
            <td><input type="text" name="addresses[${index}][firstname]"></td>
            <td><input type="text" name="addresses[${index}][lastname]"></td>
            <td><input type="text" name="addresses[${index}][address1]"></td>
            <td><input type="text" name="addresses[${index}][address2]"></td>
            <td><input type="text" name="addresses[${index}][city]"></td>
            <td><input type="text" name="addresses[${index}][postcode]"></td>
        </tr>`;
    table.insertAdjacentHTML('beforeend', html);
}
</script>

<div class="panel-heading"><a class="button button--primary" href="<?php echo ee('CP/URL')->make('addons/settings/store_blacklist'); ?>">Povratak</a></div>

<h2>Uredi</h2>
<?php echo form_open(ee('CP/URL')->make('addons/settings/store_blacklist/edit/' . $entry['id'])); ?>
    <p><label>Primaran Email:</label><br><input type="email" name="primary_email" value="<?php echo htmlspecialchars($entry['email']); ?>" required></p>
    <p><label>Dodatni Emailovi (odvojeno zarezom):</label><br><input type="text" name="additional_emails" value="<?php echo htmlspecialchars(implode(',', array_column($emails, 'email'))); ?>"></p>
    <p><label>Telefonski brojevi (odvojeno zarezom):</label><br><input type="text" name="phone_numbers" value="<?php echo htmlspecialchars(implode(',', array_column($phones, 'phone_number'))); ?>"></p>
    <h3>Adrese</h3>
    <table class="blacklist-table address-table">
        <thead>
            <tr>
                <th>Ime *</th>
                <th>Prezime *</th>
                <th>Ulica</th>
                <th>Broj</th>
                <th>Grad</th>
                <th>Poštanski broj</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($addresses as $index => $addr): ?>
            <tr>
                <td><input type="text" name="addresses[<?php echo $index; ?>][firstname]" value="<?php echo htmlspecialchars($addr['firstname']); ?>"></td>
                <td><input type="text" name="addresses[<?php echo $index; ?>][lastname]" value="<?php echo htmlspecialchars($addr['lastname']); ?>"></td>
                <td><input type="text" name="addresses[<?php echo $index; ?>][address1]" value="<?php echo htmlspecialchars($addr['address1']); ?>"></td>
                <td><input type="text" name="addresses[<?php echo $index; ?>][address2]" value="<?php echo htmlspecialchars($addr['address2']); ?>"></td>
                <td><input type="text" name="addresses[<?php echo $index; ?>][city]" value="<?php echo htmlspecialchars($addr['city']); ?>"></td>
                <td><input type="text" name="addresses[<?php echo $index; ?>][postcode]" value="<?php echo htmlspecialchars($addr['postcode']); ?>"></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($addresses)): ?>
            <tr>
                <td><input type="text" name="addresses[0][firstname]"></td>
                <td><input type="text" name="addresses[0][lastname]"></td>
                <td><input type="text" name="addresses[0][address1]"></td>
                <td><input type="text" name="addresses[0][address2]"></td>
                <td><input type="text" name="addresses[0][city]"></td>
                <td><input type="text" name="addresses[0][postcode]"></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <p><button class="btn draft" type="button" onclick="addAddressRow()">Dodaj adresu</button></p>
    <p><input type="submit" name="submit" value="Spremi"></p>
    <p><small>*Ime i prezime su samo za lakše snalaženje, ne koriste se u provjeri</small></p>
<?php echo form_close(); ?>