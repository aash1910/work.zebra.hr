<style>
.blacklist-table { width: 100%; border-collapse: collapse; }
.blacklist-table th, .blacklist-table td { border: 1px solid #ccc; padding: 8px; vertical-align: top; }
.blacklist-sub { margin: 0; padding: 0; list-style: none; font-size: 0.9em; }
.blacklist-sub li { margin-bottom: 2px; }
</style>
<h2 class="panel-heading">Store Blacklist</h2>
<div class="panel-heading">
    Primaran Email, Dodatni Emailovi i Telefonski brojevi moraju biti 100% match, i automatski blokiraju naručivanje.<br>
    Telefonski broj se normalizira, pa ako je kupac unesao 099-222-333, prije nego pretražujete popis, tražite 99222333<br>
    Grad i poštanski broj moraju odgovarati 100%, a adresa + broj se gledaju skupa, i moraju biti slični. Ako su grad i pošta match, naziv ulice će već blokirati naručivanje.<br>
    Ime i Prezime su samo vama za bolje snalaženje, ne uzimaju se u obzir kod provjere.<br>
</div>

<div class="panel-heading"><a class="button button--primary" href="<?php echo ee('CP/URL')->make('addons/settings/store_blacklist/add'); ?>">Dodaj na listu</a></div>
<div class="panel-heading"><a class="button button--primary" href="<?php echo ee('CP/URL')->make('addons/settings/store_blacklist/logs'); ?>">Blacklist Log</a></div>
<table class="blacklist-table">
    <tr>
        <th>Primaran Email</th>
        <th>Dodatni Emailovi</th>
        <th>Telefonski brojevi</th>
        <th>Ime / Prezime</th>
        <th>Ulica</th>
        <th>Broj</th>
        <th>Grad</th>
        <th>Poštanski broj</th>
        <th>Akcija</th>
    </tr>
    <?php foreach ($entries as $entry): ?>
    <tr>
        <td><?php echo htmlspecialchars($entry['email']); ?></td>
        <td>
            <ul class="blacklist-sub">
                <?php foreach ($entry['emails'] as $e): ?>
                    <li><?php echo htmlspecialchars($e['email']); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
        <td>
            <ul class="blacklist-sub">
                <?php foreach ($entry['phones'] as $p): ?>
                    <li><?php echo htmlspecialchars($p['phone_number']); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
        <td>
            <ul class="blacklist-sub">
                <?php foreach ($entry['addresses'] as $a): ?>
                    <li><?php echo htmlspecialchars(trim($a['firstname'] . ' ' . $a['lastname'])); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
        <td>
            <ul class="blacklist-sub">
                <?php foreach ($entry['addresses'] as $a): ?>
                    <li><?php echo htmlspecialchars($a['address1']); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
        <td>
            <ul class="blacklist-sub">
                <?php foreach ($entry['addresses'] as $a): ?>
                    <li><?php echo htmlspecialchars($a['address2']); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
        <td>
            <ul class="blacklist-sub">
                <?php foreach ($entry['addresses'] as $a): ?>
                    <li><?php echo htmlspecialchars($a['city']); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
        <td>
            <ul class="blacklist-sub">
                <?php foreach ($entry['addresses'] as $a): ?>
                    <li><?php echo htmlspecialchars($a['postcode']); ?></li>
                <?php endforeach; ?>
            </ul>
        </td>
        <td>
            <a class="btn draft" href="<?php echo ee('CP/URL')->make('addons/settings/store_blacklist/edit/' . $entry['id']); ?>">Uredi</a> |
            <a class="btn draft" href="<?php echo ee('CP/URL')->make('addons/settings/store_blacklist/delete/' . $entry['id']); ?>" onclick="return confirm('Sigurni ste?');">Obriši</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>