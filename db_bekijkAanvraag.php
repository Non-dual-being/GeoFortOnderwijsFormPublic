<?php
/** || --------sessie controleren en inactiviteit controleren ----------- */
/**|| --------modules --------------- */
require_once 'module_makeConnectionToDataBase.php';
require_once 'defaultFunctions.php';
require_once 'db_module_inactivityCheck.php';
require 'vendor/autoload.php'; 


use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$Hash_Key_ID = $_ENV['HASH_ID_KEY'] ?? '';

$pdo;

//Array gebruik tijdens user input validatie bij SQL queries
$errors = []; 

//geselecteerde dag in in optie kalender
$selectedDate = null; 

//De kalenderdata voor een dag 
$calenderData = [];

//de boekingen ophalen op basis van de gekozen dag
$requests = []; 

//(Fout) bericht voor de in optie kalender
$Flashmessage_calender = '';

//(Fout) bericht voor de in opie submit knop
$Flashmessage_calenderSubmitKnop = '';

$Flashmessage_DownloadButton = '';

//Algemene foutmelding boven het dagoverzicht
$Flashmessage_general_Requests = '';

//Algemene foutmelding 
$Flashmessage_general = '';

// 'success' of 'error'
$FlashmessageType = ''; 

// hash
$hash = '';

//associatieve array bedoeld om de meldingen leeg te halen __newcode (met modulaire werking)
$resetServerMessages = [
    'calender' => function (&$FlashMessages){
        $FlashMessages['message']['calender'] = '';
        $FlashMessages['type'] = '';
    },
    'calender_Submit' => function (&$FlashMessages){
        $FlashMessages['message']['calender_Submit'] = '';
        $FlashMessages['type'] = '';
    },
    'general_Requests' => function (&$FlashMessages){
        $FlashMessages['message']['general_Requests'] = '';
        $FlashMessages['type'] = '';
    },
    'download' => function (&$FlashMessages){
        $FlashMessages['message']['download'] = '';
        $FlashMessages['type'] = '';
    },
    'general' => function (&$FlashMessages){
        $FlashMessages['message']['general'] = '';
        $FlashMessages['type'] = '';
    }
];

/**||--- creatie van associatieve array als vervanging voor het gebruik van globale variabelen in displaynessage */ 

/**
 * Het gebruik van een globale variabelen kan leiden tot onverspelbaar gedrag vooral in grote projecten
 * !nadeel van global te werken is dat elke functie toegang heeft en dat wijzingen lastig zijn bij te houden
 * !testen van een functie met behulp van een globale variabele is lastig om de globale variable ook elders in gebruik kan zijn
 * ! elke nieuwe flashmessage vraagt om een nieuwe globale
 * todo: maak een associatieve array met alle messages om een globale werking om te zetten in een modulaire
 * * modulariteit wil zich stukjes code die onafhankelijk een werking hebben 
 */

 //associatievel array als vervangen van de globale aparte variabelem
 $FlashMessages = [
    'message' => [
        'calender' => '',
        'calender_Submit' => '',
        'general_Requests' => '',
        'download' => '',
        'general' => '',
        
    ],
    'type' => ''
];

/**|| ------------------------functions------------------------|| */



function get_data_for_calendar($pdo, $resetServerMessages) {
    try {
        /** door de group by kan count id nooit 0 zijn */
        $sql = "
        SELECT 
            bezoekdatum, 
            COUNT(id) AS aantal_scholen
        FROM 
            aanvragen
        GROUP BY 
            bezoekdatum
        ORDER BY 
            bezoekdatum ASC;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $calenderData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($calenderData)) {
            list($Flashmessage_calender, $MessageType) = displayMessage(
                'calender', 'Geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
        } 

    } catch (PDOException $e) {
        list($Flashmessage_general, $MessageType) = displayMessage(
            'general','Fout in de database', $resetServerMessages, $FlashMessages, 'error');
    } 
    return $calenderData;

}

