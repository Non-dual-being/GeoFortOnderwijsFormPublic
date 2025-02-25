<?php
/** || --------sessie controleren en inactiviteit controleren ----------- */
/**|| --------modules --------------- */
require_once 'module_makeConnectionToDataBase.php';
require_once 'defaultFunctions.php';
require_once 'db_module_inactivityCheck.php';

//Array gebruik tijdens user input validatie bij SQL queries
$errors = []; 

//geselecteerde dag in blokkeerkalender
$selectedDate = null; 

//De kalenderdata voor een dag 
$calenderData = [];

//(Fout) bericht voor de in blokkeer calender
$Flashmessage_calender = '';

//(Fout) bericht get knop van de kalender
$Flashmessage_calenderSubmitKnop = '';

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
    'general' => function (&$FlashMessages){
        $FlashMessages['message']['general'] = '';
        $FlashMessages['type'] = '';
    }
];

$FlashMessages = [
    'message' => [
        'calender' => '',
        'calender_Submit' => '',
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
        bezoekdatum
    FROM 
        aanvragen
    WHERE 
        status IN ('In optie', 'Definitief')
        AND 
        bezoekdatum BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 12 MONTH);
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
            'general', 'Fout in database', $resetServerMessages,  $FlashMessages, 'error');
        error_log('Databasefout');
    } 
    return $calenderData;

}

function blokkeerDatumMetSelectedDate ($pdo, $datum = null){
    if (!$datum){
        return false;
    }

    try {
        $sql = "
        SELECT 
            bezoekdatum,
            SUM(CASE WHEN status = 'In optie' THEN 1 ELSE 0 END) AS total_in_optie,
            SUM(CASE WHEN status = 'Definitief' THEN 1 ELSE 0 END) AS total_definitief,
            COUNT(ID) as total
        FROM 
            aanvragen
        WHERE 
            status IN ('In optie', 'Definitief') 
            AND bezoekdatum = :datum
        GROUP BY bezoekdatum;
    ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':datum' => $datum]);
        $booked = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booked && $booked['total'] > 0) {
        return false;
        } 

        $sql = "
        INSERT INTO aanvragen (
            voornaam_contactpersoon, 
            achternaam_contactpersoon, 
            email, 
            school_telefoon, 
            contact_telefoon, 
            aantal_leerlingen, 
            bezoekdatum, 
            schoolnaam, 
            adres, 
            postcode, 
            plaats, 
            keuze_module, 
            remise_break, 
            kazerne_break, 
            fortgracht_break, 
            waterijsje, 
            glas_limonade, 
            remise_lunch, 
            eigen_picknick, 
            niveau1,
            niveau2,
            niveau3,
            leeftijdsgroep1,
            leeftijdsgroep2,
            leeftijdsgroep3,
            leeftijdsgroep4,
            leeftijdsgroep5,
            status
        ) VALUES (
            'GeoFort Planner', 
            'Stichting', 
            'GeoFortPlanner@gmail.com', 
            '0345630480',
            '0345630480', 
            160, 
            :datum, 
            'Geblokt door GeoFort', 
            'Nieuwe Steeg 74', 
            '4171KG', 
            'Herwijnen', 
            'Earth-Watch', 
            0, 
            0, 
            0, 
            0, 
            0, 
            0, 
            0, 
            'Regulier', 
            'Regulier', 
            'Regulier', 
            'Geblokt door GeoFort Planner', 
            'Geblokt door GeoFort Planner', 
            'Geblokt door GeoFort Planner', 
            'Geblokt door GeoFort Planner', 
            'Geblokt door GeoFort Planner', 
            'Definitief'
        )
    ";
    $insertStmt = $pdo->prepare($sql);
    $insertStmt->execute([':datum' => $datum]);
    return true;


    
    

} catch (PDOException $e) {
    return false;
                                                        
}}


/**|| --------------------loading the page with the data and handling the post and get requests ----------------------------------|| */


/**|| verbinding initialiseren en calenderdata op halen -------------- ||*/

