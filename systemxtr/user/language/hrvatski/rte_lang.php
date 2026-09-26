<?php

$lang = array(

    // Settings
    'rte_file_browser' => 'Preglednik datoteka',
    'rte_file_browser_desc' => 'Koji preglednik datoteka se treba koristiti prilikom odabira slika i datoteka iz vaših RTE polja?',

    // Configs
    'rte_clone' => 'Kloniraj',
    'rte_no_configs' => 'Trenutno nema konfiguracija.',

    // Edit Config
    'rte_basic_settings' => 'Osnovne postavke',
    'rte_create_config' => 'Kreiraj novu konfiguraciju',
    'rte_edit_config' => 'Uredi konfiguraciju',
    'rte_config_settings' => 'Postavke konfiguracije',
    'rte_config_name' => 'Naziv konfiguracije',
    'rte_toolbar' => 'Prilagodi alatnu traku',
    'custom_stylesheet' => 'Prilagođeni CSS',
    'custom_stylesheet_desc' => 'CSS predložak sa stilovima koji će se primijeniti na polja koja koriste ovaj set alata. Svi stilovi automatski će imati prefiks klase alatnog seta.',
    'custom_javascript' => 'Dodatni JavaScript',
    'custom_javascript_rte_desc' => 'JS predložak koji se uključuje u polja koja koriste ovaj set alata. Obično se koristi za dodatne pluginove kod naprednih konfiguracija.',
    'rte_min_height' => 'Minimalna visina',
    'rte_min_height_desc' => 'Unesite broj piksela ili ostavite prazno',
    'rte_max_height' => 'Maksimalna visina',
    'rte_max_height_desc' => 'Unesite broj piksela ili ostavite prazno',
    'rte_limiter' => 'Ograničenje znakova',
    'rte_limiter_desc' => 'Ograničite broj znakova koje korisnik može unijeti.',
    'rte_upload_dir' => 'Mapa za prijenos',
    'rte_advanced_settings' => 'Napredne postavke',
    'rte_advanced_config' => 'Napredna konfiguracija',
    'rte_advanced_config_desc' => 'Uredite konfiguraciju izravno u JSON formatu',
    'rte_config_json' => 'Konfiguracija JSON',
    'rte_config_json_desc' => 'Zamjenjuje vizualno kreirani alatni set',
    'rte_advanced_config_warning' => '<p><b>Upozorenje</b>: <b class="no">Samo za napredne korisnike.</b> Pažljivo koristite ovu opciju i provjerite svoj rad.</p><p>Pružanje neispravne konfiguracije može učiniti RTE polja nedostupnim.</p><p>Provjerite dokumentaciju uređivača: <a href="https://ckeditor.com/docs/ckeditor5/latest/installation/getting-started/configuration.html" target="_blank">CKEditor</a>, <a href="https://imperavi.com/redactor/docs/settings/" target="_blank">Redactor</a>, <a href="https://imperavi.com/redactorx/docs/settings/" target="_blank">RedactorX</a>.</p><p>Neke opcije možda neće biti dostupne ili će biti implementirane drugačije. Preporučujemo korištenje pune konfiguracije kao početne baze.</p>',
    'rte_config_saved' => 'Konfiguracija spremljena!',
    'rte_config_saved_desc' => 'Vaša konfiguracija je uspješno spremljena.',
    'rte_custom_ckeditor_build' => 'Koristiti prilagođenu CKEditor verziju?',
    'rte_custom_ckeditor_build_desc' => 'Omogućuje korištenje prilagođene CKEditor verzije s dodatnim pluginovima. Ako je uključeno, RTE instance koje koriste CKEditor bit će kreirane pomoću <code>themes/user/rte/javascript/ckeditor.js</code>. Pogledajte Upute za korisnika za <a href="' . DOC_URL . 'add-ons/rte.html#ckeditor" rel="external">upute za izradu</a>.',

    // Delete Config
    'rte_delete_config' => 'Obriši konfiguraciju',
    'rte_delete_config_confirm' => 'Jeste li sigurni da želite trajno obrisati ovu konfiguraciju?',
    'rte_config_deleted' => 'Konfiguracija obrisana',
    'rte_config_deleted_desc' => 'Vaša konfiguracija je uspješno obrisana.',

    // -------------------------------------------
    //  Field Settings
    // -------------------------------------------

    'rte_editor_config' => 'Konfiguracija uređivača',
    'rte_edit_configs' => 'Uredi&nbsp;konfiguracije',
    'rte_defer' => 'Odgoditi inicijalizaciju uređivača?',
    'rte_defer_desc' => 'Ako odaberete “Da”, RTE neće inicijalizirati javascript polja dok se polje ne klikne.',

    // RTE

    'available_tool_sets' => 'Dostupni setovi alata',

    'btn_save_settings' => 'Spremi postavke',

    'choose_tools' => 'Odaberite alate',

    'configuration' => 'Konfiguracija',

    'create_new' => 'Kreiraj novo',

    'create_tool_set' => 'Kreiraj set alata',

    'create_tool_set_header' => 'Kreiraj <abbr title="Rich Text Editor">RTE</abbr> set alata',

    'edit_tool_set' => 'Uredi set alata',

    'edit_tool_set_header' => 'Uredi <abbr title="Rich Text Editor">RTE</abbr> set alata',

    'no_tool_sets' => 'Nema pronađenih setova alata',

    'rte_module_description' => '',

    'rte_module_name' => 'Rich Text Editor',

    'status' => 'Status',

    'tool_set' => 'Set alata',

    'tool_set_name' => 'Naziv',

    'tool_type' => 'Vrsta uređivača',

    /* Headings */
    'create_new_toolset' => 'Kreiraj novi set alata',

    'edit_my_toolset' => 'Uredi moj set alata',

    'edit_toolset' => 'Uredi set alata',

    'my_toolset' => 'Moj set alata',

    'nav_rte_settings' => 'Postavke Rich Text Editora',

    'nav_rte_settings_short_desc' => 'Upravljajte alatima i setovima alata Rich Text Editora',

    'rte_prefs' => 'Postavke Rich Text Editora',

    'rte_settings' => 'Postavke stranice',

    'tools' => 'Alati',

    'toolsets' => 'Setovi alata',

    /* Snippets */
    'cancel' => 'Odustani',

    'delete' => 'Obriši',

    'tool' => 'Alat',

    'toolset' => 'Set alata',

    /* Flashes */

    'cannot_remove_default_toolset' => 'Zadani RTE set alata se ne može ukloniti',

    'disable_fail_desc' => 'Sljedeći setovi alata <b>nisu</b> onemogućeni',

    'disable_success_desc' => 'Sljedeći setovi alata su onemogućeni',

    'enable_fail_desc' => 'Sljedeći setovi alata <b>nisu</b> omogućeni',

    'enable_success_desc' => 'Sljedeći setovi alata su omogućeni',

    'name_required' => 'Naziv seta alata je obavezan.',

    'remove_fail_desc' => 'Sljedeći setovi alata <b>nisu</b> uklonjeni',

    'remove_success_desc' => 'Sljedeći setovi alata su uklonjeni',

    'settings_error' => 'Greška pri spremanju postavki',

    'settings_error_desc' => 'Vaše postavke Rich Text Editora nisu spremljene. Pokušajte ponovo.',

    'settings_saved' => 'Postavke spremljene',

    'settings_saved_desc' => 'Vaše postavke Rich Text Editora su spremljene.',

    'tool_updated' => 'Alat ažuriran',

    'toolset_created' => 'Set alata kreiran',

    'toolset_created_desc' => '<b>%s</b> je uspješno kreiran.',

    'toolset_updated_desc' => '<b>%s</b> je uspješno ažuriran.',

    'toolset_deleted' => 'Set alata uspješno obrisan.',

    'toolset_edit_failed' => 'Set alata nije moguće otvoriti za uređivanje.',

    'toolset_error' => 'Greška seta alata',

    'toolset_error_desc' => 'Nismo mogli spremiti set alata, provjerite i ispravite greške ispod.',

    'toolset_json_error_desc' => 'Napredna konfiguracija nije valjani JSON.',

    'toolset_not_deleted' => 'Set alata nije moguće obrisati.',

    'toolset_update_failed' => 'Ažuriranje seta alata nije uspjelo. Pokušajte ponovo.',

    'toolset_updated' => 'Set alata ažuriran',

    'toolsets_removed' => 'Setovi alata uklonjeni',

    'toolsets_removed_desc' => '%d setova alata je uklonjeno.',

    'toolsets_updated' => 'Setovi alata ažurirani',

    'unique_name_required' => 'Naziv seta alata mora biti jedinstven.',

    'valid_name_required' => 'Naziv seta alata ne smije sadržavati posebne znakove.',

    'valid_url_required' => 'Potrebna je valjana URL adresa.',

    /* Labels */
    'available_tools' => 'Dostupni alati (trenutno ne koriste se)',

    'default_toolset' => 'Zadani <abbr title="Rich Text Editor">RTE</abbr> set alata',

    'default_toolset_details' => 'Prikazano za korisnike koji nisu kreirali vlastiti ili odabrali drugi.',

    'enable_rte_for_field' => 'Omogući Rich Text Editor',

    'enable_rte_globally' => 'Omogući Rich Text Editor',

    'enable_rte_in_forum' => 'Omogući Rich Text Editor za forume?',

    'enable_rte_myaccount' => 'Omogući Rich Text Editor',

    'rte_image_caption' => 'Natpis slike:',

    'rte_relationship' => 'Veza',

    'rte_selection_error' => 'Prvo odaberite tekst ili slike.',

    'rte_title' => 'Naslov',

    'rte_url' => 'URL',

    'tools_in_toolset' => 'U ovom setu alata',

    'toolset_builder_instructions' => 'Odaberite jedan ili više alata i povucite ih na željenu poziciju.',

    'toolset_builder_label' => 'Koji alati trebaju biti dostupni u ovom setu alata?',

    'toolset_name' => 'Naziv seta alata',

    /* tool names */

    'paragraph_rte' => 'Odlomak',

    'heading_h1_rte' => 'Naslov H1',

    'heading_h2_rte' => 'Naslov H2',

    'heading_h3_rte' => 'Naslov H3',

    'heading_h4_rte' => 'Naslov H4',

    'heading_h5_rte' => 'Naslov H5',

    'heading_h6_rte' => 'Naslov H6',

    'bold_rte' => 'Podebljano',

    'italic_rte' => 'Kurziv',

    'deleted_rte' => 'Precrtano',

    'lists_rte' => 'Liste',

    'image_rte' => 'Slika',

    'imageposition_rte' => 'Pozicija slike',

    'imageresize_rte' => 'Promjena veličine slike',

    'file_rte' => 'Datoteka',

    'strikethrough_rte' => 'Precrtano',

    'underline_rte' => 'Podcrtano',

    'subscript_rte' => 'Indeks',

    'sub_rte' => 'Indeks',

    'superscript_rte' => 'Eksponent',

    'sup_rte' => 'Eksponent',

    'code_rte' => 'Kod',

    'blockcode_rte' => 'Kod',

    'blockquote_rte' => 'Blok citat',

    'quote_rte' => 'Citat',

    'heading_rte' => 'Naslov',

    'format_rte' => 'Format',

    'inlineformat_rte' => 'Format',

    'removeFormat_rte' => 'Ukloni formatiranje',

    'removeformat_rte' => 'Ukloni formatiranje',

    'undo_rte' => 'Poništi',

    'redo_rte' => 'Ponovi',

    'numberedList_rte' => 'Numerirana lista',

    'ol_rte' => 'Numerirana lista',

    'bulletedList_rte' => 'Lista s oznakama',

    'ul_rte' => 'Lista s oznakama',

    'outdent_rte' => 'Smanji uvlačenje',

    'indent_rte' => 'Povećaj uvlačenje',

    'link_rte' => 'Link',

    'horizontalrule_rte' => 'Horizontalna crta',

    'line_rte' => 'Horizontalna crta',

    'filemanager_rte' => 'Odabir slike ili datoteke',

    'insertImage_rte' => 'Umetni sliku putem URL-a',

    'insertTable_rte' => 'Tablica',

    'selector_rte' => 'Klasa & ID',

    'table_rte' => 'Tablica',

    'mediaEmbed_rte' => 'Multimedija',

    'htmlEmbed_rte' => 'HTML',

    'html_rte' => 'HTML',

    'alignment_rte' => 'Poravnanje',

    'alignment:left_rte' => 'Poravnaj lijevo',

    'alignment:right_rte' => 'Poravnaj desno',

    'alignment:center_rte' => 'Poravnaj centrirano',

    'alignment:justify_rte' => 'Poravnaj obostrano',

    'horizontalLine_rte' => 'Horizontalna linija',

    'specialCharacters_rte' => 'Posebni znakovi',

    'specialchars_rte' => 'Posebni znakovi',

    'readMore_rte' => 'Separator "Pročitaj više"',

    'readmore_rte' => 'Separator "Pročitaj više"',

    'fontColor_rte' => 'Boja fonta',

    'fontBackgroundColor_rte' => 'Pozadina fonta',

    'codeBlock_rte' => 'Blok koda',

    'sourceEditing_rte' => 'Uređivanje izvornog koda',

    'findAndReplace_rte' => 'Pronađi i zamijeni',

    'open_in_new_tab' => 'Otvori u novoj kartici',

    'source_rte' => 'Pogledaj izvor',

    'showBlocks_rte' => 'Prikaži blokove',

    'video_rte' => 'Video',

    'fullscreen_rte' => 'Cijeli ekran',

    'properties_rte' => 'Svojstva',

    'textdirection_rte' => 'Smjer teksta',

    'codemirror_rte' => 'Codemirror',

    'widget_rte' => 'Widget',

    'inlinestyle_rte' => 'Stil',

    'rte_plugins' => 'Dodaci',

    'rte_toolbar_buttons' => 'Tipke alatne trake',

    'rte_definedlinks_rte' => 'Linkovi stranica',

    'filebrowser_rte' => 'Preglednik datoteka',

    'counter_rte' => 'Brojač',

    'pages_rte' => 'Stranice',

    'fontcolor_rte' => 'Boja teksta',

    'rte_spellcheck' => 'Provjera pravopisa',

    'rte_spellcheck_desc' => 'Omogući provjeru pravopisa u editoru (mora biti omogućeno i u pregledniku)',

    'browser' => 'Preglednik',

    'grammarly' => 'Grammarly',

    'rte_control_bar' => 'Prikaži kontrolnu traku?',

    'rte_control_bar_desc' => 'Kontrolna traka je svedeni meni prikazan s lijeve strane fokusiranog elementa s nekim uobičajenim radnjama',

    'rte_format' => 'Opcije formatiranja',

    'rte_format_desc' => 'Oznake dopuštene u padajućem izborniku Format',

    'add_rte' => 'Dodaj',

    'shortcut_rte' => 'Prečac',

    'embed_rte' => 'Ugradi',

    'mark_rte' => 'Označi',

    'kbd_rte' => 'Tastatura',

    'pre_rte' => 'Preformatirano',

    'rte_show_context' => 'Prikaži kontekstnu traku?',

    'rte_show_context_desc' => 'Kontekstna traka se pojavljuje kada je tekst odabran',

    'rte_context' => 'Kontekstna traka',

    'rte_show_addbar' => 'Prikaži addbar?',

    'rte_show_addbar_desc' => 'Addbar se pojavljuje kada se klikne tipka Dodaj',

    'rte_addbar' => 'Addbar',

    'rte_show_topbar' => 'Prikaži gornju traku?',

    'rte_show_topbar_desc' => 'Prikazuje se desno od glavne alatne trake',

    'rte_topbar' => 'Gornja traka',

    'rte_toolbar_sticky' => 'Fiksiraj alatnu traku?',

    'rte_show_main_toolbar_desc' => 'Drži alatnu traku uvijek vidljivom prilikom skrolanja',

    'rte_show_main_toolbar' => 'Prikaži glavnu alatnu traku',

    'rte_show_main_toolbar_desc' => 'Može biti onemogućeno dok funkcionalnost ostaje dostupna putem drugih alatnih traka ili tipkovničkih prečaca',

    'rte_main_toolbar' => 'Glavna alatna traka',

    '' => ''
);