//aanvraag verzamelen uit de sql op basis van geselecteerde dag 
function get_requests_for_dayOverview($pdo, $selectedDateSanitized){
    $requests = [];
    $sqlInoptieAanvraag = "
        SELECT 
            *
        FROM
            aanvragen
        WHERE
            bezoekdatum = :bezoekdatum
        ORDER BY
            status
    
    ";

    $stmt = $pdo->prepare($sqlInoptieAanvraag);
    $stmt->execute([':bezoekdatum' => $selectedDateSanitized]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    if (!empty($requests)){
        return $requests;
    } else return [];

}


#html op bouwen voor de dag aanvraag 
function generateRequestsOverview($requestsHTML, $message = []) {

    //lege velden weglaten
    function filterEmptyFields(array $requestHTML): array {
        return array_filter($requestHTML, function ($value) {
            return !empty($value) && $value !== null;
        });
    }

    // default const in function
    $html = '';
    $hashedIDActive = '';
    $h2Text = ''; // default value
    $messageinHTML = [];

    if (!empty($message)){
        if ($message['button']){
            $messageinHTML['id'] =  null;
            $messageinHTML['text'] = $message['text'] ?? '';
            $messageinHTML['type'] = $message['type'] ?? 'error';
            $messageinHTML['button']= true;  
        } else {
            $messageinHTML['id'] =  $message['id'];
            $messageinHTML['text'] = $message['text'] ?? '';
            $messageinHTML['type'] = $message['type'] ?? 'error';
            $messageinHTML['button']= false;
        }

    }

    //just in case
    if (empty($requestsHTML)) {
        $html .= '<section class="overviewRequestsWrapperContainer">';
        $html .= '<h2 class="overviewRequestsH2">Leeg aanvraag overzicht</h2>';
        $html .= '<div id="change-status-message" class="flash-message error">';
        $html .=  "Geen aanvragen gevonden voor gekozen datum";
        $html .= '</div>';
        $html .= '</section>';
        return $html;
    }



    /** || ---logica om de request op basis van bezoekdatum te rankschikken ---|| 
     * 
         * usort werkt me negatief en postief en gelijk negatief als het eerste element voor het tweede element moet komen, dat is als de datum eerder is dus een kleinere waarde heeft
         * strotime is een universeele standaard conversie naar een getal op basis van het aantal seconden sinds 1 januari 1970
     * 
    */



    // Groepeer aanvragen per bezoekdatum en convert van tijdDatum naar datum naar Nederlandse datum

    foreach ($requestsHTML as $index => $request) {
        $formattedDate = date('d F Y', strtotime($request['bezoekdatum']));
        $formattedDate = convertEnglishDatetoDutchDate($formattedDate);
        $requestsHTML[$index]['bezoekdatum'] = $formattedDate;
    }

    $gekozenDatum = $requestsHTML[0]['bezoekdatum'];
    $h2Text = "Overzicht van de aanvragen op $gekozenDatum";

    //de aanvragen hier per status groepen 

    $groupedRequests = [];


        
    
     
    for ($i = 0; $i < count($requestsHTML); $i++) {
        $request = $requestsHTML[$i]; // Haal de aanvraag op via de index



        
        // Verwerk de aanvraag hier
        $status = $request['status'];

        if (!isset($groupedRequests[$status])) {
            $groupedRequests[$status] = [];
        }
     
        $filteredRequest = filterEmptyFields($request);

        $groupedRequests[$status][] = $filteredRequest;
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
    $html .= "<form method='POST' class='request-item form__bekijk'>";
    foreach ($groupedRequests as $status => $requestswithStatus) {
        $html .= '<div class="bekijkDateGroup">';
        if ($status === 'Definitief'){
            $html .= "<h4>Overzicht van definitieve aanvragen</h4>";
        } else if ($status === 'In optie'){
            $html .= "<h4>Overzicht aanvragen in optie</h4>";
        } else if ($status === 'Afgewezen'){
            $html .= "<h4>Overzicht van de afgewezen aanvragen</h4>";
        }
        foreach ($requestswithStatus as $request) {
            $id = $request['id'];
            $Hash_Key_ID = $_ENV['HASH_ID_KEY'];
            $hashedId = hash_hmac('sha256', $id, $Hash_Key_ID);
            $data = json_encode(['id' => $id, 'hash' => $hashedId]);
            $html .= '<div class="request-item card-bekijk">';
            $html .= '<div class="request-item containerWrapper__bekijk">';
            $html .= '<div class="request-item container-bekijk">';  
            foreach($request as $key => $value){
                $html .= '<p class="request-item para-bekijk">' . $key . ': ' . $value .'</p>';
            }
            $html .= '</div>';
            $html .= '</div>';

            // Voeg select-lijst toe voor statuswijziging
            
            if ($messageinHTML !== [] &&
                $messageinHTML['button'] === false && 
                $messageinHTML['id'] !== null && 
                (int)$request['id'] ===  (int) $messageinHTML['id']) {
                $hashedIDActive = $hashedId;
            

                $html .= '<div class="generalFlashmessageWrapper">';
                $html .= '<div 
                    id="change-number-message"  
                    class="flash-numberofStudents-message absolute-positioned-General__numberOfStudents-FromServer ' . htmlspecialchars($messageinHTML['type'] ?? '') . '">';
                $html .=  htmlspecialchars($messageinHTML['text'] ?? '');
                $html .= '</div>';
                $html .= '</div>';

            } // Sluit de flash-message div correct af
            $html .= '<div class="request-item checkbox-container">';
            $html .= '<label 
                        for="checkForDownload-' . htmlspecialchars($hashedId) . '"
                        class="input-label"
                        >
                    Download aanvraag</label>';
            $html .= '<input 
                        name="checkedRequests[]"  
                        id="downloadCheck-' . htmlspecialchars($hashedId) . '"
                        value = "' . htmlspecialchars($data) . '"
                        class="overzicht-checkbox" 
                        type = "checkbox"
                    >';
            $html .= '</div>'; // einde item-card 
            $html .= '</div>'; // Einde van request-item
        }

        $html .= '</div>'; // Einde van date-group
    }

    $html .= '<div class="generalFlashmessageWrapper">';
    $html .= '<div 
        id="donwload-started-message"  
        class="flash-numberofStudents-message absolute-positioned-General__numberOfStudents success">';
    /**lege div die wordt gevuld vanuit js op bais van de cookie en een klik op de knop */
    $html .= '</div>';
    $html .= '</div>';


    if ($messageinHTML !== [] &&
        $messageinHTML['button'] === true && 
        $messageinHTML['id'] === null) {
            $Flashmessage_DownloadButton = $messageinHTML['text'];
            $MessageType = $messageinHTML['type'];
            $html .= '<div class="generalFlashmessageWrapper">';
            $html .= '<div 
                id="downloadRequests-button-message"  
                class="flash-numberofStudents-message absolute-positioned-General__numberOfStudents ' . htmlspecialchars($MessageType ?? '') . '">';
            $html .=  htmlspecialchars($Flashmessage_DownloadButton ?? '');
            $html .= '</div>';
            $html .= '</div>';
        }


    $html .= '<button 
                id="download-button" 
                class="dashboardKnopOverviewRequests" 
                name="download_Requests" 
                 type="submit"
            >Download de aanvragen</button>';
    $html .= '</form>';
    $html .= '</section>';

    return $html;
}

function displayRequestsAfterPost ($pdo, $messagePara = []){
    $request = [];
    $overviewHtml = '';
    $messageToHTML = [];

    if ( !empty($messagePara)){
         if ($messagePara['button']){
            $messageToHTML['id'] = null;
            $messageToHTML['text'] = $messagePara['text'];
            $messageToHTML['type'] = $messagePara['type'];
            $messageToHTML['button'] = true;
            $messageToHTML['download'] = $messagePara['download'] ?? null;

         } else {
            $messageToHTML['id'] = $messagePara['id'];
            $messageToHTML['text'] = $messagePara['text'];
            $messageToHTML['type'] = $messagePara['type'];
            $messageToHTML['button'] = false;
    
         }
    }


    if($_GET['verzoek'] === 'dagaanvraag'){
        $selectedDate = $_GET['selectedDate'] ?? null;
        $SQLDate = get_SQL_Date($selectedDate);
        $selectedDateSanitized = sanitize_datum($SQLDate, $errors);
    if(!$selectedDateSanitized || !empty($errors) ) {
        list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'ongeldige invoer',  $resetServerMessages, $FlashMessages, 'error');
        $errors=[];
    } else {
        $requests = get_requests_for_dayOverview($pdo, $selectedDateSanitized);
        if (empty($requests)){
            list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'geen boekingen gevonden',  $resetServerMessages, $Flashmessages, 'error');
        } else {
            $overviewHtml = generateRequestsOverview($requests, $messagePara);
        }
    }

    if (!empty($overviewHtml)) {
        return $overviewHtml;
    } else {
        return null;
        }
    }
}

