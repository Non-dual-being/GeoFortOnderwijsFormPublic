<?php
/**|| --------modules---------|| */
require_once 'db_module_inactivityCheck.php';
require 'vendor/autoload.php';
require_once 'module_makeConnectionToDataBase.php';
require_once 'defaultFunctions.php';



/**|| ---------settings-------------------------|| */
ini_set('display_errors', 0);  // Schakel weergave van fouten in de browser uit
ini_set('log_errors', 1);      // Log fouten naar een logbestand
error_reporting(E_ALL);        // Log alle fouten



/*
 * Gebruik de Dotenv-library van PHP (vlucas/phpdotenv)
 * Deze library helpt bij het lezen en laden van variabelen uit een .env-bestand in de omgeving van je applicatie.
 * Het zorgt ervoor dat gevoelige gegevens zoals wachtwoorden, API-sleutels, en configuratie-instellingen
 * buiten de broncode worden opgeslagen, zodat ze niet direct in het script worden hard gecodeerd.
 */
use Dotenv\Dotenv;

/*
 * Maak een nieuwe Dotenv-instantie aan voor de huidige directory (__DIR__).
 * __DIR__ is een ingebouwde constante die het absolute pad naar de directory bevat waar dit script zich bevindt.
 * Dit betekent dat de Dotenv-library het .env-bestand in deze directory zoekt.
 */
$dotenv = Dotenv::createImmutable(__DIR__);

/*
 * Laad de variabelen uit het .env-bestand en voeg ze toe aan de omgevingsvariabelen ($_ENV)
 * en de servervariabelen ($_SERVER).
 * Het .env-bestand bevat sleutel-waardeparen in het formaat:
 * 
 *   VARIABEL_NAAM=waarde
 * 
 * Voorbeeld:
 *   DB_HOST=localhost
 *   DB_USER=root
 *   DB_PASS=secret
 * 
 * Na het aanroepen van $dotenv->load():
 *   - $_ENV['DB_HOST'] bevat 'localhost'
 *   - $_ENV['DB_USER'] bevat 'root'
 *   - $_ENV['DB_PASS'] bevat 'secret'
 */
$dotenv->load();

// Haal de specifieke variabele op
$Hash_Key_ID = $_ENV['HASH_ID_KEY'] ?? '';


/** || ----setting up data base connection and make it globaly available --|| */
/**
 * Functie om een PDO-verbinding te maken met de database.
 * 
 * @return PDO De PDO-instantie voor de databaseverbinding.
 * @throws PDOException Als de verbinding niet kan worden gemaakt.
 */




/** || -----------Global variables------------|| */ 

//Array gebruik tijdens user input validatie bij SQL queries
$errors = []; 

//geselecteerde dag in de weekkalender
$selectedDate = null; 

//de boekingen om de status te veranderen opgevraagd door weekkalander of in optie knop
$requests = []; 

//bericht weekalender
$Flashmessage_weekaanvraagkalender = ''; 

// bericht sumbit knop week kalender
$Flashmessage_weekaanvraag = ''; 

// bericht in optie knop get verzoek 
$Flashmessage_inoptieaanvraag = '';

//algemeen bericht 
$Flashmessage_general = '';

$Flasmessage_requests_general = '';

// 'success' of 'error'
$FlashmessageType = ''; 

//Requestype
$RequestType = '';

$hash = '';

$FlashMessages = [
    'message' => [
        'weekaanvraag' => '',
        'In optie' => '',
        'weekkalender' => '',
        'requests-general' => '',
        'general' => '',
        
    ],
    'type' => ''
];


$resetServerMessages = [
    'weekaanvraag' => function (&$FlashMessages){
        $FlashMessages['message']['weekaanvraag'] = '';
        $FlashMessages['type'] = '';
    },
    'In optie' => function (&$FlashMessages){
        $FlashMessages['message']['In optie'] = '';
        $FlashMessages['type'] = '';
    },
    'weekkalender' => function (&$FlashMessages){
        $FlashMessages['message']['weekkalender'] = '';
        $FlashMessages['type'] = '';
    },
    'requests-general' => function (&$FlashMessages){
        $FlashMessages['message'][ 'requests-general'] = '';
        $FlashMessages['type'] = '';
    },

    'general' => function (&$FlashMessages){
        $FlashMessages['message']['general'] = '';
        $FlashMessages['type'] = '';
    },
];