// Gebruik de functie om een databaseverbinding te maken
try {
    $pdo = connectToDataBase();
} catch (PDOException $e) {
    list ($Flashmessage_general, $FlashmessageType) = displayMessage(
        'general', 'Fout in verbinding met database', $resetServerMessages, $FlashMessages, 'error');
    die("Fout bij het verbinden met de database. Neem contact op met de beheerder.");
}

$calenderData = get_data_for_calendar($pdo, $resetServerMessages);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['verzoek']) && $_GET['verzoek'] === 'datumvastzetten'){
    $selectedDate = $_GET['selectedDate'] ?? null;
    $dateChosen = true;
    if (is_String($selectedDate) && $selectedDate === ''){
        list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'Kies een datum', $resetServerMessages, $FlashMessages, 'success');
        $dateChosen = false;
    }
    if ($dateChosen){
        $SQLDate = get_SQL_Date($selectedDate);
        $selectedDateSanitized = sanitize_datum($SQLDate, $errors);
        if(!$selectedDateSanitized || !empty($errors) ) {
            list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'ongeldige invoer', $resetServerMessages, $FlashMessages, 'error');
            $errors=[];
        } else {
            $blocked = blokkeerDatumMetSelectedDate($pdo,$selectedDateSanitized);
            if ($blocked){
                $NLDatum = sqlDatumNaarNederlandseNotatie($selectedDateSanitized);
                list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', "$NLDatum succesvol geblokkeert", $resetServerMessages, $FlashMessages, 'success');
            } else {
                list($Flashmessage_calenderSubmitKnop, $FlashmessageType) = displayMessage('calender_Submit', 'Fout in het vastzetten', $resetServerMessages, $FlashMessages, 'error');
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
    <title>Blok een datum - GeoFort Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/nl.js"></script>
    <script>
        window.calenderData = <?php echo json_encode($calenderData); ?>;
    </script>
    <script defer type="module" src="db_blokkeerEenDatum.js"></script>
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
            <li><a href="db_wijzigAantalLeerlingen.php">Wijzig aantal leerlingen</a></li>
            <li><a href="db_bekijkAanvraag.php">Bekijk een schoolaanvraag</a></li>
            <li><a href="db_blokkeerEenDatum.php" class="nav-link-active">Datum blokkeren</a></li>
            <li><a href="db_downloadDataset.php">Dataset downloaden</a></li>
        </ul>
    </nav>
    <form class="logOutForm dashBoard" action="logout.php" method="post" class="logout-form">
        <button type="submit" class="Uitlog_Dashboard">Uitloggen</button>
    </form>
</header>

<div id="content-container">
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
            <legend class="dashboard-legend">Datum &nbsp;<span class="info_wijzig_aantallen" data-info-tip="Alleen data zonder aanvragen kunnen geblokkeert worden">blokkeren</span>&nbsp;voor schoolbezoeken</legend>
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
                value="datumvastzetten"
                class = "dashboardKnop"
            >
            Blok de geselecteerde dag</button>

            <div class="generalFlashmessageWrapper">
                <div 
                    id="receive-message-calenderSubmitKnop" 
                    class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>"
                >
                <?php echo htmlspecialchars($Flashmessage_calenderSubmitKnop); ?>	
                </div>
            </div>
            <div class="meer-informatie-container">
                    <a href="#" class="meerInformatieToggle" data-target="datumvastzettenInfo"><span>Meer informatie datum blokkeren</span></a>
                        <div id="datumvastzettenInfo" class="meerInformatieContent">
                            <p> De agenda laat alle <strong class = "highlighted-text" >mogelijk</strong> datums zien die geblokkeert kunnen worden</p>
                            <p><strong class = "highlighted-text">Geblokkeerde data</strong> zijn data waarop een school geen bezoek meer kan plannen</p>
                            <p><strong class = "highlighted-text" >Datum kiezen:</strong> In de agenda klik je op de datum die wilt blokkeren.</p>
                        </div>
                    </div>
        </fieldset>
    </form>
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