//functie om op basis van de ontvange ids array de als download mee te geven
function get_requested_downloads($pdo, array $ids) {
    if (!is_array($ids) || empty($ids)){
        return false;
    }
    try {
        ob_clean(); // Zorgt ervoor dat er geen output voor de headers staat
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        /**
         ** de placeholder is om sql injectie te voorkomen 
         * $placeholders = implode(',', array_fill(0, count($ids), '?'));
         * = $placeholders = implode(',', array_fill(0, count($ids), '?'));
         * = "?, ?, ?"
         */


        $SQLCheck = "
        SELECT
            id
        FROM
            aanvragen
        WHERE
            id IN ($placeholders)
        ";

        $stmt = $pdo->prepare($SQLCheck);
        $stmt->execute($ids);
        $validIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($validIds)){
            return false; // Geen geldige aanvragen gevonden in de database
        }

        $placeholders = implode(',', array_fill(0, count($validIds), '?'));
        $sqlBuildCSV = "
        SELECT
            *
        FROM
            aanvragen
        WHERE
            id IN ($placeholders)
        ";

        $stmt = $pdo->prepare($sqlBuildCSV);
        $stmt->execute($validIds);
        $aanvragen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($aanvragen)) {
            return false;
        }

        setcookie('download_started', '1', time() + 60, "/");

        /**
         * 'download started is de naam'
         * '1' wil is de waarde en geeft aan dat de download is gestart
         * tikme() + 60 wil zeggen dat de cookie na 60 seconden verwijderd wordt
         * "/" de cookie is geldig voor de hele website 
         */

        // **Stap 1: Headers instellen voor CSV-download**
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="aanvragen.csv"');


        // **Stap 2: CSV genereren**
        $output = fopen('php://output', 'w');

        // **Stap 3: Kolomnamen toevoegen**
        fputcsv($output, array_keys($aanvragen[0]), ";");

        foreach ($aanvragen as $row) {
            fputcsv($output, $row, ";");
        }

        fclose($output);
        exit();
          
         

    } catch (PDOException $e) {
        error_log("Databasefout: " . $e->getMessage());
        return [
            'type' => 'error',
            'message' => "Er is een fout opgetreden. Probeer het later opnieuw.",
            'id' => null,
            'general' => true
        ];
    }
}


