<?php

$lang = array(
    // All generic CLI lang entries
    'cli_error_no_command_given'                   => 'Nije unesena nijedna naredba. Pokušajte `php eecli.php list` za puni popis naredbi.',
    'cli_error_command_not_found'                  => 'Naredba nije pronađena. Pokušajte `php eecli.php list` za puni popis naredbi.',
    'cli_error_ee_not_installed'                   => 'ExpressionEngine trenutno nije instaliran.',
    'cli_error_is_required'                        => 'Polje je obavezno.',
    'cli_error_is_required_field'                  => 'Polje je obavezno. Polje: ',
    'cli_option_help'                              => 'Pogledajte izbornik pomoći za zadanu naredbu',
    'cli_option_help_json'                         => 'Pogledajte izbornik pomoći za zadanu naredbu u JSON formatu',
    'cli_error_the_specified_addon_does_not_exist' => 'Navedeni dodatak ne postoji',
    'cli_error_cli_disabled'                       => 'ExpressionEngine CLI je trenutno onemogućen. Za korištenje CLI-ja, morate ga omogućiti u postavkama.',
    'cli_no_addons'                                => 'Nema dostupnih dodataka',
    'cli_table_no_results'                         => 'Nema pronađenih rezultata.',

    // Lang entries for command cache:clear
    'command_cache_clear_description'              => 'Briše sve ExpressionEngine predmemorije',
    'command_cache_clear_summary'                  => '',
    'command_cache_clear_option_type'              => 'Vrsta predmemorije za brisanje (zadano: sve)',
    'command_cache_clear_cache_does_not_exist'     => 'Predmemorija ne postoji. Koristite --help za pregled dostupnih predmemorija.',
    'command_cache_clear_caches_cleared'           => ' predmemorija je obrisana!',

    // Lang entries for command addons:install
    'command_addons_install_description'            => 'Instalira dodatak i sve njegove komponente',
    'command_addons_install_summary'                => '',
    'command_addons_install_begin'                  => 'Instalacija dodatka uskoro počinje',
    'command_addons_install_ask_addon'              => 'Koji dodatak želite instalirati?',
    'command_addons_install_in_progress'            => 'Izvršava se instalacija dodatka %s',
    'command_addons_install_complete'               => '%s je uspješno instaliran',
    'command_addons_install_option_addon'           => 'Kratko ime dodatka',

    // Lang entries for command addons:uninstall
    'command_addons_uninstall_description'            => 'Deinstalira dodatak i sve njegove komponente',
    'command_addons_uninstall_summary'                => '',
    'command_addons_uninstall_begin'                  => 'Deinstalacija dodatka uskoro počinje',
    'command_addons_uninstall_ask_addon'              => 'Koji dodatak želite deinstalirati?',
    'command_addons_uninstall_in_progress'            => 'Izvršava se deinstalacija dodatka %s',
    'command_addons_uninstall_complete'               => '%s je uspješno deinstaliran',
    'command_addons_uninstall_option_addon'           => 'Kratko ime dodatka',

    // Lang entries for command addons:update
    'command_addons_update_description'            => 'Ažurira dodatak na najnoviju verziju',
    'command_addons_update_summary'                => '',
    'command_addons_update_begin'                  => 'Ažuriranje dodatka uskoro počinje.',
    'command_addons_update_ask_addon'              => 'Koji dodatak želite ažurirati?',
    'command_addons_update_in_progress'            => 'Izvršava se ažuriranje dodatka %s...',
    'command_addons_update_complete'               => '%s je uspješno ažuriran.',
    'command_addons_update_all_complete'           => 'Svi dodaci su uspješno ažurirani.',
    'command_addons_update_option_addon'           => 'Kratko ime dodatka',
    'command_addons_update_option_all'             => 'Ažurira sve dodatke koji imaju dostupna ažuriranja',

    // Lang entries for command addons:list
    'command_addons_list_description'                => 'Prikazuje popis dodataka',
    'command_addons_list_summary'                    => '',
    'command_addons_list'                            => 'Sljedeći dodaci %s',
    'command_addons_option_available'                => 'su dostupni',
    'command_addons_option_installed'                => 'su instalirani',
    'command_addons_option_uninstalled'              => 'nisu instalirani',
    'command_addons_option_update'                   => 'mogu se ažurirati',
    'command_addons_list_table_header_name'          => 'Naziv',
    'command_addons_list_table_header_shortname'     => 'Kratko ime',
    'command_addons_list_table_header_version'       => 'Verzija',
    'command_addons_list_table_header_installed'     => 'Instalirano',

    // Lang entries for command list
    'command_list_description'                  => 'Prikazuje sve dostupne naredbe',
    'command_list_summary'                      => 'Ovo daje puni popis svih naredbi',
    'command_list_all_available_commands'       => 'Sve dostupne naredbe:',
    'command_list_run_with_help'                => 'Pokrenite naredbu s --help za više informacija',
    'command_list_command_header'               => 'Naredba',
    'command_list_description_header'           => 'Opis',

    // Lang entries for command make:addon
    'command_make_addon_description'            => 'Stvara novi dodatak',
    'command_make_addon_summary'                => 'Interaktivno generira EE dodatak izravno u vašem korisničkom direktoriju',
    'command_make_addon_lets_build_addon'       => 'Krenimo s izradom vašeg dodatka!',
    'command_make_addon_description_question'   => 'Opis?',
    'command_make_addon_version_question'       => 'Verzija?',
    'command_make_addon_author_question'        => 'Autor?',
    'command_make_addon_author_url_question'    => 'URL autora?',
    'command_make_addon_have_settings_question' => 'Ima postavke?',
    'command_make_addon_lets_build'             => 'Krenimo s izradom!',
    'command_make_addon_created_successfully'   => 'Vaš dodatak je uspješno kreiran!',
    'command_make_addon_what_hooks_to_use'      => 'Koje hooks želite koristiti? (Više: https://docs.expressionengine.com/latest/development/extensions.html)',
    'command_make_addon_ext_hooks'              => 'Extension hooks:',
    'command_make_addon_ft_compatibility'       => 'Kompatibilnost s Fieldtype-om?',
    'command_make_addon_what_type_of_addon'     => 'Koju vrstu dodatka želite kreirati?',
    'command_make_addon_select_proper_addon'    => 'Odaberite ispravnu vrstu dodatka.',
    'command_make_addon_what_is_name'           => 'Koje je ime vašeg dodatka?',
    'command_make_addon_does_your'              => 'Da li vaš ',
    'command_make_addon_addon_name_required'    => 'Ime dodatka je obavezno.',
    // make:addon options
    'command_make_addon_option_extension'       => 'Stvori ekstenziju',
    'command_make_addon_option_plugin'          => 'Stvori plugin',
    'command_make_addon_option_fieldtype'       => 'Stvori fieldtype',
    'command_make_addon_option_module'          => 'Stvori modul',
    'command_make_addon_option_typography'      => 'Treba koristiti tipografiju plugina',
    'command_make_addon_option_has'             => 'Dodatak ima postavke (da/ne)',
    'command_make_addon_option_version'         => 'Verzija dodatka',
    'command_make_addon_option_description'     => 'Opis dodatka',
    'command_make_addon_option_author'          => 'Autor dodatka',
    'command_make_addon_option_author_url'      => 'URL autora dodatka',
    'command_make_addon_option_services'        => 'Servisi za kreiranje. Višestruka opcija.',
    'command_make_addon_option_models'          => 'Modeli za kreiranje. Višestruka opcija.',
    'command_make_addon_option_commands'        => 'Naredbe za kreiranje. Višestruka opcija.',
    'command_make_addon_option_consents'        => 'Pristanak. Višestruka opcija.',
    'command_make_addon_option_cookies'         => 'Kolačići za kreiranje, s dvotočkom između imena i vrijednosti (npr. ime:vrijednost). Višestruka opcija.',
    'command_make_addon_option_hooks'           => 'Hooks u uporabi. Višestruka opcija.',
    'command_make_addon_option_compatibility_mode'  => 'Generiraj dodatak kompatibilan s ExpressionEngine verzijama nižim od 7.2.0 i 6.4.0',

    // Lang entries for command make:command
    'command_make_command_description'          => 'Stvara novu CLI naredbu za dodatak',
    'command_make_command_summary'              => 'Interaktivno generira CLI naredbu za postojeći treći dodatak',
    'command_make_command_lets_build_command'   => 'Krenimo s izradom vaše naredbe!',
    'command_make_command_ask_description'      => 'Opis naredbe?',
    'command_make_command_ask_signature'        => 'Potpis naredbe? (npr. make:magic)',
    'command_make_command_lets_build'           => 'Krenimo!',
    'command_make_command_created_successfully' => 'Vaša naredba je uspješno kreirana!',
    'command_make_command_ask_command_name'     => 'Ime naredbe?',
    'command_make_command_ask_addon'            => 'Koji dodatak želite dodati ovoj naredbi?',
    // make:command options
    'command_make_command_option_addon'         => 'Mapa trećeg dodatka u koji želite dodati naredbu.',
    'command_make_command_option_description'   => 'Opis naredbe',
    'command_make_command_option_signature'     => 'Potpis naredbe (npr. make:magic)',

    // Lang entries for command make:migration
    'command_make_migration_description'                        => 'Stvara novu migraciju',
    'command_make_migration_summary'                            => 'Generira novu migraciju za core ili dodatak',
    'command_make_migration_what_is_migration_name'             => 'Koje je ime vaše migracije?',
    'command_make_migration_no_name_specified'                  => 'Nije navedeno ime migracije. Za pomoć s ovom naredbom koristite --help',
    'command_make_migration_using_migration_name'               => 'Koristi se ime migracije:      ',
    'command_make_migration_table_creating_migration'           => 'Kreiranje migracije: ',
    'command_make_migration_table_migration_action'             => '  Radnja migracije: ',
    'command_make_migration_table_type_name'                    => '  Tip imena:        ',
    'command_make_migration_table_class_name'                   => '  Ime klase:       ',
    'command_make_migration_table_file_location'                => '  Lokacija datoteke:    ',
    'command_make_migration_table_template_name'                => '  Ime predloška:    ',
    'command_make_migration_successfully_wrote_file'            => 'Nova datoteka migracije je uspješno kreirana.',
    'command_make_migration_what_table_is_migration_for'        => 'Za koju tablicu je ova migracija?',
    'command_make_migration_ask_migration_action'               => 'Koja je radnja migracije',
    'command_make_migration_ask_migration_category'             => 'Koja je kategorija migracije',
    'command_make_migration_where_to_generate_migration'        => 'Gdje želite generirati ovu migraciju? (ExpressionEngine ili postojeći dodatak)',

    // make:migration options
    'command_make_migration_option_name'                        => 'Ime migracije',
    'command_make_migration_option_table'                       => 'Ime tablice',
    'command_make_migration_option_status'                      => 'Ime statusa',
    'command_make_migration_option_location'                    => 'Lokacija migracije. Trenutne opcije su ExpressionEngine ili kratko ime dodatka koji je trenutno instaliran. Zadano: ExpressionEngine.',
    'command_make_migration_option_create'                      => 'Navedite da je naredba create',
    'command_make_migration_option_update'                      => 'Navedite da je naredba update',

    // make:migration Error message
    'command_make_migration_missing_required_template_variable' => 'Nedostaje obavezna varijabla za parsiranje predloška migracije: ',

    // Lang entries for command make:prolet
    'command_make_prolet_description'                  => 'Stvara novi prolet za dodatak',
    'command_make_prolet_summary'                      => 'Interaktivno generira EE Prolet za postojeći treći dodatak',
    'command_make_prolet_lets_build_prolet'            => 'Krenimo s izradom novog prolet-a!',
    'command_make_prolet_ask_prolet_name'              => 'Koje je ime prolet-a?',
    'command_make_prolet_ask_addon'                    => 'U koji dodatak se prolet dodaje?',
    'command_make_prolet_ask_description'              => 'Koji je opis Prolet-a?',
    'command_make_prolet_building_prolet'              => 'Izrada Proleta.',
    'command_make_prolet_created_successfully'         => 'Prolet je uspješno kreiran!',
    'command_make_prolet_ask_widget_name'              => 'Koje je ime widgeta?',
    'command_make_prolet_generating_widget'            => 'Generiranje widgeta.',
    'command_make_prolet_widget_created_successfully'  => 'Widget je uspješno kreiran!',
    'command_make_prolet_error_addon_must_have_module' => 'Za generiranje prolet-a, dodatak mora imati modul.',
    'command_make_prolet_error_addon_must_have_icon'   => 'Za generiranje prolet-a, dodatak mora imati ikonu. Za generiranje zadane ikone, koristite --generate-icon.',

    // make:prolet options
    'command_make_prolet_option_addon'                 => 'Mapa trećeg dodatka u koji želite dodati prolet.',
    'command_make_prolet_option_description'           => 'Opis prolet-a',
    'command_make_prolet_option_has_widget'            => 'Kreiraj widget za dodatak nakon generiranja prolet-a (opcionalno)',
    'command_make_prolet_option_widget_name'           => 'Ime widgeta',
    'command_make_prolet_option_generate_icon'         => 'Generiraj zadanu ikonu dodatka prilikom kreiranja prolet-a',

    // Lang entries for command backup:database
    'command_backup_database_description'                  => 'Izrada sigurnosne kopije baze podataka',
    'command_backup_database_summary'                      => 'Sigurnosna kopija ExpressionEngine baze podataka',
    'command_backup_database_beginning_database_backup'    => 'Počinje izrada sigurnosne kopije baze podataka.',
    'command_backup_database_backing_up_database'          => 'Izrada sigurnosne kopije baze podataka...',
    'command_backup_database_failed_with_error'            => 'Sigurnosna kopija baze podataka nije uspjela, poruka o pogrešci:',
    'command_backup_database_completed_successfully'       => 'Sigurnosna kopija baze podataka je uspješno završena.',
    'command_backup_database_backup_path'                  => 'Put do sigurnosne kopije: %s',

    // backup:database options
    'command_backup_database_option_absolute_path'   => 'Apsolutna putanja do direktorija u kojem će se pohraniti sigurnosna kopija baze',
    'command_backup_database_option_relative_path'   => 'Putanja do sigurnosne kopije baze, relativno prema cache folderu',
    'command_backup_database_option_file_name'       => 'Ime SQL datoteke koja će se spremiti',
    'command_backup_database_option_speed'           => 'Brzina izrade sigurnosne kopije baze (1-10). Niža brzina omogućava više vremena između naredbi baze. Zadana brzina je 5.',

    // Lang entries for command make:action
    'command_make_action_description'                  => 'Stvara novu akciju za dodatak',
    'command_make_action_summary'                      => 'Interaktivno generira EE akciju za postojeći treći dodatak',
    'command_make_action_lets_build_action'            => 'Krenimo s izradom nove akcije!',
    'command_make_action_ask_action_name'              => 'Koje je ime akcije?',
    'command_make_action_ask_addon'                    => 'U koji dodatak se akcija dodaje?',
    'command_make_action_building_action'              => 'Izrada akcije.',
    'command_make_action_created_successfully'         => 'Akcija je uspješno kreirana!',
    'command_make_action_error_addon_must_have_module' => 'Za generiranje akcije, dodatak mora imati modul.',
    'command_make_action_installing_action'            => 'Instalacija akcije...',
    'command_make_action_installed_action'             => 'Akcija je instalirana!',
    'command_make_action_addon_must_be_installed_to_install_action' => 'Akcija nije mogla biti instalirana. Dodatak mora biti prvo instaliran. Migracija akcije će se izvršiti nakon instalacije dodatka.',

    // make:action options
    'command_make_action_option_addon'              => 'Mapa trećeg dodatka u koji želite dodati akciju.',
    'command_make_action_option_install'            => 'Instaliraj ovu akciju nakon kreiranja. Ovo izvršava sve trenutne migracije za navedeni dodatak. Dodatak mora biti prvo instaliran.',

    // Lang entries for command make:template-tag
    'command_make_template_tag_description'                  => 'Stvara novi tag za dodatak',
    'command_make_template_tag_summary'                      => 'Interaktivno generira EE tag za postojeći treći dodatak',
    'command_make_template_tag_lets_build_tag'               => 'Krenimo s izradom novog taga!',
    'command_make_template_tag_ask_tag_name'                 => 'Koje je ime taga?',
    'command_make_template_tag_ask_addon'                    => 'U koji dodatak se tag dodaje?',
    'command_make_template_tag_building_tag'                 => 'Izrada taga.',
    'command_make_template_tag_created_successfully'         => 'Tag je uspješno kreiran!',
    'command_make_template_tag_error_addon_must_have_module' => 'Za generiranje taga, dodatak mora imati modul.',

    // make:template-tag options
    'command_make_template_tag_option_addon'                 => 'Mapa trećeg dodatka u koji želite dodati tag.',

    // Lang entries for command make:sidebar
    'command_make_sidebar_description'                  => 'Stvara kontrolnu ploču sidebar za dodatak',
    'command_make_sidebar_summary'                      => 'Generira sidebar za postojeći treći dodatak',
    'command_make_sidebar_lets_build_sidebar'           => 'Krenimo s izradom sidebar-a dodatka!',
    'command_make_sidebar_ask_addon'                    => 'U koji dodatak se sidebar dodaje?',
    'command_make_sidebar_building_sidebar'             => 'Izrada sidebar-a.',
    'command_make_sidebar_created_successfully'         => 'Sidebar je uspješno kreiran!',

    // make:sidebar options
    'command_make_sidebar_option_addon'                 => 'Mapa trećeg dodatka u koji želite dodati sidebar.',

    // Lang entries for command make:extension-hook
    'command_make_extension_hook_description'                  => 'Implementira EE extension hook u dodatku',
    'command_make_extension_hook_summary'                      => 'Interaktivno implementira EE extension hook u postojeći treći dodatak',
    'command_make_extension_hook_lets_build_extension_hook'    => 'Krenimo s implementacijom extension hook-a!',
    'command_make_extension_hook_ask_extension_hook_name'      => 'Koje hook-ove želite koristiti? (Više: https://docs.expressionengine.com/latest/development/extensions.html)',
    'command_make_extension_hook_ask_addon'                    => 'U koji dodatak se dodaje extension hook?',
    'command_make_extension_hook_building_extension_hook'      => 'Izrada extension hook-a.',
    'command_make_extension_hook_created_successfully'         => 'Extension hook je uspješno kreiran!',
    'command_make_extension_hook_installing_hook'              => 'Instalacija extension hook-a...',
    'command_make_extension_hook_installed_hook'               => 'Extension hook je instaliran!',
    'command_make_extension_hook_addon_must_be_installed_to_install_hook' => 'Extension hook nije mogao biti instaliran. Dodatak mora biti prvo instaliran. Migracija extension hook-a će se izvršiti nakon instalacije dodatka.',

    // make:extension-hook options
    'command_make_extension_hook_option_addon'                 => 'Mapa trećeg dodatka u koji želite dodati extension hook.',
    'command_make_extension_hook_option_install'               => 'Instaliraj ovaj extension hook nakon kreiranja. Ovo izvršava sve trenutne migracije za navedeni dodatak. Dodatak mora biti prvo instaliran.',

    // Lang entries for command make:fieldtype
    'command_make_fieldtype_description'                  => 'Generira fieldtype za navedeni treći dodatak',
    'command_make_fieldtype_summary'                      => 'Interaktivno generira fieldtype u postojećem trećem dodatku',
    'command_make_fieldtype_lets_build_fieldtype'         => 'Krenimo s implementacijom fieldtype-a!',
    'command_make_fieldtype_ask_fieldtype_name'           => 'Koje je ime fieldtype-a?',
    'command_make_fieldtype_ask_addon'                    => 'U koji dodatak se fieldtype dodaje?',
    'command_make_fieldtype_building_fieldtype'           => 'Izrada fieldtype-a.',
    'command_make_fieldtype_created_successfully'         => 'Fieldtype je uspješno kreiran!',

    // make:fieldtype options
    'command_make_fieldtype_option_addon'                 => 'Mapa trećeg dodatka u koji želite dodati fieldtype.',

    // Lang entries for command config:config
    'command_config_config_description'             => 'Ažurira vrijednosti u config.php datoteci',
    'command_config_config_summary'                 => 'Omogućuje ažuriranje vrijednosti u config-u',
    'command_config_config_ask_config_variable'     => 'Koju config stavku želite postaviti?',
    'command_config_config_ask_config_value'        => 'Koju vrijednost želite postaviti?',
    'command_config_config_updating_config_variable' => 'Ažuriranje config stavke...',
    'command_config_config_config_value_saved'      => 'Config stavka je spremljena.',

    // config:config options
    'command_config_config_option_config_variable'  => 'Config stavka koja se mijenja',
    'command_config_config_option_value'            => 'Vrijednost koju treba postaviti za config stavku',

    // Lang entries for command config:env
    'command_config_env_description'             => 'Ažurira env vrijednosti u .env.php datoteci',
    'command_config_env_summary'                 => 'Omogućuje ažuriranje env vrijednosti',
    'command_config_env_ask_config_variable'     => 'Koju env stavku želite postaviti?',
    'command_config_env_ask_config_value'        => 'Koju vrijednost želite postaviti?',
    'command_config_env_updating_config_variable' => 'Ažuriranje env stavke...',
    'command_config_env_config_value_saved'      => 'Env stavka je spremljena.',

    // config:env options
    'command_config_env_option_config_variable'  => 'Env stavka koja se postavlja/izmjenjuje',
    'command_config_env_option_value'            => 'Vrijednost koju treba postaviti za env stavku',

    // Lang entries for command make:cp-route
    'command_make_cp_route_description'                  => 'Generira kontrolnu ploču rutu za navedeni treći dodatak',
    'command_make_cp_route_summary'                      => 'Interaktivno generira kontrolnu ploču rutu u postojećem trećem dodatku',
    'command_make_cp_route_lets_build_mcp_route'         => 'Krenimo s izradom rute za kontrolnu ploču!',
    'command_make_cp_route_ask_route_name'               => 'Koje je ime rute?',
    'command_make_cp_route_ask_addon'                    => 'U koji dodatak se ruta dodaje?',
    'command_make_cp_route_building_mcp_route'           => 'Izrada rute za kontrolnu ploču.',
    'command_make_cp_route_created_successfully'         => 'Ruta za kontrolnu ploču je uspješno kreirana!',

    // make:cp-route options
    'command_make_cp_route_option_addon'                 => 'Mapa trećeg dodatka u koji želite dodati Mcp rutu.',

    // Lang entries for command make:jump
    'command_make_jump_description'                      => 'Generira jump menu datoteku za navedeni treći dodatak.',
    'command_make_jump_summary'                          => 'Interaktivno generira jump menu datoteku u postojećem trećem dodatku',
    'command_make_cp_jumps'                              => 'Kreirajmo Jump datoteku dodatka!',
    'command_make_cp_jumps_ask_addon'                    => 'U koji dodatak se dodaje Jumps datoteka?',
    'command_make_cp_jumps_building_jumps'               => 'Izrada Jumps datoteke dodatka.',
    'command_make_cp_jumps_created_successfully'         => 'Jumps datoteka je uspješno kreirana! Napomena: možda ćete morati očistiti cache preglednika prije nego što vidite nove stavke jump menija',

    // make:jump options
    'command_make_jump_file_addon'                 => 'Mapa trećeg dodatka u koji želite dodati Jump Menu datoteku.',

    // Lang entries for command make:widget
    'command_make_widget_description'                 => 'Generira widgete za postojeće dodatke.',
    'command_make_widget_lets_build_widget'           => 'Kreirajmo widget!',
    'command_make_widget_ask_widget_name'             => 'Koje je ime widgeta?',
    'command_make_widget_ask_addon'                   => 'Za koji dodatak je ovo?',
    'command_make_widget_building_widget'             => 'Izrada widgeta.',
    'command_make_widget_created_successfully'        => 'Widget je uspješno kreiran!',
    'command_make_widget_option_addon'                => 'Ime dodatka',

    // Lang entries for command make:model
    'command_make_model_description'                            => 'Stvara novi model za dodatak',
    'command_make_model_summary'                                => 'Interaktivno generira EE model za postojeći treći dodatak',
    'command_make_model_lets_build_model'                       => 'Kreirajmo vaš model!',
    'command_make_model_lets_build'                             => 'Kreirajmo!',
    'command_make_model_created_successfully'                   => 'Vaš model je uspješno kreiran!',
    'command_make_model_ask_model_name'                         => 'Ime modela?',
    'command_make_model_ask_addon'                              => 'U koji dodatak želite dodati ovaj model?',
    // make:model options
    'command_make_model_option_addon' => 'Mapa trećeg dodatka u koji želite dodati model.',

    // Lang entries for command migrate
    'command_migrate_description'                  => 'Izvršava navedene migracije (sve, core ili dodaci)',
    'command_migrate_summary'                      => 'Prolazi kroz core migracije i migracije dodataka te izvršava sve migracije koje ranije nisu izvršene. Ako se pokreću sve migracije, prvo se izvršavaju core migracije, zatim migracije dodataka. Kada se migracije izvode za više dodataka, sve migracije za svaki dodatak grupiraju se zajedno i izvode zajedno',
    'command_migrate_all_migrations_ran'           => 'Sve dostupne migracije su već izvršene.',
    'command_migrate_what_is_location'             => 'Koja je lokacija vaše migracije?',
    'command_migrate_error_please_select_location' => 'Molimo odaberite lokaciju migracije koristeći --core, --everything, --addons ili --addon=ime_dodatka.',
    'command_migrate_migrated'                     => 'Migrirano: ',
    'command_migrate_all_migrations_completed'     => 'Sve migracije su uspješno izvršene!',
    // migrate options
    'command_migrate_option_steps'                 => 'Odredite broj migracija koje treba izvršiti',
    'command_migrate_option_everything'            => 'Pokreni sve migracije. Core se izvršava prvo, zatim sve migracije dodataka, jednu po jednu.',
    'command_migrate_option_all'                   => 'Pokreni sve migracije. Alias za --everything',
    'command_migrate_option_core'                  => 'Pokreni samo core migracije. Isključuje sve migracije dodataka.',
    'command_migrate_option_addon'                 => 'Pokreni migraciju samo za navedeni dodatak.',
    'command_migrate_option_addons'                => 'Pokreni migraciju samo za navedeni dodatak.',

    // Lang entries for command migrate:addon
    'command_migrate_addon_description'               => 'Izvršava migracije dodataka',
    'command_migrate_addon_summary'                   => 'Prolazi kroz mape dodataka i izvršava sve migracije koje ranije nisu izvršene. Ako se pokreću svi dodaci, migracije se grupiraju po dodatku i izvršavaju zajedno',
    'command_migrate_addon_all_migrations_ran'        => 'Sve dostupne migracije dodataka su već izvršene.',
    'command_migrate_addon_ask_location_of_migration' => 'Koja je lokacija vaše migracije?',
    'command_migrate_addon_error_no_location_set'     => 'Molimo odaberite lokaciju migracije koristeći --everything ili --addon=ime_dodatka.',
    'command_migrate_addon_migrated'                  => 'Migrirano: ',
    'command_migrate_addon_all_migrations_completed'  => 'Sve migracije su uspješno izvršene!',
    // migrate:addon options
    'command_migrate_addon_option_steps'              => 'Odredite broj migracija koje treba izvršiti',
    'command_migrate_addon_option_everything'         => 'Pokreni sve migracije dodataka',
    'command_migrate_addon_option_all'                => 'Pokreni sve migracije dodataka. Alias za --everything',
    'command_migrate_addon_option_addon'              => 'Pokreni migraciju samo za navedeni dodatak.',

    // Lang entries for command migrate:all
    'command_migrate_all_description'              => 'Pokreće core migracije, zatim migracije svakog dodatka',
    'command_migrate_all_summary'                  => 'Prolazi kroz core migracije i migracije dodataka te izvršava sve migracije koje ranije nisu izvršene. Core migracije se izvršavaju prvo, zatim migracije dodataka. Kada se migracije izvode za više dodataka, sve migracije za svaki dodatak grupiraju se zajedno i izvode zajedno',
    'command_migrate_all_migrated'                 => 'Migrirano: ',
    'command_migrate_all_all_migrations_completed' => 'Sve migracije su uspješno izvršene!',
    // migrate:all options
    'command_migrate_all_option_steps'             => 'Odredite broj migracija koje treba izvršiti',

    // Lang entries for command migrate:core
    'command_migrate_core_description'                          => 'Izvršava core migracije',
    'command_migrate_core_summary'                              => 'Prolazi kroz core migracije i izvršava sve migracije koje ranije nisu izvršene',
    'command_migrate_core_migrated'                             => 'Migrirano: ',
    'command_migrate_core_all_migrations_completed'             => 'Sve migracije su uspješno izvršene!',
    // migrate:core options
    'command_migrate_core_option_steps'                         => 'Odredite broj migracija koje treba izvršiti',

    // Lang entries for command migrate:reset
    'command_migrate_reset_description'                         => 'Vraća sve migracije unatrag',
    'command_migrate_reset_summary'                             => 'Vraća sve migracije odjednom',
    'command_migrate_reset_no_migrations_to_rollback'           => 'Nema migracija za povrat.',
    'command_migrate_reset_rolling_back'                        => 'Vraćanje: ',
    'command_migrate_reset_all_migrations_rolled_back'          => 'Sve migracije su uspješno vraćene!',

    // Lang entries for command migrate:rollback
    'command_migrate_rollback_description'                      => 'Vraća najnoviju grupu migracija',
    'command_migrate_rollback_summary'                          => 'Uzimanje najnovije grupe migracija i vraćanje svih',
    'command_migrate_rollback_no_migrations_to_rollback'        => 'Nema migracija za povrat.',
    'command_migrate_rollback_rolling_back'                     => 'Vraćanje: ',
    'command_migrate_rollback_migrations_executed_successfully' => ' migracije su uspješno izvršene.',
    'command_migrate_rollback_all_migrations_rolled_back'       => 'Sve migracije u grupi su uspješno vraćene!',
    // migrate:rollback options
    'command_migrate_rollback_option_steps'                     => 'Odredite broj migracija koje treba vratiti unatrag',

    // Lang entries for command update
    'command_update_description'                                => 'Ažurira ExpressionEngine',
    'command_update_summary'                                    => 'Pokreće sve dostupne ExpressionEngine nadogradnje',
    'command_update_is_already_up_to_date'                      => ' je već ažuriran!',
    'command_update_new_version_available'                      => 'Dostupna je nova verzija ExpressionEngine:',
    'command_update_confirm_upgrade'                            => 'Želite li nadograditi?',
    'command_update_not_run'                                    => 'Nadogradnja nije izvršena.',
    'command_update_success'                                    => 'Uspjeh! Kreirajte nešto sjajno!',
    'command_update_indicated_upgrade_all_addons'               => 'Naznačili ste da želite nadograditi sve dodatke.',
    'command_update_confirm_addon_upgrade'                      => 'Jeste li sigurni? Ovo može biti destruktivna akcija.',
    'command_update_addon_update_halted'                        => 'Nadogradnja dodatka zaustavljena',
    'command_update_getting_info_from_local_env'                => 'Dohvaćanje informacija o nadogradnji iz lokalnog okruženja',
    'command_update_getting_info_from_ee_com'                   => 'Dohvaćanje informacija o nadogradnji s ExpressionEngine.com',
    'command_update_updater_failed'                             => 'Ažuriranje nije uspjelo',
    'command_update_updating_to_version'                        => 'Ažuriranje na verziju ',
    'command_update_failed_on_version'                          => 'Nije uspjelo na verziji ',
    'command_update_error_updater_failed_missing_version'       => 'Ažuriranje nije uspjelo zbog nedostatka verzije. Molimo ažurirajte UpgradeMap. Verzija: ',
    'command_update_missing_avatar_path_message'                => 'Proces nadogradnje će neuspjeti bez postavljene putanje avatara.',
    'command_update_enter_full_avatar_path'                     => 'Unesite punu putanju avatara',
    // update options
    'command_update_option_rollback'                            => 'Vrati posljednju nadogradnju',
    'command_update_option_verbose'                             => 'Detaljan ispis',
    'command_update_option_microapp'                            => 'Pokreni kao microapp',
    'command_update_option_step'                                => 'Korak u procesu (parametar je obavezan)',
    'command_update_option_no_bootstrap'                        => 'Pokreće bez bootstrap-a',
    'command_update_option_force_addon_upgrades'                => 'Automatski pokreće sve nadogradnje dodataka na kraju nadogradnje (napredno)',
    'command_update_option_y'                                   => 'Preskoči sve potvrde. Nemojte to raditi.',
    'command_update_option_skip_cleanup'                        => 'Preskoči korake čišćenja nakon nadogradnje',

    // Lang entries for command sync:file-usage
    'command_sync_file_usage_description'     => 'Sinkronizira korištenje svih datoteka',
    'command_sync_file_usage_summary'         => '',
    'command_sync_file_usage'                 => 'Ažuriranje korištenja datoteka.',
    'command_sync_file_usage_done'            => 'Korištenje datoteka uspješno ažurirano.',

    // Lang entries for command sync:reindex
    'command_reindex_description'                               => 'Reindeksiranje sadržaja',
    'command_reindex_summary'                                   => 'Pretraživi sadržaj može postati zastario ako ste nedavno promijenili svojstva nekih polja. Reindeksiranje će ponovno popuniti podatke koje koriste kompleksna polja u pretraživanju i Entry Manager-u.',
    'command_reindex_option_site_id'                            => 'ID web-mjesta. Preskočite ovaj parametar za reindeksiranje sadržaja na svim web-mjestima',
    
    // Lang entries for command sync:upload-directory
    'command_sync_upload_directory_description'     => 'Sinkronizira direktorij za upload',
    'command_sync_upload_directory_summary'         => '',
    'command_sync_upload_directory_started'         => 'Sinkronizacija u tijeku',
    'command_sync_upload_directory_option_id'       => 'ID direktorija za upload',
    'command_sync_upload_directory_ask_id'          => 'Unesite ID direktorija za upload',
    'command_sync_upload_directory_option_regenerate_manipulations' => 'Manipulacije slike koje treba ponovno generirati. Zarezom odvojeni popis ID-eva manipulacija. \'all\' za regeneriranje svih manipulacija, prazno za preskakanje.',
    'command_sync_upload_directory_ask_regenerate_manipulations' => 'Unesite ID-eve manipulacija za regeneriranje odvojene zarezom. Unesite \'all\' za regeneriranje svih manipulacija, prazno za preskakanje.',
    'cli_error_sync_upload_directory_base_path_is_empty' => '{base_path} se koristi u putanji Upload Directory, ali je prazan.',

    // Lang entries for command update:prepare
    'command_update_prepare_description'                        => 'Priprema web-mjesto za nadogradnju korištenjem ovih datoteka',
    'command_update_prepare_summary'                            => 'Ova naredba kopira sve datoteke potrebne za nadogradnju u drugo ExpressionEngine web-mjesto i restrukturira ga',
    'command_update_prepare_preparing_upgrade_for_site'         => 'Priprema nadogradnju za web-mjesto.',
    'command_update_prepare_running_ee_upgrade'                 => 'Pokretanje EE nadogradnje',
    'command_update_prepare_process_complete'                   => 'Proces završen!',
    'command_update_prepare_running_preflight_hooks'            => 'Pokretanje preflight hook-ova',
    'command_update_prepare_running_postflight_hooks'           => 'Pokretanje postflight hook-ova',
    'command_update_prepare_how_things_are_configured'          => 'Evo kako su stvari konfigurirane:',
    'command_update_prepare_notify_moving_files_to_tmp'         => 'Premještamo X datoteku u tmp/X i Y u system/Y',
    'command_update_prepare_make_sure_you_have_backups'         => 'Provjerite imate li backup!',
    'command_update_prepare_are_you_sure_you_want_to_proceed'   => 'Jeste li sigurni da želite nastaviti?',
    'command_update_prepare_upgrade_aborted'                    => 'Nadogradnja prekinuta',
    'command_update_prepare_notify_also_upgrade_ee_after'       => 'Također ste naznačili da želite nadograditi EE nakon premještanja ovih datoteka.',
    'command_update_prepare_what_is_path_to_upgrade_config'     => 'Koja je putanja do vašeg upgrade.config.php? (zadano je SYSPATH, trenutno ',
    'command_update_prepare_custom_config_not_found'            => 'Prilagođeni config nije pronađen.',
    'command_update_prepare_database_file_found_move_to_config' => 'Pronašli smo database datoteku. Molimo premjestite ove informacije u config.php',
    // update:prepare options
    'command_update_prepare_option_upgrade_ee'                  => 'Pokreni nadogradnju nakon premještanja datoteka',
    'command_update_prepare_option_force_add_on_upgrade'        => 'Nakon nadogradnje EE, pokreće nadogradnje dodataka',
    'command_update_prepare_option_old_base_path'               => 'Apsolutna putanja starog web-mjesta',
    'command_update_prepare_option_new_base_path'               => 'Apsolutna putanja novog web-mjesta',
    'command_update_prepare_option_old_public_path'             => 'Apsolutna putanja javnog dijela starog web-mjesta',
    'command_update_prepare_option_new_public_path'             => 'Apsolutna putanja javnog dijela novog web-mjesta',
    'command_update_prepare_option_no_config_file'              => 'Ignorira config datoteku i ne provjerava je',
    'command_update_prepare_option_ee_version'                  => 'Trenutno web-mjesto ',
    'command_update_prepare_option_should_move_system_path'     => 'Da li proces nadogradnje treba premjestiti staru system mapu u novo web-mjesto',
    'command_update_prepare_option_old_system_path'             => 'Apsolutna putanja stare system mape',
    'command_update_prepare_option_new_system_path'             => 'Apsolutna putanja nove system mape',
    'command_update_prepare_option_should_move_template_path'   => 'Da li proces nadogradnje treba premjestiti staru template mapu u novo web-mjesto',
    'command_update_prepare_option_old_template_path'           => 'Apsolutna putanja stare template mape',
    'command_update_prepare_option_new_template_path'           => 'Apsolutna putanja nove template mape',
    'command_update_prepare_option_should_move_theme_path'      => 'Da li proces nadogradnje treba premjestiti staru theme mapu u novo web-mjesto',
    'command_update_prepare_option_old_theme_path'              => 'Apsolutna putanja stare user theme mape',
    'command_update_prepare_option_new_theme_path'              => 'Apsolutna putanja nove user theme mape',
    'command_update_prepare_option_run_preflight_hooks'         => 'Da li proces nadogradnje treba pokrenuti definirane preflight hook-ove',
    'command_update_prepare_option_run_postflight_hooks'        => 'Da li proces nadogradnje treba pokrenuti definirane postflight hook-ove',
    'command_update_prepare_option_temp_directory'              => 'Direktorij u kojem radimo čaroliju',

    // Lang entries for command update:run-hook
    'command_update_run_hook_description'                       => 'Pokreće update hook-ove iz vašeg upgrade.config.php',
    'command_update_run_hook_summary'                           => 'Ovo će pokrenuti jedan od preflight ili postflight hook-ova definirane u upgrade.config.php. Ovo može biti destruktivna akcija, stoga koristite s oprezom',
    'command_update_run_hook_running'                           => 'Pokretanje: ',
    'command_update_run_hook_hook_not_found'                    => 'Hook nije pronađen: ',
    'command_update_run_hook_success'                           => 'Uspjeh!',
    'command_update_run_hook_what_is_path_to_upgrade_config'    => 'Koja je putanja do vašeg upgrade.config.php? (zadano SYSPATH)',
    'command_update_run_hook_custom_config_not_found'           => 'Prilagođeni config nije pronađen.',

    // Lang entries for command sync:conditional-fields
    'command_sync_conditional_fields_name'              => 'Sinkronizacija logike uvjetnih polja',
    'command_sync_conditional_fields_description'       => 'Sinkronizira uvjetnu logiku unosa kanala',
    'command_sync_conditional_fields_summary'           => 'Provjerava svaki unos kanala kako bi se uvjerio da je uvjetna logika točna. Ako nije, ažurira logiku i sprema unos.',

    // sync:conditional-fields options
    'command_sync_conditional_fields_option_channel_id' => 'ID kanala za sinkronizaciju. Zadano na sve kanale',
    'command_sync_conditional_fields_option_verbose'    => 'Detaljno',
    'command_sync_conditional_fields_option_clear'      => 'Očisti',

    // sync:conditional-fields output
    'command_sync_conditional_fields_sync_utility'      => 'Alat za sinkronizaciju uvjetne logike',
    'command_sync_conditional_fields_syncing'           => 'Sinkronizacija %d unosa kanala',
    'command_sync_conditional_fields_current_entry'     => 'Trenutni unos kanala: %s',
    'command_sync_conditional_fields_entries_processed' => "Obrađeni unosi: %d\t%s\t%s",
    'command_sync_conditional_fields_sync_complete'     => "Sinkronizacija završena: %d unosa\t%s\t%s",
    'command_sync_conditional_fields_cleared_all_hidden_fields' => "Svi skriveni podaci su očišćeni",
    'command_sync_conditional_fields_database_info'             => "Baza podataka: %d upita u %f sekundi",

    // generate:templates
    'command_generate_templates_summary' => 'Stvara predloške temeljene na postojećoj strukturi podataka',
    'command_generate_templates_description' => 'Koristeći unaprijed definirane stubove koje pruža ExpressionEngine ili dodaci, generator predložaka će generirati spremne predloške za vašu web-stranicu.',
    'command_generate_templates_list_generators' => 'Prikaži dostupne generatore predložaka',
    'command_generate_templates_list_themes' => 'Prikaži dostupne teme',
    'command_generate_templates_show_template_content' => 'Prikaži sadržaj predloška bez spremanja',
    'command_generate_templates_show_template_code' => 'Ispiši samo generirani kod predloška (suppressira sve ostale izlaze)',
    'command_generate_templates_listing_generators' => 'Dostupni generatori predložaka:',
    'command_generate_templates_listing_themes' => 'Dostupne teme:',
    'command_generate_templates_ask_generator' => 'Koji generator želite koristiti?',
    'command_generate_templates_invalid_generator' => 'Unesen je neispravan generator.',
    'separate_choices_commas' => 'Odvojite višestruke opcije zarezima',
    'command_generate_templates_building_templates' => 'Generiranje predložaka...',

    // channels:list
    'command_channels_list_description' => 'Prikazuje sve kanale u sustavu',
    'command_channels_list_summary' => 'Prikazuje sve kanale s njihovim detaljima u različitim formatima',
    'command_channels_list_header' => 'Kanali:',
    'command_channels_list_no_channels_found' => 'Nema pronađenih kanala.',
    'command_channels_list_total' => 'Ukupno kanala: %d',
    'command_channels_list_id' => 'ID',
    'command_channels_list_name' => 'Ime',
    'command_channels_list_title' => 'Naslov',
    'command_channels_list_entries' => 'Unosi',
    'command_channels_list_last_entry' => 'Zadnji unos',
    'command_channels_list_never' => 'Nikad',
    'command_channels_list_ask_site' => 'Koji ID web-mjesta?',
    'command_channels_list_ask_format' => 'Format izlaza (table, json, csv)?',
    'command_channels_list_option_site' => 'ID web-mjesta za prikaz kanala',
    'command_channels_list_option_format' => 'Format izlaza: table, json, ili csv',
    'command_channels_list_option_channel_id' => 'Filtriraj po određenom ID-u kanala',

    // version
    'command_version_description' => 'Prikazuje informacije o verziji ExpressionEngine',
    'command_version_summary' => 'Prikazuje trenutnu verziju ExpressionEngine i informacije o sustavu',
    'command_version_header' => 'Informacije o verziji ExpressionEngine',
    'command_version_expressionengine' => 'Verzija ExpressionEngine: %s',
    'command_version_build' => 'Build: %s',
    'command_version_php' => 'PHP verzija: %s',
    'command_version_option_format' => 'Format izlaza: simple, ili json',
    'command_version_option_field' => 'Ispiši samo određeno polje: version, build, ili php_version',
    'command_version_invalid_field' => 'Neispravno polje: %s. Dostupna polja: version, build, php_version',

    // fields:list
    'command_fields_list_description' => 'Prikazuje sva polja kanala u sustavu',
    'command_fields_list_summary' => 'Prikazuje sva polja kanala s njihovim detaljima u različitim formatima',
    'command_fields_list_header' => 'Polja kanala:',
    'command_fields_list_no_fields_found' => 'Nema pronađenih polja.',
    'command_fields_list_total' => 'Ukupno polja: %d',
    'command_fields_list_id' => 'ID',
    'command_fields_list_name' => 'Ime',
    'command_fields_list_label' => 'Oznaka',
    'command_fields_list_type' => 'Tip',
    'command_fields_list_required' => 'Obavezno',
    'command_fields_list_search' => 'Pretraži',
    'command_fields_list_hidden' => 'Skriveno',
    'command_fields_list_ask_site' => 'Koji ID web-mjesta?',
    'command_fields_list_ask_format' => 'Format izlaza (table, json, csv)?',
    'command_fields_list_ask_type' => 'Filtrirati po tipu polja?',
    'command_fields_list_ask_group' => 'Filtrirati po imenu grupe polja?',
    'command_fields_list_ask_channel_id' => 'Filtrirati po ID-u kanala?',
    'command_fields_list_option_site' => 'ID web-mjesta za prikaz polja',
    'command_fields_list_option_format' => 'Format izlaza: table, json, ili csv',
    'command_fields_list_option_type' => 'Filtrirati po tipu polja (npr. text, textarea, select)',
    'command_fields_list_option_group' => 'Filtrirati po imenu ili kratkom imenu grupe polja',
    'command_fields_list_option_channel_id' => 'Filtrirati po ID-u kanala',
    'command_fields_list_option_field_id' => 'Filtrirati po određenom ID-u polja',

    // fieldtypes:list
    'command_fieldtypes_list_description' => 'Prikazuje sve dostupne tipove polja u sustavu',
    'command_fieldtypes_list_summary' => 'Prikazuje sve tipove polja koje pružaju instalirani i standardni dodaci',
    'command_fieldtypes_list_header' => 'Tipovi polja:',
    'command_fieldtypes_list_no_fieldtypes_found' => 'Nema pronađenih tipova polja.',
    'command_fieldtypes_list_total' => 'Ukupno tipova polja: %d',
    'command_fieldtypes_list_shortname' => 'Kratko ime',
    'command_fieldtypes_list_name' => 'Ime',
    'command_fieldtypes_list_addon' => 'Dodatak',
    'command_fieldtypes_list_option_format' => 'Format izlaza: table, json, ili csv',
    'command_fieldtypes_list_option_installed' => 'Prikaži samo tipove polja iz instaliranih dodataka',
    'command_fieldtypes_list_option_addon' => 'Filtrirati po kratkom imenu dodatka, odvojeno zarezom',
    'command_fieldtypes_list_option_short' => 'Filtrirati po kratkom imenu tipa polja, odvojeno zarezom',

);

// EOF