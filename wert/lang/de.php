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
    'nav_dashboard' => 'Dashboard',
    'nav_all_items' => 'Alle Gegenstände',
    'nav_new_item' => 'Neuer Gegenstand',
    'nav_settings' => 'Einstellungen',
    'nav_activities' => 'Aktivitäten',
    'nav_export' => 'Export',
    'nav_admin'  => 'Admin',
    'nav_logout' => 'Abmelden',
    
    // Export Dropdown
    'nav_export_csv' => 'CSV-Datei',
    'nav_export_html' => 'HTML-Seite',
    'nav_export_pdf' => 'PDF Detailliert',
    'nav_export_pdf_compact' => 'PDF Kompakt',
    
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
    'btn_add_another' => 'Weiteren hinzufügen',
    'btn_share' => 'Teilen',
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
    'field_location' => 'Raum',
    'col_ort'        => 'Raum',
    'field_notes' => 'Notizen',
    'field_image' => 'Bild',
    'field_created_by' => 'Erstellt von',
    'field_username' => 'Benutzername',
    'field_password' => 'Passwort',
    'field_role' => 'Berechtigung',
    'field_search' => 'Suchen',
    
    // Placeholder
    'placeholder_search' => 'Suchen...',
    'placeholder_notes'  => 'Notizen...',
    'placeholder_select' => '-- Bitte wählen --',
    'placeholder_all_locations' => 'Alle Räume',
    'placeholder_all_categories' => 'Alle Kategorien',
    
    // ============================================================================
    // TABELLEN-HEADER
    // ============================================================================
    'tab_name' => 'Name',
    'tab_image' => 'Bild',
    'tab_category' => 'Kategorie',
    'tab_location' => 'Raum',
    'tab_price' => 'Preis',
    'tab_purchase_date' => 'Kaufdatum',
    'tab_created_by' => 'Erstellt von',
    'tab_documents' => 'Dok.',
    'tab_notes' => 'Notizen',
    'tab_actions' => 'Aktionen',
    'tab_username' => 'Benutzername',
    'tab_role' => 'Berechtigung',
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
    'dashboard_max_value' => 'Wertvollster Gegenstand',
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
    'form_location' => 'Raum',
    'form_category' => 'Kategorie',
    'form_purchase_date' => 'Kaufdatum',
    'form_listed_date' => 'Hier gelistet am',
    'form_price' => 'Preis (€)',
    'price_decimal_hint' => 'Dezimalzahlen mit Punkt oder Komma möglich (z.B. 123.45 oder 123,45)',
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
    'add_documents'  => 'Dokumente hinzufügen',
    
    // Hilfe-Texte
    'form_help_created_by_default' => 'Standard: %s (kann geändert werden in den Einstellungen)',
    'form_help_created_by_empty' => 'Lassen Sie leer für automatische Verwendung des aktuellen Benutzers',
    
    // Erfolgs-Meldungen
    'msg_item_created' => 'Gegenstand erfolgreich hinzugefügt',
    'msg_item_updated' => 'Gegenstand erfolgreich aktualisiert',
    
    // Fehler-Meldungen
    'error_invalid_id' => 'Ungültige ID',
    'error_item_not_found' => 'Eintrag nicht gefunden',
    'error_generic' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
    
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
    'nav_stats'                    => 'Statistiken',
    
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
    'settings_users_title' => 'Benutzerverwaltung',
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
    'export_location' => 'Raum',
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
    'csv_header_location' => 'Raum',
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
    'html_footer_created' => 'Erstellt mit ValuSafe',
    
    // ============================================================================
    // HEADER & NAVIGATION
    // ============================================================================
    'header_app_title' => 'Wertsachen-Inventarverwaltung',
    'header_app_title_mobile' => 'Meine Wertsachen',
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
    'index_success_hidden'  => '%d Einträge verborgen',
    'index_success_visible' => '%d Einträge wieder sichtbar',
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

    // Aktions-Labels (Activity Log)
    'action_created'    => 'Erstellt',
    'action_updated'    => 'Bearbeitet',
    'action_deleted'    => 'Gelöscht',
    'action_uploaded'   => 'Hochgeladen',
    'action_downloaded' => 'Heruntergeladen',
    'action_exported'   => 'Exportiert',
    'action_login'      => 'Angemeldet',
    'action_logout'     => 'Abgemeldet',

    // Feld-Labels (formatChanges)
    'field_hidden'      => 'Verborgen',
    'field_description' => 'Beschreibung',
    'label_empty'       => 'leer',

    // Spalten-Preset
    'columns_preset_complete' => 'Vollständig',

    // activity_log.php
    'page_activity_log'     => 'Aktivitäts-Log',
    'log_all_users'         => 'Alle Benutzer',
    'log_all_actions'       => 'Alle Aktionen',
    'log_all_areas'         => 'Alle Bereiche',
    'log_hours_ago'         => 'vor %s Std',
    'log_days_ago'          => 'vor %s Tagen',
    'log_show_details'      => 'Details anzeigen',
    'log_hide_details'      => 'Details ausblenden',

    'log_col_area'          => 'Bereich',
    'log_col_details'       => 'Details',
    'log_date_from'         => 'Von',
    'log_date_to'           => 'Bis',
    'log_cleanup_link'      => 'Alte Logs bereinigen',
    // settings.php
    'settings_error_theme_save'    => 'Fehler beim Speichern des Themes',
    'settings_error_invalid_theme' => 'Ungültiges Theme gewählt',
    'settings_error_creator_empty' => 'Standard-Ersteller darf nicht leer sein',
    'settings_error_creator_long'  => 'Standard-Ersteller ist zu lang (max. 100 Zeichen)',
    'settings_success_creator'     => 'Standard-Ersteller erfolgreich gespeichert',
    'settings_error_save'          => 'Fehler beim Speichern',
    'settings_save_theme'          => 'Theme speichern',
    'settings_security_session'    => 'Sicherheit & Session',
    'settings_expires_in'          => 'Läuft ab in',
    'settings_role_switch'         => 'Admin: Rollenwechsel',
    'settings_role_switch_desc'    => 'Ansicht als andere Rolle testen.',
    'settings_login_as_editor'     => 'Als <strong>Editor</strong> anmelden',
    'settings_login_as_viewer'     => 'Als <strong>Leser</strong> anmelden',
    'stats_items'                  => 'Wertsachen',
    'stats_locations'              => 'Orte',
    'stats_categories'             => 'Kategorien',
    'error_loading'                => 'Fehler beim Laden.',

    // index.php
    'index_show_hidden'            => 'Verborgene anzeigen',
    // manage_kategorien.php
    'cat_invalid_id'               => 'Ungültige ID',
    'cat_in_use'                   => 'Kategorie kann nicht gelöscht werden, da sie noch verwendet wird',
    'cat_deleted'                  => 'Kategorie erfolgreich gelöscht',
    'cat_added'                    => 'Kategorie erfolgreich hinzugefügt',
    'cat_error_delete'             => 'Fehler beim Löschen',
    'cat_error_add'                => 'Fehler beim Hinzufügen',
    'cat_confirm_delete'           => 'Kategorie wirklich löschen?',

    // manage_orte.php
    'loc_invalid_id'               => 'Ungültige ID',
    'loc_in_use'                   => 'Ort kann nicht gelöscht werden, da er noch verwendet wird',
    'loc_deleted'                  => 'Ort erfolgreich gelöscht',
    'loc_added'                    => 'Ort erfolgreich hinzugefügt',
    'loc_error_delete'             => 'Fehler beim Löschen',
    'loc_error_add'                => 'Fehler beim Hinzufügen',
    'loc_confirm_delete'           => 'Ort wirklich löschen?',

    // manage_users.php
    'user_no_permission'           => 'Diese Funktion steht Ihnen nicht zur Verfügung.',
    'user_passwords_mismatch'      => 'Passwörter stimmen nicht überein!',
    'user_invalid_role'            => 'Ungültige Rolle!',
    'user_cannot_delete_self'      => 'Sie können Ihren eigenen Account nicht löschen!',
    'user_cannot_change_own_role'  => 'Sie können Ihre eigene Rolle nicht ändern!',
    'user_deleted'                 => 'Benutzer erfolgreich gelöscht!',
    'user_password_changed'        => 'Passwort erfolgreich geändert!',
    'user_role_changed'            => 'Rolle erfolgreich geändert!',
    'user_error_delete'            => 'Fehler beim Löschen des Benutzers!',
    'user_error_password'          => 'Fehler beim Ändern des Passworts!',
    'user_error_role'              => 'Fehler beim Ändern der Rolle!',
    'user_invalid_csrf'            => 'Ungültiger CSRF-Token!',

    // manage_documents.php
    'doc_invalid_id'               => 'Ungültige ID',
    'doc_too_large'                => 'Datei zu groß (max. 5 MB)',
    'doc_deleted'                  => 'Dokument gelöscht',
    'doc_error_delete'             => 'Fehler beim Löschen',
    'doc_confirm_delete'           => 'Dokument wirklich löschen?',

    // bulk actions
    'bulk_entry_selected'          => 'Eintrag ausgewählt',
    'bulk_entries_selected'        => 'Einträge ausgewählt',
    'bulk_entries_deleted'         => 'Einträge gelöscht',
    'bulk_entries_hidden'          => 'Einträge verborgen',
    'bulk_entries_visible'         => 'Einträge wieder sichtbar',
    'bulk_entries_updated'         => 'Einträge aktualisiert',
    'bulk_invalid_ids'             => 'Ungültige IDs',
    'bulk_invalid_field'           => 'Ungültiges Feld',
    'bulk_confirm_delete'          => 'Eintrag wirklich löschen?',

    // backup.php
    'backup_success'               => 'Backup erfolgreich erstellt!',
    'backup_failed'                => 'Backup fehlgeschlagen. Bitte Cron Log prüfen.',
    'backup_not_found'             => 'Datei nicht gefunden oder ungültiger Pfad.',
    'backup_running'               => 'Backup läuft…',

    // cleanup_activity_logs.php
    'log_invalid_days'             => 'Ungültige Anzahl Tage',
    'log_entries_deleted'          => '%d alte Log-Einträge wurden gelöscht (älter als %d Tage).',
    'log_oldest_entry'             => 'Ältester Eintrag',
    'log_newest_entry'             => 'Neuester Eintrag',
    'log_no_entries'               => 'Keine Einträge',
    'log_confirm_delete'           => 'Wirklich alte Logs löschen? Diese Aktion kann nicht rückgängig gemacht werden!',

    // image_manager.php / ajax
    'error_csrf_invalid'           => 'CSRF Token ungültig',
    'error_invalid_data'           => 'Ungültige Daten',
    'error_delete_failed'          => 'Löschen fehlgeschlagen',
    'error_no_ids'                 => 'Keine IDs übergeben',

    'search_advanced'          => 'Erweiterte Suche',
    'filter_label_search'       => 'Suche',
    'filter_label_category'     => 'Kategorie',
    'filter_label_location'     => 'Raum',
    'filter_label_price'        => 'Preis',
    'filter_label_date'         => 'Kaufdatum',
    'filter_placeholder_search' => 'Name, Beschreibung, Notizen...',
    'filter_only_with_image'    => 'Nur mit Bild',
    'filter_only_with_docs'     => 'Nur mit Dokumenten',
    'filter_price_from'         => 'Von',
    'filter_price_to'           => 'Bis',
    'stats_entries_found'       => 'Einträge gefunden',
    'stats_entry_found'         => 'Eintrag gefunden',
    'stats_total_label'         => 'Gesamtwert',
    'stats_average_label'       => 'Durchschnitt',

    // Barcode
    'barcode_placeholder'       => 'Barcode / ISBN eingeben oder scannen',
    'barcode_scan_button'       => '📷 Scannen',
    'barcode_manual_prompt'     => 'Barcode / ISBN eingeben:',
    'barcode_in_frame'          => 'Barcode im Rahmen halten',

    // Custom Fields
    'custom_field_placeholder'  => 'Eigener Wert',
    'custom_field_type'         => 'Feldtyp',
    'custom_field_number'       => 'Zahl',

    // Öffentlicher Ansichts-Link
    'public_visible_label'      => 'Öffentlich sichtbar',
    'public_visible_hint'       => 'Gegenstand im öffentlichen Sammlungslink anzeigen',

    // Formular-Sektionen
    'form_section_basic' => 'Basisdaten',
    'form_section_value' => 'Wert & Kauf',
    'form_section_notes' => 'Notizen',

    // Empty State
    'empty_state_no_items' => 'Noch keine Gegenstände',
    'empty_state_no_items_hint' => 'Lege deinen ersten Gegenstand an und beginne mit der Inventarisierung.',
    'empty_state_no_results' => 'Keine Ergebnisse gefunden',
    'empty_state_no_results_hint' => 'Die aktiven Filter liefern keine Treffer. Filter zurücksetzen?',
    'empty_state_clear_filter' => 'Filter zurücksetzen',

    // Dashboard Trend
    'dashboard_vs_30days' => 'vs. Vormonat',

    // Dashboard Zeitwert
    'dashboard_zeitwert' => '⏱ Zeitwert',
    'dashboard_zeitwert_desc' => 'von',

    'dashboard_zeitwert_von' => 'von',

    'dashboard_zeitwert_bewertet' => 'Gegenständen bewertet',

    // DSGVO Settings
    'settings_privacy_title' => 'Datenschutz & meine Daten',
    'settings_privacy_desc' => 'Gemäß DSGVO hast du das Recht auf Auskunft, Export und Löschung deiner Daten.',
    'settings_export_title' => 'Meine Daten exportieren',
    'settings_export_desc' => 'Lädt alle deine gespeicherten Daten als JSON-Datei herunter (Benutzerdaten, Gegenstände, Aktivitätsprotokoll).',
    'settings_export_btn' => 'Daten herunterladen',
    'settings_delete_account_title' => 'Account löschen',
    'settings_delete_account_desc' => 'Löscht dein Konto unwiderruflich. Deine Gegenstände bleiben erhalten.',
    'settings_delete_account_btn' => 'Account löschen',
    'settings_delete_confirm_title' => 'Account wirklich löschen?',
    'settings_delete_confirm_desc' => 'Diese Aktion ist unwiderruflich. Zur Bestätigung gib deinen Benutzernamen ein:',
    'settings_delete_confirm_btn' => 'Unwiderruflich löschen',

    // Standortverwaltung
    'standort_raum'             => 'Standort',
    'standort_position'         => 'Position',
    'standort_kein'             => '— kein Standort —',
    'standort_position_waehlen' => 'Zuerst Standort wählen…',
    'alle_raeume'               => 'Alle Räume',
    'alle_positionen'           => 'Alle Positionen',
    // --- backup.php Keys (v3.27) ---
    'backup_page_title' => 'Backup Management',
    'backup_stat_total' => 'Backups gesamt',
    'backup_stat_size' => 'Gesamtgröße',
    'backup_content_title' => 'Backup-Inhalt',
    'backup_type_db_desc' => 'Alle Tabellen als SQL-Dump',
    'backup_btn_save_config' => 'Konfiguration speichern',
    'backup_btn_create' => 'Backup jetzt erstellen',
    'backup_btn_email' => 'Email konfigurieren',
    'backup_files_title' => 'Backup-Dateien',
    'backup_no_files' => 'Noch keine Backups vorhanden.',
    'backup_bulk_delete' => 'Ausgewählte löschen',
    'backup_age_yesterday' => 'Gestern',
    'backup_email_title' => 'Email-Benachrichtigung',
    'backup_email_label' => 'Email-Adresse:',
    'backup_email_hint' => 'Leer lassen um Benachrichtigungen zu deaktivieren.',
    'backup_download_disabled' => 'Download vom Betreiber deaktiviert',

    // --- public.php Käufer-Anfrage Keys (v3.27) ---
    'public_no_items' => 'Noch keine Gegenstände freigegeben',
    'public_no_items_desc' => 'Der Eigentümer hat noch keine Gegenstände öffentlich geteilt.',
    'public_btn_interest' => 'Interesse bekunden',
    'public_email_label' => 'Deine E-Mail *',
    'public_message_hint' => '(optional, max. 200 Zeichen)',
    'public_ph_name' => 'Max Mustermann',
    'public_ph_email' => 'max@beispiel.de',
    'public_ph_message' => 'Ich interessiere mich für diesen Gegenstand...',

    // --- public.php Modal-Keys (v3.27) ---
    'public_name_label' => 'Dein Name *',
    'public_message_label' => 'Nachricht',
    'public_btn_send' => 'Anfrage senden',
    'public_anfrage_ok' => 'Deine Anfrage wurde erfolgreich gesendet!',
    'public_anfrage_no_email' => 'Der Betreiber hat noch keine Kontakt-E-Mail hinterlegt. Bitte versuche es später erneut.',
    'public_anfrage_error' => 'Beim Senden ist ein Fehler aufgetreten. Bitte versuche es später erneut.',

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

    // Next-Interface
    'search_placeholder'        => 'Suchen…',
    'nav_items'                 => 'Objekte',
    'nav_add'                   => 'Hinzufügen',
    'filter_all'                => 'Alle',
    'sort_manual_active'        => '↕ Manuell aktiv — alphabetisch',
    'sort_manual'               => '↕ Manuell sortieren',
    'detail_select_item'        => 'Gegenstand auswählen',


    // ── Next-Interface & Backend: neue Keys (ab Juni 2026) ──────────────────

    // Aktivitätslog
    'act_recent_activity'           => 'Letzte Aktivitäten',
    'act_col_user'                  => 'Benutzer',
    'act_col_action'                => 'Aktion',
    'act_col_item'                  => 'Gegenstand / Bereich',
    'act_col_time'                  => 'Zeit',
    'act_none_yet'                  => 'Noch keine Aktivitäten aufgezeichnet',
    'act_older_than_days'           => 'Älter als %d Tage',
    'act_older_than_year'           => 'Älter als 1 Jahr',
    'act_delete_all'                => 'Alle löschen',
    'act_created'                   => 'hat erstellt',
    'act_updated'                   => 'hat bearbeitet',
    'act_deleted'                   => 'hat gelöscht',
    'act_login'                     => 'hat sich angemeldet',
    'act_logout'                    => 'hat sich abgemeldet',

    // Zeitangaben
    'time_just_now'                 => 'gerade eben',
    'time_min'                      => 'Min',
    'time_hours'                    => 'Std',
    'time_days'                     => 'Tagen',
    'time_weeks'                    => 'Wochen',

    // Buttons (allgemein)
    'btn_copy'                      => 'Kopieren',
    'btn_create'                    => 'Erstellen',
    'btn_export_pdf'                => 'PDF exportieren',
    'btn_manage'                    => 'Verwalten',
    'btn_preview'                   => 'Vorschau',
    'btn_save_qr'                   => 'QR speichern',

    // Schadenfall-Bereitschaft
    'claims_readiness'              => 'Schadenfall-Bereitschaft',
    'readiness_photo'               => 'Foto',
    'readiness_price'               => 'Preis',
    'readiness_purchase_date'       => 'Kaufdatum',
    'readiness_perfect_msg'         => 'Gut dokumentiert — bestens für den Schadenfall vorbereitet!',

    // Tabellenspalten
    'col_aktueller_wert'            => 'Aktueller Wert',
    'col_anzahl'                    => 'Anzahl',
    'col_aufbewahrungsort'          => 'Aufbewahrungsort',
    'col_bezeichnung'               => 'Bezeichnung',
    'col_bild'                      => 'Bild',
    'col_dokumente'                 => 'Dokumente',
    'col_gesamtwert'                => 'Gesamtwert',
    'col_kategorie'                 => 'Kategorie',
    'col_preis'                     => 'Preis',
    'col_rang'                      => 'Rang',
    'col_wert'                      => 'Wert',

    // Dashboard-Kacheln
    'dash_users'                    => 'Benutzer',
    'dash_items'                    => 'Gegenstände',
    'dash_locations_detail'         => 'Orte · %d Räume · %d Pos.',
    'dash_total_value'              => 'Gesamtwert',
    'dash_categories'               => 'Kategorien',
    'dash_online'                   => 'Online',
    'dash_nobody_active'            => 'Niemand aktiv',
    'dash_public_link'              => 'Öffentlicher Link',
    'dash_recent_uploads'           => 'Letzte Uploads',
    'dash_most_valuable'            => 'Wertvollste Gegenstände',
    'dash_quick_access'             => 'Schnellzugriff',
    'dash_no_categories'            => 'Noch keine Kategorien',
    'dash_all_categories'           => 'Alle Kategorien →',
    'dash_storage'                  => 'Speicherplatz',
    'dash_storage_images'           => 'Bilder',
    'dash_storage_documents'        => 'Dokumente',
    'dash_storage_files'            => 'Dateien',

    // Links
    'link_manage'                   => 'Verwalten →',
    'link_view'                     => 'Ansehen →',
    'link_view_all'                 => 'Alle ansehen →',
    'link_statistics'               => 'Statistiken →',
    'link_settings'                 => 'Einstellungen →',
    'link_user_management'          => 'Benutzerverwaltung →',

    // Navigation
    'nav_categories'                => 'Kategorien',
    'nav_locations'                 => 'Orte',

    // Online-Widget
    'online_widget_title'           => 'Online',
    'online_widget_none'            => 'Niemand aktiv',
    'online_widget_link'            => 'Benutzerverwaltung →',

    // Pagination
    'pagination_prev'               => '« Zurück',
    'pagination_next'               => 'Weiter »',
    'per_page'                      => 'Pro Seite',

    // Galerie
    'image_gallery'                 => 'Bild-Galerie',
    'gallery_total_images'          => 'Bilder gesamt',
    'gallery_total_size'            => 'Gesamtgröße',
    'gallery_avg_size'              => 'Ø Dateigröße',
    'view_grid'                     => 'Grid',
    'view_list'                     => 'Liste',

    // Versicherung
    'insurance_overview'            => 'Versicherungsübersicht',
    'n_items'                       => '%d Gegenstände',

    // Orte-Verwaltung
    'loc_page_title'                => 'Räume-Verwaltung',
    'loc_rooms_total'               => 'Räume gesamt',
    'loc_locations_label'           => 'Standorte',
    'loc_positions_label'           => 'Positionen',
    'loc_tab_rooms'                 => 'Räume',
    'loc_tab_locations'             => 'Standorte',
    'loc_tab_positions'             => 'Positionen',
    'loc_tab_positions_short'       => 'Pos.',
    'loc_all_rooms'                 => 'Alle Räume',
    'loc_all_locations'             => 'Alle Standorte',
    'loc_all_positions'             => 'Alle Positionen',
    'loc_no_rooms'                  => 'Keine Räume vorhanden',
    'loc_no_locations'              => 'Keine Standorte vorhanden',
    'loc_no_positions'              => 'Keine Positionen vorhanden',
    'loc_none'                      => 'Keine',
    'loc_room_name'                 => 'Raum-Name',
    'loc_location_name'             => 'Standort-Name',
    'loc_position_name'             => 'Positions-Name',
    'loc_location'                  => 'Standort',
    'loc_positions'                 => 'Positionen',
    'loc_usage'                     => 'Verwendung',
    'loc_rooms_in_use'              => 'Räume in Verwendung',
    'loc_new_room'                  => 'Neuer Raum',
    'loc_new_location'              => 'Neuer Standort',
    'loc_new_position'              => 'Neue Position',
    'loc_err_room_in_use'           => 'Dieser Raum wird noch verwendet',
    'loc_err_location_has_positions'=> 'Dieser Standort enthält noch Positionen',
    'loc_err_position_in_use'       => 'Diese Position wird noch verwendet',

    // Statistiken
    'stats_top10_categories'        => 'Top 10 Kategorien',
    'stats_top10_locations'         => 'Top 10 Orte',
    'stats_top10_valuable'          => 'Top 10 Wertvollste',
    'stats_top10_total_value'       => 'Gesamtwert Top 10',
    'stats_top10_value'             => 'Gesamtwert Top 10',
    'stats_top_categories'          => 'Top Kategorien',
    'stats_top_locations'           => 'Top Orte',
    'stats_top_items'               => 'Top Gegenstände',
    'stats_most_valuable'           => 'Wertvollste Gegenstände',
    'stats_total_wealth'            => 'Gesamtvermögen',
    'stats_wealth_by_category'      => 'Vermögen nach Kategorie',
    'stats_value_trend'             => 'Wertentwicklung',
    'stats_avg_value'               => 'Ø Wert',
    'stats_pieces'                  => 'Stück',
    'stats_months'                  => 'Monate',
    'stats_no_categories'           => 'Keine Kategorien gefunden',
    'stats_no_locations'            => 'Keine Orte gefunden',
    'stats_no_items'                => 'Keine Gegenstände gefunden',
    'stats_no_history'              => 'Keine Verlaufsdaten vorhanden',

    // System-Kacheln
    'sys_info'                      => 'System-Info',
    'sys_logs'                      => 'System-Logs',
    'sys_login_security'            => 'Login-Security',
    'sys_backup'                    => 'Backup',
    'sys_restore'                   => 'Restore',
    'sys_extended_logs'             => 'Erweiterte Logs',

    // Berechtigungen
    'perm_page_title'               => 'Berechtigungen – Admin',
    'perm_page_heading'             => 'Berechtigungsübersicht',
    'perm_checkbox_hint'            => 'Klicke auf eine Checkbox um die Berechtigung sofort zu ändern.',
    'perm_admin_fixed_note'         => 'Admin-Rechte sind fest.',
    'perm_admin_full_access'        => 'Admin hat immer Vollzugriff',
    'perm_admin_full_access_badge'  => 'Admin – Vollzugriff (fest)',
    'perm_editor_badge'             => 'Editor – konfigurierbar',
    'perm_reader_badge'             => 'Leser – konfigurierbar',
    'perm_col_action'               => 'Aktion',
    'perm_col_admin'                => 'Admin',
    'perm_col_editor'               => 'Editor',
    'perm_col_reader'               => 'Leser',
    'perm_group_items'              => 'Gegenstände',
    'perm_group_bulk'               => 'Bulk-Aktionen',
    'perm_group_media'              => 'Bilder & Dokumente',
    'perm_group_export'             => 'Export',
    'perm_group_settings'           => 'Einstellungen',
    'perm_group_admin'              => 'Admin-Bereich',
    'perm_locked'                   => 'Diese Berechtigung ist fest',
    'perm_manage_link'              => 'Rechte verwalten →',
    'perm_act_items_view'           => 'Gegenstände anzeigen (Hauptliste)',
    'perm_act_items_search'         => 'Erweiterte Suche & Filter',
    'perm_act_items_add'            => 'Neuen Gegenstand anlegen',
    'perm_act_items_edit'           => 'Gegenstand bearbeiten',
    'perm_act_items_delete'         => 'Gegenstand löschen',
    'perm_act_items_hide'           => 'Gegenstand verbergen/einblenden',
    'perm_act_items_value'          => 'Aktuellen Wert & Werthistorie pflegen',
    'perm_act_bulk_delete'          => 'Mehrere Gegenstände löschen',
    'perm_act_bulk_hide'            => 'Mehrere Gegenstände verbergen',
    'perm_act_bulk_update'          => 'Kategorie/Ort für mehrere setzen',
    'perm_act_media_view'           => 'Bilder anzeigen',
    'perm_act_media_upload'         => 'Bilder hochladen',
    'perm_act_media_delete'         => 'Bilder löschen',
    'perm_act_docs_view'            => 'Dokumente anzeigen',
    'perm_act_docs_upload'          => 'Dokumente hochladen',
    'perm_act_docs_delete'          => 'Dokumente löschen',
    'perm_act_export_pdf'           => 'PDF exportieren',
    'perm_act_export_csv'           => 'Excel/CSV exportieren',
    'perm_act_export_html'          => 'HTML exportieren',
    'perm_act_export_insurance'     => 'Versicherungsexport',
    'perm_act_export_qr'            => 'QR-Codes generieren',
    'perm_act_settings_theme'       => 'Theme ändern',
    'perm_act_settings_lang'        => 'Sprache wechseln (DE/EN)',
    'perm_act_settings_columns'     => 'Spalten konfigurieren',
    'perm_act_settings_password'    => 'Eigenes Passwort ändern',
    'perm_act_admin_users'          => 'Benutzer verwalten',
    'perm_act_admin_categories'     => 'Kategorien verwalten',
    'perm_act_admin_locations'      => 'Orte verwalten',
    'perm_act_admin_logs'           => 'Aktivitätslog einsehen',
    'perm_act_admin_backup'         => 'Backups erstellen & verwalten',
    'perm_act_admin_permissions'    => 'Berechtigungen konfigurieren',
    'perm_act_admin_restore'        => 'Backups wiederherstellen (Restore)',

    // Öffentlicher Link
    'pub_page_heading'              => 'Öffentlicher Ansichts-Link',
    'pub_page_subtitle'             => 'Teile deine Sammlung ohne Login – kontrolliert per Token',
    'pub_link_status'               => 'Link-Status',
    'pub_link_active'               => 'Link ist aktiv',
    'pub_generate_link'             => 'Neuen Link generieren',
    'pub_revoke_link'               => 'Link deaktivieren',
    'pub_no_link_active'            => 'Kein öffentlicher Link aktiv',
    'pub_generate_hint'             => 'Generiere einen Link, um deine markierten Gegenstände öffentlich zu teilen.',
    'pub_appearance'                => 'Darstellung',
    'pub_page_title_label'          => 'Seiten-Titel',
    'pub_description_label'         => 'Beschreibungstext',
    'pub_show_price'                => 'Kaufpreis anzeigen',
    'pub_show_location'             => 'Standort anzeigen',
    'pub_visibility_label'          => 'Sichtbarkeit der Gegenstände',
    'pub_own_items_only'            => 'Benutzer sehen nur eigene Gegenstände',
    'pub_own_items_desc'            => 'Wenn aktiviert, sieht jeder Benutzer nur Gegenstände bei denen „Erstellt von" seinem Benutzernamen entspricht. Admins und Benutzer mit „Alle sehen"-Berechtigung sind ausgenommen.',
    'pub_notes_title'               => 'Hinweise',
    'pub_of'                        => 'von',
    'pub_items_marked_public'       => 'Gegenstände sind als öffentlich markiert',
    'pub_note_token_title'          => 'Token-gesichert',
    'pub_note_token_desc'           => 'nur wer den Link kennt, kann die Seite sehen',
    'pub_note_noindex'              => 'wird von Suchmaschinen nicht indexiert',
    'pub_note_mark_items_title'     => 'Gegenstände markieren',
    'pub_note_mark_items_desc'      => 'in edit.php das Häkchen „Öffentlich sichtbar" setzen',
    'pub_note_new_link_title'       => 'Neuen Link',
    'pub_note_new_link_desc'        => 'macht alte Links sofort ungültig',
    'pub_note_deactivate_title'     => 'Deaktivieren',
    'pub_note_deactivate_desc'      => 'Link ist sofort nicht mehr aufrufbar',
    'pub_no_public_items_hint'      => 'Bearbeite Gegenstände und setze das Häkchen „Öffentlich sichtbar".',
    'pub_placeholder_title'         => 'Meine Sammlung',
    'pub_placeholder_desc'          => 'Kurze Beschreibung deiner Sammlung...',
    'btn_back_to_overview'          => '← Zurück zur Übersicht',


    'nav_permissions' => 'Rechtevergabe',

    // Locations — usage badges
    'loc_items_count' => 'Gegenstände',
    'loc_not_used' => 'Nicht verwendet',
    'loc_err_room_items_used' => 'Dieser Raum wird noch von %d Gegenstand/Gegenständen verwendet.',
    'loc_err_position_items_used' => 'Diese Position wird noch von %d Gegenstand/Gegenständen verwendet.',

    'gallery_col_preview' => 'Vorschau',
    'gallery_col_location' => 'Ort',
    'gallery_col_size' => 'Größe',

    // ============================================================================
    // 2FA SETUP
    // ============================================================================
    '2fa_page_title'           => '2FA-Einrichtung',
    '2fa_page_subtitle'        => 'Schütze deinen Account mit einem zusätzlichen Sicherheitsschritt.',
    '2fa_status_active'        => '2FA ist aktiv',
    '2fa_status_active_desc'   => 'Dein Account ist mit TOTP geschützt.',
    '2fa_btn_show_backup'      => '🔑 Backup-Codes anzeigen',
    '2fa_btn_disable'          => '2FA deaktivieren',
    '2fa_status_inactive'      => '2FA ist nicht aktiv',
    '2fa_status_inactive_desc' => 'Aktiviere 2FA für mehr Sicherheit.',
    '2fa_app_hint'             => 'Du benötigst eine Authenticator-App auf deinem Smartphone:',
    '2fa_btn_setup'            => '🔐 2FA einrichten',
    '2fa_step1_title'          => 'Schritt 1: QR-Code scannen',
    '2fa_step1_desc'           => 'Öffne deine Authenticator-App und scanne diesen QR-Code:',
    '2fa_manual_entry'         => 'Secret manuell eingeben',
    '2fa_step2_title'          => 'Schritt 2: Code bestätigen',
    '2fa_code_label'           => '6-stelliger Code aus der App',
    '2fa_code_placeholder'     => '6-oder-8-stellig',
    '2fa_code_hint'            => '6-stelliger App-Code oder 8-stelliger Backup-Code',
    '2fa_btn_confirm'          => '✅ Bestätigen & aktivieren',
    '2fa_btn_cancel'           => 'Abbrechen',
    '2fa_backup_title'         => '🔑 Backup-Codes',
    '2fa_backup_success'       => '✅ 2FA wurde erfolgreich aktiviert! Speichere diese Backup-Codes sicher.',
    '2fa_backup_desc'          => 'Verwende diese Codes wenn du keinen Zugriff auf deine Authenticator-App hast.',
    '2fa_backup_once'          => 'Jeder Code kann nur einmal verwendet werden.',
    '2fa_btn_print'            => '🖨 Drucken',
    '2fa_btn_done'             => 'Fertig',
    '2fa_disable_title'        => '2FA deaktivieren',
    '2fa_disable_warning'      => '⚠️ Nach dem Deaktivieren ist dein Account nur noch mit Passwort geschützt.',
    '2fa_disable_code_label'   => 'Code aus der Authenticator-App',
    '2fa_disabled_msg'         => '2FA wurde deaktiviert.',
    '2fa_error_session'        => 'Session abgelaufen. Bitte neu starten.',
    '2fa_error_code_invalid'   => 'Code ungültig oder abgelaufen. Bitte nochmal versuchen.',
    '2fa_error_disable'        => 'Code ungültig. 2FA wurde nicht deaktiviert.',
    '2fa_error_generic'        => 'Code ungültig.',


    // 4.3.5 — Texte aus alert()/confirm()/prompt(), vorher fest verdrahtet
    'error_camera_unavailable' => 'Kamera nicht verfügbar: %s',
    'error_cover_load'         => 'Cover konnte nicht geladen werden: %s',
    'log_enter_valid_days'     => 'Bitte eine gültige Anzahl Tage eingeben.',
    'error_unknown'            => 'Unbekannter Fehler',
    'error_save_prefix'        => 'Fehler beim Speichern: %s',
    'index_select_action'      => 'Bitte Aktion auswählen',
    'confirm_delete_image'     => 'Bild wirklich löschen?',
    'confirm_remove_avatar'    => 'Profilbild entfernen?',
    'loc_err_cannot_delete'    => '%s und kann nicht gelöscht werden.',

    // 4.3.8 — Schluessel zu tn()-Aufrufen, die bisher nur ihren deutschen Rueckfalltext zeigten
    'export_location_unknown' => 'Unbekannt',
    'form_change_name'        => 'Namen ändern',
    'form_save_anyway'        => 'Trotzdem speichern',
    'index_bulk_action'       => 'Aktion…',
    'index_bulk_apply'        => 'Anwenden',
    'index_bulk_hide'         => 'Verbergen',
    'index_bulk_unhide'       => 'Wieder anzeigen',
    'index_filter_category'   => 'Kategorie filtern',
    'nav_gallery'             => 'Galerie',
    'nav_help'                => 'Hilfe & Informationen',
    'nav_insurance'           => 'Versicherungen',
    'nav_profile'             => 'Profil',
    'settings_language_title' => 'Sprache',

    // ── Versicherungsuebersicht (4.3.17) ──────────────────────────────
    'ins_cov_title'        => 'Deckung',
    'ins_cov_ok'           => 'Versicherungssumme deckt den erfassten Wert (%s %%)',
    'ins_cov_tight'        => 'Knapp unterversichert — die Versicherungssumme deckt %s %% des erfassten Werts',
    'ins_cov_under'        => 'Unterversichert — die Versicherungssumme deckt nur %s %% des erfassten Werts',
    'ins_cov_gap'          => 'Es fehlen %s',
    'ins_cov_no_sum'       => 'Keine Versicherungssumme hinterlegt — zur Deckung ist keine Aussage möglich',
    'ins_cov_unvalued'     => 'Ohne Wertangabe: %s von %s Gegenständen — der erfasste Wert ist eine Untergrenze',
    'ins_print'            => 'Drucken / Als PDF speichern',
    'ins_created_on'       => 'Erstellt am %s',
    'ins_doc_created_on'   => 'Dokument erstellt am %s',
    'ins_none_yet'         => 'Noch keine Versicherungen vorhanden.',
    'ins_provider'         => 'Anbieter',
    'ins_contract_no'      => 'Nr.',
    'ins_premium'          => 'Prämie',
    'ins_per_year'         => '%s/Jahr',
    'ins_sum'              => 'Versicherungssumme',
    'ins_sum_short'        => 'Vers.-Summe',
    'ins_start'            => 'Beginn',
    'ins_until'            => 'Läuft bis',
    'ins_expired'          => 'abgelaufen',
    'ins_contract_data'    => 'Vertragsdaten',
    'ins_deductible'       => 'Selbstbeteiligung',
    'ins_payment_mode'     => 'Zahlungsweise',
    'ins_notice_period'    => 'Kündigungsfrist',
    'ins_object'           => 'Objekt',
    'ins_address'          => 'Adresse',
    'ins_building_type'    => 'Gebäudeart',
    'ins_living_area'      => 'Wohnfläche',
    'ins_rooms'            => 'Zimmer',
    'ins_floor'            => 'Etage',
    'ins_ground_floor'     => 'Erdgeschoss',
    'ins_floor_nth'        => '%s. Etage',
    'ins_cellar'           => 'Keller',
    'ins_yes'              => 'Ja',
    'ins_no'               => 'Nein',
    'ins_modules'          => 'Zusatzbausteine',
    'ins_module_bike'      => 'Fahrraddiebstahl',
    'ins_module_glass'     => 'Glasbruch',
    'ins_module_elemental' => 'Elementarschäden',
    'ins_module_surge'     => 'Überspannung',
    'ins_no_items'         => 'Keine Gegenstände zugeordnet.',
    'ins_note'             => 'Notiz',
    'ins_items_count'      => '%s Gegenstände',
    'ins_total_all'        => 'Gesamtwert aller versicherten Gegenstände',
    'ins_value_eur'        => 'Wert (€)',
];