/**|| --------------------loading the page with the data and handling the post and get requests ----------------------------------|| */


/**|| verbinding initialiseren en calenderdata op halen -------------- ||*/

// Gebruik de functie om een databaseverbinding te maken
try {
    $pdo = connectToDataBase();
} catch (PDOException $e) {
    list ($Flashmessage_general, $FlashmessageType) = displayMessage(
        'general', 'Fout in verbinding met database',$resetServerMessages, $FlashMessages, 'error');
    die("Fout bij het verbinden met de database. Neem contact op met de beheerder.");
}

$calenderData = get_data_for_calendar($pdo, $resetServerMessages);

/**||--------------------------POST EN GET Request Logica --------------------------------|| */

/**---------------|| GET ||------------------------------ */

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['verzoek']) && $_GET['verzoek'] === 'dagaanvraag'){
    $dateChosen = true;
    $selectedDate = $_GET['selectedDate'] ?? null;
    $requests = [];
    if (is_String($selectedDate) && $selectedDate === ''){
        $dateChosen = false;
        list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'kies eerst een datum',$resetServerMessages, $FlashMessages, 'success');
    }
    if ($dateChosen){
        $SQLDate = get_SQL_Date($selectedDate);
        $selectedDateSanitized = sanitize_datum($SQLDate, $errors);
        if(!$selectedDateSanitized || !empty($errors) ) {
            list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'ongeldige invoer',$resetServerMessages, $FlashMessages, 'error');
            $errors=[];
        } else {
            $requests = get_requests_for_dayOverview($pdo, $selectedDateSanitized);
            if (empty($requests)){
                list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'geen boekingen gevonden',  $resetServerMessages, $FlashMessages, 'error');
            } else {
                $overviewHtml = generateRequestsOverview($requests);
                setAndResetHash($hash, 'overviewRequestMainContainer');

            }
        }
    }
}
/**---------------|| POST ||------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_Requests'])){
    $messageFromPost = [
        'id' => null,
        'text' => '',
        'type' => '',
        'button' => false
    ];
    if (!isset($_POST['checkedRequests'])){
        $messageFromPost = [
            'id' => null,
            'text' => 'Geen aanvragen aangevinkt',
            'type' => 'success',
            'button' => true
        ];

        $overviewHtml = displayRequestsAfterPost($pdo, $messageFromPost);
        setAndResetHash($hash, 'downloadRequests-button-message');
        
    } else {
        $receivedIDSets = $_POST['checkedRequests'];
        $receivedIDS = [];
        foreach($receivedIDSets as $setString){
            $set = json_decode($setString, true);
            $idinset = $set['id'];
            $hashedidinset = $set['hash'];
            if (empty($idinset) || !is_string($hashedidinset) || strlen($hashedidinset) !== 64 || !filter_var($idinset, FILTER_VALIDATE_INT)){
                setAndResetHash($hash, 'receive-message-general-Requests');
                list($Flashmessage_general_Requests, $FlashmessageType) = displayMessage('general_Requests', 'ongeldige invoer', $resetServerMessages, $FlashMessages, 'error');
                break;
            } else {
                $calculatedHashedId = hash_hmac('sha256', $idinset, $Hash_Key_ID);
                if (!hash_equals($calculatedHashedId, $hashedidinset)){
                    list($Flashmessage_general_Requests, $FlashmessageType) = displayMessage('general_Requests', 'ongeldige invoer', $resetServerMessages, $FlashMessages, 'error');
                    break;
                } else {
                    $receivedIDS[] = $idinset;
                }              
            }   
        }

        if (!empty($receivedIDS) && count($receivedIDS) === count($receivedIDSets)) {  
            
            $messageFromPost = [
                'id' => null,
                'text' => 'Download wordt gestart...',
                'type' => 'success',
                'button' => true
            ];
            if(!get_requested_downloads($pdo, $receivedIDS)){
                list($Flashmessage_DownloadButton, $FlashmessageType) = displayMessage('download', 'Download niet beschikbaar...', $resetServerMessages, $FlashMessages, 'error');
                setAndResetHash($hash, '');

            }

        }
        
    }
   
    }

?>



<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bekijk een schoolaanvraag - GeoFort Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/nl.js"></script>
    <script defer type="module" src="db_bekijkAanvraag.js"></script>
    <script>
        window.calenderData = <?php echo json_encode($calenderData); ?>;
    </script>
        <!--hash opvangen-->
    <?php if (!empty($hash)) : ?>
        <script>
            window.location.hash = "<?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?>";
        </script>
    <?php endif; ?>
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

    <form action="" method = "GET" class="dashboard-form">
        <fieldset class="fieldset-dashboard">
            <legend class="dashboard-legend">Bekijk een schoolaanvraag</legend>
            <div id="date-picker">
                <label for="start_date">Agenda</label>
                <input 
                    type="text" 
                    id="start_date"
                    name="selectedDate"
                    value = "<?php echo htmlspecialchars($_GET['selectedDate'] ?? ''); ?>"
                >
                <div 
                    id="receive-message-calender" 
                    class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
                > 
                <?php echo htmlspecialchars($Flashmessage_calender); ?>	
                </div>
            </div>
            <button 
                id="submit-date" 
                name="verzoek"
                value="dagaanvraag"
                class ="dashboardKnop"
            >
            Verkrijg een overzicht
            </button>
            <div class="generalFlashmessageWrapper">
                <div 
                    id="receive-message-calenderSubmitKnop" 
                    class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
                >
                <?php echo htmlspecialchars($Flashmessage_calenderSubmitKnop); ?>	
                </div>
            </div>
            <div class="meer-informatie-container">
                <a href="#" class="meerInformatieToggle" data-target="dagaanvragenOverzicht"><span>Meer informatie aanvraagoverzicht</span></a>
                <div id="dagaanvragenOverzicht" class="meerInformatieContent">
                    <p><strong class = "highlighted-text">Gekozen status: </strong> De agenda laat alle aanvragen zien van de gekozen status</p>
                    <p><strong class = "highlighted-text">Datum kiezen:</strong> In de agenda klik je op de bezoekdatum van de school waarvan je het overzicht wil verkrijgen.</p>
                    <p><strong class = "highlighted-text">Overzicht aanvragen:</strong> In het overzicht kun je alle gegevens van een schoolaanvraag bekijken.</p>
                    <br>
                </div>    
            </div>
        </fieldset>
    </form>
    <div class="generalFlashmessageWrapper">
        <div 
            id="receive-message-general-Requests" 
            class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
            >
            <?php echo htmlspecialchars($Flashmessage_general_Requests); ?>	
        </div>
    </div>

    <?php if (!empty($overviewHtml)) { echo $overviewHtml; } ?>

   <?php if (!empty($Flashmessage_DownloadButton)) : ?>]
    <div class="generalFlashmessageWrapper">
        <div 
            id="download-button-message" 
            class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
        >
            <?php echo htmlspecialchars($Flashmessage_DownloadButton); ?>	
        </div>
    </div>
<?php endif; ?>


</div>
<footer class="footer_dashboard">
    <p id="copy_logo">&copy; <span id="currentYear"></span> GeoFort</p>
    <div class="footer-logo-container">
        <img src="images/geofort_logo.png" alt="GeoFort Logo" class="footer-logo">
    </div>
</footer>

<script>
    document.getElementById('currentYear').textContent = new Date().getFullYear();
</script>
</body>
</html>
