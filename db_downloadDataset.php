<?php
/** || --------sessie controleren en inactiviteit controleren ----------- */
/**|| --------modules --------------- */
require_once 'module_makeConnectionToDataBase.php';
require_once 'defaultFunctions.php';
require_once 'db_module_inactivityCheck.php';
require 'vendor/autoload.php'; 

/**|| --------declaratie globale variabelen -------------|| */
$pdo;

//Array gebruik tijdens user input validatie bij SQL queries
$errors = []; 

//startdate en enddate zelf
$startdate = '';
$enddate = '';

//array om uit de get parameters de startdate en enddate halen
$dataRange = [
    'startdate' => '',
    'enddate' => ''
];

$resetDataRange = [
    'startdate' => function(&$dataRange){
        $dataRange['startdate'] = '';
    },
    'enddate' => function(&$dataRange){
        $dataRange['enddate'] = '';
    } 
];

//Dit wordt het bereik waarbinnn gekozen kan worden om een dataset te downloaden
$calenderData = [];

//de info ophalen voor de donwload data set als overzicht
$requests = []; 


//(Fout) bericht voor de startkalender
$Flashmessage_calender = '';

//(Fout) bericht voor knop ender de startkalender
$Flashmessage_calenderSubmitKnop = '';

//fout bericht als een download niet is gelukt
$Flashmessage_DownloadButton = '';

//Algemene foutmelding boven het overzichtgegevens van de te donwnloaden dataset
$Flashmessage_general_Requests = '';

//Algemene foutmelding 
$Flashmessage_general = '';

// 'success' of 'error'
$FlashmessageType = ''; 

// deze hash wordt bijgehouden in een windowsvariable en is bedoeld als anker voor navigatie en scrollen naar dat html-element
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


function setandResetDataRange($target, $date, $resetDataRange, &$dataRange){
    if (array_key_exists($target, $dataRange)){
        $resetDataRange[$target]($dataRange);
        $dataRange[$target] = $date;
    } else {
        error_log("Ongeldige target doorgegeven setandResetDataRange");
    }

    return $dataRange[$target];
}


function catchDBError($e){
    global $resetServerMessages, $FlashMessages;
    
    list($FlashMessages['message']['general'], $FlashMessages['type']) = displayMessage(
        'general', 'databasefout', $resetServerMessages, $FlashMessages, 'error'
    );
    
    error_log('Databasefout: ' . $e->getMessage());
}
    


//Data bedoeld om het bereik door te geven waarbinnen een dataset gedownload kan worden 
function get_range_for_startcalendar($pdo, $resetServerMessages, &$FlashMessages, &$Flashmessage_calender, &$FlashmessageType) {
    try {
        /** door de group by kan count id nooit 0 zijn */
        $sql = "
        SELECT 
            MIN(bezoekdatum) AS startdate, 
            MAX(bezoekdatum) AS enddate
        FROM 
            aanvragen;
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $calenderData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($calenderData)) {
            list($Flashmessage_calender, $FlashmessageType) = displayMessage(
                'calender', 'Geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'success');  
            return;    
        } 

    } catch (PDOException $e) {
        catchDBError($e);
    } 
    return $calenderData;

}

//aanvraag verzamelen uit de sql op basis van geselecteerde dag 
function get_info_for_datarange_overview($pdo, array $dataRange, $resetServerMessages, $FlashMessages, $Flashmessage_calenderSubmitKnop, $FlashmessageType){
    $requests = [];
    if (
        !is_array($dataRange) || 
        empty($dataRange) || 
        !array_key_exists("startdate", $dataRange) || !array_key_exists("enddate", $dataRange) || 
        empty($dataRange["startdate"]) || empty($dataRange["enddate"])
        )
        {
        list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'ongeldige invoer', $resetServerMessages, $FlashMessages, 'error');
        return null;         	
    }

    $startdate = $dataRange['startdate'];
    $enddate = $dataRange['enddate'];

    try{
        $sqlDataRangeInfo = "
            SELECT 
                IFNULL(SUM(COALESCE(aantal_leerlingen, 0)), 0) AS `Totaal aantal leerlingen`,
                IFNULL(SUM(COALESCE(CASE WHEN schooltype = 'Primair Onderwijs' THEN aantal_leerlingen END, 0)),0) AS `totaal aantal leerlingen PO`,
                IFNULL(SUM(COALESCE(CASE WHEN schooltype REGEXP '^Voortgezet' THEN aantal_leerlingen END, 0)), 0) AS `totaal aantal leerlingen VO`,

                COUNT(id) AS `Totaal aantal boekingen`,
                COUNT(CASE WHEN schooltype = 'Primair Onderwijs' THEN ID END) AS `Totaal boekingen PO`,
                COUNT(CASE WHEN schooltype REGEXP '^Voortgezet' THEN ID END) AS `Totaal boekingen VO`,

                COUNT(CASE WHEN status = 'Definitief' THEN id END) AS `Totaal defintieve aanvragen`,
                COUNT(CASE WHEN status = 'In optie' THEN id END) AS `Totaal in optie aanvragen`,
                COUNT(CASE WHEN status = 'Afgewezen' THEN id END) AS `Totaal afgewezen aanvragen`                
            FROM
                aanvragen
            WHERE
                bezoekdatum BETWEEN :startdate  and :enddate        
        ";

        $stmt = $pdo->prepare($sqlDataRangeInfo);
        $stmt->execute([':startdate' => $startdate, ':enddate' => $enddate]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($requests)){
            return $requests;
        } else {
            list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'geen boekingen gevonden', $resetServerMessages, $FlashMessages, 'error');
            return null;         
        }

    }catch(PDOException $e) {
        catchDBError($e);
    }

}