function return_type_of_get_Request() {
 //met get kun de parameters uit de url halen , de request methode is hier onafhankelijk van
    if (isset($_GET['verzoek'])) {
     
        if ($_GET['verzoek'] === "weekaanvraag") {
       
            return 'weekaanvraag';
        } else if ($_GET['verzoek'] === "In optie") {
       
            return 'In optie';
        } else {
            return 'unknown request';
        }
    }
    return 'general';
}

/**|| ----------kalender in laden met globale variable zonder gebruikers interactie ----------------|| */ 

function get_weekdata_for_calendar($pdo, $resetServerMessages) {
    $response = ['weekdata' => []];
    try {
        $sql = "
        SELECT DISTINCT 
            WEEK(bezoekdatum, 3) AS weeknummer, 
            YEAR(bezoekdatum) AS jaar 
        FROM aanvragen
        WHERE status IN ('In Optie', 'Definitief', 'Afgewezen')
        ORDER BY jaar ASC, weeknummer ASC;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($result)) {
            $repsonse['weekdata'] = $result;
        } else {
            list($Flashmessage_weekaanvraagkalender, $FlashmessageType) = displayMessage('weekkalender', 'Geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
        }

    } catch (PDOException $e) {
        list($Flashmessage_general, $FlashmessageType) = displayMessage('general', 'Fout in database', $resetServerMessages, $FlashMessages, 'error');
     
    } 
    return $repsonse;

}



/** || ---------op basis van gebruikers interactie boekingen verwerkt in html opsturen --------------|| */ 

//html maken van de week of in optie data


