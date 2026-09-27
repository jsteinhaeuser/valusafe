-- =============================================================================
-- ValuSafe — Datenbankschema
--
-- Wird beim ERSTEN Start des Datenbank-Containers automatisch ausgefuehrt
-- (docker-entrypoint-initdb.d). Bei einem bestehenden Datenverzeichnis laeuft
-- die Datei nicht erneut.
--
-- Erzeugt aus dem Schema der Referenzinstanz (mysqldump --no-data, damals 22
-- Tabellen). Am 26.09.2026 um drei Altlasten auf 19 gekuerzt:
--
--   orte                 erstes Raummodell, vom Code nicht mehr gelesen;
--                        mit ihr faellt wertsachen.ort_id samt Fremdschluessel
--   standort_raeume,     gehoerten zu backend/standorte.php, einer zweiten
--   standort_positionen  Ortsverwaltung, die nie mit wertsachen verbunden war
--                        (entfernt mit 4.3.21)
--
-- Geblieben ist das eine Modell, das der Code benutzt:
--   raeume  <-  standorte.raum_id  <-  positionen.raum_id (zeigt auf standorte!)
--   wertsachen.raum_id -> raeume, wertsachen.position_id -> positionen,
--   wertsachen.standort_id -> standorte (Standort ohne Position; mit
--   Position gilt deren Standort)
--
-- HINWEIS ZU BENUTZERKONTEN
-- Diese Datei legt bewusst KEINE Benutzer an. Frueher standen hier drei
-- Demokonten mit fest eingebauten Passwort-Hashes; das Administratorkonto
-- trug dabei den in unzaehligen Anleitungen kursierenden Hash von "password".
-- Das Administratorkonto wird jetzt beim ersten Start von docker/entrypoint.sh
-- angelegt, mit dem Passwort aus ADMIN_PASS in der .env — oder, wenn die
-- Variable leer bleibt, mit einem Zufallspasswort, das einmalig ins
-- Container-Log geschrieben wird.
-- =============================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `aktion` varchar(50) NOT NULL,
  `tabelle` varchar(100) NOT NULL,
  `datensatz_id` int(11) NOT NULL,
  `bezeichnung` varchar(255) DEFAULT NULL,
  `alt_wert` text DEFAULT NULL,
  `neu_wert` text DEFAULT NULL,
  `zeitstempel` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_adresse` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_benutzer` (`user_id`),
  KEY `idx_tabelle_datensatz` (`tabelle`,`datensatz_id`),
  KEY `idx_zeitstempel` (`zeitstempel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dokumente` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wertsache_id` int(11) NOT NULL,
  `dateiname` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `dateityp` varchar(50) NOT NULL,
  `dateigröße` int(11) NOT NULL,
  `hochgeladen_am` datetime DEFAULT current_timestamp(),
  `hochgeladen_von` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_wertsache` (`wertsache_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `item_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_primary` (`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kategorien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `erstellt_am` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `attempted_at` timestamp NULL DEFAULT current_timestamp(),
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip` (`ip_address`),
  KEY `idx_attempted` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action_key` varchar(80) NOT NULL,
  `section` varchar(40) NOT NULL,
  `icon` varchar(10) NOT NULL DEFAULT '',
  `label` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `admin_can` tinyint(1) NOT NULL DEFAULT 1,
  `edit_can` tinyint(1) NOT NULL DEFAULT 0,
  `read_can` tinyint(1) NOT NULL DEFAULT 0,
  `locked_for` set('admin','edit','read') DEFAULT NULL COMMENT 'Rollen die NICHT geändert werden dürfen',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_action` (`action_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `positionen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raum_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `beschreibung` varchar(255) DEFAULT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `raum_id` (`raum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `raeume` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `erstellt_am` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rate_limit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `action` varchar(50) NOT NULL,
  `attempts` int(11) DEFAULT 1,
  `first_attempt` datetime NOT NULL,
  `last_attempt` datetime NOT NULL,
  `locked_until` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ip_action` (`ip_address`,`action`),
  KEY `idx_locked` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `security_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `standorte` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `raum_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `erstellt_am` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'read',
  `theme` varchar(20) DEFAULT 'light',
  `standard_ersteller` varchar(100) DEFAULT NULL,
  `dokumente_aktiv` tinyint(1) DEFAULT 1,
  `erstellt_am` datetime DEFAULT current_timestamp(),
  `letzter_login` datetime DEFAULT NULL,
  `spalten_auswahl` text DEFAULT NULL COMMENT 'JSON-String mit ausgewählten Spalten für index.php Tabelle',
  `avatar` varchar(255) DEFAULT NULL,
  `sieht_alle` tinyint(1) NOT NULL DEFAULT 0,
  `last_seen_version` varchar(20) DEFAULT NULL,
  `lang` varchar(5) DEFAULT 'de',
  `totp_secret` varchar(32) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `totp_backup_codes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `benutzername` (`username`),
  KEY `idx_benutzername` (`username`),
  KEY `idx_berechtigung` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_activity` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'read',
  `last_seen` datetime NOT NULL,
  `current_page` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `idx_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `versicherungen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `anbieter` varchar(255) DEFAULT NULL,
  `vertragsnummer` varchar(100) DEFAULT NULL,
  `praemie` decimal(10,2) DEFAULT NULL,
  `versicherungssumme` decimal(10,2) DEFAULT NULL,
  `selbstbeteiligung` decimal(10,2) DEFAULT NULL,
  `vertragsbeginn` date DEFAULT NULL,
  `kuendigungsfrist` varchar(100) DEFAULT NULL,
  `zahlungsweise` enum('monatlich','vierteljaehrlich','halbjaehrlich','jaehrlich') DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `plz` varchar(10) DEFAULT NULL,
  `wohnort` varchar(100) DEFAULT NULL,
  `wohnflaeche_qm` decimal(6,1) DEFAULT NULL,
  `anzahl_zimmer` int(11) DEFAULT NULL,
  `gebaeudeart` enum('Wohnung','Haus','Gewerbe','Sonstiges') DEFAULT NULL,
  `etage` int(11) DEFAULT NULL,
  `keller` tinyint(1) DEFAULT 0,
  `fahrraddiebstahl` tinyint(1) DEFAULT 0,
  `glasbruch` tinyint(1) DEFAULT 0,
  `elementarschaeden` tinyint(1) DEFAULT 0,
  `ueberspannung` tinyint(1) DEFAULT 0,
  `laufzeit_bis` date DEFAULT NULL,
  `notiz` text DEFAULT NULL,
  `erstellt_am` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `wertsachen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `kategorie_id` int(11) DEFAULT NULL,
  `kaufdatum` date DEFAULT NULL,
  `preis` decimal(10,2) DEFAULT 0.00,
  `aktueller_wert` decimal(10,2) DEFAULT NULL,
  `aktueller_wert_datum` date DEFAULT NULL,
  `notizen` text DEFAULT NULL,
  `bild` varchar(255) DEFAULT NULL,
  `erstellt_von` varchar(100) DEFAULT NULL,
  `gelistet_am` date DEFAULT NULL,
  `erstellt_am` datetime DEFAULT current_timestamp(),
  `geaendert_am` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hidden` tinyint(1) DEFAULT 0,
  `schadenfall` tinyint(1) DEFAULT 0,
  `custom1_wert` text DEFAULT NULL,
  `custom1_typ` enum('text','zahl') DEFAULT 'text',
  `custom2_wert` text DEFAULT NULL,
  `custom2_typ` enum('text','zahl') DEFAULT 'text',
  `barcode` varchar(100) DEFAULT NULL,
  `versicherung_id` int(11) DEFAULT NULL,
  `oeffentlich` tinyint(1) NOT NULL DEFAULT 0,
  `public_token` varchar(64) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `position_id` int(11) DEFAULT NULL,
  `raum_id` int(11) DEFAULT NULL,
  `standort_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_kategorie` (`kategorie_id`),
  KEY `idx_erstellt_von` (`erstellt_von`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_versicherung_id` (`versicherung_id`),
  KEY `idx_oeffentlich` (`oeffentlich`),
  KEY `idx_wertsachen_sort` (`sort_order`),
  KEY `idx_standort_position_id` (`position_id`),
  KEY `idx_raum` (`raum_id`),
  KEY `idx_standort` (`standort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `wert_historie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wertsache_id` int(11) NOT NULL,
  `wert` decimal(10,2) NOT NULL,
  `datum` date NOT NULL,
  `notiz` varchar(255) DEFAULT NULL,
  `erstellt_am` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wertsache_datum` (`wertsache_id`,`datum`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE `dokumente`
  ADD CONSTRAINT `dokumente_ibfk_1` FOREIGN KEY (`wertsache_id`) REFERENCES `wertsachen` (`id`) ON DELETE CASCADE;

ALTER TABLE `item_images`
  ADD CONSTRAINT `item_images_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `wertsachen` (`id`) ON DELETE CASCADE;

ALTER TABLE `security_log`
  ADD CONSTRAINT `security_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `wertsachen`
  ADD CONSTRAINT `fk_versicherung` FOREIGN KEY (`versicherung_id`) REFERENCES `versicherungen` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wertsachen_ibfk_2` FOREIGN KEY (`kategorie_id`) REFERENCES `kategorien` (`id`) ON DELETE SET NULL;

ALTER TABLE `wert_historie`
  ADD CONSTRAINT `wert_historie_ibfk_1` FOREIGN KEY (`wertsache_id`) REFERENCES `wertsachen` (`id`) ON DELETE CASCADE;

-- ─── Startdaten ──────────────────────────────────────────────────────────────
-- INSERT IGNORE, damit ein wiederholter Lauf nicht an bestehenden Zeilen
-- scheitert. Vollstaendig wiederholbar ist die Datei damit trotzdem nicht: die
-- ADD-CONSTRAINT-Anweisungen weiter oben brechen beim zweiten Lauf ab, weil die
-- Fremdschluessel dann bereits existieren. Das ist unkritisch — Docker fuehrt
-- init.sql nur bei leerem Datenverzeichnis aus.

INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('instance_name',             'ValuSafe'),
('public_link_enabled',       '0'),
('public_token',              ''),
('backup_enabled',            '1'),
('only_own_items',            '0'),
('sa_backup_db_allowed',      '1'),
('sa_backup_php_allowed',     '1'),
('sa_backup_images_allowed',  '1'),
('sa_backup_download_allowed','1'),
('sa_restore_allowed',        '1');

-- Startwerte fuer Raeume und Kategorien. Beide sind in der Anwendung frei
-- editierbar; sie sollen nur verhindern, dass die erste Maske leer ist.
--
-- Bis 26.09.2026 gingen die Raeume in die Tabelle `orte`, die der Code seit
-- der Umstellung auf `raeume` nicht mehr liest. Eine Neuinstallation hatte
-- dadurch vier Raeume in der Datenbank und ein leeres Raum-Auswahlfeld.
INSERT IGNORE INTO `raeume` (`name`) VALUES
('Wohnzimmer'), ('Schlafzimmer'), ('Büro'), ('Keller');

INSERT IGNORE INTO `kategorien` (`name`) VALUES
('Elektronik'), ('Schmuck'), ('Möbel'), ('Musikinstrumente'), ('Sonstiges');

-- Berechtigungen. OHNE diese Zeilen ist die Tabelle leer; hasPermission()
-- faellt dann auf die fest verdrahteten Vorgaben in _permissionFallback()
-- zurueck und die Seite backend/permissions.php zeigt eine leere Liste — die
-- Rechteverwaltung waere also vorhanden, aber unbedienbar. Bis 4.3.22 legte
-- nur der Setup-Assistent sie an, eine Docker-Installation bekam sie nie.
--
-- Die 32 Eintraege sind aus der Berechtigungstabelle des Masters uebernommen
-- und Schluessel fuer Schluessel mit der Liste im alten Setup-Assistenten
-- (v3.27, Mai 2026) abgeglichen: identisch, in sechs Monaten nicht
-- auseinandergelaufen. id und created_at fehlen absichtlich — die vergibt
-- die Datenbank.
INSERT IGNORE INTO `permissions`
  (`action_key`, `section`, `icon`, `label`, `sort_order`,
   `admin_can`, `edit_can`, `read_can`, `locked_for`) VALUES
  ('items_view', 'Gegenstände', '📋', 'Gegenstände anzeigen (Hauptliste)', 1, 1, 1, 1, 'admin,edit,read'),
  ('items_search', 'Gegenstände', '🔍', 'Erweiterte Suche & Filter', 2, 1, 1, 1, 'admin'),
  ('items_add', 'Gegenstände', '➕', 'Neuen Gegenstand anlegen', 3, 1, 1, 0, 'admin'),
  ('items_edit', 'Gegenstände', '✏️', 'Gegenstand bearbeiten', 4, 1, 1, 0, 'admin'),
  ('items_delete', 'Gegenstände', '🗑️', 'Gegenstand löschen', 5, 1, 1, 0, 'admin'),
  ('items_hide', 'Gegenstände', '👁️', 'Gegenstand verbergen/einblenden', 6, 1, 1, 0, 'admin'),
  ('items_value', 'Gegenstände', '📈', 'Aktuellen Wert & Werthistorie pflegen', 7, 1, 1, 0, 'admin'),
  ('bulk_delete', 'Bulk-Aktionen', '🗑️', 'Mehrere Gegenstände löschen', 8, 1, 1, 0, 'admin'),
  ('bulk_hide', 'Bulk-Aktionen', '👁️', 'Mehrere Gegenstände verbergen', 9, 1, 1, 0, 'admin'),
  ('bulk_update', 'Bulk-Aktionen', '🏷️', 'Kategorie/Ort für mehrere setzen', 10, 1, 1, 0, 'admin'),
  ('media_view', 'Bilder & Dokumente', '📷', 'Bilder anzeigen', 11, 1, 1, 1, 'admin'),
  ('media_upload', 'Bilder & Dokumente', '⬆️', 'Bilder hochladen', 12, 1, 1, 0, 'admin'),
  ('media_delete', 'Bilder & Dokumente', '🗑️', 'Bilder löschen', 13, 1, 1, 0, 'admin'),
  ('docs_view', 'Bilder & Dokumente', '📄', 'Dokumente anzeigen', 14, 1, 1, 1, 'admin'),
  ('docs_upload', 'Bilder & Dokumente', '⬆️', 'Dokumente hochladen', 15, 1, 1, 0, 'admin'),
  ('docs_delete', 'Bilder & Dokumente', '🗑️', 'Dokumente löschen', 16, 1, 1, 0, 'admin'),
  ('export_pdf', 'Export', '📄', 'PDF exportieren', 17, 1, 1, 1, 'admin'),
  ('export_csv', 'Export', '📊', 'Excel/CSV exportieren', 18, 1, 1, 1, 'admin'),
  ('export_html', 'Export', '🌐', 'HTML exportieren', 19, 1, 1, 1, 'admin'),
  ('export_insurance', 'Export', '🛡️', 'Versicherungsexport', 20, 1, 1, 1, 'admin'),
  ('export_qr', 'Export', '📱', 'QR-Codes generieren', 21, 1, 1, 1, 'admin'),
  ('settings_theme', 'Einstellungen', '🎨', 'Theme ändern', 22, 1, 1, 1, 'admin,edit,read'),
  ('settings_lang', 'Einstellungen', '🌐', 'Sprache wechseln (DE/EN)', 23, 1, 1, 1, 'admin,edit,read'),
  ('settings_columns', 'Einstellungen', '📊', 'Spalten konfigurieren', 24, 1, 1, 1, 'admin'),
  ('settings_password', 'Einstellungen', '👤', 'Eigenes Passwort ändern', 25, 1, 1, 1, 'admin,edit,read'),
  ('admin_users', 'Admin-Bereich', '👥', 'Benutzer verwalten', 26, 1, 0, 0, 'admin,edit,read'),
  ('admin_categories', 'Admin-Bereich', '🏷️', 'Kategorien verwalten', 27, 1, 0, 0, 'admin,edit,read'),
  ('admin_locations', 'Admin-Bereich', '📍', 'Orte verwalten', 28, 1, 0, 0, 'admin,edit,read'),
  ('admin_log', 'Admin-Bereich', '📋', 'Aktivitätslog einsehen', 29, 1, 0, 0, 'admin,edit,read'),
  ('admin_backup', 'Admin-Bereich', '💾', 'Backups erstellen & verwalten', 30, 1, 0, 0, 'admin,edit,read'),
  ('admin_permissions', 'Admin-Bereich', '🔐', 'Berechtigungen konfigurieren', 31, 1, 0, 0, 'admin,edit,read'),
  ('admin_restore', 'Admin-Bereich', '♻️', 'Backups wiederherstellen (Restore)', 32, 1, 0, 0, 'admin,edit,read');

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
