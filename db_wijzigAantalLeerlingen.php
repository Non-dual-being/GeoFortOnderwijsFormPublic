<?php
require_once 'db_module_inactivityCheck.php';
require_once 'module_makeConnectionToDataBase.php';
require_once 'defaultFunctions.php';


/**|| ---------settings-------------------------|| */
ini_set('display_errors', 0);  // Schakel weergave van fouten in de browser uit
ini_set('log_errors', 1);      // Log fouten naar een logbestand
error_reporting(E_ALL);        // Log alle fouten


/**|| --------modules --------------- */
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

//de boekingen ophalen op basis van de gekozen dag
$requests = []; 

//De kalenderdata voor in optie aanvragen 
$InOptieKalenderData = [];

//(Fout) bericht voor de in optie kalender
$Flashmessage_inoptieaanvraagkalender = '';

//(Fout) bericht voor de in opie submit knop
$Flashmessage_inoptieSubmitKnop = '';

//Algemene foutmelding 
$Flashmessage_general = '';

$Flashmessage_general_afterNumberOfStudentsChange = '';
// 'success' of 'error'
$FlashmessageType = ''; 

$FlashMessages = [
    'message' => [
        'inoptieKalender' => '',
        'inoptieSubmitKnop' => '',
        'general_afterNumberOfStudentsChange' => '',
        'general' => '',
        
    ],
    'type' => ''
];

$resetServerMessages = [
    'inoptieKalender' => function (&$FlashMessages){
        $FlashMessages['message']['inoptieKalender'] = '';
        $FlashMessages['type'] = '';
    },
    'inoptieSubmitKnop' => function (&$FlashMessages){
        $FlashMessages['message']['inoptieSubmitKnop'] = '';
        $FlashMessages['type'] = '';
    },
    'general_afterNumberOfStudentsChange' => function (&$FlashMessages){
        $FlashMessages['message']['general_afterNumberOfStudentsChange'] = '';
        $FlashMessages['type'] = '';
    },
    'general' => function (&$FlashMessages){
        $FlashMessages['message']['general'] = '';
        $FlashMessages['type'] = '';
    }
];


/**|| -----kalender in laden met in optie data en doorgeven via window var ---|| */ 

function get_inoptiedata_for_calendar($pdo, $resetServerMessages) {
    $response = [
        'inoptiedata' => [],
    ];
    try {
        $sql = "
        SELECT 
            bezoekdatum, 
            COUNT(id) AS aantal_scholen
        FROM 
            aanvragen
        WHERE 
            status = 'In optie' 
            AND bezoekdatum BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY 
            bezoekdatum
        ORDER BY 
            bezoekdatum ASC;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $inoptieDates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($inoptieDates)) {
            $repsonse['inoptieDates'] = $inoptieDates;
        } else {
            list($Flashmessage_inoptieaanvraagkalender, $FlashmessageType) = displayMessage('inoptieKalender', 'Geen in optie boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
        }

    } catch (PDOException $e) {
        list($Flashmessage_general, $FlashmessageType) = displayMessage('general', 'Fout in database', $resetServerMessages, $FlashMessages, 'error');
    } 
    return $repsonse;

}