function generateRequestsOverview($requests, $message = []) {
    // default const in function
    $html = '';
    $hashedIDActive = '';
    $h2Text = 'overzicht van de aanvragen'; // default value

    //de construeer het ontvange bericht in waardes
    $MessageId =  $message['id'] ?? null;
    $MessageText = $message['text'] ?? null;
    $MessageType = $message['type'] ?? null; 
    $MessageInOption = $message['from_in_option'] ?? null; //bepaalt of een bericht van in optie naar def of afgewezen is gegaan

    //just in case
    if (empty($requests)) {
        $html .= '<section class="overviewRequestsWrapperContainer">';
        $html .= '<h2 class="overviewRequestsH2">Leeg aanvraag overzicht</h2>';
        $html .= '<div id="change-status-message" class="flash-message error">';
        $html .=  "Geen aanvragen gevonden voor gekozen datum";
        $html .= '</div>';
        $html .= '</section>';
        return $html;
    }

    //bepaal de text voor h2 kop in de htm aanvraag container
    if (isset($_GET['verzoek']) && $_GET['verzoek'] === 'weekaanvraag') {

        // Haal de eerste bezoekdatum uit de `$requests` arra
        $firstBezoekdatum = $requests[0]['bezoekdatum'] ?? null;
    
        if ($firstBezoekdatum) {
            $weekStartDate = new DateTime($firstBezoekdatum);
            $weekNumber = ltrim($weekStartDate->format("W"), '0'); // Weeknummer ophalen en leidende nul verwijderen
            $h2Text = "Overzicht van aanvragen van week $weekNumber"; // Dynamische tekst
        }
    } else if (isset($_GET['verzoek']) && $_GET['verzoek'] === "In optie") {
            $h2Text = "Overzicht van alle in optie aanvragen";
    }


    /** || ---logica om de request op basis van bezoekdatum te rankschikken ---|| 
     * 
         * usort werkt me negatief en postief en gelijk negatief als het eerste element voor het tweede element moet komen, dat is als de datum eerder is dus een kleinere waarde heeft
         * strotime is een universeele standaard conversie naar een getal op basis van het aantal seconden sinds 1 januari 1970
     * 
    */

    // Sorteer aanvragen op bezoekdatum
    usort($requests, function ($a, $b) {
        return strtotime($a['bezoekdatum']) - strtotime($b['bezoekdatum']);
    });

    // Groepeer aanvragen per bezoekdatum en convert van tijdDatum naar datum naar Nederlandse datum
    $groupedRequests = [];
    foreach ($requests as $request) {
        $bezoekdatum = $request['bezoekdatum'];
        $formattedDate = date('d F Y', strtotime($bezoekdatum));
        $formattedDate = convertEnglishDatetoDutchDate($formattedDate);
        if (!isset($groupedRequests[$formattedDate])) {
            $groupedRequests[$formattedDate] = [];
        }
        $groupedRequests[$formattedDate][] = $request;
    }


    
    /** || ---html toevoegen  ---|| 
     * 
         ** '' 
            * enkel strings worden niet gevalueerd en deze letterlijk text wordt opgeslagen dus een variabele wordt niet vertaald naar een waarde, maar naar de variable letterlijke naam

         ** "" 
            *variabelen worden vetaald naar waarde
            
         ** ${} 
            *handig voor complexe variabelen zoals array index of object actige structuren

         **  .
            * voor string samenstellingen
     * 
    */
   
    // Genereer de HTML-output
    $html .= '<section class="overviewRequestsWrapperContainer" id="overviewRequestMainContainer">';
    $html .= '<h2 class="overviewRequestsH2">'. htmlspecialchars($h2Text) .'</h2>';
    if ($_GET['verzoek'] === "In optie" && $MessageInOption === true) {
        $html .= '<div id="change-status-message" class="flash-in-option-message ' . htmlspecialchars($message['type'] ?? '') . '">';
        $html .= htmlspecialchars($message['text'] ?? '');
        $html .= '</div>';
    }
    

    foreach ($groupedRequests as $date => $requestsOnDate) {
        $html .= '<div class="date-group">';
        $html .= "<h4>Bezoekdatum: $date</h4>";

        foreach ($requestsOnDate as $request) {
            $id = $request['id'];
            $Hash_Key_ID = $_ENV['HASH_ID_KEY'];
            $hashedId = hash_hmac('sha256', $id, $Hash_Key_ID);
           
            if ((int)$request['id'] ===  (int) $MessageId) {
                $html .= '<div id="focus" class="request-item card">';
            } else {
                $html .= '<div class="request-item card">';
            }
            $html .= '<div class="request-item containerWrapper">';
            $html .= '<div class="request-item container">';
            $html .= '<p class="request-item para">status: ' . htmlspecialchars($request["status"]) . '</p>';
            $html .= '<p class="request-item para">School: ' . htmlspecialchars($request["schoolnaam"]) . '</p>';
            $html .= '<p class="request-item para">Aantal leerlingen: ' . htmlspecialchars($request["aantal_leerlingen"]) . '</p>';
            $html .= '<p class="request-item para">Telefoonummer contactpersoon: ' . htmlspecialchars($request["contact_telefoon"]) . '</p>';
            $html .= '<p class="request-item para">Email: ' . htmlspecialchars($request["email"]) . '</p>';
            $html .= '</div>';
            $html .= '</div>';

            // Voeg select-lijst toe voor statuswijziging
            $html .= '<div class="request-item status-container">';
            if ((int)$request['id'] ===  (int) $MessageId) {
                $hashedIDActive = $hashedId;
                $html .= '<div class="change-status-message-flexContainer">';
                $html .= '<div id="change-status-message"  class="flash-message absolute-positioned ' . htmlspecialchars($MessageType ?? '') . '">';
                $html .=  htmlspecialchars($MessageText ?? '');
                $html .= '</div>';
                $html .= '</div>';

            } // Sluit de flash-message div correct af
            $html .= "<form method='POST' class='request-item form selectExtraHeight'>";
            $html .= "<input type='hidden' name='request_id_hashed' value='". htmlspecialchars($hashedId) . "'>";
            $html .= "<input type='hidden' name='request_id' value='". htmlspecialchars($id) . "'>";
            $html .= '<div class="request-item status-containerTitle">';
            $html .= '<label for="status">Status wijzigen</label>';
            $html .= '</div>';

  
            $html .= '<select name="status" id="status" size="3">';
            $statuses = ['In optie', 'Definitief', 'Afgewezen'];
            foreach ($statuses as $status) {
                $selected = $status === $request['status'] ? 'selected' : '';
                $html .= "<option value='$status' $selected>$status</option>";
            }
            $html .= '</select>';
            $html .= '<button id="change-status-button-' . htmlspecialchars($hashedId) . '" class="dashboardKnopOverviewRequests" name="update_status" type="submit">Wijzig</button>';
            $html .= '</form>';
            $html .= '</div>'; // Einde van request-item
            $html .= '</div>'; // Einde van request-item
        }

        $html .= '</div>'; // Einde van date-group
    }

    $html .= '</section>';
    unset($_SESSION['status_flash_type']);
    return $html;
}



