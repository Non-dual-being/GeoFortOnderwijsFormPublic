<?php

/**-----------[default error settings]------------------ */
ini_set('display_errors', 0);  // Schakel weergave van fouten in de browser uit
ini_set('log_errors', 1);      // Log fouten naar een logbestand
error_reporting(E_ALL);        // Log alle fouten

/**|||-------------------------[functions]---------------------------------------||| */
function setAndResetHash(&$hash, $newHash = '') {
    if (is_string($newHash) && !empty($newHash)) {
        $hash = htmlspecialchars(strip_tags($newHash), ENT_QUOTES, 'UTF-8'); // Beveilig de input
    } else {
        $hash = ''; // Reset naar een lege string als de input ongeldig is
    }
}

/**|| -----------  Valideer Functies ------------------------ ||*/


function convertDutchToEnglishDate($date) {
    $dutchMonths = [
        'januari', 'februari', 'maart', 'april', 'mei', 'juni', 
        'juli', 'augustus', 'september', 'oktober', 'november', 'december'
    ];
    
    $englishMonths = [
        'January', 'February', 'March', 'April', 'May', 'June', 
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    return str_replace($dutchMonths, $englishMonths, $date);
}


function convertEnglishDatetoDutchDate($date) {
    $dutchMonths = [
        'januari', 'februari', 'maart', 'april', 'mei', 'juni', 
        'juli', 'augustus', 'september', 'oktober', 'november', 'december'
    ];
    
    $englishMonths = [
        'January', 'February', 'March', 'April', 'May', 'June', 
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    return str_replace($englishMonths, $dutchMonths, $date);
}

function get_SQL_Date($dateNL) {
    // Converteer Nederlandse maandnamen naar Engelse maandnamen
    $dateEnglish = convertDutchToEnglishDate($dateNL);

    // Gebruik DateTime::createFromFormat om de Engelse datum om te zetten naar een DateTime-object
    $datumObject = DateTime::createFromFormat('d F Y', $dateEnglish);

    if ($datumObject) {
        // Formatteer de datum in SQL-formaat (Y-m-d)
        return $datumObject->format('Y-m-d');
    }

    // Ongeldige invoer
    return null;
}

function sanitize_datum($datum, &$errors, $maxLength = 10, $fieldName = 'datum') {
    if (!$datum) {
        $errors[$fieldName] = "Er is geen datum doorgegeven";
        return;
    }

    // Valideer of de ontvangen datum in het juiste formaat is (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) {
        $errors[$fieldName] = "Er is geen datum in het geldige formaat doorgegeven";
        return;
    }

    list($year, $month, $day) = explode('-', $datum); 
    if (!checkdate((int)$month, (int)$day, (int)$year)) {
        $errors[$fieldName] = "De datum is geen kalender datum";
        exit;
    }
        /**
     * explode zet de datum in array
     * list wijst automatisch vanuit de array de waarden toe
     * (int) is een cast
     */

     $sanitizedDatum = htmlspecialchars(strip_tags(stripslashes(trim($datum))));
     if (strlen($sanitizedDatum) > $maxLength) {
         $errors[$fieldName] = "ongeldige datum doorgegeven";
         return false;
     }
 
     return $sanitizedDatum;
 }

 function sqlDatumNaarNederlandseNotatie($sqlDatum) {
    if (!$sqlDatum) {
        return null; // Voorkomt fouten bij lege of ongeldige invoer
    }

    // Zet de SQL-datum om naar een DateTime-object
    $date = DateTime::createFromFormat('Y-m-d', $sqlDatum);

    /**
     * Y-m-d is het te lezen formaat, niet waar het nnaar omgezet wordt
     * volledig jaartal, maand in twee cijefers, dag in twe cijfers
     */

    // Controleer of de conversie gelukt is
    if ($date) {
        // Nederlandse maandnamen
        $dutchMonths = [
            'januari', 'februari', 'maart', 'april', 'mei', 'juni', 
            'juli', 'augustus', 'september', 'oktober', 'november', 'december'
        ];

        // Haal de dag, maand en jaar op
        $day = $date->format('j'); // Zonder nul voor getallen onder de 10 (veloopnul weghalen)
        $monthIndex = (int) $date->format('n') - 1; // Haal de juiste index op (1-based naar 0-based)
        $year = $date->format('Y');

        // Vorm de datumstring: "13 februari 2025"
        return "{$day} {$dutchMonths[$monthIndex]} {$year}";
    } else {
        return null; // Fallback als het geen geldige datum was
    }
}

function displayMessage($target, $message, $resetServerMessages, &$FlashMessages, $type = 'error') {

    if (array_key_exists($target, $FlashMessages['message'])){
        $resetServerMessages[$target]($FlashMessages);
        $FlashMessages['message'][$target] = $message;
        $FlashMessages['type'] = $type;
    } else {
        return;
    }
    return [$FlashMessages['message'][$target], $FlashMessages['type']];
    
}

function sanitize_status($status, &$errors) {
    $allowedStatuses = ['Definitief', 'In optie', 'Afgewezen'];
    if (!in_array($status, $allowedStatuses)) {
        $errors['status'] = "Ongeldige status geselecteerd.";
        return false;
    }

    $sanitizedStatus = htmlspecialchars(strip_tags(stripslashes(trim($status))));
    return $sanitizedStatus;
}




 
?>