function generateRequestsOverview($requestsHTML = [], $dataRange = []) {
    //global vars for messages
    global $resetServerMessages, $FlashMessages, $Flashmessage_general_Requests;
    global $FlashmessageType;

    // default const in function
    $html = '';
    $h2Text = ''; 

    //parameters controleren en bij foute invoer foutbericht laten zien en null returnen
    if (empty($requestsHTML) || empty($dataRange) ||
        !is_array($requestsHTML) || !is_array($dataRange) ||
        !array_key_exists("startdate", $dataRange) || !array_key_exists("enddate", $dataRange) || 
        empty($dataRange["startdate"]) || empty($dataRange["enddate"])
        ){
            list($Flashmessage_general_Requests, $FlashmessageType) = displayMessage(
                'general_Requests', 'ongeldige input', $resetServerMessages, $FlashMessages, 'error');
            return null;    
        }

    //converteren start en eind datum naar nl format om te displayen in titel
    $formattedstartdate = date('d F Y', strtotime($dataRange['startdate']));
    $formattedstartdate = convertEnglishDatetoDutchDate($formattedstartdate);

    $formattedenddate = date('d F Y', strtotime($dataRange['enddate']));
    $formattedenddate = convertEnglishDatetoDutchDate($formattedenddate);

    $h2Text = "Overzicht totallen binnen de periode van $formattedstartdate tot $formattedenddate";

    $requestsHTML = $requestsHTML[0];
   
    // Genereer de HTML-output
    $html .= '<section class="overviewRequestsWrapperContainer downloadDataSetFromRange" id="overviewRequestMainContainer">';
    $html .= '<h2 class="overviewRequestsH2 downloadDataSetFromRange">'. htmlspecialchars($h2Text) .'</h2>';
    $html .= "<form method='POST' class='request-item form__bekijk'>";
    $html .= '<div class="bekijkDateGroup downloadDataSetFromRange">';
    $html .= '<div class="request-item card-bekijk downloadDataSetFromRange">';
    $html .= '<div class="request-item containerWrapper__bekijk downloadDataSetFromRange">';
    $html .= '<div class="request-item container-bekijk">';  
    foreach ($requestsHTML as $key => $value) {
        $html .= '<p class="request-item para-bekijk downloadDataSetFromRange">
                    <span class="request-label">' . $key . ':</span> 
                    <span class="request-value">' . $value . '</span>
                  </p>';
    }
    
    $html .= '</div>'; //Sluit request-item container-bekijk af
    $html .= '</div>'; //sluit containerwrapper-bekijk af
    $html .= '</div>'; // einde item-card 
    $html .= '</div>'; // Einde van date-group


    /** || ------------[download started message]----------||*/
    $html .= '<div class="generalFlashmessageWrapper">';
    $html .= '<div 
        id="donwload-started-message"  
        class="flash-numberofStudents-message absolute-positioned-General__numberOfStudents success">';
    $html .= '</div>';
    $html .= '</div>';


    /** || ------------[download knop zelf]----------||*/
    $html .= '<button 
        id="download-button" 
        class="dashboardKnopOverviewRequests" 
        name="download_Requests" 
        type="submit"
    >Download dataset</button>';
    $html .= '</form>';
    $html .= '</section>';

    return $html;
}