function get_requests_change_status($pdo, $verzoekType, $selectedDateSanitized = null){
    $requests = [];
    if ($verzoekType === 'weekaanvraag') {
        $weekStart = new DateTime($selectedDateSanitized);
        $weekStart->modify('monday this week');
        $weekEnd = clone $weekStart;
        $weekEnd->modify('sunday this week');
        $sqlWeekaanvraag = "
        SELECT 
            id, 
            schoolnaam, 
            aantal_leerlingen, 
            status, 
            bezoekdatum, 
            email,
            contact_telefoon
        FROM 
            aanvragen
        WHERE 
            bezoekdatum BETWEEN :weekStart AND :weekEnd
        ";
        

        $stmt = $pdo->prepare($sqlWeekaanvraag);

        $stmt->execute([
            ':weekStart' => $weekStart->format('Y-m-d'),
            ':weekEnd' => $weekEnd->format('Y-m-d')
        ]);

        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);


    } else if ($verzoekType === "In optie"){
        $sqlInoptie = "
            SELECT 
                id, 
                schoolnaam, 
                aantal_leerlingen, 
                status, 
                bezoekdatum, 
                email,
                contact_telefoon
            FROM 
                aanvragen
            WHERE 
                status = :status
                AND 
                bezoekdatum BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 12 MONTH)
        ";


        $stmt = $pdo->prepare($sqlInoptie);
        $stmt->execute([':status' => 'In optie']);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    }

    if (!empty($requests)){
        return $requests;
    } else return [];
    
}

