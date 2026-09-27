<?php
/**
 * Language Helper Functions
 * NUR die Funktionen die db.php NICHT hat!
 */

// getCurrentLanguage() - db.php hat das nicht!
if (!function_exists('getCurrentLanguage')) {
    function getCurrentLanguage() {
        // Session hat höchste Priorität
        if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['de', 'en'])) {
            return $_SESSION['lang'];
        }
        
        // Cookie als Fallback
        if (isset($_COOKIE['user_language']) && in_array($_COOKIE['user_language'], ['de', 'en'])) {
            return $_COOKIE['user_language'];
        }
        
        // Default
        return defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'de';
    }
}

// setLanguage() - Sprache setzen
if (!function_exists('setLanguage')) {
    function setLanguage($lang) {
        if (in_array($lang, ['de', 'en'])) {
            $_SESSION['lang'] = $lang;
            setcookie('user_language', $lang, time() + (365 * 24 * 60 * 60), '/');
            return true;
        }
        return false;
    }
}

// t() - Übersetzung (nur wenn nicht in db.php)
if (!function_exists('t')) {
    function t($key, $params = []) {
        global $translations;
        
        if (!isset($translations[$key])) {
            error_log("Translation missing: $key");
            return $key;
        }
        
        $text = $translations[$key];
        
        if (!empty($params)) {
            return vsprintf($text, $params);
        }
        
        return $text;
    }
}

// getAvailableLanguages()
if (!function_exists('getAvailableLanguages')) {
    function getAvailableLanguages() {
        return [
            'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
            'en' => ['name' => 'English', 'flag' => '🇬🇧']
        ];
    }
}
?>
