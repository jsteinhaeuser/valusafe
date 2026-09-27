<?php
/**
 * Migration 004 — app_settings: fehlende Default-Keys nachtragen
 * (Lizenz-/Backup-Berechtigungs-Keys aus db_check.php)
 */
$defaults = [
    'app_version'                 => '4.1',
    'licence_valid'               => '0',
    'licence_tier'                => 'private',
    'licence_expires'             => '',
    'licence_days_left'           => '0',
    'licence_last_check'          => '',
    'sa_backup_php_allowed'       => '1',
    'sa_backup_download_allowed'  => '1',
    'sa_backup_images_allowed'    => '1',
    'sa_restore_allowed'          => '1',
    'only_own_items'              => '0',
];

$statements = [];
foreach ($defaults as $key => $value) {
    $k = addslashes($key);
    $v = addslashes($value);
    $statements[] = "INSERT IGNORE INTO `app_settings` (`setting_key`, `setting_value`) VALUES ('$k', '$v')";
}

return [
    'description' => 'app_settings: Lizenz-/Backup-Default-Keys',
    'statements'  => $statements,
];