// functie om de in optie aanvraag data uit de database op te halen voor de gekozen dag 
function get_requests_for_change_number_of_students($pdo, $selectedDateSanitized){
    $requests = [];
    $sqlInoptieAanvraag = "
        SELECT
            id, 
            schoolnaam,
            aantal_leerlingen,
            bezoekdatum,
            email
        FROM
            aanvragen
        WHERE
            status = 'In optie'
        AND
            bezoekdatum = :bezoekdatum
    ";

    $stmt = $pdo->prepare($sqlInoptieAanvraag);
    $stmt->execute([':bezoekdatum' => $selectedDateSanitized]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    if (!empty($requests)){
        return $requests;
    } else return [];

}





function generateRequestsOverview($requests, $message = []) {
    // default const in function
    $html = '';
    $hashedIDActive = '';
    $h2Text = 'overzicht van de in optie aanvragen'; // default value

    if ($message !== []){
        $MessageId =  $message['id'] ?? null;
        $MessageText = $message['text'] ?? null;
        $MessageType = $message['type'] ?? null;
    }

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


    foreach ($groupedRequests as $date => $requestsOnDate) {
        $html .= '<div class="date-group">';
        $html .= "<h4>Bezoekdatum: $date</h4>";

        foreach ($requestsOnDate as $request) {
            $id = $request['id'];
            $Hash_Key_ID = $_ENV['HASH_ID_KEY'];
            $hashedId = hash_hmac('sha256', $id, $Hash_Key_ID);
            $numberOfStudents = $request['aantal_leerlingen'];
            $html .= '<div class="request-item card">';
            $html .= '<div class="request-item containerWrapper">';
            $html .= '<div class="request-item container">';
            $html .= '<p class="request-item para">School: ' . htmlspecialchars($request["schoolnaam"]) . '</p>';
            $html .= '<p class="request-item para">Aantal leerlingen: ' . htmlspecialchars($request["aantal_leerlingen"]) . '</p>';
            $html .= '<p class="request-item para">Email: ' . htmlspecialchars($request["email"]) . '</p>';
            $html .= '</div>';
            $html .= '</div>';

            // Voeg select-lijst toe voor statuswijziging
            $html .= '<div class="request-item status-container">';
            if ($message !== [] && (int)$request['id'] ===  (int) $MessageId) {
                $hashedIDActive = $hashedId;
            

                $html .= '<div class="generalFlashmessageWrapper">';
                $html .= '<div 
                    id="change-number-message"  
                    class="flash-numberofStudents-message absolute-positioned-General__numberOfStudents-FromServer ' . htmlspecialchars($MessageType ?? '') . '">';
                $html .=  htmlspecialchars($MessageText ?? '');
                $html .= '</div>';
                $html .= '</div>';

            } // Sluit de flash-message div correct af
            $html .= "<form action = '' method='POST' class='request-item form'>";
            $html .= "<input type='hidden' name='request_id_hashed' value='". htmlspecialchars($hashedId) . "'>";
            $html .= "<input type='hidden' name='request_id' value='". htmlspecialchars($id) . "'>";
            $html .= '<div class="request-item status-containerTitle">';
            $html .= '<label for="aantalLeerlingen-' . htmlspecialchars($hashedId) . '">Aantal wijzigen</label>';
            $html .= '</div>';

            $html .= '<div class="generalFlashmessageWrapper">';
            $html .= '<div 
                    id="meldingAantallenFromJS-' . htmlspecialchars($hashedId) . '" 
                    class="flash-numberofStudents-message success absolute-positioned-General__numberOfStudents">';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<button type="button" class="spin-button spin-up">▲</button>';

            $html .= '<input 
                name="aantalLeerlingen" 
                id="aantallen-' . htmlspecialchars($hashedId) . '"
                class="aantalLeerlingenInput" 
                type = "number"
                min="1" max="200"
                step="1"
                value="'.htmlspecialchars($numberOfStudents).'" 
            >';
            $html .= '</input>';
            $html .= '<button type="button" class="spin-button spin-down">▼</button>';
            $html .= '<button 
                id="change-numberOfStudents-button-' . htmlspecialchars($hashedId) . '" 
                class="dashboardKnopOverviewRequests" 
                name="update_numberOfStudents" 
                type="submit"
            >Wijzig</button>';
            $html .= '</form>';
            $html .= '</div>'; // Einde van request-item
            $html .= '</div>'; // Einde van request-item
        }

        $html .= '</div>'; // Einde van date-group
    }

    $html .= '</section>';
    return $html;
}

// Function to return the request after POSt with changed number of students and infomessage 

function get_changed_number_of_Students($pdo, $id, $numberOfStudents) {
    try {
        $SQLCheck = "
        SELECT
            id,
            bezoekdatum
        FROM
            aanvragen
        WHERE
            status = 'In optie'
            AND
            id = :id
        ";

        $stmt = $pdo->prepare($SQLCheck);
        $stmt->execute([':id' => $id]);
        $Check = $stmt->fetch(PDO::FETCH_ASSOC);

        $idCheck = $Check['id'] ?? null;
        $bezoekdatum = $Check['bezoekdatum'] ?? null;

        if ((int)$idCheck !== (int)$id) {
            return [
                'type' => 'error',
                'message' => "Ongeldige aanvraag-ID.",
                'id' => null,
                'general' => true
            ];
        }

        $SQLCheck = "
        SELECT
            COUNT(id) AS currentDefVisits,
            SUM(aantal_leerlingen) AS currentnumberOfStudents
        FROM
            aanvragen
        WHERE
            status = 'Definitief'
            AND
            bezoekdatum = :bezoekdatum
        GROUP BY
            bezoekdatum
        ";

        $stmt = $pdo->prepare($SQLCheck);
        $stmt->execute([':bezoekdatum' => $bezoekdatum]);
        $Check = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($Check) {
            $currentDefVisits = $Check['currentDefVisits'];
            $currentnumberOfStudents = $Check['currentnumberOfStudents'];

            if ($currentDefVisits >= 2) {
                return [
                    'type' => 'error',
                    'message' => "Voor deze dag zijn er al 2 definitieve boekingen.",
                    'id' => $id,
                    'general' => false
                ];
            } elseif ($currentnumberOfStudents + $numberOfStudents > 200) {
                return [
                    'type' => 'error',
                    'message' => "Voor deze dag kunnen maximaal " . (200 - $currentnumberOfStudents) . " leerlingen in optie worden gezet.",
                    'id' => $id,
                    'general' => false
                ];
            }
        }

        $checkSameNumberOfStudentsSQL = "
            SELECT 
                aantal_leerlingen
            FROM 
                aanvragen
            WHERE 
                id = :id
        ";
        $stmt = $pdo->prepare($checkSameNumberOfStudentsSQL);
        $stmt->execute([':id' => $id]);
        $CheckSameNumber = $stmt->fetch(PDO::FETCH_COLUMN);

        if ($CheckSameNumber !== false && (int)$CheckSameNumber === (int)$numberOfStudents) {
            return [
                'type' => 'success',
                'message' => "Het aantal leerlingen is ongewijzigd gebleven op $numberOfStudents.",
                'id' => $id,
                'general' => false
            ];
        }


        $sqlUpdateNumberOfStudents = "
            UPDATE
                aanvragen
            SET
                aantal_leerlingen = :aantal_leerlingen_nieuw
            WHERE
                id = :id 
        ";
        $stmt = $pdo->prepare($sqlUpdateNumberOfStudents);
        $stmt->execute([
            ':aantal_leerlingen_nieuw' => $numberOfStudents,
            ':id' => $id
        ]);

        return [
            'type' => 'success',
            'message' => "Het aantal leerlingen is succesvol gewijzigd naar $numberOfStudents.",
            'id' => $id,
            'general' => false
        ];
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


/** gewijzigd aantal leerlingen requestoveriew genereren met boodshap */
function displayRequestsAfterPost ($messagePara) {
    global $resetServerMessages;
    global $Flashmessage_general_afterNumberOfStudentsChange;
    global $pdo;
    $request = [];
    $overviewHtml = '';

    if (isset($_GET['verzoek'])){

        $verzoekType = $_GET['verzoek'];
        $selectedDate = $_GET['selectedDate'] ?? null;
        $requests = [];
        if ($verzoekType === 'inoptieaanvraag' && is_string($selectedDate) && $selectedDate !== ''){
            $SQLDate = get_SQL_Date($selectedDate);
            $selectedDateSanitized = sanitize_datum($SQLDate, $errors);
            if(!$selectedDateSanitized) {
                list($Flashmessage_general_afterNumberOfStudentsChange, $FlashmessageType) =  displayMessage('general_afterNumberOfStudentsChange', 'ongeldige datum', $resetServerMessages, $FlashMessages, 'error');
                setAndResetHash($hash,'receive-message-general-afterNumberOfStudentsChange');   
                $errors = [];
            } else {
                $requests = get_requests_for_change_number_of_students($pdo, $selectedDateSanitized);
                if (empty($requests)) {
                    list($Flashmessage_general_afterNumberOfStudentsChange, $FlashmessageType) =  displayMessage('general_afterNumberOfStudentsChange', 'geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');
                } else {
                    $overviewHtml = generateRequestsOverview($requests, $messagePara);
                }
            }
       
    }}
    if ($overviewHtml){
        return $overviewHtml;
    }
}



/**|| --------------------loading the page with the data and handling the post and get requests ----------------------------------|| */


// Gebruik de functie om een databaseverbinding te maken
try {
    $pdo = connectToDataBase();
} catch (PDOException $e) {
    list($Flashmessage_general, $FlashmessageType) =
    displayMessage('general', 'Fout in verbinding met database', $resetServerMessages, $FlashMessages, 'error');
    die();
}


//inoptie dagen voor de kalender ophalen
$InOptieKalenderData = get_inoptiedata_for_calendar($pdo, $resetServerMessages);

/**||--------------------------POST EN GET Request Logica --------------------------------|| */

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['verzoek']) && $_GET['verzoek'] === 'inoptieaanvraag'){
    $selectedDate = $_GET['selectedDate'] ?? null;
    $dateChosen = true;
    if (is_string($selectedDate) && $selectedDate === ''){
        list($Flashmessage_inoptieSubmitKnop, $FlashmessageType) = displayMessage('inoptieSubmitKnop', 'Kies een datum', $resetServerMessages, $FlashMessages, 'success');
        $dateChosen = false;
    }
    if ($dateChosen){
        $requests = [];
        $SQLDate = get_SQL_Date($selectedDate);
        $selectedDateSanitized = sanitize_datum($SQLDate, $errors);
        if(!$selectedDateSanitized || !empty($errors) ) {
            list($Flashmessage_inoptieSubmitKnop, $FlashmessageType) = displayMessage('inoptieSubmitKnop', 'Ongeldige datum', $resetServerMessages, $FlashMessages, 'error');
            $errors=[];
        } else {
            $requests = get_requests_for_change_number_of_students($pdo, $selectedDateSanitized);
            if (empty($requests)){
                list($Flashmessage_inoptieSubmitKnop, $FlashmessageType) = displayMessage('inoptieSubmitKnop', 'Geen boekingen gevonde', $resetServerMessages, $FlashMessages, 'error');
            } else {
                $overviewHtml = generateRequestsOverview($requests);
                setAndResetHash($hash,'overviewRequestMainContainer');
            }
        }
    }
}




/** || ------------ Post verzoek voor om het aantal leerlingen te wijzigen ---------- || */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_numberOfStudents'])){
    $HashedIDToSQL = $_POST['request_id_hashed'] ?? false;
    $IDToSQL = $_POST['request_id'] ?? false;
    $messageFromChangeStatus = [
        'text' => '',
        'type' => '',
        'id' => null
    ];
    // Valideer de ingevoerde ID
    if (empty($HashedIDToSQL) || !is_string($HashedIDToSQL) || strlen($HashedIDToSQL) !== 64) {
        $HashedIDToSQL = false; // Markeer het als ongeldig
    }

    /** id en hashed id controleren en valideren */ 

    //valideer aanvraag id unhashed
    if (empty($IDToSQL) || !filter_var($IDToSQL, FILTER_VALIDATE_INT)) {
        $IDToSQL = false; // Markeer het als ongeldig
    }

    $calculatedHashedId = hash_hmac('sha256', $IDToSQL, $Hash_Key_ID);
    if (!hash_equals($calculatedHashedId, $HashedIDToSQL)){
        $HashedIDToSQL = false;
        $IDToSQL = false;
    }

    $numberOfStudentsToSQL = $_POST['aantalLeerlingen'];
    if (empty($numberOfStudentsToSQL) && !filter_var($numberOfStudentsToSQL, FILTER_VALIDATE_INT) ){
        $numberOfStudentsToSQL = false;
    } else if ($numberOfStudentsToSQL < 1 || $numberOfStudentsToSQL > 200) {
        $numberOfStudentsToSQL = false;
    }

    if ($IDToSQL === false || $HashedIDToSQL === false || $numberOfStudentsToSQL === false){
        list($Flashmessage_inoptieSubmitKnop, $FlashmessageType) = displayMessage('inoptieSubmitKnop', 'Ongeldige datum', $resetServerMessages, $FlashMessages, 'error');
    } else {
        $result = get_changed_number_of_Students($pdo, $IDToSQL, $numberOfStudentsToSQL);
        if (isset($result['general']) && $result['general'] === true){
            $generalMessageAfterPost = $result['message'] ?? 'Ongeldig verzoek';
            list($Flashmessage_general_afterNumberOfStudentsChange, $FlashmessageType) =  displayMessage('general_afterNumberOfStudentsChange', $generalMessageAfterPost, $resetServerMessages, $FlashMessages, 'error');
            setAndResetHash($hash,'receive-message-general-afterNumberOfStudentsChange');    
        } else {
            $messageFromGetChangedNumberOfStudents['text'] = $result['message'];
            $messageFromGetChangedNumberOfStudents['type'] = $result['type'];
            $messageFromGetChangedNumberOfStudents['id'] = $result['id'];
            $overviewHtml = displayRequestsAfterPost($messageFromGetChangedNumberOfStudents);
            setAndResetHash($hash, 'change-status-message'); 

        }
    }

}

