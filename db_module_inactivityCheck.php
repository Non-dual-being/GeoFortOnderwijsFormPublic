<?php

// Start sessie en laad beveiligde instellingen

// ! Controleer of HTTPS wordt gebruikt voor een veilige verbinding
// - $_SERVER['HTTPS']: Controleert of HTTPS actief is
// - $_SERVER['SERVER_PORT'] == 443: Controleert of de poort overeenkomt met HTTPS
// - De variabele $isSecure wordt true als een van deze condities klopt

$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
session_start([
    'use_only_cookies' => 1,
    'cookie_httponly' => 1,
    'cookie_secure' => $isSecure,
    'cookie_samesite' => 'Strict'
]);

/** 
 * 'use_only_cookies' => 1 --> voorkomt dat cookies overgenomnen kunnen worden via url
 * 'cookie_httponly' => 1 --> Javascript kan cookie niet uitlezen 
 * Als https aanstaat dan alleen toelaten via HTTPS
 * Cookies mogen alleen op dezelfde site worden doorgestuurd
 * 
 * 
*/


// Controleer of de gebruiker is ingelogd en of de sessie nog geldig is

/**
 * Dit controleren gebeurt op basis van de sessie id
 * Vanuit de inlog pagina wordt de sessie id doorgestuurd door de server naar deze pagina
 * De id wordt gebruikt om onderstaande gegevens te controleren
 */
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['username'] !== "My Random User Name") {
    header("Location: db_inlogPagina.php");
    exit();
}

$inactive = 900;

if (isset($_SESSION['LAST_ACTIVITY'])) {
    if (time() - $_SESSION['LAST_ACTIVITY'] > $inactive) {
        session_unset();
        session_destroy();
        header("Location: db_inlogPagina.php?InactivityMessage=sessie_verlopen");
        exit();
    }
}

$_SESSION['LAST_ACTIVITY'] = time(); // Werk tijd van laatste activiteit bij
?>