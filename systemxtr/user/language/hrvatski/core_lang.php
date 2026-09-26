<?php

$lang = array(

    /* General word list */
    'and' => 'i',

    'and_n_others' => 'i %d drugih...',

    'at' => 'na',

    'auto_redirection' => 'Bit ćete automatski preusmjereni za %x sekundi',

    'back' => 'Natrag',

    'by' => 'od',

    'click_if_no_redirect' => 'Kliknite ovdje ako ne budete automatski preusmjereni',

    'disabled' => 'onemogućeno',

    'dot' => 'točka',

    'enabled' => 'omogućeno',

    'encoded_email' => '(JavaScript mora biti omogućen za prikaz ove email adrese)',

    'first' => 'Prvi',

    'id' => 'ID',

    'last' => 'Zadnji',

    'next' => 'Sljedeći',

    'no' => 'Ne',

    'not_authorized' => 'Nemate ovlasti za izvršavanje ove akcije',

    'not_available' => 'Nije dostupno',

    'of' => 'od',

    'off' => 'isključeno',

    'on' => 'uključeno',

    'or' => 'ili',

    'pag_first_link' => '&lsaquo; Prvi',

    'pag_last_link' => 'Zadnji &rsaquo;',

    'page' => 'Stranica',

    'preference' => 'Postavka',

    'prev' => 'Prethodni',

    'return_to_previous' => 'Povratak na prethodnu stranicu',

    'search' => 'Pretraži',

    'setting' => 'Postavka',

    'site_homepage' => 'Početna stranica',

    'submit' => 'Pošalji',

    'system_off_msg' => 'Ova stranica je trenutno neaktivna.',

    'thank_you' => 'Hvala!',

    'update' => 'Ažuriraj',

    'updating' => 'Ažuriranje',

    'yes' => 'Da',

    'required_fields' => 'Obavezna polja',

    'edit_this' => 'Uredi ovo',

    /* Errors */
    'captcha_incorrect' => 'Niste unijeli riječ točno onako kako se pojavljuje na slici',

    'captcha_required' => 'Morate unijeti riječ koja se pojavljuje na slici',

    'recaptcha_required' => 'reCAPTCHA provjera nije prošla',

    'checksum_changed_accept' => 'Prihvati promjene',

    'checksum_changed_warning' => 'Jedna ili više osnovnih datoteka su izmijenjene:',

    'checksum_email_message' => 'ExpressionEngine je detektirao izmjenu osnovne datoteke na: {url}

Sljedeće datoteke su pogođene:
{changed}

Ako ste vi napravili ove promjene, molimo prihvatite izmjene na početnoj stranici Kontrolnog panela. Ako niste mijenjali ove datoteke, to može ukazivati na pokušaj hakiranja. Provjerite datoteke za bilo kakav sumnjiv sadržaj (JavaScript ili iFrames) i pogledajte: ' . DOC_URL . 'troubleshooting/error-messages.html#expressionengine-has-detected-the-modification-of-a-core-file',

    'checksum_email_subject' => 'Osnovna datoteka je izmijenjena na vašoj stranici.',

    'warning_system_status_title' => 'Provjerite status sustava',

    'warning_system_status_message' => 'Vaš trenutni status sustava je postavljen na <b>%s</b>. Ako trebate promijeniti, posjetite <a href="%s">Postavke sustava</a> ili pritisnite gumb ispod.',

    'warning_system_status_button' => 'Postavi sustav %s',

    'csrf_token_expired' => 'Ovaj obrazac je istekao. Osvježite i pokušajte ponovo.',

    'cookie_domain_mismatch' => 'Konfigurirana domena kolačića ne odgovara URL-u stranice.',

    'current_password_incorrect' => 'Vaša trenutna lozinka nije unesena ispravno.',

    'current_password_required' => 'Potrebna je vaša trenutna lozinka.',

    'curl_not_installed' => 'cURL nije instaliran na vašem serveru',

    'error' => 'Greška',

    'file_not_found' => 'Datoteka %s ne postoji.',

    'file_manager' => 'Upravitelj datoteka',

    'general_error' => 'Sljedeće greške su zabilježene',

    'generic_fatal_error' => 'Došlo je do pogreške i ovaj URL se trenutno ne može obraditi.',

    'invalid_action' => 'Tražena akcija nije valjana.',

    'invalid_url' => 'URL koji ste unijeli nije valjan.',

    'missing_encryption_key' => 'Nemate postavljenu vrijednost za <code>%s</code> u config.php. Ovo može ostaviti instalaciju ranjivom na sigurnosne propuste. Vratite ključeve ili pogledajte <a href="%s">ovaj članak za rješavanje problema</a>.',

    'el_folder_present' => 'Direktorij <code>%s</code> postoji na vašem serveru. Provjerite jeste li zamijenili <code>index.php</code> i <code>admin.php</code> prema <a href="%s">uputama za nadogradnju</a> i uklonite ovaj direktorij.',

    'missing_mime_config' => 'Ne mogu importati vaš popis MIME tipova: datoteka %s ne postoji ili se ne može pročitati.',

    'new_version_error' => 'Došlo je do neočekivane greške prilikom pokušaja preuzimanja trenutnog broja verzije ExpressionEngine-a. Pogledajte <a href="%s" rel="external noreferrer">dokument za rješavanje problema</a> za više informacija.',

    'nonexistent_page' => 'Stranica koju ste tražili nije pronađena',

    'redirect_xss_fail' => 'Link na koji ste preusmjereni sadrži potencijalno zlonamjerni ili opasan kod. Preporučujemo da kliknete natrag i pošaljete email na %s kako biste prijavili link koji je generirao ovu poruku.',

    'redirect_warning_header' => 'Upozorenje o preusmjeravanju',

    'redirect_description' => 'Otvarate novu web stranicu koja ide na host <b>%s</b> koji nije dio',

    'redirect_check_address' => 'Molimo provjerite da je adresa ispravna.',

    'redirect_cancel' => 'Otkaži',

    'submission_error' => 'Obrazac koji ste poslali sadrži sljedeće greške',

    'theme_folder_wrong' => 'Putanja do vaše teme nije ispravna. Molimo posjetite <a href="%s">Postavke URL-a i Putanje</a> i provjerite <code>Putanja do Tema</code> i <code>URL Tema</code>.',

    'unable_to_load_field_type' => 'Ne mogu učitati traženu datoteku tipa polja: %s.<br /> Provjerite da se datoteka tipa polja nalazi u direktoriju /' . SYSDIR . '/user/addons/',

    'unwritable_cache_folder' => 'Vaš cache direktorij nema odgovarajuće dozvole.<br />Za ispravak: Postavite dozvole direktorija cache (/' . SYSDIR . '/user/cache/) na 777 (ili ekvivalentno za vaš server).',

    'unwritable_config_file' => 'Vaša konfiguracijska datoteka nema odgovarajuće dozvole.<br />Za ispravak: Postavite dozvole za datoteku config (/' . SYSDIR . '/user/config/config.php) na 666 (ili ekvivalentno za vaš server).',

    'version_mismatch' => 'Verzija vaše ExpressionEngine instalacije (%s) nije konzistentna s prijavljenom verzijom (%s). <a href="' . DOC_URL . 'installation/update.html" rel="external">Molimo ponovno ažurirajte svoju ExpressionEngine instalaciju</a>.',

    'php72_intl_error' => 'Vaš <code>intl</code> PHP extension je zastario. Provjerite imate li instaliran <code>ICU 4.6</code> ili noviji.',

    'license_error' => 'Greška licence',
    'license_error_file_not_writable' => 'Cache direktorij mora biti zapisiv kako bi ExpressionEngine Pro radio',
    'license_error_file_broken' => 'Došlo je do greške prilikom provjere statusa ExpressionEngine Pro licence',

    /* Roles */
    'banned' => 'Blokiran',

    'guests' => 'Gosti',

    'members' => 'Članovi',

    'pending' => 'Na čekanju',

    'super_admins' => 'Super Administratori',

    'anonymous' => 'Anonimno',

    /* Template.php */
    'error_fix_module_processing' => 'Provjerite da je modul \'%x\' instaliran i da je \'%y\' dostupna metoda modula',

    'error_fix_install_addon' => 'Provjerite da je dodatak \'%x\' instaliran.',

    'error_fix_syntax' => 'Ispravite sintaksu u vašem predlošku.',

    'error_invalid_conditional' => 'Imate neispravnu uvjetnu naredbu u predlošku. Provjerite uvjete zbog nezatvorenog stringa, neispravnih operatora, nedostajuće }, ili nedostajućeg {/if}.',

    'error_layout_too_late' => 'Tag plugina ili modula pronađen prije deklaracije layouta. Premjestite layout tag na vrh predloška.',

    'error_multiple_layouts' => 'Pronađeno više layouta, osigurajte da imate samo jedan layout tag po predlošku',

    'error_tag_module_processing' => 'Sljedeći tag se ne može obraditi:',

    'error_tag_syntax' => 'Sljedeći tag ima sintaksnu grešku:',

    'layout_contents_reserved' => 'Ime "contents" je rezervirano za podatke predloška i ne može se koristiti kao varijabla layouta (npr. {layout:set name="contents"} ili {layout="foo/bar" contents=""}).',

    'template_load_order' => 'Redoslijed učitavanja predloška',

    'template_loop' => 'Uzrokovali ste petlju predloška zbog nepravilno ugniježđenih podpredložaka (\'%s\' pozvan rekurzivno)',

    'route_not_found' => 'Ruta predloška nije pronađena.',

    /* Email */
    'error_sending_email' => 'Trenutno nije moguće poslati email.',

    'forgotten_email_sent' => 'Ako je ova email adresa povezana s računom, upute za resetiranje lozinke su upravo poslane.',

    'no_email_found' => 'Email adresa koju ste unijeli nije pronađena u bazi podataka.',

    'password_has_been_reset' => 'Vaša lozinka je resetirana i nova je poslana na email.',

    'password_reset_flood_lock' => 'Pokušali ste previše puta resetirati lozinku danas. Provjerite inbox i spam folder za prethodne zahtjeve ili kontaktirajte administratora stranice.',

    'forgotten_username_email_sent' => 'Ako je ova email adresa povezana s računom, email s korisničkim imenom je upravo poslan.',

    'your_new_login_info' => 'Podaci za prijavu',

    /* Timezone */
    'invalid_date_format' => 'Format datuma koji ste unijeli nije valjan.',

    'invalid_timezone' => 'Vremenska zona koju ste unijeli nije valjana.',

    'no_timezones' => 'Nema vremenskih zona',

    'select_timezone' => 'Odaberite vremensku zonu',

    /* Date */
    'singular' => 'jedan',

    'less_than' => 'manje od',

    'about' => 'oko',

    'past' => 'prije %s',

    'future' => 'za %s',

    'ago' => 'prije %x',

    'year' => 'godina',

    'years' => 'godina',

    'month' => 'mjesec',

    'months' => 'mjeseci',

    'fortnight' => 'dvotjedno razdoblje',

    'fortnights' => 'dvotjedna razdoblja',

    'week' => 'tjedan',

    'weeks' => 'tjedni',

    'day' => 'dan',

    'days' => 'dani',

    'hour' => 'sat',

    'hours' => 'sati',

    'minute' => 'minuta',

    'minutes' => 'minute',

    'second' => 'sekunda',

    'seconds' => 'sekundi',

    'am' => 'prijepodne',

    'pm' => 'poslijepodne',

    'AM' => 'PRIJEPODNE',

    'PM' => 'POSLIJEPODNE',

    'Sun' => 'Ned',

    'Mon' => 'Pon',

    'Tue' => 'Uto',

    'Wed' => 'Sri',

    'Thu' => 'Čet',

    'Fri' => 'Pet',

    'Sat' => 'Sub',

    'Su' => 'N',

    'Mo' => 'P',

    'Tu' => 'U',

    'We' => 'S',

    'Th' => 'Č',

    'Fr' => 'P',

    'Sa' => 'S',

    'Sunday' => 'Nedjelja',

    'Monday' => 'Ponedjeljak',

    'Tuesday' => 'Utorak',

    'Wednesday' => 'Srijeda',

    'Thursday' => 'Četvrtak',

    'Friday' => 'Petak',

    'Saturday' => 'Subota',

    'Jan' => 'Sij',

    'Feb' => 'Velj',

    'Mar' => 'Ožu',

    'Apr' => 'Tra',

    'May' => 'Svi',

    'Jun' => 'Lip',

    'Jul' => 'Srp',

    'Aug' => 'Kol',

    'Sep' => 'Ruj',

    'Oct' => 'Lis',

    'Nov' => 'Stu',

    'Dec' => 'Pro',

    'January' => 'Siječanj',

    'February' => 'Veljača',

    'March' => 'Ožujak',

    'April' => 'Travanj',

    'May_l' => 'Svibanj',

    'June' => 'Lipanj',

    'July' => 'Srpanj',

    'August' => 'Kolovoz',

    'September' => 'Rujan',

    'October' => 'Listopad',

    'November' => 'Studeni',

    'December' => 'Prosinac',

    'UM12' => '(UTC -12:00) Otoci Baker/Howland',

    'UM11' => '(UTC -11:00) Niue',

    'UM10' => '(UTC -10:00) Havaji-Aleuti, Otoci Cook, Tahiti',

    'UM95' => '(UTC -9:30) Marquesas Otoci',

    'UM9' => '(UTC -9:00) Aljaska standardno vrijeme, Otoci Gambier',

    'UM8' => '(UTC -8:00) Pacifičko standardno vrijeme, Otok Clipperton',

    'UM7' => '(UTC -7:00) Planinsko standardno vrijeme',

    'UM6' => '(UTC -6:00) Središnje standardno vrijeme',

    'UM5' => '(UTC -5:00) Istočno standardno vrijeme, Zapadno Karibsko standardno vrijeme',

    'UM45' => '(UTC -4:30) Venecuelsko standardno vrijeme',

    'UM4' => '(UTC -4:00) Atlantsko standardno vrijeme, Istočno Karibsko standardno vrijeme',

    'UM35' => '(UTC -3:30) Newfoundland standardno vrijeme',

    'UM3' => '(UTC -3:00) Argentina, Brazil, Francuska Gvajana, Urugvaj',

    'UM2' => '(UTC -2:00) Južna Georgia/Južna Sandwich Otoci',

    'UM1' => '(UTC -1:00) Azori, Zelenortska Ostrva',

    'UTC' => '(UTC) Greenwich Mean Time, Zapadno Europsko vrijeme',

    'UP1' => '(UTC +1:00) Središnje Europsko vrijeme, Zapadno Afričko vrijeme',

    'UP2' => '(UTC +2:00) Središnje Afrčko vrijeme, Istočno Europsko vrijeme, Kalinjingradsko vrijeme',

    'UP3' => '(UTC +3:00) Istočno Afričko vrijeme, Arapsko standardno vrijeme',

    'UP35' => '(UTC +3:30) Iransko standardno vrijeme',

    'UP4' => '(UTC +4:00) Moskva, Azerbajdžan standardno vrijeme',

    'UP45' => '(UTC +4:30) Afganistan',

    'UP5' => '(UTC +5:00) Pakistan standardno vrijeme, Jekaterinburg vrijeme',

    'UP55' => '(UTC +5:30) Indijsko standardno vrijeme, Šri Lanka vrijeme',

    'UP575' => '(UTC +5:45) Nepalsko vrijeme',

    'UP6' => '(UTC +6:00) Bangladeš standardno vrijeme, Butan vrijeme, Omsk vrijeme',

    'UP65' => '(UTC +6:30) Kokosovi otoci, Mjanmar',

    'UP7' => '(UTC +7:00) Krasnojarsk vrijeme, Kambodža, Laos, Tajland, Vijetnam',

    'UP8' => '(UTC +8:00) Australijsko zapadno standardno vrijeme, Peking vrijeme, Irkutsk vrijeme',

    'UP875' => '(UTC +8:45) Australijsko središnje zapadno standardno vrijeme',

    'UP9' => '(UTC +9:00) Japan standardno vrijeme, Korejsko standardno vrijeme, Jakutsk vrijeme',

    'UP95' => '(UTC +9:30) Australijsko središnje standardno vrijeme',

    'UP10' => '(UTC +10:00) Australijsko istočno standardno vrijeme, Vladivostok vrijeme',

    'UP105' => '(UTC +10:30) Otok Lord Howe',

    'UP11' => '(UTC +11:00) Magadan vrijeme, Otoci Solomon, Vanuatu',

    'UP115' => '(UTC +11:30) Norfolk Otok',

    'UP12' => '(UTC +12:00) Fidži, Gilbert Otoci, Kamčatka vrijeme, Novi Zeland standardno vrijeme',

    'UP1275' => '(UTC +12:45) Chatham otoci standardno vrijeme',

    'UP13' => '(UTC +13:00) Samoa vremenska zona, Phoenix Otoci vrijeme, Tonga',

    'UP14' => '(UTC +14:00) Line Otoci',

    /* Cookies */

    'cookie_csrf_token' => 'CSRF Token',
    'cookie_csrf_token_desc' => 'Sigurnosni cookie koji identificira korisnika i sprječava Cross Site Request Forgery napade.',

    'cookie_flash' => 'Flash podaci',
    'cookie_flash_desc' => 'Poruke povratne informacije korisnika, šifrirane radi sigurnosti.',

    'cookie_remember' => 'Zapamti me',
    'cookie_remember_desc' => 'Određuje hoće li korisnik biti automatski prijavljen prilikom posjete stranici.',

    'cookie_sessionid' => 'ID Sesije',
    'cookie_sessionid_desc' => 'ID sesije, koristi se za povezivanje prijavljenog korisnika s njegovim podacima.',

    'cookie_visitor_consents' => 'Pristanak posjetitelja',
    'cookie_visitor_consents_desc' => 'Spremanje odgovora na zahtjeve za pristanak za neprijavljene posjetitelje',

    'cookie_last_activity' => 'Zadnja Aktivnost',
    'cookie_last_activity_desc' => 'Bilježi vrijeme posljednjeg učitavanja stranice. Koristi se za izračun aktivnih sesija.',

    'cookie_last_visit' => 'Zadnja Posjeta',
    'cookie_last_visit_desc' => 'Datum posljednje posjete korisnika, temeljen na cookie-u last_activity. Može se prikazati kao statistika za članove i koristiti u forumu i komentarima za prikaz nepročitanih tema.',

    'cookie_anon' => 'Anonimiziraj',
    'cookie_anon_desc' => 'Određuje hoće li korisničko ime biti prikazano u popisu trenutno prijavljenih članova.',

    'cookie_tracker' => 'Tracker',
    'cookie_tracker_desc' => 'Sadrži posljednjih 5 pregledanih stranica, šifrirano radi sigurnosti. Obično se koristi za povratak na obrazac ili poruku o grešci.',

    'cookie_viewtype' => 'Tip prikaza Filemanager-a',
    'cookie_viewtype_desc' => 'Određuje tip prikaza u Filemanageru (tablica ili thumbnail prikaz)',

    'cookie_cp_last_site_id' => 'Zadnji CP Site ID',
    'cookie_cp_last_site_id_desc' => 'MSM cookie koji pokazuje posljednju pristupljenu stranicu u Control Panelu.',

    'cookie_collapsed_nav' => 'Sakrij Navigaciju',
    'cookie_collapsed_nav_desc' => 'Određuje hoće li bočna navigacija u Control Panelu biti sažeta.',

    'cookie_secondary_sidebar' => 'Stanje sekundarne bočne trake',
    'cookie_secondary_sidebar_desc' => 'Određuje hoće li sekundarna bočna traka u Control Panelu biti sažeta za svaki odgovarajući odjeljak.',

    'cookie_ee_cp_viewmode' => 'CP Prikaz',
    'cookie_ee_cp_viewmode_desc' => 'Određuje način prikaza za Control Panel.',

    'cp' => 'Control Panel',

    'adapter_local' => 'Lokalno',

);

// EOF
