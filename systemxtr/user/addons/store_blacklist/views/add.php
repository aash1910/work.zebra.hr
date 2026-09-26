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

<h2>Dodaj</h2>
<?php echo form_open(ee('CP/URL')->make('addons/settings/store_blacklist/add')); ?>
    <p><label>Primaran Email:</label><br><input type="email" name="primary_email" required></p>
    <p><label>Dodatni Emailovi (odvojeno zarezom):</label><br><input type="text" name="additional_emails"></p>
    <p><label>Telefonski brojevi (odvojeno zarezom):</label><br><input type="text" name="phone_numbers"></p>
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
            <tr>
                <td><input type="text" name="addresses[0][firstname]"></td>
                <td><input type="text" name="addresses[0][lastname]"></td>
                <td><input type="text" name="addresses[0][address1]"></td>
                <td><input type="text" name="addresses[0][address2]"></td>
                <td><input type="text" name="addresses[0][city]"></td>
                <td><input type="text" name="addresses[0][postcode]"></td>
            </tr>
        </tbody>
    </table>
    <div class="panel-heading"><button class="button button--primary" type="button" onclick="addAddressRow()">Dodaj adresu</button></div>
    <div class="panel-heading"><input class="button button--success" type="submit" name="submit" value="Spremi"></div>
    <p><small>*Ime i prezime su samo za lakše snalaženje, ne koriste se u provjeri</small></p>
<?php echo form_close(); ?>