function get_requested_downloads($pdo, array $dataRange) {
    if (
        empty($dataRange) || !is_array($dataRange)  || 
        !array_key_exists("startdate", $dataRange)   || 
        !array_key_exists("enddate", $dataRange)    || 
        empty($dataRange["startdate"]) || empty($dataRange["enddate"])
    ){
        return false;    
    }
 
    $SQLstartdate_Sanitized = $dataRange['startdate'];
    $SQLenddate_Sanitized = $dataRange['enddate'];


    try {
        ob_clean(); // Zorgt ervoor dat er geen output voor de headers staat
        $sqlBuildCSV = "
        SELECT
            *
        FROM
            aanvragen
        WHERE
            bezoekdatum BETWEEN :startdate AND :enddate
        ";

        $stmt = $pdo->prepare($sqlBuildCSV);
        $stmt->execute([':startdate' => $SQLstartdate_Sanitized, ':enddate' => $SQLenddate_Sanitized]);
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
        header('Content-Disposition: attachment; filename="dataset_range_'. $SQLstartdate_Sanitized . '_tot_'. $SQLenddate_Sanitized .'.csv"');


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
        catchDBError($e);
    }
}

/**|| --------------------loading the page with the data and handling the post and get requests ----------------------------------|| */


// Gebruik de functie om een databaseverbinding te maken
try {
    $pdo = connectToDataBase();
} catch (PDOException $e) {
    list ($Flashmessage_general, $FlashmessageType) = displayMessage(
        'general', 'Fout in verbinding met database', $resetServerMessages, $FlashMessages, 'error');
    die("Fout bij het verbinden met de database. Neem contact op met de beheerder.");
}
/**||-------[startkalender vullen met bereik]------------|| */
$calenderData = get_range_for_startcalendar($pdo, $resetServerMessages, $FlashMessages, $Flashmessage_calender, $FlashmessageType);


/**---------------|| GET ||------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['verzoek']) && $_GET['verzoek'] === 'dataRange' && isset($_GET['startdate'])  && isset($_GET['enddate'])){
    $requests = [];
    $dataRangeChosen = true;
    $startdateFromGet = $_GET['startdate'] ?? null;
    $enddateFromGet = $_GET['enddate'] ?? null;

    if ((is_string($startdateFromGet) && is_string($enddateFromGet)) && ( $startdateFromGet ==='' || $enddateFromGet === '')){
        list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'kies zowel een start als einddatum', $resetServerMessages, $FlashMessages, 'success');
        $dataRangeChosen = false;
    }

    if ($dataRangeChosen){
        $startdateFromGet__SQL = get_SQL_Date($startdateFromGet);
        $enddateFromGet__SQL = get_SQL_Date($enddateFromGet);

        $startdateFromGet__SQL__Checked = sanitize_datum($startdateFromGet__SQL, $errors);
        $enddateFromGet__SQL__Checked = sanitize_datum($enddateFromGet__SQL, $errors);


        //uitroepteken toevoegen empty
        if(!$startdateFromGet__SQL__Checked ||  !$enddateFromGet__SQL__Checked || !empty($errors)) {
            list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'ongeldige datums doorgegeven', $resetServerMessages, $FlashMessages, 'error');
            $errors=[];
        } else {
            $startdate = setandResetDataRange('startdate',$startdateFromGet__SQL__Checked, $resetDataRange, $dataRange);
            $enddate = setandResetDataRange('enddate',$enddateFromGet__SQL__Checked, $resetDataRange, $dataRange);

            $requests = get_info_for_datarange_overview($pdo, $dataRange, $resetServerMessages, $FlashMessages, $Flashmessage_calenderSubmitKnop, $FlashmessageType);
            if ($requests === null || empty($requests)){
                list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'geen boekingen gevonden', 'error', $resetServerMessages, $Flashmessages);
            } else {
            $overviewHtml = generateRequestsOverview($requests, $dataRange);
            setAndResetHash($hash, 'overviewRequestMainContainer');
            }
        }
    }
}

/**---------------|| POST ||------------------------------ */

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_Requests'])){
    $startdateFromGet = $_GET['startdate'] ?? null;
    $enddateFromGet = $_GET['enddate'] ?? null;

    $startdateFromGet__SQL = get_SQL_Date($startdateFromGet);
    $enddateFromGet__SQL = get_SQL_Date($enddateFromGet);

    $startdateFromGet__SQL__Checked = sanitize_datum($startdateFromGet__SQL, $errors);
    $enddateFromGet__SQL__Checked = sanitize_datum($enddateFromGet__SQL, $errors);

     //uitroepteken toevoegen empty
     if(!$startdateFromGet__SQL__Checked ||  !$enddateFromGet__SQL__Checked || !empty($errors)) {
        $overviewHtml = generateRequestsOverview($requests, $dataRange);
        list($Flashmessage_DownloadButton, $FlashmessageType) = displayMessage('download', 'ongeldige datums doorgegeven', $resetServerMessages, $FlashMessages, 'error');
        setAndResetHash($hash, 'download-button-message');
        $errors=[];
    } else {
        $resetDataRange['startdate']($dataRange);
        $resetDataRange['enddate']($dataRange);

        $dataRange['startdate'] = $startdateFromGet__SQL__Checked;
        $dataRange['enddate'] = $enddateFromGet__SQL__Checked;

        if(!get_requested_downloads($pdo, $dataRange)){
            $overviewHtml = generateRequestsOverview($requests, $dataRange);
            list($Flashmessage_DownloadButton, $FlashmessageType) = displayMessage('download', 'fout in het donwloaden', $resetServerMessages, $FlashMessages, 'error');
            setAndResetHash($hash, 'download-button-message');
        }
        
    }

}


