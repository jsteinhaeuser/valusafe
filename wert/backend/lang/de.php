<?php
/**
 * Deutsche Übersetzungen
 * 
 * STRUKTUR:
 * - nav_* = Navigation
 * - btn_* = Buttons
 * - field_* = Formular-Felder
 * - msg_* = Nachrichten (Erfolg/Fehler)
 * - tab_* = Tabellen-Header
 * - txt_* = Allgemeine Texte
 */

return [
    // ============================================================================
    // NAVIGATION
    // ============================================================================
    'nav_logout' => 'Abmelden',
    
    // Export Dropdown
    
    // ============================================================================
    // ALLGEMEIN
    // ============================================================================
    'app_title' => 'Wertsachen-Inventarverwaltung',
    'logged_in_as' => 'Angemeldet als',
    'session_expires_in' => 'Session läuft ab in',
    
    // ============================================================================
    // BUTTONS
    // ============================================================================
    'btn_save' => 'Speichern',
    'btn_cancel' => 'Abbrechen',
    'btn_delete' => 'Löschen',
    'btn_edit' => 'Bearbeiten',
    'btn_back' => 'Zurück',
    'btn_search' => 'Suchen',
    'btn_filter' => 'Filter',
    'btn_reset' => 'Zurücksetzen',
    'btn_add' => 'Hinzufügen',
    'btn_upload' => 'Hochladen',
    'btn_download' => 'Download',
    'btn_view' => 'Ansicht',
    'btn_close' => 'Schließen',
    'btn_confirm' => 'Bestätigen',
    'btn_apply' => 'Anwenden',
    
    // ============================================================================
    // FORMULAR-FELDER
    // ============================================================================
    'field_name' => 'Name',
    'field_price' => 'Preis',
    'field_purchase_date' => 'Kaufdatum',
    'field_listed_date' => 'Hier gelistet am',
    'field_category' => 'Kategorie',
    'field_location' => 'Ort',
    'field_notes' => 'Notizen',
    'field_image' => 'Bild',
    'field_created_by' => 'Erstellt von',
    'field_username' => 'Benutzername',
    'field_password' => 'Passwort',
    'field_role' => 'Berechtigung',
    'field_search' => 'Suchen',
    
    // Placeholder
    'placeholder_search' => 'Suchen...',
    'placeholder_select' => '-- Bitte wählen --',
    'placeholder_all_locations' => 'Alle Orte',
    'placeholder_all_categories' => 'Alle Kategorien',
    
    // ============================================================================
    // TABELLEN-HEADER
    // ============================================================================
    'tab_name' => 'Name',
    'tab_image' => 'Bild',
    'tab_category' => 'Kategorie',
    'tab_location' => 'Ort',
    'tab_price' => 'Preis',
    'tab_purchase_date' => 'Kaufdatum',
    'tab_created_by' => 'Erstellt von',
    'tab_documents' => 'Dok.',
    'tab_notes' => 'Notizen',
    'tab_username' => 'Benutzername',
    'tab_created_at' => 'Erstellt am',
    'tab_last_login' => 'Letzter Login',
    
    // ============================================================================
    // STATISTIKEN
    // ============================================================================
    'stats_entries' => 'Einträge',
    'stats_entry' => 'Eintrag',
    'stats_total_value' => 'Gesamtwert',
    'stats_average' => 'Durchschnitt',
    'stats_maximum' => 'Maximum',
    
    // ============================================================================
    // NACHRICHTEN - ERFOLG
    // ============================================================================
    'msg_saved_success' => 'Erfolgreich gespeichert',
    'msg_deleted_success' => 'Erfolgreich gelöscht',
    'msg_created_success' => 'Erfolgreich erstellt',
    'msg_updated_success' => 'Erfolgreich aktualisiert',
    'msg_uploaded_success' => 'Erfolgreich hochgeladen',
    
    // ============================================================================
    // NACHRICHTEN - FEHLER
    // ============================================================================
    'msg_error_generic' => 'Ein Fehler ist aufgetreten',
    'msg_error_required' => 'Dieses Feld ist erforderlich',
    'msg_error_file_too_large' => 'Datei ist zu groß',
    'msg_error_invalid_type' => 'Ungültiger Dateityp',
    'msg_error_upload_failed' => 'Upload fehlgeschlagen',
    'msg_error_not_found' => 'Nicht gefunden',
    
    // ============================================================================
    // BESTÄTIGUNGEN
    // ============================================================================
    'confirm_delete' => 'Wirklich löschen?',
    'confirm_delete_item' => 'Diesen Gegenstand wirklich löschen?',
    'confirm_delete_user' => 'Diesen Benutzer wirklich löschen?',
    'confirm_discard_changes' => 'Änderungen verwerfen?',
    
    // ============================================================================
    // SEITEN-TITEL
    // ============================================================================
    'page_dashboard' => 'Dashboard',
    'page_overview' => 'Übersicht',
    'page_new_item' => 'Neuer Gegenstand',
    'page_edit_item' => 'Gegenstand bearbeiten',
    'page_settings' => 'Einstellungen',
    'page_activities' => 'Aktivitäten',
    'page_login' => 'Anmeldung',
    
    // ============================================================================
    // ROLLEN
    // ============================================================================
    'role_admin' => 'Admin',
    'role_editor' => 'Editieren',
    'role_viewer' => 'Nur Lesen',
    
    // ============================================================================
    // SPALTEN-AUSWAHL
    // ============================================================================
    'columns_title' => 'Spalten anpassen',
    'columns_preset_minimal' => 'Minimal',
    'columns_preset_standard' => 'Standard',
    'columns_preset_full' => 'Vollständig',
    'columns_preset_inventory' => 'Inventur',
    'columns_save' => 'Speichern',
    'columns_reset' => 'Standard',
    
    // ============================================================================
    // SONSTIGES
    // ============================================================================
    'no_entries' => 'Keine Einträge gefunden',
    'no_image' => 'Kein Bild',
    'never' => 'Noch nie',
    'loading' => 'Lädt...',
    
    // ============================================================================
    // DASHBOARD
    // ============================================================================
    'dashboard_title' => 'Dashboard',
    'dashboard_welcome' => 'Willkommen zurück',
    'dashboard_welcome_message' => 'Hier ist deine Übersicht.',
    'dashboard_total_value' => 'Gesamtwert',
    'dashboard_total_value_desc' => 'Aller Gegenstände',
    'dashboard_total_items' => 'Gegenstände',
    'dashboard_total_items_desc' => 'Im Inventar',
    'dashboard_avg_value' => 'Durchschnittswert',
    'dashboard_avg_value_desc' => 'Pro Gegenstand',
    'dashboard_max_value' => 'Wertvollster',
    'dashboard_max_value_desc' => 'Einzelgegenstand',
    'dashboard_top_category' => 'Wertvollste Kategorie',
    'dashboard_item_singular' => 'Gegenstand',
    'dashboard_item_plural' => 'Gegenstände',
    'dashboard_top_5_valuable' => 'Top 5 Wertvollste',
    'dashboard_recently_added' => 'Zuletzt hinzugefügt',
    'dashboard_no_items' => 'Noch keine Gegenstände vorhanden',
    'dashboard_value_by_category' => 'Wert pro Kategorie',
    'dashboard_quick_actions' => 'Schnellzugriff',
    'dashboard_new_item' => 'Neuer Gegenstand',
    'dashboard_all_items' => 'Alle Gegenstände',
    'dashboard_pdf_export' => 'PDF Export',
    'dashboard_create_backup' => 'Backup erstellen',
    'dashboard_categories' => 'Kategorien',
    'dashboard_locations' => 'Orte',
    'dashboard_settings' => 'Einstellungen',
    'dashboard_users' => 'Benutzer',
    'dashboard_recent_activity' => 'Letzte Aktivitäten',
    'dashboard_view_all_activity' => 'Alle Aktivitäten anzeigen',
    'dashboard_time_minutes' => 'vor %d Min',
    'dashboard_time_hours' => 'vor %d Std',
    'dashboard_time_days' => 'vor %d Tagen',
    
    // ============================================================================
    // ADD & EDIT FORMULARE
    // ============================================================================
    'form_new_item' => 'Neuer Gegenstand',
    'form_edit_item' => 'Gegenstand bearbeiten',
    'form_name' => 'Name',
    'form_location' => 'Ort',
    'form_category' => 'Kategorie',
    'form_purchase_date' => 'Kaufdatum',
    'form_listed_date' => 'Hier gelistet am',
    'form_price' => 'Preis (€)',
    'form_created_by' => 'Erstellt von',
    'form_notes' => 'Notizen',
    'form_image' => 'Bild',
    'form_current_image' => 'Aktuelles Bild',
    'form_upload_new_image' => 'Neues Bild hochladen',
    'form_no_image' => 'Kein Bild vorhanden',
    
    // Upload Zone
    'upload_drag_or_click' => 'Bild hierher ziehen oder klicken',
    'upload_max_size' => 'Max. %s MB',
    'upload_formats' => 'JPG, PNG, GIF, WebP',
    'upload_replaces_current' => 'Aktuelles Bild wird ersetzt, wenn ein neues hochgeladen wird',
    'upload_camera' => 'Kamera',
    'upload_gallery' => 'Galerie',
    'upload_remove_image' => 'Bild entfernen',
    'upload_remove_new_image' => 'Neues Bild entfernen',
    'upload_error_size' => 'Datei zu groß! Maximum: %s MB',
    'upload_error_type' => 'Ungültiger Dateityp! Erlaubt: JPG, PNG, GIF, WebP',
    
    // Formular-Buttons
    'form_save' => 'Speichern',
    'form_cancel' => 'Abbrechen',
    'form_documents' => 'Dokumente',
    
    // Hilfe-Texte
    'form_help_created_by_default' => 'Standard: %s (kann geändert werden in den Einstellungen)',
    'form_help_created_by_empty' => 'Lassen Sie leer für automatische Verwendung des aktuellen Benutzers',
    
    // Erfolgs-Meldungen
    'msg_item_created' => 'Gegenstand erfolgreich hinzugefügt',
    'msg_item_updated' => 'Gegenstand erfolgreich aktualisiert',
    
    // Fehler-Meldungen
    'error_item_not_found' => 'Eintrag nicht gefunden',
    'error_generic'              => 'Ein Fehler ist aufgetreten',
    'error_loading'              => 'Fehler beim Laden',

    // Kategorien
    'cat_management'             => 'Kategorien-Verwaltung',
    'cat_management_desc'        => 'Verwalten Sie hier die Kategorien für Ihre Gegenstände',
    'cat_add_new'                => 'Neue Kategorie hinzufügen',
    'cat_name_label'             => 'Kategorie-Name',
    'cat_placeholder'            => 'z.B. Elektronik, Möbel, ...',
    'cat_all'                    => 'Alle Kategorien',
    'cat_col_name'               => 'Kategorie',
    'cat_none'                   => 'Noch keine Kategorien vorhanden. Fügen Sie oben eine neue Kategorie hinzu!',
    'cat_edit_title'             => 'Kategorie bearbeiten',
    'cat_delete_title'           => 'Kategorie löschen?',
    'cat_in_use_title'           => 'Kategorie wird noch verwendet',
    'cat_name_empty'             => 'Kategorie-Name darf nicht leer sein',
    'cat_already_exists'         => 'Diese Kategorie existiert bereits',
    'cat_in_use'                 => 'Diese Kategorie wird noch von %d Gegenständen verwendet und kann nicht gelöscht werden',
    'cat_added_success'          => 'Kategorie erfolgreich hinzugefügt',
    'cat_updated_success'        => 'Kategorie erfolgreich aktualisiert',
    'cat_deleted_success'        => 'Kategorie erfolgreich gelöscht',

    // Orte
    'loc_management'             => 'Orte-Verwaltung',
    'loc_management_desc'        => 'Verwalten Sie hier die Aufbewahrungsorte für Ihre Gegenstände',
    'loc_add_new'                => 'Neuen Ort hinzufügen',
    'loc_name_label'             => 'Ort-Name',
    'loc_placeholder'            => 'z.B. Wohnzimmer, Büro, Keller...',
    'loc_all'                    => 'Alle Orte',
    'loc_col_name'               => 'Ort-Name',
    'loc_none'                   => 'Noch keine Orte vorhanden. Erstelle deinen ersten Ort um Gegenstände zu organisieren.',
    'loc_edit_title'             => 'Ort bearbeiten',
    'loc_delete_title'           => 'Ort löschen?',
    'loc_name_empty'             => 'Ort-Name darf nicht leer sein',
    'loc_already_exists'         => 'Dieser Ort existiert bereits',
    'loc_in_use'                 => 'Dieser Ort wird noch von %d Gegenständen verwendet und kann nicht gelöscht werden',
    'loc_in_use_alert'           => 'Dieser Ort wird noch verwendet und kann nicht gelöscht werden.',
    'loc_added_success'          => 'Ort erfolgreich hinzugefügt',
    'loc_updated_success'        => 'Ort erfolgreich aktualisiert',
    'loc_deleted_success'        => 'Ort erfolgreich gelöscht',
    'loc_total'                  => 'Orte gesamt',
    'loc_in_use_label'           => 'In Verwendung',
    'loc_unused'                 => 'Ungenutzt',
    'loc_add_btn'                => 'Neuer Ort',
    'loc_col_usage'              => 'Verwendung',
    'loc_col_value'              => 'Gesamtwert',
    
    // Bestätigungen
    'confirm_cancel_unsaved' => 'Wirklich abbrechen? Ungespeicherte Änderungen gehen verloren.',
    
    // Activity History
    'activity_history_title' => 'Änderungs-Historie',
    'activity_view_full' => 'Vollständige Historie anzeigen',
    
    // ============================================================================
    // SETTINGS / EINSTELLUNGEN
    // ============================================================================
    'settings_title' => 'Einstellungen',
    'settings_back' => 'Zurück zur Übersicht',
    
    // Tabs
    'settings_tab_theme' => 'Erscheinungsbild',
    'settings_tab_profile' => 'Profil',
    'settings_tab_master' => 'Stammdaten',
    'settings_tab_users' => 'Benutzerverwaltung',
    'settings_tab_backup' => 'Backup',
    'settings_tab_stats' => 'Statistiken',
    'settings_tab_security' => 'Sicherheit',
    'settings_tab_tools' => 'Werkzeuge',
    
    // Theme
    'settings_theme_title' => 'Theme-Auswahl',
    'settings_theme_select' => 'Wählen Sie ein Theme',
    'settings_theme_save' => 'Theme speichern',
    'settings_theme_saved' => 'Theme erfolgreich gespeichert',
    
    // Dokumente
    'settings_docs_title' => 'Dokumente-Feature',
    'settings_docs_description' => 'Aktivieren oder deaktivieren Sie die Möglichkeit, Dokumente zu Gegenständen hinzuzufügen.',
    'settings_docs_enabled' => 'Aktiviert - Dokumente-Spalte wird angezeigt',
    'settings_docs_disabled' => 'Deaktiviert - Dokumente-Spalte wird ausgeblendet',
    'settings_docs_save' => 'Dokumente-Einstellung speichern',
    'settings_docs_saved' => 'Dokumente-Einstellung gespeichert',
    
    // Profil
    'settings_creator_title' => 'Standard-Ersteller',
    'settings_creator_description' => 'Dieser Name wird automatisch als "Erstellt von" eingetragen, wenn Sie neue Gegenstände hinzufügen.',
    'settings_creator_save' => 'Standard-Ersteller speichern',
    'settings_creator_saved' => 'Standard-Ersteller erfolgreich gespeichert',
    'settings_creator_help' => 'Dieser Name wird automatisch bei neuen Einträgen verwendet',
    
    // Sicherheit
    'settings_security_title' => 'Sicherheit',
    'settings_session_info' => 'Session-Information',
    'settings_logged_in_as' => 'Angemeldet als',
    'settings_your_role' => 'Ihre Rolle',
    'settings_last_activity' => 'Letzte Aktivität',
    'settings_session_expires' => 'Session läuft ab in',
    'settings_minutes' => 'Minuten',
    'settings_logout' => 'Abmelden',
    
    // Stammdaten
    'settings_master_categories' => 'Kategorien verwalten',
    'settings_master_categories_desc' => 'Organisieren Sie Ihre Wertsachen mit Kategorien wie "Elektronik", "Schmuck", "Möbel" etc.',
    'settings_master_locations' => 'Orte verwalten',
    'settings_master_locations_desc' => 'Legen Sie fest, wo sich Ihre Wertsachen befinden - z.B. "Wohnzimmer", "Keller", "Büro" etc.',
    'settings_categories_count' => 'Kategorien angelegt',
    'settings_locations_count' => 'Orte angelegt',
    'settings_manage_categories' => 'Kategorien verwalten',
    'settings_manage_locations' => 'Orte verwalten',
    'settings_overview' => 'Übersicht',
    'settings_top_categories' => 'Top Kategorien',
    'settings_top_locations' => 'Top Orte',
    
    // Benutzerverwaltung
    'settings_users_title'       => 'Benutzerverwaltung',
    'users_total'                => 'Gesamt',
    'users_editors'              => 'Editoren',
    'users_viewers'              => 'Viewer',
    'users_new_user'             => 'Neuer Benutzer',
    'users_edit_user'            => 'Benutzer bearbeiten',
    'users_create_user'          => 'Benutzer erstellen',
    'users_save_changes'         => 'Änderungen speichern',
    'users_permission'           => 'Berechtigung',
    'users_new_password'         => 'Neues Passwort (optional)',
    'users_password_placeholder' => 'Nur ausfüllen wenn ändern',
    'users_password_hint'        => 'Leer lassen um Passwort beizubehalten',
    'users_see_all'              => 'Alle Gegenstände sehen',
    'users_see_all_hint'         => 'Wenn deaktiviert, sieht der Benutzer nur eigene Gegenstände (Feld „Erstellt von")',
    'users_added_success'        => 'Benutzer erfolgreich hinzugefügt',
    'users_updated_success'      => 'Benutzer erfolgreich aktualisiert',
    'users_deleted_success'      => 'Benutzer erfolgreich gelöscht',
    'users_username_empty'       => 'Benutzername darf nicht leer sein',
    'users_cannot_delete_self'   => 'Du kannst dich nicht selbst löschen',
    'users_confirm_delete'       => 'Benutzer',
    'users_confirm_delete_text'  => 'wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden!',
    'error_invalid_id'           => 'Ungültige ID',
    'tab_created'                => 'Erstellt am',
    'tab_actions'                => 'Aktionen',
    'tab_role'                   => 'Rolle',
    // Statistiken
    'stats_top_categories'       => 'Top Kategorien',
    'stats_top_locations'        => 'Top Orte',
    'stats_top_items'            => 'Top Items',
    'stats_top10_value'          => 'Top 10 Wert',
    'stats_top10_categories'     => 'Top 10 Kategorien',
    'stats_top10_locations'      => 'Top 10 Orte',
    'stats_top10_valuable'       => 'Top 10 Wertvollste Gegenstände',
    'stats_avg_value'            => 'Ø Wert',
    'stats_pieces'               => 'Stück',
    'stats_items'                => 'Gegenstände',
    'stats_no_items'             => 'Keine Gegenstände gefunden',
    'stats_no_categories'        => 'Keine Kategorien gefunden',
    'stats_no_locations'         => 'Keine Orte gefunden',
    'stats_no_history'           => 'Noch keine Wertänderungen erfasst. Sobald du bei einem Gegenstand einen aktuellen Wert einträgst, erscheint hier der Chart.',
    'stats_value_trend'          => 'Wertentwicklung über Zeit',
    'stats_wealth_by_category'   => 'Vermögen nach Kategorie',
    'stats_total_wealth'         => 'Gesamtvermögen',
    'stats_most_valuable'        => 'Wertvollste Gegenstände',
    'stats_months'               => ['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez'],
    'settings_users_description' => 'Hier können Sie neue Benutzer anlegen, Passwörter ändern und Berechtigungen verwalten.',
    'settings_users_goto' => 'Zur Benutzerverwaltung',
    'settings_permissions_title' => 'Berechtigungsstufen',
    'settings_permission_admin' => 'Admin: Vollzugriff auf alle Funktionen inkl. Benutzerverwaltung',
    'settings_permission_edit' => 'Editieren: Gegenstände hinzufügen, bearbeiten und löschen',
    'settings_permission_read' => 'Nur Lesen: Nur Übersicht ansehen, keine Änderungen',
    
    // Backup
    'settings_backup_title' => 'Backup erstellen',
    'settings_backup_description' => 'Erstellen Sie ein komplettes Backup der Datenbank und aller hochgeladenen Dateien.',
    'settings_backup_email' => 'E-Mail-Adresse für Backup-Versand (optional)',
    'settings_backup_email_help' => 'Lassen Sie dieses Feld leer, um das Backup direkt herunterzuladen',
    'settings_backup_create' => 'Backup erstellen',
    
    // Statistiken
    'settings_stats_title' => 'Statistiken',
    'settings_stats_items' => 'Wertsachen',
    'settings_stats_locations' => 'Orte',
    'settings_stats_categories' => 'Kategorien',
    'settings_stats_total_value' => 'Gesamtwert',
    
    // Sicherheits-Tab
    'settings_delete_all_title' => 'Alle Gegenstände löschen',
    'settings_delete_all_warning' => 'Diese Aktion löscht ALLE Gegenstände unwiderruflich!',
    'settings_delete_all_backup' => 'Erstellen Sie vorher unbedingt ein Backup!',
    'settings_delete_all_confirm' => 'Geben Sie "LÖSCHEN" ein zur Bestätigung',
    'settings_delete_all_button' => 'ALLE Gegenstände unwiderruflich löschen',
    
    // Werkzeuge
    'settings_tools_title' => 'Entwickler-Werkzeuge',
    'settings_tools_description' => 'Test-Tools und Diagnose-Funktionen für Entwickler und Administratoren.',
    'settings_tools_open' => 'Öffnen',
    'settings_tools_none' => 'Keine Tools gefunden im /Tools/ Verzeichnis.',
    
    // Meldungen
    'msg_theme_saved' => 'Theme erfolgreich gespeichert',
    'msg_creator_saved' => 'Standard-Ersteller erfolgreich gespeichert',
    'error_theme_save' => 'Fehler beim Speichern des Themes',
    'error_invalid_theme' => 'Ungültiges Theme gewählt',
    'error_creator_empty' => 'Standard-Ersteller darf nicht leer sein',
    'error_creator_too_long' => 'Standard-Ersteller ist zu lang (max. 100 Zeichen)',
    
    // ============================================================================
    // LOGIN
    // ============================================================================
    'login_title' => 'Anmeldung',
    'login_app_name' => 'Wertsachen-Inventarverwaltung',
    'login_username' => 'Benutzername',
    'login_password' => 'Passwort',
    'login_button' => 'Anmelden',
    'login_session_expired' => 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.',
    'login_fill_all_fields' => 'Bitte alle Felder ausfüllen',
    'login_invalid_credentials' => 'Ungültiger Benutzername oder Passwort',
    'login_error' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.',
    
    // ============================================================================
    // EXPORT
    // ============================================================================
    'export_pdf_title' => 'Wertsachen-Inventar Export',
    'export_pdf_instructions' => 'PDF-Export-Anleitung',
    'export_pdf_step1' => 'Klicken Sie auf den "Als PDF drucken" Button',
    'export_pdf_step2' => 'Wählen Sie im Druck-Dialog: "Als PDF speichern" oder "Microsoft Print to PDF"',
    'export_pdf_step3' => 'WICHTIG: Aktivieren Sie "Hintergrundgrafiken" in den Druck-Optionen!',
    'export_pdf_step4' => 'Speichern Sie die Datei',
    'export_pdf_tip' => 'Dieser Export enthält alle Bilder und Details Ihrer Gegenstände!',
    'export_pdf_print_button' => 'Als PDF drucken',
    'export_back_button' => 'Zurück zur Übersicht',
    'export_inventory_title' => 'Wertsachen-Inventar',
    'export_date' => 'Exportiert am',
    'export_user' => 'Benutzer',
    'export_count' => 'Anzahl',
    'export_entries' => 'Einträge',
    'export_no_entries' => 'Keine Einträge vorhanden',
    'export_no_image' => 'Kein Bild',
    'expraum_id' => 'ID',
    'export_created_by' => 'Erstellt von',
    'export_listed_on' => 'Gelistet am',
    'export_location' => 'Ort',
    'export_category' => 'Kategorie',
    'export_purchase_date' => 'Kaufdatum',
    'export_price' => 'Preis',
    'export_notes' => 'Notizen',
    'export_total_value' => 'Gesamtwert aller Gegenstände',
    'export_based_on' => 'Basierend auf',
    'export_footer' => 'Erstellt mit Wertsachen-Inventarverwaltung',
    
    // CSV Export
    'csv_header_id' => 'ID',
    'csv_header_name' => 'Name',
    'csv_header_location' => 'Ort',
    'csv_header_category' => 'Kategorie',
    'csv_header_purchase_date' => 'Kaufdatum',
    'csv_header_price' => 'Preis (EUR)',
    'csv_header_notes' => 'Notizen',
    'csv_header_image' => 'Bild',
    'csv_header_created_by' => 'Erstellt von',
    'csv_header_listed_on' => 'Hier gelistet am',
    'csv_header_created_at' => 'Erstellt am',
    'error_export' => 'Fehler beim Export. Bitte versuchen Sie es später erneut.',
    'error_loading_data' => 'Fehler beim Laden der Daten.',
    'tip' => 'Tipp',
    
    // HTML Export
    'html_export_title' => 'Wertsachen-Inventar Export',
    'html_exported_at' => 'Exportiert am',
    'html_user' => 'Benutzer',
    'html_entry_count' => 'Anzahl Einträge',
    'html_format' => 'Format',
    'html_format_desc' => 'HTML mit eingebetteten Bildern (Excel-kompatibel)',
    'html_table_image' => 'Bild',
    'html_summary' => 'Zusammenfassung',
    'html_total_count' => 'Gesamtanzahl',
    'html_items_count' => 'Gegenstände',
    'html_total_value' => 'Gesamtwert',
    'html_footer_text' => 'Diese Datei kann direkt in Excel/LibreOffice geöffnet werden.',
    
    // ============================================================================
    // HEADER & NAVIGATION
    // ============================================================================
    'header_app_title' => 'Wertsachen-Inventarverwaltung',
    'header_keyboard_shortcuts' => 'Tastenkombinationen',
    'header_shortcuts_new' => 'Neuer Eintrag',
    'header_shortcuts_save' => 'Speichern',
    'header_shortcuts_back' => 'Zurück',
    'header_shortcuts_close' => 'Schließen',
    'header_shortcuts_show' => 'Tastenkombinationen anzeigen',
    'header_logged_in_as' => 'Angemeldet als',
    'header_session_expires' => 'Session läuft ab in',
    'header_session_expired' => 'Abgelaufen',
    'header_logout' => 'Abmelden',
    
    // Navigation
    'nav_dashboard' => 'Dashboard',
    'nav_all_items' => 'Alle Gegenstände',
    'nav_new_item' => 'Neuer Gegenstand',
    'nav_settings' => 'Einstellungen',
    'nav_activities' => 'Aktivitäten',
    'nav_export' => 'Export',
    'nav_export_csv' => 'CSV-Datei',
    'nav_export_html' => 'HTML-Seite',
    'nav_export_pdf' => 'PDF Detailliert',
    'nav_export_pdf_compact' => 'PDF Kompakt',
    
    // Meta
    'app_description' => 'Verwalten Sie Ihre wertvollen Gegenstände mit Leichtigkeit',
    
    // ============================================================================
    // INDEX / ÜBERSICHT
    // ============================================================================
    'index_no_entries' => 'Keine Einträge gefunden',
    'index_entries_selected' => 'Einträge ausgewählt',
    'index_select_all' => 'Alle auswählen',
    'index_filter' => 'Filter',
    'index_reset' => 'Zurücksetzen',
    'index_columns' => 'Spalten',
    'index_edit' => 'Bearbeiten',
    'index_delete' => 'Löschen',
    'index_confirm_delete' => 'Wirklich löschen?',
    'index_bulk_change_category' => 'Kategorie ändern',
    'index_bulk_change_location' => 'Ort ändern',
    'index_bulk_delete' => 'Löschen',
    'index_bulk_cancel' => 'Abbrechen',
    'index_select_category' => 'Kategorie wählen...',
    'index_select_location' => 'Ort wählen...',
    'index_confirm_bulk_delete' => 'Wirklich %d Einträge löschen? Diese Aktion kann nicht rückgängig gemacht werden!',
    'index_confirm_category_change' => 'Kategorie für ausgewählte Einträge wirklich ändern?',
    'index_confirm_location_change' => 'Ort für ausgewählte Einträge wirklich ändern?',
    'index_confirm_reset_columns' => 'Wirklich auf Standard-Einstellungen zurücksetzen?',
    'index_columns_saved' => 'Spaltenauswahl gespeichert',
    'index_columns_reset' => 'Spaltenauswahl zurückgesetzt',
    'index_error_save' => 'Fehler beim Speichern',
    'index_error_reset' => 'Fehler beim Zurücksetzen',
    'index_success_deleted' => '%d Einträge erfolgreich gelöscht',
    'index_success_updated' => '%d Einträge aktualisiert',
    'index_error_bulk_action' => 'Fehler bei Bulk-Aktion',
    
    // Export Page Numbers
    'export_page' => 'Seite',
    'export_of' => 'von',
    
    // Settings - Additional Tabs
    'settings_categories_description' => 'Organisieren Sie Ihre Wertsachen mit Kategorien',
    'settings_locations_description' => 'Definieren Sie Aufbewahrungsorte',
    'settings_current' => 'Aktuell',
    'settings_categories' => 'Kategorien',
    'settings_locations' => 'Orte',
    'settings_add_category' => 'Kategorie hinzufügen',
    'settings_add_location' => 'Ort hinzufügen',
    'settings_user_management' => 'Benutzerverwaltung',
    'settings_create_user' => 'Neuen Benutzer anlegen',
    'settings_username' => 'Benutzername',
    'settings_role' => 'Rolle',
    'settings_created_at' => 'Erstellt am',
    'settings_actions' => 'Aktionen',
    'settings_create_backup' => 'Backup erstellen',
    'settings_create_full_backup' => 'Vollständiges Backup erstellen',
    'settings_restore_backup' => 'Backup wiederherstellen',
    'settings_recent_backups' => 'Letzte Backups',
    'settings_date' => 'Datum',
    'settings_size' => 'Größe',
    'settings_download' => 'Herunterladen',
    'settings_statistics' => 'Statistiken',
    'settings_stats_description' => 'Übersicht über Ihre Wertsachen-Sammlung',
    'settings_total_value' => 'Gesamtwert',
    'settings_item_count' => 'Anzahl Gegenstände',
    'settings_average_value' => 'Durchschnittswert',
    'settings_most_expensive' => 'Teuerster Gegenstand',
    'settings_by_category' => 'Nach Kategorie',
    'settings_by_location' => 'Nach Ort',
    'settings_delete_all_items' => 'Alle Gegenstände löschen',
    'settings_delete_warning' => 'Vorsicht! Diese Aktion kann nicht rückgängig gemacht werden',
    'settings_database_cleanup' => 'Datenbank-Bereinigung',
    'settings_cleanup_description' => 'Bereinigen Sie verwaiste Einträge',
    'settings_system_logs' => 'System-Protokolle',
    'settings_show_last' => 'Zeige die letzten',
    'settings_entries' => 'Einträge',
    'settings_advanced_security' => 'Erweiterte Sicherheit',
    'settings_security_description' => 'Sicherheitseinstellungen für Administratoren',
    'settings_developer_tools' => 'Entwickler-Werkzeuge',
    'settings_system_info' => 'System-Informationen',
    'settings_php_version' => 'PHP Version',
    'settings_mysql_version' => 'MySQL Version',
    'settings_server_software' => 'Server Software',
    'settings_directory_status' => 'Verzeichnis-Status',
    'settings_directory' => 'Verzeichnis',
    'settings_status' => 'Status',
    'settings_writable' => 'Beschreibbar',
    'settings_not_writable' => 'Nicht beschreibbar',
    'settings_php_extensions' => 'Geladene PHP-Erweiterungen',
    'settings_save' => 'Speichern',
    'settings_cancel' => 'Abbrechen',
    'settings_add' => 'Hinzufügen',
    'settings_reset' => 'Zurücksetzen',
    'settings_export' => 'Exportieren',
    'settings_import' => 'Importieren',
    'settings_open' => 'Öffnen',
    'settings_close' => 'Schließen',
    'settings_confirm' => 'Bestätigen',
    'settings_no_tools_found' => 'Keine Tools gefunden',
    'settings_tools_hint' => 'Legen Sie PHP-Dateien im Tools-Ordner ab',
    'settings_saved_success' => 'Erfolgreich gespeichert',
    'settings_save_error' => 'Fehler beim Speichern',
    'settings_confirm_delete' => 'Wirklich löschen?',
    'settings_deleted_success' => 'Erfolgreich gelöscht',
    // Settings Profile Tab - Missing Keys
    'settings_default_creator_title' => 'Standard-Ersteller',
    'settings_default_creator_description' => 'Dieser Name wird automatisch als "Erstellt von" eingetragen, wenn Sie neue Gegenstände hinzufügen.',
    'settings_default_creator_label' => 'Standard-Ersteller',
    'settings_default_creator_hint' => 'Dieser Name wird automatisch bei neuen Einträgen verwendet',
    'settings_save_creator' => 'Standard-Ersteller speichern',
    'settings_security_section' => 'Sicherheit',

    // Dashboard Widget Labels
    'nav_categories' => 'Kategorien',
    'btn_manage' => 'Verwalten',
    // Who is online?
    'online_widget_title'  => 'Online',
    'online_widget_none'   => 'Niemand aktiv',
    'online_widget_link'   => 'Benutzerverwaltung →',
    'online_window'        => 'Letzte 15 Min.',
    'online_just_now'      => 'Gerade eben',
    'online_minutes_ago'   => 'vor %d Min.',
    'online_col_user'      => 'Benutzer',
    'online_col_page'      => 'Aktuelle Seite',
    'online_col_ip'        => 'IP-Adresse',
    'online_col_seen'      => 'Zuletzt aktiv',
    'online_status_dot'    => '🟢 Online',
    'online_section_title' => '🟢 Aktuell aktive Benutzer',
    'online_section_none'  => 'Aktuell niemand aktiv (letzte 15 Min.)',

    // Backup-Verwaltung (backup.php)
    'backup_management'         => 'Backup-Verwaltung',
    'backup_total'              => 'Backups gesamt',
    'backup_total_size'         => 'Gesamtgröße',
    'backup_last'               => 'Letztes Backup',
    'backup_days_ago'           => 'vor %d Tagen',
    'backup_no_email'           => 'Kein E-Mail',
    'backup_content'            => 'Backup-Inhalt',
    'backup_save_config'        => 'Konfiguration speichern',
    'backup_restore'            => 'Backup wiederherstellen',
    'backup_create_now'         => 'Backup jetzt erstellen',
    'backup_email_configure'    => 'E-Mail konfigurieren',
    'backup_files'              => 'Backup-Dateien',
    'backup_col_filename'       => 'Dateiname',
    'backup_col_type'           => 'Typ',
    'backup_size'               => 'Größe',
    'backup_col_created'        => 'Erstellt',
    'backup_col_age'            => 'Alter',
    'backup_type_db'            => '🗄️ DB',
    'backup_type_files'         => '🖼️ Dateien',
    'backup_type_php'           => '📄 PHP',
    'backup_confirm_delete'     => 'Backup wirklich löschen?',
    'backup_confirm_bulk_delete'=> 'Backup(s) unwiderruflich löschen?',
    'backup_selected'           => 'ausgewählt',
    'backup_email_notification' => 'E-Mail-Benachrichtigung',
    'backup_cron_log'           => 'Cron-Log',
    'backup_config_saved'       => '✅ Backup-Konfiguration gespeichert.',
    'backup_created'            => '✅ Backup erstellt: ',
    'backup_no_types'           => '❌ Keine Backup-Typen ausgewählt. Bitte Konfiguration prüfen.',
    'backup_email_disabled'     => '✅ E-Mail-Benachrichtigungen deaktiviert.',
    'backup_email_saved'        => '✅ E-Mail-Adresse gespeichert.',
    'backup_email_invalid'      => '❌ Ungültige E-Mail-Adresse.',
    'backup_deleted'            => '✅ Backup gelöscht: ',
    'backup_deleted_count'      => '✅ %d Backup(s) gelöscht.',
    'backup_delete_error'       => '❌ %d Datei(en) konnten nicht gelöscht werden.',
    'backup_none_selected'      => '❌ Keine Backups ausgewählt.',

    // Next-Interface Strings
    'search_placeholder'        => 'Suchen…',
    'nav_items'                 => 'Objekte',
    'nav_add'                   => 'Hinzufügen',
    'filter_all'                => 'Alle',
    'sort_manual_active'        => '↕ Manuell aktiv — alphabetisch',
    'sort_manual'               => '↕ Manuell sortieren',
    'detail_select_item'        => 'Gegenstand auswählen',

    // 4.3.5 — Texte aus alert()/confirm() im Backend, vorher fest verdrahtet
    'backup_select_at_least_one' => 'Bitte mindestens ein Backup auswählen.',
    'log_confirm_delete_range'   => 'Logs für den gewählten Zeitraum wirklich löschen?',
    'ins_confirm_delete'         => 'Versicherung löschen? Zuordnungen werden entfernt.',
    'ins_confirm_unassign'       => 'Zuweisung aufheben?',
    'pub_confirm_new_link'       => 'Einen neuen Link generieren? Der alte Link wird ungültig.',
    'pub_confirm_disable_link'   => 'Link wirklich deaktivieren? Der Link wird sofort ungültig.',
    'pub_qr_not_ready'           => 'QR-Code noch nicht bereit.',
    'loc_room_has_positions'     => 'Dieser Raum enthält noch %d Position(en) und kann nicht gelöscht werden.',
    'loc_position_in_use'        => 'Diese Position wird noch von %d Gegenstand/Gegenständen verwendet.',

    // ── System-Logs ─────────────────────────────────────────────
    'syslog_title'               => 'System-Logs',
    'syslog_title_extended'      => 'System-Logs (Erweitert)',
    'syslog_tab_activity'        => 'Activity Logs',
    'syslog_tab_security'        => 'Security Logs',
    'syslog_view_only'           => 'Diese Seite zeigt nur an.',
    'syslog_cleanup_link'        => 'Einträge löschen oder aufräumen',
    'syslog_error'               => 'Fehler!',
    'syslog_none_found'          => 'Keine Logs gefunden',
    'syslog_entries_n'           => '%d Einträge',
    'syslog_total'               => 'Gesamt',
    'syslog_total_all_time'      => 'Gesamt (seit Beginn)',
    'syslog_success_24h'         => 'Erfolg (24h)',
    'syslog_errors_24h'          => 'Fehler (24h)',
    'syslog_success_rate_24h'    => 'Erfolgsquote (24h)',
    'syslog_logins_ok'           => 'Erfolgreiche Logins',
    'syslog_logins_failed'       => 'Fehlgeschlagene Logins',
    'syslog_col_table'           => 'Tabelle',
    'syslog_col_event'           => 'Event',
    'syslog_source'              => 'Quelle',
    'syslog_source_db'           => 'Datenbank (neuere Logs)',
    'syslog_source_file'         => 'Datei (historische Logs)',
    'syslog_event_login_ok'      => 'Login OK',
    'syslog_event_login_failed'  => 'Login Fehler',
    'syslog_event_logout'        => 'Logout',
    'syslog_event_password'      => 'Passwort',
    'syslog_event_db_error'      => 'DB-Fehler',
    'syslog_event_backup'        => 'Backup',

    // ── Protokoll-Bereinigung ───────────────────────────────────
    'syscleanup_title'           => 'Activity Log Bereinigung',
    'syscleanup_stats'           => 'Aktuelle Statistiken',
    'syscleanup_total'           => 'Gesamt-Einträge',
    'syscleanup_older_24h'       => 'Älter als 24 Stunden',
    'syscleanup_older_7d'        => 'Älter als 7 Tage',
    'syscleanup_older_30d'       => 'Älter als 30 Tage',
    'syscleanup_older_3m'        => 'Älter als 3 Monate',
    'syscleanup_heading'         => 'Logs bereinigen',
    'syscleanup_warn_title'      => 'Wichtig:',
    'syscleanup_warn_text'       => 'Gelöschte Logs lassen sich nicht wiederherstellen. Erstellen Sie vor der Bereinigung ein Backup.',
    'syscleanup_opt_24h'         => 'Logs älter als 24 Stunden löschen',
    'syscleanup_opt_7d'          => 'Logs älter als 7 Tage löschen',
    'syscleanup_opt_30d'         => 'Logs älter als 30 Tage löschen',
    'syscleanup_opt_3m'          => 'Logs älter als 3 Monate löschen (empfohlen)',
    'syscleanup_opt_custom'      => 'Benutzerdefiniert',
    'syscleanup_deletes_n'       => 'Löscht %s Einträge',
    'syscleanup_after_tests'     => 'nützlich nach Tests',
    'syscleanup_older_than'      => 'Älter als',
    'syscleanup_days_delete'     => 'Tage löschen',
    'syscleanup_btn_delete'      => 'Ausgewählte Logs jetzt löschen',
    'syscleanup_info'            => 'Informationen',
    'syscleanup_why'             => 'Warum sollte ich alte Logs löschen?',
    'syscleanup_why_perf'        => 'Datenbank-Performance:',
    'syscleanup_why_perf_d'      => 'Weniger Einträge, schnellere Abfragen',
    'syscleanup_why_space'       => 'Speicherplatz:',
    'syscleanup_why_space_d'     => 'Das Protokoll wächst mit der Zeit erheblich',
    'syscleanup_why_privacy'     => 'Datenschutz:',
    'syscleanup_why_privacy_d'   => 'Die DSGVO legt nahe, alte Protokolle zu löschen',
    'syscleanup_why_clarity'     => 'Übersichtlichkeit:',
    'syscleanup_why_clarity_d'   => 'Neuere Einträge sind aussagekräftiger',
    'syscleanup_what'            => 'Was passiert beim Löschen?',
    'syscleanup_what_1'          => 'Alle Einträge, die älter als die gewählte Zeitspanne sind, werden endgültig gelöscht.',
    'syscleanup_what_2'          => 'Die Löschung selbst wird protokolliert: wer wann wie viele Einträge entfernt hat.',
    'syscleanup_what_3'          => 'Neuere Einträge bleiben vollständig erhalten.',
    'syscleanup_what_4'          => 'Die Löschung lässt sich nicht rückgängig machen.',
    'syscleanup_hint'            => 'Empfehlung:',
    'syscleanup_hint_text'       => 'Einmal im Jahr bereinigen und Einträge älter als ein Jahr löschen. Die Datenbank bleibt schlank, und für Nachweiszwecke bleibt genug Historie.',
    'syscleanup_to_logs'         => 'Zu den Activity Logs',
    'syscleanup_back'            => 'Zurück zu Einstellungen',
    'syscleanup_error'           => 'Fehler beim Bereinigen: %s',
];