?>


!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wijzig aantal leerlingen - GeoFort Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/nl.js"></script>
    <script defer type="module" src="db_wijzigAantalLeerlingen.js"></script>
    <script>
        window.InOptieKalenderData = <?php echo json_encode($InOptieKalenderData); ?>;
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
        <li><a href="db_dashBoard.php">Wijzig statussen</a></li>
            <li><a href="db_wijzigAantalLeerlingen.php" class="nav-link-active">Wijzig aantal leerlingen</a></li>
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
            <legend class="dashboard-legend">Aantal leerlingen&nbsp;<span class="info_wijzig_aantallen" data-info-tip="Alleen van in optie aanvragen kan het aantal leerlingen gewijzigd worden">in optie</span>&nbsp;aanvragen</legend>
            <div id="date-picker">
                <label for="start_date">Agenda</label>
                <input 
                    type="text" 
                    id="start_date"
                    name="selectedDate"
                    value= "<?php echo htmlspecialchars($_GET['selectedDate'] ?? ''); ?>"
                >
                    <!--Bij refresh page na get verzoek de waarde uit url halen om datum te behouden-->
                <div id="receive-message-inoptiekalender" class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>">
                <?php echo htmlspecialchars($Flashmessage_inoptieaanvraagkalender); ?>	
                </div>
                <button 
                    id="submit-date" 
                    name="verzoek"
                    value="inoptieaanvraag"
                    class="dashboardKnop"
                > Bekijk de in optie aanvragen
                </button>
                <div class="generalFlashmessageWrapper">
                    <div id="receive-message-inoptieSubmitKnop" class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>">
                    <?php echo htmlspecialchars($Flashmessage_inoptieSubmitKnop); ?>	
                    </div>
                </div>
            </div>
            <div class="meer-informatie-container">
                    <a href="#" class="meerInformatieToggle" data-target="daginoptieaanvragenInfo"><span>Meer informatie over de in optie dagaanvragen</span></a>
                        <div id="daginoptieaanvragenInfo" class="meerInformatieContent">
                            <p> De agenda laat alle <strong class = "highlighted-text" >In optie</strong> aanvragen zien</p>
                            <p><strong class = "highlighted-text" >Datum kiezen:</strong> In de agenda klik je op de bezoekdatum van de school waarvan je het aantal leerlingen wilt wijzigen.</p>
                            <p><strong class = "highlighted-text">Overzicht aavragen:</strong> In het overzicht kun je het aantal leerlingen veranderen en de veranderingen doorgeven.</p>
                        </div>
                    </div>
        </fieldset>
    </form>
    <div>
    <div 
        id="receive-message-general-afterNumberOfStudentsChange" 
        class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
        >
        <?php echo htmlspecialchars($Flashmessage_general_afterNumberOfStudentsChange); ?>	
    </div>

    <?php if (!empty($overviewHtml)) {echo $overviewHtml;} ?>
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