?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download dataset - GeoFort Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/nl.js"></script>
    <script defer type= "module" src="db_downloadDataset.js"></script>
    <script>
        window.calenderData = <?php echo json_encode($calenderData); ?>;
    </script>
     <!--hash opvangen de : is correct en is vervangende  syntax voor {}-->
     <?php if (!empty($hash)) : ?>
        <script>
            window.location.hash = "<?php echo htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?>";
        </script>
    <?php endif; ?>
</head>
<body class="body-dashboard">
<header class="header-dashboard">
    <h1 class="Title_Dashboard">GeoFort
    <span>
    Dashboard<span>
    </h1>
    <nav class="Navbar-dashboard">
        <ul>
            <li><a href="db_dashBoard.php">Wijzig statussen</a></li>
            <li><a href="db_wijzigAantalLeerlingen.php">Wijzig aantal leerlingen</a></li>
            <li><a href="db_bekijkAanvraag.php">Bekijk een schoolaanvraag</a></li>
            <li><a href="db_blokkeerEenDatum.php">Datum blokkeren</a></li>
            <li><a href="db_downloadDataset.php" class="nav-link-active">Dataset downloaden</a></li>
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

    <!-------------------[Form met GET Methode]---------------------------->
    <!----[startdatum]----------[einddatum]-----------[submitKnop]--------->

    <form action="" method ="GET" class="dashboard-form">
        <fieldset class="fieldset-dashboard">
            <legend class="dashboard-legend">Download een dataset</legend>
            <div id="date-picker">
                <!--[startkalender]-->
                <label for="start_date">Startdatum</label>
                <input 
                    type="text" 
                    id="start_date"
                    name="startdate"
                    value="<?php echo htmlspecialchars($_GET['startdate'] ?? ''); ?>"
                >
                <!--[messagendiv-startkalender]-->
                <div 
                    id="receive-message-startcalender" 
                    class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
                > 
                <?php echo htmlspecialchars($Flashmessage_calender); ?>	
                </div>
            </div>

            <!--[calender met einddatum]-->
            <div id="date-picker">
                <label for="end_date">Einddatum</label>
                <input 
                    type="text" 
                    id="end_date"
                    name="enddate"
                    value="<?php echo htmlspecialchars($_GET['enddate'] ?? ''); ?>"
                    >
            </div>
            
            <!--[dataRange knop met melding div]-->
            <button 
                id="submit-dataRange" 
                name="verzoek"
                value="dataRange"
                class ="dashboardKnop"
            >
            Verkrijg een downloadoverzicht
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
                    <a href="#" class="meerInformatieToggle" id="meerInformatieDataset" data-target="datum_kiezen_dataset"><span>Meer informatie over het verkrijgen van een dataset</span></a>
                        <div id="datum_kiezen_dataset" class="meerInformatieContent">
                            <p> Kies de <strong class = "highlighted-text">start-datum</strong> van de dataset die je wilt downloaden</p>
                            <p> Kies de <strong class = "highlighted-text">eind-datum</strong> van de dataset die je wilt downloaden</p>
                    </div>
            </div>
        </fieldset>
    </form>
    <!--[meldingsdiv voor foutafhandeling donwloadoverzicht]-->
    <div class="generalFlashmessageWrapper">
        <div 
            id="receive-message-general-Requests" 
            class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
            >
            <?php echo htmlspecialchars($Flashmessage_general_Requests); ?>	
        </div>
    </div>
    
    <!--[donwload overzicht]-->
    <?php if (!empty($overviewHtml)) { echo $overviewHtml; } ?>

    <!--[bericht na een succesvolle download op bass van klikken en cookie]-->    
    <?php if (!empty($Flashmessage_DownloadButton)) : ?>
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