function status_change($pdo, $id, $status) {
    try {
        $currentStatus = '';

        $stmt = $pdo->prepare("SELECT id FROM aanvragen WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $idCheck = $stmt->fetch(PDO::FETCH_COLUMN);


        if ((int)$idCheck !== (int)$id) {
            $id = null; // Ongeldige ID
        }

        if ($id === null) {
            return ['type' => 'error', 'message' => "Ongeldige aanvraag-ID.", 'id' => null, 'general' => true];
        }

  

        // Haal aanvraaggegevens op
        $stmt = $pdo->prepare("SELECT bezoekdatum, aantal_leerlingen, status FROM aanvragen WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            return ['type' => 'error', 'message' => "Aanvraag met ID $id bestaat niet.", 'id' => null, 'general' => true];
        }

        $currentStatus = $request['status']; // sla huidige status op om later te controleren of een in optie veranderd is naar def of afgewezen tbv correct melden

        // Controleer statuswijziging
        if ($status === $request['status']) {
            return ['type' => 'success', 'message' => "Status ongewijzigd gebleven op {$status}.", 'id' => $id];
        }

        if ($status === 'Definitief') {
            // Controleer limieten
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as aantal_definitief, SUM(aantal_leerlingen) as totale_leerlingen 
                FROM aanvragen 
                WHERE bezoekdatum = :date AND status = 'Definitief'
            ");
            $stmt->execute([':date' => $request['bezoekdatum']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['aantal_definitief'] >= 2) {
                return ['type' => 'error', 'message' => "Limiet van 2 definitieve boekingen per dag overschreden.", 'id' => $id];
            }
            if (($result['totale_leerlingen'] + $request['aantal_leerlingen']) > 200) {
                return ['type' => 'error', 'message' => "Geboekt voor {$result['totale_leerlingen']}, maximaal 200 mogelijk.", 'id' => $id];
            }
        }

        // Update de status
        $stmt = $pdo->prepare("UPDATE aanvragen SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        if ($currentStatus === 'In optie' && in_array($status,  ['Definitief', 'Afgewezen'])) {
            return ['type' => 'success', 'message' => "Status succesvol gewijzigd naar {$status}.", 'id' => $id, 'from_in_option' => true];
        }

        return ['type' => 'success', 'message' => "Status succesvol gewijzigd naar {$status}.", 'id' => $id];
    } catch (PDOException $e) {
 
        return ['type' => 'error', 'message' => "Er is een fout opgetreden. Probeer het later opnieuw.", 'id' => null];
    }
}

function displayRequestsAfterPost ($messageFromChangeStatusPara, $pdo, $FlashMessages, $resetServerMessages) {
    $request = [];
    $overviewHtml = '';

    if (isset($_GET['verzoek'])){
        $verzoekType = $_GET['verzoek'];
        $selectedDate = $_GET['selectedDate'] ?? null;
        $requests = [];
        if ($verzoekType === 'weekaanvraag' && is_string($selectedDate) && $selectedDate !== ''){
            $SQLDate = get_SQL_Date($selectedDate);
            $selectedDateSanitized = sanitize_datum($SQLDate, $errors);
            $verzoekTypeSanitized = htmlspecialchars(strip_tags(stripslashes(trim($verzoekType))));
            if(!$selectedDateSanitized) {
                list($Flashmessage_weekaanvraag, $FlashmessageType) = displayMessage('weekaanvraag', 'Ongeldige datum', $resetServerMessages, $FlashMessages, 'error');
            } else {
                $requests = get_requests_change_status($pdo, $verzoekTypeSanitized, $selectedDateSanitized);
                if (empty($requests)) {
                    list($Flashmessage_weekaanvraag, $FlashmessageType) = displayMessage('weekaanvraag', 'Geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'error');
                } else {
                    $overviewHtml = generateRequestsOverview($requests, $messageFromChangeStatusPara);
                }
            }
    
        } else if ($verzoekType === 'In optie') {
            $verzoekTypeSanitized = sanitize_status($verzoekType, $errors);
            if (!$verzoekTypeSanitized){
                list($Flashmessage_inoptieaanvraag, $FlashmessageType) = displayMessage('In optie', 'Ongeldige invoer', $resetServerMessages, $FlashMessages, 'error');
            } else {
                $requests = get_requests_change_status($pdo, $verzoekTypeSanitized);
                if (empty($requests)){
                    list($Flashmessage_inoptieaanvraag, $FlashmessageType) = displayMessage('In optie', 'Geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
                } else {
                    $overviewHtml = generateRequestsOverview($requests, $messageFromChangeStatusPara);
                }
            }
    }
    if (!empty($overviewHtml)) {
        return $overviewHtml;
    } else {
        return null;
    }
}}
      
/**||-----------Declaratie gebruikte variabelen met (default initialisatie)---------------||  */



// Gebruik de functie om een databaseverbinding te maken
try {
    $pdo = connectToDataBase();
} catch (PDOException $e) {
    list($Flashmessage_inoptieaanvraag, $FlashmessageType) = displayMessage('In optie', 'Geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
}


//weekkalender vulle
$weekDataResponse = get_weekdata_for_calendar($pdo, $resetServerMessages);

/** ||-------------- variabelen uit de gebruikers interactie halen via url------------|| */ 

/** || -- uitleg onderscheid weekaanvraag en in optie --- || */

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['verzoek'])){
    $verzoekType = $_GET['verzoek'];
    $selectedDate = $_GET['selectedDate'] ?? null;
    $requests = [];

    if ($verzoekType === 'weekaanvraag' && is_string($selectedDate) && $selectedDate !== ''){
        $resetServerMessages['weekaanvraag']($FlashMessages);
        $SQLDate = get_SQL_Date($selectedDate);
        $selectedDateSanitized = sanitize_datum($SQLDate, $errors);
        $verzoekTypeSanitized = htmlspecialchars(strip_tags(stripslashes(trim($verzoekType))));
        if(!$selectedDateSanitized) {
            list($Flashmessage_weekaanvraag, $FlashmessageType) = displayMessage('weekaanvraag', 'ongeldige datum', $resetServerMessages, $FlashMessages, 'error');
            $errors = [];

        } else {
            $requests = get_requests_change_status($pdo, $verzoekTypeSanitized, $selectedDateSanitized);
            if (empty($requests)) {
                list($Flashmessage_weekaanvraag, $FlashmessageType) = displayMessage('weekaanvraag', 'geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
            } else {
                $overviewHtml = generateRequestsOverview($requests);
                setAndResetHash($hash, 'overviewRequestMainContainer');
            }
        }

    } else if ($verzoekType === 'In optie') {   
        $verzoekTypeSanitized = sanitize_status($verzoekType, $errors);
        if (!$verzoekTypeSanitized){
            list($Flashmessage_inoptieaanvraag, $MessageType) = displayMessage('In optie', 'Ongeldige invoer', $resetServerMessages, $FlashMessages, 'error');
        } else {
            $requests = get_requests_change_status($pdo, $verzoekTypeSanitized);
            if (empty($requests)){
                list($Flashmessage_inoptieaanvraag, $FlashmessageType) = displayMessage('In optie', 'Geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
            } else {
                $overviewHtml = generateRequestsOverview($requests);
                setAndResetHash($hash, 'overviewRequestMainContainer');
            }       
        } 
    } else if ($verzoekType === 'weekaanvraag' && is_string($selectedDate) && $selectedDate === '') {
        list($Flashmessage_weekaanvraag, $FlashmessageType) = displayMessage('weekaanvraag', 'kies een maandag om weekaanvragen te zien', $resetServerMessages, $FlashMessages, 'success');
    }
}

/** || ------------ Post verzoek voor de change status ---------- || */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $HashedIDToSQL = $_POST['request_id_hashed'] ?? false;
    $IDToSQL = $_POST['request_id'] ?? false;
    $messageFromChangeStatus = [
        'text' => '',
        'type' => '',
        'id' => null,
        'from_in_option' => false
    ];

    // Valideer de ingevoerde ID
    if (empty($HashedIDToSQL) || !is_string($HashedIDToSQL) || strlen($HashedIDToSQL) !== 64) {
        $HashedIDToSQL = false; // Markeer het als ongeldig
    }

    //valideer aanvraag id unhashed
    if (empty($IDToSQL) || !filter_var($IDToSQL, FILTER_VALIDATE_INT)) {
        $IDToSQL = false; // Markeer het als ongeldig
    }

    $calculatedHashedId = hash_hmac('sha256', $IDToSQL, $Hash_Key_ID);
    if (!hash_equals($calculatedHashedId, $HashedIDToSQL)){
        $HashedIDToSQL = false;
        $IDToSQL = false;
    }



    //valideer status
    $status = sanitize_status($_POST['status'], $errors);


    
  
    if (!$HashedIDToSQL || !$status || !$IDToSQL) {
        $RequestType = return_type_of_get_Request();
        list($Flasmessage_requests_general, $FlashmessageType) = displayMessage('requests-general', 'kies een maandag om weekaanvragen te zien', $resetServerMessages, $FlashMessages, 'error');
    } else {
        $result = status_change($pdo, $IDToSQL, $status);
        // Controleer op algemene of specifieke melding
        if (isset($result['general'])) {
            $RequestType = return_type_of_get_Request();
            if ($RequestType === 'weekaanvraag'){
                list($Flashmessage_weekaanvraag, $FlashmessageType) = displayMessage($RequestType, $result['message'], $resetServerMessages, $FlashMessages, $result['type']); 
            } else if ($RequestType === 'In optie'){
                list($Flashmessage_inoptieaanvraag, $FlashmessageType) = displayMessage($RequestType, $result['message'], $resetServerMessages, $FlashMessages, $result['type']); 
            }
        } else {
            $messageFromChangeStatus['text'] = $result['message'];
            $messageFromChangeStatus['type'] = $result['type'];
            $messageFromChangeStatus['id'] = $result['id'];
            if (isset($result['from_in_option'])) {
                $messageFromChangeStatus['from_in_option'] = $result['from_in_option'];
            }
            $overviewHtml = displayRequestsAfterPost($messageFromChangeStatus, $pdo, $FlashMessages, $resetServerMessages);      
            setAndResetHash($hash, 'change-status-message'); 
        }
    }
}

?>


<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoFort Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/nl.js"></script>
    <script defer type="module" src="db_dashBoard.js"></script>
    <?php if (!empty($hash)) : ?>
        <script>
            window.location.hash = "<?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?>";
        </script>
    <?php endif; ?>
        <!-- Dynamische weekData ophalen van PHP in de globale variabele-->
    <script>
        window.weekDataResponse = <?php echo json_encode($weekDataResponse); ?>;
    </script>
</head>
<body class="body-dashboard">
    <header class="header-dashboard">
        <h1 class="Title_Dashboard">GeoFort Dashboard</h1>
        <nav class="Navbar-dashboard">
            <ul>
                <li><a href="db_dashBoard.php" id="nav-link-active" class="nav-link-active">Wijzig statussen</a></li>
                <li><a href="db_wijzigAantalLeerlingen.php">Wijzig aantal leerlingen</a></li>
                <li><a href="db_bekijkAanvraag.php">Bekijk een schoolaanvraag</a></li>
                <li><a href="db_blokkeerEenDatum.php">Datum blokkeren</a></li>
                <li><a href="db_downloadDataset.php">Dataset downloaden</a></li>
            </ul>
        </nav>
        <form class="logOutForm dashBoard" action="logout.php" method="post" class="logout-form">
            <button type="submit" class="Uitlog_Dashboard">Uitloggen</button>
        </form>
    </header>
        <div id="content-container" class="mainContentBody">
            <section class="welcome-section">
                <h2>Welkom op het Dashboard, <?php echo $_SESSION['username']; ?>!</h2>
            </section>
                <!--General messages form server-->
            <div 
                id="receive-message-general" 
                class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
                >
                <?php echo htmlspecialchars($Flashmessage_general); ?>	
            </div>
            <form method ="GET" action="" class="dashboard-form">
                <!--
                    action "" verwijst naar dezelfde pagina
                    get method wil zeggen gegegevens zichtbaar in url
                    max lengt van 2000 tekens in url


                -->
                <fieldset class="fieldset-dashboard">
                    <legend class="dashboard-legend">Aanvragen per week</legend>
                    <div id="date-picker">
                        <label for="start_date">Agenda</label> <!--for helpt bij focus en is gekoppelt aan id invoerveld-->
                        <div id="receive-message-weekkalender" class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>">
                                <?php echo htmlspecialchars($Flashmessage_weekaanvraagkalender); ?>
                        </div>
                        <!--name bepaalt de naam van de queryparameter-->
                        <!--value is in dit geval een php snippet code die uit de url de selecteddate haalt bij het herladen van de pagina-->
                        <!--button type submit zorg ervoor dat deze knop een verzendknop voor het formulier wordt-->
                        <!--button de name is hier weer de naam van de waarde en value zelf is waarde die bij action hoor-->
                        <!--Zowel de waarde van de knop als die van selectdate worden in de superglobale get verkregen-->
                        <input 
                        type="text" id="start_date" name="selectedDate" 
                        value="<?php echo htmlspecialchars($_GET['selectedDate'] ?? ''); ?>" >
                        <div id="receive-message-WeekAanvraag" class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>">
                                <?php echo htmlspecialchars($Flashmessage_weekaanvraag); ?>
                        </div>
                        <button id="submit-date" type="submit" name="verzoek" value="weekaanvraag" class="dashboardKnop">Bekijk aanvragen voor de week</button>
                    </div>
            
            
                    <div class="meer-informatie-container">
                        <a href="#" class="meerInformatieToggle" data-target="aanvragenInfo"><span>Meer informatie over het bekijken van de aanvragen</span></a>
                            <div id="aanvragenInfo" class="meerInformatieContent">
                                <p><strong class = "highlighted-text" >Datum kiezen:</strong> In de agenda klik je op de maandagen om de aanvragen van die week te zien.</p>
                                <p><strong class = "highlighted-text">Overzicht aavragen:</strong> In het overzicht kun je per aanvraag de status veranderen en de veranderingen doorgeven.</p>
                            </div>
                        </div>
                </fieldset>
            </form>
            <form method="GET" action="" class="dashboard-form">
                <fieldset class="fieldset-dashboard">
                <legend class="dashboard-legend">Aanvragen in optie</legend>
                    <div id="receive-message-InOptie" class="flash-message <?php echo htmlspecialchars($FlashmessageType ?? ''); ?>">
                    <?php echo htmlspecialchars($Flashmessage_inoptieaanvraag ?? ''); ?>
                    </div> 
                    <button type="submit" name="verzoek" value="In optie" id="submit-inOptie-status" class="dashboardKnop">Bekijk alle in optie aanvragen</button>
                    
                    <p><div class="meer-informatie-container">
                    <a href="#" class="meerInformatieToggle" data-target="aanvrageninoptieInfo"><span>Meer informatie over het bekijken van de aanvragen in optie</span></a>
                        <div id="aanvrageninoptieInfo" class="meerInformatieContent">
                            <p><strong class = "highlighted-text">Overzicht in optie aavragen:</strong> Klik op deze knop om alle opestaande in optie aanvragen te zien.</p>
                        </div>
                    </div></p>
                </fieldset>
            </form>
            <?php 
                if (!empty($overviewHtml)) {echo $overviewHtml;} 
            ?>
        </div>
        <footer class="footer_dashboard">
            <p id="copy_logo">&copy; <span id="currentYear"></span> GeoFort</p>
            <script>
                document.getElementById('currentYear').textContent = new Date().getFullYear();
            </script>
            <div class="footer-logo-container">
                <img src="images/geofort_logo.png" alt="GeoFort Logo" class="footer-logo">
            </div>
        </footer>
    </body>
</html>