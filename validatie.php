<?php
/**-------------[default settings]-------------------------- */
session_start();
ini_set('display_errors', 0);  // Schakel weergave van fouten in de browser uit
ini_set('log_errors', 1);      // Log fouten naar een logbestand
error_reporting(E_ALL);        // Log alle fouten
header('Content-Type: application/json');  // Zorg ervoor dat de PHP-respons in JSON-formaat is

/**----------------------[modules en depedencies]----------------------------- */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'module_makeConnectionToDataBase.php';

/**||--[logica om uit .env de waarden op te halen]---||*/

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
/**
 * __DIR__ is een magic constant in PHP die altijd de absolute padnaam (directory) van het huidige script bevat
 * !not hardcode erin zetten het pad
 * todo: gebruik __dir__ om je pad platform onafhakelijk 
 * * Create immutable zorgt ervoor dat omgevings variabelen niet worden overschreven
 * 
 */
$dotenv->load();

// Haal alleen de specifieke variabele op die je nodig hebt
$smtp_password = getenv('SMTP_PASSWORD') ?: ($_SERVER['SMTP_PASSWORD'] ?? null);

if (!$smtp_password) {
    error_log("SMTP wachtwoord niet gevonden!");
    exit(json_encode(['success' => false, 'servererror' => 'De aanvraag is niet verwerkt!']));
}

/**
 * ?: is het zelfde als $a ? $a : $b
 * Elvis operator wil zeggen als a niet bestaat gebruik b
 */


 /**||--------------[constanten]---------------------------------|| */

$onderwijsModules = [
    'primairOnderwijs' => [
        'standaard' => ["Klimaat-Experience", "Klimparcours", "Voedsel-Innovatie", "Dynamische-Globe"],
        'keuze' => ["Minecraft-Klimaatspeurtocht", "Earth-Watch","Stop-de-Klimaat-Klok","Minecraft-Programmeren"]
    ],
    'voortgezetOnderbouw' => [
        'standaard' => ["Klimaat-Experience", "Voedsel-Innovatie", "Dynamische-Globe", "Earth-Watch"],
        'keuze' => ["Minecraft-Windenergiespeurtocht", "Stop-de-Klimaat-Klok", "Minecraft-Programmeren"]
    ],
    'voortgezetBovenbouw' => [
        'standaard' => ["Klimaat-Experience", "Voedsel-Innovatie", "Dynamische-Globe", "Earth-Watch"],
        'keuze' => ["Crisismanagement"]
    ]
];

$schooltypeMapping = [
    'primairOnderwijs' => 'Primair Onderwijs',
    'voortgezetOnderbouw' => 'Voorgezet Onderwijs onderbouw',
    'voortgezetBovenbouw' => 'Voorgezet Onderwijs bovenbouw'
];

$leeftijden = [
    "primairOnderwijs" => [
        "regulier" => ["Groep 4", "Groep 5", "Groep 6", "Groep 7", "Groep 8"],
        "speciaal" => ["Groep 4", "Groep 5", "Groep 6", "Groep 7", "Groep 8"]
    ],
    "voortgezetOnderbouw" => [
        "VMBO_BB_KB" => ["VMBO 1", "VMBO 2", "VMBO 3"],
        "VMBO_GL_TL" => ["VMBO 1", "VMBO 2", "VMBO 3"],
        "HAVO" => ["HAVO 1", "HAVO 2", "HAVO 3"],
        "VWO" => ["Atheneum 1", "Atheneum 2", "Atheneum 3", "Gymnasium 1", "Gymnasium 2", "Gymnasium 3"],
        "PraktijkOnderwijs" => ["Praktijk Onderwijs 1", "Praktijk Onderwijs 2", "Praktijk Onderwijs 3"]
    ],
    "voortgezetBovenbouw" => [
        "VMBO" => ["VMBO 4", "VMBO 5", "VMBO 6"],
        "MAVO" => ["MAVO 4"],
        "HAVO" => ["HAVO 4", "HAVO 5"],
        "VWO" => ["Atheneum 4", "Atheneum 5", "Atheneum 6", "Gymnasium 4", "Gymnasium 5", "Gymnasium 6"],
        "PraktijkOnderwijs" => ["Praktijk Onderwijs 4", "Praktijk Onderwijs 5"]
    ]
];
//Leeftijden is een associatieve array, de wil zeggen dat je sleutels gebruikt om de waarden aan te spreken ivp indexen

$prijzen = [
    'remiseBreak' => 2.60,
    'kazerneBreak' => 2.60,
    'fortgrachtBreak' => 2.60,
    'waterijsje' => 1.00,
    'pakjeDrinken' => 1.00,
    'remiseLunch' => 5.20
];



/**||-----------[functies]-----------------------------------------|| */

function generateDisabledDates() {
    $disabledDates = [];

    // Voeg weekenden en vakantiedata toe aan de lijst van niet-beschikbare data
    $currentDate = new DateTime('now');
    $endOfYearDate = new DateTime($currentDate->format('Y')+1 . '-12-31');
    
    // Voeg weekenden toe
    while ($currentDate <= $endOfYearDate) {
        if (in_array($currentDate->format('N'), [6, 7])) {
            $disabledDates[] = $currentDate->format('Y-m-d');
        }
        $currentDate->modify('+1 day');
    }

 // Voeg vakanties toe
 $schoolVacations = [
    ['start' => '2025-01-01', 'end' => '2025-01-05'],  // Nieuwjaarsvakantie 2025
    ['start' => '2025-02-15', 'end' => '2025-03-09'],  // Voorjaarsvakantie 2025
    ['start' => '2025-04-19', 'end' => '2025-05-04'],  // Meivakantie 2025
    ['start' => '2025-05-29', 'end' => '2025-06-01'],  // Hemelvaartvakantie 2025
    ['start' => '2025-06-07', 'end' => '2025-06-09'],  // Pinkstervakantie 2025
    ['start' => '2025-07-05', 'end' => '2025-08-31'],  // Zomervakantie 2025
    ['start' => '2025-10-12', 'end' => '2025-10-26'],  // Herfstvakantie 2025
    ['start' => '2025-12-21', 'end' => '2025-12-31']   // Kerstvakantie 2025
];



    foreach ($schoolVacations as $vacation) {
        $start = new DateTime($vacation['start']);
        $end = new DateTime($vacation['end']);
        while ($start <= $end) {
            $disabledDates[] = $start->format('Y-m-d');
            $start->modify('+1 day');
        }
    }

    return $disabledDates;
}

function sanitize_input($data, $maxLength, &$errors, $fieldName) {
     $sanitizedData = htmlspecialchars(strip_tags(stripslashes(trim($data))));
    
    if (strlen($sanitizedData) > $maxLength) {
        $errors[$fieldName] = ucfirst($fieldName) . " mag niet langer zijn dan " . $maxLength . " tekens.";
        return false;
    }
    
    return $sanitizedData;
}


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

function validateInteger($value, $min, $max, $fieldName, &$errors){
    $value = trim($value); 

    if ($value === "" || $value === null) {
        $errors[$fieldName] = "Geen geldig aantal ingevoerd.";
        return false;
    }

    // Forceer naar integer voordat je het valideert
    if (!ctype_digit($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
        $errors[$fieldName] = "Ongeldig aantal.";
        return false;
    }

    // Omzetten naar integer
    $value = (int) $value;

    // Bereik controleren
    if ($value < $min || $value > $max) {
        $errors[$fieldName] = "Ongeldig bereik.";
        return false;
    }

    return $value;
}


function validateFloat($value, $min, $max, $fieldName, &$errors) {
    // Trim de input en check of het leeg is
    $value = trim($value);

    if ($value === "" || $value === null) {
        $errors[$fieldName] = "Geen geldig aantal ingevoerd.";
        return false;
    }

    // Controleer of de waarde numeriek is
    if (!is_numeric($value)) {
        $errors[$fieldName] = "Ongeldig aantal.";
        return false;
    }

    // Zet om naar float
    $floatValue = (float) $value;


    // Bereik controleren
    if ($floatValue < $min || $floatValue > $max) {
        $errors[$fieldName] = "Ongeldig bereik.";
        return false;
    }

    return $floatValue;
}





/**||--------------[verbinding maken met de database]--------------- || */

try {
    $pdo = connectToDataBase();
    if (!$pdo) {
        error_log("Verbinding met db mislukt: controleer makeconnectionToDatabase");
        echo json_encode(['success' => false, 'servererror' => 'Aanvraag is niet verwerkt!']);
        exit();
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e){
    error_log("Databasefout: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'servererror' => 'Er is een fout opgetreden bij het verwerken van de aanvraag!'
    ]);
    exit(); 
}

/**
 * PDO ATTR_ERRMODE bepaalt hoe fouten worden afgehandeld
 * todo:PDO::ERRMODE zorgt ervoor dat SQL fouten als exceptions bezorgt ivp stil falen of waarschuwingen bieden
 */


try {
    
    if (!$pdo->inTransaction()) { 
        try {
            $pdo->beginTransaction();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'servererror' => 'Fout in het verwerken van de aanvraag']);
            exit();
        }
    }
    
    
    /**
     * Transaction stel je instaat om binnen een sessie via commit je wijzigingen defintief door te voeren
     * ? is de commit niet gelukt dan stelt beginTransaction je instaat om te rollbacken 
     */

    // Valideer en ontsmet elk veld
    $errors = [];

    // Voornaam validatie
    if (empty($_POST['contactpersoonvoornaam']) || !preg_match("/^[\p{L}\s.-]*$/u", $_POST['contactpersoonvoornaam'])) {
        $errors['contactpersoonvoornaam'] = "Ongeldige voornaam.";
    } else {
        $voornaam = sanitize_input($_POST['contactpersoonvoornaam'], 50, $errors, 'voornaam');
    }

    // Achternaam validatie
    if (empty($_POST['contactpersoonachternaam']) || !preg_match("/^[\p{L}\s.-]*$/u", $_POST['contactpersoonachternaam'])) {
        $errors['contactpersoonachternaam'] = "Ongeldige achternaam.";
    } else {
        $achternaam = sanitize_input($_POST['contactpersoonachternaam'], 50, $errors, 'achternaam');
    }

   

    // E-mail validatie
    if (empty($_POST['emailadres']) || !filter_var($_POST['emailadres'], FILTER_VALIDATE_EMAIL)) {
        $errors['emailadres'] = "Ongeldig e-mailadres.";
    } else {
        $email = sanitize_input($_POST['emailadres'], 100, $errors, 'email');
    }


    // Telefoonnummer validatie 1
    if (empty($_POST['schoolTelefoonnummer']) || !preg_match("/^(\+31|0)[1-9][0-9]{8}$/", $_POST['schoolTelefoonnummer'])) {
        $errors['schoolTelefoonnummer'] = "Ongeldig school-telefoonnummer.";
    } else {
        $schooltelefoonnummer = sanitize_input($_POST['schoolTelefoonnummer'], 15, $errors, 'schooltelefoonnummer');
    }


     // Telefoonnummer validatie 1
     if (empty($_POST['contactTelefoonnummer']) || !preg_match("/^(\+31|0)[1-9][0-9]{8}$/", $_POST['contactTelefoonnummer'])) {
        $errors['contactTelefoonnummer'] = "Ongeldig contact-telefoonnummer.";
    } else {
        $contacttelefoonnummer = sanitize_input($_POST['contactTelefoonnummer'], 15, $errors,'contactTelefoonnummer' );
    }



    $aantalLeerlingen = validateInteger(($_POST['aantalLeerlingen'] ?? 0), 40, 160, 'aantalLeerlingen', $errors);

    if ($aantalLeerlingen){
        $minAantalBegeleiders =  $minAantalBegeleiders = max(1, ceil($aantalLeerlingen / 16));

        $aantalBegeleiders = validateInteger(($_POST['totaalBegeleiders'] ?? 0), $minAantalBegeleiders ?? 3, 50, 'totaalBegeleiders', $errors); 

    } else {
        $aantalBegeleiders = validateInteger(($_POST['totaalBegeleiders'] ?? 0), 3, 50, 'totaalBegeleiders', $errors);
    }



  
    // Bezoekdatum validatie
    if (empty($_POST['bezoekdatum'])) {
        $errors['bezoekdatum'] = "Bezoekdatum is verplicht.";
        error_log("Bezoekdatum is niet ingevuld.");
    } else {
        $ontvangenDatum = $_POST['bezoekdatum'];
        $ontvangenDatumEngels = convertDutchToEnglishDate($ontvangenDatum);
        $datumObject = DateTime::createFromFormat('d F Y', $ontvangenDatumEngels);

        if ($datumObject) {
            $bezoekdatum = $datumObject->format('Y-m-d');
           
            // Controleer of de datum een disabled date is
            $disabledDates = generateDisabledDates();
            if (in_array($bezoekdatum, $disabledDates)) {
                $errors['bezoekdatum'] = "De gekozen datum is niet beschikbaar.";
                
            }
        } else {
            $errors['bezoekdatum'] = "Ongeldige datum geselecteerd.";
            
        }
    }

 

    // Schoolnaam validatie
    if (empty($_POST['schoolnaam']) || !preg_match("/^[A-Za-z0-9\s.]+$/", $_POST['schoolnaam'])) {
        $errors['schoolnaam'] = "Ongeldige schoolnaam.";
    } else {
        $schoolnaam = sanitize_input($_POST['schoolnaam'], 80, $errors, 'schoolnaam');
    }

    

    // Adres validatie
    if (empty($_POST['adres']) || !preg_match("/^[A-Za-z0-9\s.,'-]+$/", $_POST['adres'])) {
        $errors['adres'] = "Ongeldig adres.";
    } else {
        $adres = sanitize_input($_POST['adres'], 100, $errors, 'adres');
    }

   

    // Postcode validatie
    if (empty($_POST['postcode']) || !preg_match("/^[1-9][0-9]{3}\s?[A-Za-z]{2}$/", $_POST['postcode'])) {
        $errors['postcode'] = "Ongeldige postcode.";
    } else {
        $postcode = sanitize_input($_POST['postcode'], 7, $errors, 'postcode');
    }
    
    // Plaats validatie
    if (empty($_POST['plaats']) || !preg_match("/^[A-Za-z\s'-]+$/", $_POST['plaats'])) {
        $errors['plaats'] = "Ongeldige plaatsnaam.";
    } else {
        $plaats = sanitize_input($_POST['plaats'], 100, $errors, 'plaats');
    }

  


    if (isset($_POST['vragenOpmerkingen']) && !empty($_POST['vragenOpmerkingen'])) {
        if (!preg_match("/^[A-Za-z0-9\s,.:?!]+$/", $_POST['vragenOpmerkingen'])) { //de punt weer terug zetten, is nu zonder om te testen
            $errors['vragenOpmerkingen'] = "Ongeldige invoer van vragen en opmerkingen: vermijd speciale tekens";
        } else {
            $vragenenOpmerkingen = sanitize_input($_POST['vragenOpmerkingen'], 600, $errors, 'vragenOpmerkingen');
        }
    } else {
        // Als het veld leeg is, stel het in op een lege string
        $vragenenOpmerkingen = '';
    }
    


    // Keuze module validatie
    $keuzemodule = $_POST['keuzeModule'] ?? '';
    $schooltype = $_POST['onderwijsNiveau'] ?? '';
    if (!array_key_exists($schooltype, $onderwijsModules) || !in_array($keuzemodule, $onderwijsModules[$schooltype]['keuze'])) {
        $errors['keuzeModule'] = "Ongeldige keuzemodule.";
    } else {
        $keuze_module = sanitize_input($_POST['keuzeModule'], 50, $errors, 'keuzeModule');
        $schooltype = sanitize_input($_POST['onderwijsNiveau'], 50, $errors, 'onderwijsNiveau');
    }



    if($schooltype === "primairOnderwijs") {
        $schooltypeNiveauPO = $_POST['onderwijsSchooltypeNiveau'] ?? '';
        if(!is_array($schooltypeNiveauPO)) {
            $errors['onderwijsSchooltypeNiveau'] = "Ongeldige selectie";
        } else if (count($schooltypeNiveauPO) !== 1) {
            $errors['onderwijsSchooltypeNiveau'] = "Kies 1 niveau";
        } else {
            $gekozenNiveauPO = $schooltypeNiveauPO[0] ?? '';
            if(!array_key_exists($gekozenNiveauPO, $leeftijden[$schooltype]) ?? ''){
                $errors['onderwijsSchooltypeNiveau'] = "Ongeldige selectie";
            } else {
                $gekozenNiveauPO = sanitize_input($gekozenNiveauPO, 10, $errors, 'onderwijsSchooltypeNiveau'); //php is niet block scoped maar functie scoped
            }
        }

        $schooltypeNiveauLeeftijdsGroepenPO = $_POST['onderwijsSchooltypeNiveauLeeftijdsGroepenPO'] ?? '';
        if (!is_array($schooltypeNiveauLeeftijdsGroepenPO)) {
            $errors['onderwijsSchooltypeNiveauLeeftijdsGroepenPO'] = "Ongeldige selectie";
        } else if (count($schooltypeNiveauLeeftijdsGroepenPO) === 0) {
            $errors['onderwijsSchooltypeNiveauLeeftijdsGroepenPO'] = "Kies minstens 1 leeftijdsgroep";
        } else if (count($schooltypeNiveauLeeftijdsGroepenPO) > count($leeftijden[$schooltype][$gekozenNiveauPO] ?? [])) {
            $errors['onderwijsSchooltypeNiveauLeeftijdsGroepenPO'] = "Ongeldige selectie gemaakt";
        } else {
           foreach($schooltypeNiveauLeeftijdsGroepenPO as $index => $leeftijdsgroep){
                if(!in_array($leeftijdsgroep, $leeftijden[$schooltype][$gekozenNiveauPO])??[]) {
                    $errors['onderwijsSchooltypeNiveauLeeftijdsGroepenPO'] = "Ongeldige selectie gemaakt";
                    break;
                }

                $schooltypeNiveauLeeftijdsGroepenPO[$index] = sanitize_input($leeftijdsgroep, 7, $errors, 'onderwijsSchooltypeNiveauLeeftijdsGroepenPO');

                if ($schooltypeNiveauLeeftijdsGroepenPO[$index] === false) {
                    break;
                }
           }
        }
        
    } else if ($schooltype === "voortgezetOnderbouw" || $schooltype === "voortgezetBovenbouw") {
        $schooltypeNiveauVO = $_POST['onderwijsSchooltypeNiveau'] ?? '';
        if(!is_array($schooltypeNiveauVO)) {
            $errors['onderwijsSchooltypeNiveau'] = "Ongeldige selectie";
        } else if (count($schooltypeNiveauVO) === 0) {
            $errors['onderwijsSchooltypeNiveau'] = "Kies tenminste 1 niveau";
        } else if (count($schooltypeNiveauVO) > count($leeftijden[$schooltype])){
            $errors['onderwijsSchooltypeNiveau'] = "Ongeldige selectie";
        } else {
            foreach($schooltypeNiveauVO as $index => $niveau){
                if(!array_key_exists($niveau, $leeftijden[$schooltype])){
                    $errors['onderwijsSchooltypeNiveau'] = "Ongeldige selectie";
                    break;
                }
                $schooltypeNiveauVO[$index] = sanitize_input($niveau, 25, $errors, 'onderwijsSchooltypeNiveau');
                if ($schooltypeNiveauVO[$index] === false) {
                    break;
                }
                
            }
        }

        foreach($schooltypeNiveauVO as $niveau){
            $leeftijdsgroepVO = "onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau;
            $$leeftijdsgroepVO = $_POST["onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau] ?? '';
            if(!is_array($$leeftijdsgroepVO)){
                $errors["onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau] = "Ongeldige selectie";
            } else if (count($$leeftijdsgroepVO) === 0){
                $errors["onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau] = "Kies tenminste 1 leeftijdsgroep";
            } else if (count($$leeftijdsgroepVO) > 3){
                $errors["onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau] = "Ongeldige selectie gemaakt";
            } else {
                foreach($$leeftijdsgroepVO as $index => $gekozenLeeftijdVO) {
                    if (!in_array($gekozenLeeftijdVO, $leeftijden[$schooltype][$niveau] ?? [])){
                        $errors["onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau] = "Ongeldige selectie gemaakt";
                        break;
                    }

                    $$leeftijdsgroepVO[$index] = sanitize_input($gekozenLeeftijdVO, 30, $errors, "onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau);
                    if ($$leeftijdsgroepVO[$index] === false){
                    break;
                    }
                }
            }

        }

    }

    //snack en lunch aantallen controleren

    $remiseBreakAantal = validateInteger(($_POST['remiseBreakAantal'] ?? 0), 0, 200, 'remiseBreakAantal', $errors);
    $kazerneBreakAantal = validateInteger(($_POST['kazerneBreakAantal'] ?? 0), 0, 200, 'kazerneBreakAantal', $errors);
    $fortgrachtBreakAantal = validateInteger(($_POST['fortgrachtBreakAantal'] ?? 0), 0, 200, 'fortgrachtBreakAantal', $errors);
    $waterijsjeAantal = validateInteger(($_POST['waterijsjeAantal'] ?? 0), 0, 200, 'waterijsjeAantal', $errors);
    $pakjeDrinkenAantal = validateInteger(($_POST['pakjeDrinkenAantal'] ?? 0), 0, 200, 'pakjeDrinkenAantal', $errors);
    $_POST['remiseLunchAantal'] == 0 ? $remiseLunchAantal = 0 : $remiseLunchAantal = validateInteger((int) ($_POST['remiseLunchAantal'] ?? 0), 50, 200, 'remiseLunchAantal', $errors);
    $eigenPicknick = validateInteger(($_POST['eigenPicknick'] ?? 0), 0, 1, 'eigenPicknick', $errors);
    $foodPrice = validateFloat(($_POST['foodPrice'] ?? 0), 0, 4000, 'prijs', $errors);
    $bezoekPrice = validateFloat(($_POST['bezoekPrice'] ?? 0), 720, 8000, 'prijs', $errors); 
    $tempTotalPrice = ($bezoekPrice ?? 0) + ($foodPrice ?? 0);
    $totalPrice = ($tempTotalPrice > 0 && $tempTotalPrice < 11000) ? $tempTotalPrice : 0;

    // Zet waarde van het de doorgegeven onderwijs niveaus van PO en VO om in een algemene die naar de mail kan en naar de database
    $onderwijsNiveau = "";
    $onderwijsLeeftijdsGroepenPO = "";
    $onderwijsLeeftijdsGroepenVO = [];


    if ($schooltype === "primairOnderwijs") {
        // Gebruik de string $gekozenNiveauPO
        $onderwijsNiveauMail = $gekozenNiveauPO;

        if (count($schooltypeNiveauLeeftijdsGroepenPO) > 1){
            $onderwijsLeeftijdsGroepenPO = implode(", ", $schooltypeNiveauLeeftijdsGroepenPO);
        } else if (count($schooltypeNiveauLeeftijdsGroepenPO) === 1) 
            {
                $onderwijsLeeftijdsGroepenPO = $schooltypeNiveauLeeftijdsGroepenPO[0];
            }
    } else {
        $onderwijsNiveau = array_map(function ($niveau) {
            switch ($niveau) {
                case "VMBO_BB_KB":
                    return "VMBO Basis Kader";
                case "VMBO_GL_TL":
                    return "VMBO Gemengd Theoretisch";
                case "PraktijkOnderwijs":
                    return "Praktijk Onderwijs";
                default:
                    return $niveau;
            }
        }, $schooltypeNiveauVO);

        $onderwijsNiveauMail = implode(", ", $onderwijsNiveau);
        

        foreach($schooltypeNiveauVO as $niveau){
            $gekozenleeftijdsgroepVO = "onderwijsSchooltypeNiveauLeeftijdsGroepenVO_" . $niveau;
            if (count($$gekozenleeftijdsgroepVO) === 1) {
                $$gekozenleeftijdsgroepVO = $$gekozenleeftijdsgroepVO[0];
            } else if (count($$gekozenleeftijdsgroepVO) > 1)
            {
                $$gekozenleeftijdsgroepVO = implode(", ", $$gekozenleeftijdsgroepVO);
            }
            $onderwijsLeeftijdsGroepenVO[$niveau] = $$gekozenleeftijdsgroepVO;
            
        }



    }


    if (!empty($errors)) {
   
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);

        exit();
    }
   
    // SQL-query om te controleren op bestaande boekingen
    $sql = "
        SELECT 
            COALESCE(SUM(aantal_leerlingen), 0) AS totale_leerlingen, 
            COUNT(*) AS aantal_definitieve_boekingen
        FROM 
            aanvragen
        WHERE 
            status = :status
            AND 
            bezoekdatum = :bezoekdatum
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':bezoekdatum' => $bezoekdatum, ':status' => 'Definitief']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Controleer op boekingen en aantal leerlingen
    if ($result['aantal_definitieve_boekingen'] >= 2) {
        echo json_encode([
            'success' => false,
            'errors' => ['bezoekdatum' => 'Er zijn al 2 definitieve boekingen voor deze datum. Geen extra boekingen toegestaan.']
        ]);
        exit();
    }

    if ($result['aantal_definitieve_boekingen'] == 1 && ($result['totale_leerlingen'] + $aantalLeerlingen) > 160) {
        $maxMogelijk = 160 - $result['totale_leerlingen']; // Bereken het maximaal mogelijke aantal leerlingen
        error_log("Aantal leerlingen is teveel, namelijk: " . ($result['totale_leerlingen'] + $aantalLeerlingen));
        echo json_encode([
            'success' => false,
            'errors' => ['aantalLeerlingen' => "Er is voor {$aantalLeerlingen} leerlingen geboekt: maximaal {$maxMogelijk} mogelijk."]
        ]);
        exit();
    }

    $niveau1 = null;
    $niveau2 = null;
    $niveau3 = null;
    $leeftijdsgroep1SQL = null;
    $leeftijdsgroep2SQL = null;
    $leeftijdsgroep3SQL = null;
    $leeftijdsgroep4SQL = null;
    $leeftijdsgroep5SQL = null;
  

    if($schooltype === "primairOnderwijs"){
        if (!empty($onderwijsNiveau)){
            $niveau1 = $onderwijsNiveau;    
        } else if(!empty($gekozenNiveauPO)) {
            $niveau1 = $onderwijsNiveau;
        }
    }

    if($schooltype === "voortgezetOnderbouw" || $schooltype === "voortgezetBovenbouw") {
        $niveau1=  $onderwijsNiveau[0] ?? null;
        $niveau2 = $onderwijsNiveau[1] ?? null;
        $niveau3 = $onderwijsNiveau[2] ?? null;
    }

    if ($schooltype === "primairOnderwijs") {
        $indexSQL = 1; // Start de index bij 1
        foreach ($schooltypeNiveauLeeftijdsGroepenPO as $leeftijdsgroepSQL) {
            $leeftijdsgroepPO = "leeftijdsgroep" . $indexSQL . "SQL";
            $$leeftijdsgroepPO = $leeftijdsgroepSQL ?? null;
            $indexSQL++; // Verhoog de index handmatig
        }
    } else if ($schooltype === "voortgezetOnderbouw" || $schooltype === "voortgezetBovenbouw") {
        $indexSQLVO = 1; // Start de index bij 1
        foreach($onderwijsLeeftijdsGroepenVO as $niveau => $leeftijdsgroepSQLVO) {
            $leeftijdsgroepSQLVoort = "leeftijdsgroep" . $indexSQLVO . "SQL";
            $$leeftijdsgroepSQLVoort = $leeftijdsgroepSQLVO ?? null;
            $indexSQLVO++; // Verhoog de index handmatig
        }
        
    }


    
    

    // Voeg de aanvraag toe aan de database
    $sql = "INSERT INTO aanvragen (
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
        :voornaam, 
        :achternaam, 
        :email, 
        :schooltelefoonnummer, 
        :contacttelefoonnummer, 
        :aantal_leerlingen, 
        :bezoekdatum, 
        :schoolnaam, 
        :adres, 
        :postcode, 
        :plaats, 
        :keuze_module, 
        :remiseBreakAantal, 
        :kazerneBreakAantal, 
        :fortgrachtBreakAantal, 
        :waterijsjeAantal, 
        :pakjeDrinkenAantal, 
        :remiseLunchAantal, 
        :eigenPicknick, 
        :niveau1,
        :niveau2,
        :niveau3,
        :leeftijdsgroep1,
        :leeftijdsgroep2,
        :leeftijdsgroep3,
        :leeftijdsgroep4,
        :leeftijdsgroep5,
        'In optie'
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
    ':voornaam' => $voornaam,
    ':achternaam' => $achternaam,
    ':email' => $email,
    ':schooltelefoonnummer' => $schooltelefoonnummer,
    ':contacttelefoonnummer' => $contacttelefoonnummer,
    ':aantal_leerlingen' => $aantalLeerlingen,
    ':bezoekdatum' => $bezoekdatum,
    ':schoolnaam' => $schoolnaam,
    ':adres' => $adres,
    ':postcode' => $postcode,
    ':plaats' => $plaats,
    ':keuze_module' => $keuze_module,
    ':remiseBreakAantal' => $remiseBreakAantal,
    ':kazerneBreakAantal' => $kazerneBreakAantal,
    ':fortgrachtBreakAantal' => $fortgrachtBreakAantal,
    ':waterijsjeAantal' => $waterijsjeAantal,
    ':pakjeDrinkenAantal' => $pakjeDrinkenAantal,
    ':remiseLunchAantal' => $remiseLunchAantal,
    ':eigenPicknick' => $eigenPicknick,
    ':niveau1' => !empty($niveau1) ? $niveau1 : null,
    ':niveau2' => !empty($niveau2) ? $niveau2 : null,
    ':niveau3' => !empty($niveau3) ? $niveau3 : null,
    ':leeftijdsgroep1'=> !empty($leeftijdsgroep1SQL) ? $leeftijdsgroep1SQL : null, //lege stringen worden ook null
    ':leeftijdsgroep2'=> !empty($leeftijdsgroep2SQL) ? $leeftijdsgroep2SQL : null,
    ':leeftijdsgroep3'=> !empty($leeftijdsgroep3SQL) ? $leeftijdsgroep3SQL : null,
    ':leeftijdsgroep4'=> !empty($leeftijdsgroep4SQL) ? $leeftijdsgroep4SQL : null,
    ':leeftijdsgroep5'=> !empty($leeftijdsgroep5SQL) ? $leeftijdsgroep5SQL : null
    ]);

    $lastInsertedId = $pdo->lastInsertId();

    // Commit de transactie
    try {
        $mail = new PHPMailer(true);
        // Roep get_rooster.php aan om het rooster op te halen
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost/GeoFortForm/Get_Rooster.php"); // Zorg ervoor dat je de correcte URL gebruikt
        curl_setopt($ch, CURLOPT_POST, 1);
        
        // Verzenden van de POST data
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'schooltype' => $schooltype,
            'lesmodule' => $keuzemodule,
            'aantalleerlingen' => $aantalLeerlingen
        ]));
        
        // Ontvang het antwoord als string in plaats van het direct af te drukken
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Uitvoeren van de cURL-oproep
        $response = curl_exec($ch);
        
        // Foutcontrole bij cURL
        if ($response === false) {
            die('Fout bij cURL: ' . curl_error($ch)); // Geeft de cURL-fout terug
        }
        
        
        // Sluit de cURL-verbinding
        curl_close($ch);
        
        // JSON-decoding van de respons
        $roosterData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            die('Ongeldige JSON-respons: ' . json_last_error_msg());
        }
        
        // Verwerking van het rooster
        if ($roosterData['success']) {
            $roosterAfbeelding = $roosterData['pdf'];  // Het pad naar de PDF-afbeelding
            
            // Controleer of het bestand daadwerkelijk bestaat voordat je het toevoegt
            if (file_exists($roosterAfbeelding)) {
                $mail->addAttachment($roosterAfbeelding, 'GeoFort_Onderwijs_Conceptrooster.pdf');
            } else {
                error_log('Roosterbestand niet gevonden: ' . $roosterAfbeelding);
            }
        } else {
            error_log('Fout bij ophalen rooster: ' . $roosterData['message']);
        }
        
        //hier zit je ook nog in het try catch blok van de aanvraag er in zetten zelf ook 

        
        // Configuratie voor het gebruik van SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com'; // verander dit straks weer naar .com
        $mail->SMTPAuth = true;
        $mail->Username = 'kevin@geofort.nl';
        $mail->Password = $smtp_password;  // Zorg ervoor dat wachtwoorden veilig worden opgeslagen en niet hardcoded
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Toon SMTP-fouten en communicatie in de log

        $schooltypeMail = $schooltypeMapping[$schooltype];
    

        setlocale(LC_TIME, 'nl_NL.UTF-8');

        // Maak een DateTime-object aan
        $dateTime = new DateTime($bezoekdatum);

        // Formatter voor Nederlandse datuminstellingen
        $fmt = new IntlDateFormatter('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Europe/Amsterdam', IntlDateFormatter::GREGORIAN, 'EEEE d MMMM Y');

        // Datum omzetten naar het gewenste formaat
        $nederlandseDatum = $fmt->format($dateTime);
    
        // Afzender en ontvangers
        $mail->setFrom('kevin@geofort.nl', 'GeoFort Onderwijs');
        $mail->addAddress($email, $voornaam . ' ' . $achternaam);  // Ontvanger
        //$mail->addBCC('onderwijs@geofort.nl');  // Blind Carbon Copy
        $mail->addBCC('kevin@geofort.nl');  // Zelf een BCC ontvangen


    
        // Zet het formaat van de e-mail op HTML
        $mail->isHTML(true);
        $mail->Subject = 'Bevestiging aanvraag schoolbezoek GeoFort';
    
        // Inhoud van de e-mail (HTML)
        $mailContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                :root {
                    --BG-DARK-BLUE: #081540;
                    --THEME-RED: #D93134;
                    --BG-FIELDSET-GRAY: #f2f2f2;
                    --GOLDEN-HIGHLIGHT: #fec027;
                    --BORDER-DASHBOARD-LEGEND: #28613f;
                    --BG-DARK-GRAY-BLUE: #d1dce5;
                    --THEME-RED:  #D93134;
                }
                body {
                    font-family: Arial, sans-serif;
                    background-color: #081540;
                    color: #333;
                    line-height: 1.6;
                    margin: 0;
                    padding: 20px;
                }
                .email-container {
                    max-width: 700px;
                    margin: 0 auto;
                    background-color: #fff;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0px 2px 6px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background-color: #081540;
                    padding: 15px;
                    text-align: center;
                    color: #FFF;
                }
                .header h1 {
                    margin: 0;
                    font-size: 22px;
                }
                .content {
                    padding: 20px;
                }
                .content p {
                    margin: 10px 0;
                }
                .content h4 {
                    margin: 20px 0 10px;
                    color: #D93134;
                    font-size: 16px;
                    text-decoration: underline;
                }
                ul {
                    list-style-type: none;
                    padding: 0;
                }
                ul li {
                    margin: 8px 0;
                    font-size: 14px;
                }
                ul li strong {
                    color: #081540;
                }
                .footer {
                    background-color: #081540;
                    color: #fff;
                    text-align: center;
                    padding: 10px;
                    font-size: 12px;
                    border-top: 1px solid var(--THEME-RED);
                }
                .highlight {
                    color: #D93134;
                    font-weight: 900;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>GeoFort Onderwijs Team</h1>
                </div>
                <div class='content'>
                    <p>Beste " . htmlspecialchars($voornaam) . ",</p>
                    <p>Bedankt voor uw aanvraag voor een schoolbezoek aan het GeoFort. In de bijlage treft u het conceptrooster aan: het definitieve rooster kan hier nog van afwijken.</p>
                    <p>Hieronder vindt u een overzicht van uw aanvraag:</p>
        
                    <h4>Algemene gegevens</h4>
                    <ul>
                        <li><strong>Voornaam:</strong> " . htmlspecialchars($voornaam) . "</li>
                        <li><strong>Achternaam:</strong> " . htmlspecialchars($achternaam) . "</li>
                        <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
                        <li><strong>Schooltelefoon:</strong> " . htmlspecialchars($schooltelefoonnummer) . "</li>
                        <li><strong>Telefoon contactpersoon:</strong> " . htmlspecialchars($contacttelefoonnummer) . "</li>
                        <li><strong>Schoolnaam:</strong> " . htmlspecialchars($schoolnaam) . "</li>
                        <li><strong>Adres:</strong> " . htmlspecialchars($adres) . "</li>
                        <li><strong>Postcode:</strong> " . htmlspecialchars($postcode) . "</li>
                        <li><strong>Plaats:</strong> " . htmlspecialchars($plaats) . "</li>
                    </ul>
        
                    <h4>Bezoekgegevens</h4>
                    <ul>
                        <li><strong>Aantal leerlingen:</strong> " . htmlspecialchars($aantalLeerlingen) . "</li>
                        <li><strong>Aantal begeleiders:</strong> " . htmlspecialchars($aantalBegeleiders) . "</li>
                        <li><strong>Bezoekdatum:</strong> " . htmlspecialchars($nederlandseDatum) . "</li>
                        <li><strong>Keuze-module:</strong> " . htmlspecialchars($keuze_module) . "</li>
                        <li><strong>Schooltype:</strong> " . htmlspecialchars($schooltypeMail) . "</li>";

        if (strpos($onderwijsNiveauMail, ',') !== false){
            $mailContent .= "<li><strong>Onderwijsniveau's: </strong>" . htmlspecialchars($onderwijsNiveauMail) . "</li>";
        } else {
            $mailContent .= "<li><strong>Onderwijsniveau:</strong> " . htmlspecialchars($onderwijsNiveauMail) . "</li>"; 
        }

        if ($schooltypeMail === "Primair Onderwijs") {
            if (count($schooltypeNiveauLeeftijdsGroepenPO) === 1){
                $mailContent .= "<li><strong>Leeftijdsgroep:</strong> " . htmlspecialchars($onderwijsLeeftijdsGroepenPO) . "</li>";
            } else {
                $mailContent .= "<li><strong>Leeftijdsgroepen: </strong> " . htmlspecialchars($onderwijsLeeftijdsGroepenPO) . "</li>";
            }
        } else {
            foreach ($onderwijsLeeftijdsGroepenVO as $niveau => $groepen) {
                switch ($niveau) {
                    case "VMBO_BB_KB":
                        $niveau = "VMBO Basis Kader";
                        break;
                    case "VMBO_GL_TL":
                        $niveau = "VMBO Gemengd Theoretisch";
                        break;
                    case "PraktijkOnderwijs":
                        $niveau = "Praktijk Onderwijs";
                        break;
                    default:
                        break;
                }
                if (strpos($groepen, ',') !== false){
                    $mailContent .= "<li><strong>Leeftijdsgroepen (" . htmlspecialchars($niveau) . "): </strong>" . htmlspecialchars($groepen) . "</li>";
                } else {
            
                    $mailContent .= "<li><strong>Leeftijdsgroep (" . htmlspecialchars($niveau) . "): </strong>" . htmlspecialchars($groepen) . "</li>";    
                }
            }
        }

        if (!empty($vragenenOpmerkingen)) {
            $mailContent .= "<li><strong>Vragen en Opmerkingen:</strong> " . htmlspecialchars($vragenenOpmerkingen) . "</li>";
        }
            $mailContent .= "</ul>";
        
        // Verwerking van eten en drinken
        $etenDrinken = [];
        $totaalEtenDrinken = 0;
        
        if ($remiseBreakAantal > 0) {
            $remiseBreakPrijs = $remiseBreakAantal * $prijzen['remiseBreak'];
            $totaalEtenDrinken += $remiseBreakPrijs;
            $etenDrinken[] = "<strong>Aantal Remise Break snacks: </strong>" . htmlspecialchars($remiseBreakAantal) . " (" . number_format($remiseBreakPrijs, 2, ',', '.') . " euro)";
        }
        
        if ($kazerneBreakAantal > 0) {
            $kazerneBreakPrijs = $kazerneBreakAantal * $prijzen['kazerneBreak'];
            $totaalEtenDrinken += $kazerneBreakPrijs;
            $etenDrinken[] = "<strong>Aantal Kazerne Break snacks: </strong>" . htmlspecialchars($kazerneBreakAantal) . " (" . number_format($kazerneBreakPrijs, 2, ',', '.') . " euro)";
        }
        
        if ($fortgrachtBreakAantal > 0) {
            $fortgrachtBreakPrijs = $fortgrachtBreakAantal * $prijzen['fortgrachtBreak'];
            $totaalEtenDrinken += $fortgrachtBreakPrijs;
            $etenDrinken[] = "<strong>Aantal Fortgracht Break snacks: </strong>" . htmlspecialchars($fortgrachtBreakAantal) . " (" . number_format($fortgrachtBreakPrijs, 2, ',', '.') . " euro)";
        }
        
        if ($waterijsjeAantal > 0) {
            $waterijsjePrijs = $waterijsjeAantal * $prijzen['waterijsje'];
            $totaalEtenDrinken += $waterijsjePrijs;
            $etenDrinken[] = "<strong>Aantal Waterijsjes: </strong>" . htmlspecialchars($waterijsjeAantal) . " (" . number_format($waterijsjePrijs, 2, ',', '.') . " euro)";
        }
        
        if ($pakjeDrinkenAantal > 0) {
            $pakjeDrinkenPrijs = $pakjeDrinkenAantal * $prijzen['pakjeDrinken'];
            $totaalEtenDrinken += $pakjeDrinkenPrijs;
            $etenDrinken[] = "<strong>Aantal Pakjes Drinken: </strong>" . htmlspecialchars($pakjeDrinkenAantal) . " (" . number_format($pakjeDrinkenPrijs, 2, ',', '.') . " euro)";
        }
        
        if ($remiseLunchAantal > 0) {
            $remiseLunchPrijs = $remiseLunchAantal * $prijzen['remiseLunch'];
            $totaalEtenDrinken += $remiseLunchPrijs;
            $etenDrinken[] = "<strong>Aantal Remise Lunches: </strong>" . htmlspecialchars($remiseLunchAantal) . " (" . number_format($remiseLunchPrijs, 2, ',', '.') . " euro)";
        }
        
        if ($eigenPicknick == 1) {
            $etenDrinken[] = "<strong>Eigen Picknick: </strong> Ja";
        } elseif ($eigenPicknick == 0) {
            $etenDrinken[] = "<strong>Eigen Picknick: </strong> Nee";
        }
        
        if (!empty($etenDrinken)) {
            $mailContent .= "
                    <h4>Eten en drinken</h4>
                    <ul>";
            foreach ($etenDrinken as $item) {
                $mailContent .= "
                        <li>" . $item . "</li>";
            }
            $mailContent .= "
                    </ul>";
        }
        
        $mailContent .= "
                    <h4>Prijsoverzicht</h4>
                    <ul>
                        <li><strong>Prijs voor het bezoek:</strong> " . number_format($bezoekPrice, 2, ',', '.') . " euro</li>";
        if ($foodPrice !== null) {
            $mailContent .= "
                        <li><strong>Prijs voor het bestelde eten en drinken:</strong> " . number_format($foodPrice, 2, ',', '.') . " euro</li>";
        }
        $mailContent .= "
                        <li><strong>Totale prijs:</strong> <span class='highlight'>" . number_format($totalPrice, 2, ',', '.') . " euro</span></li>
                    </ul>
        
                    <p>Mocht er iets niet kloppen in uw aanvraag of heeft u nog vragen, aarzel dan niet om contact met ons op te nemen. Wij helpen u graag verder.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " GeoFort. Alle rechten voorbehouden.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        

    
        // Stel de e-mail body in
        $mail->Body = $mailContent;
    
        // Voor niet-HTML clients stel je een alternatieve tekstversie in
        $mail->AltBody = strip_tags($mailContent);
    
        // Verstuur de e-mail
        if ($mail->send()){
            $pdo->commit(); //pas als de mail verzonden is de aanvraag commiten
            echo json_encode(['success' => true, 'message' => 'Aanvraag succesvol ontvangen!']);
            exit();

        } else {
            error_log("E-mailfout: " . $mail->ErrorInfo);
            //verzorg de logica van het terugzetten in het catchblok
            throw new Exception("De mail kan niet verzonden worden: " . $mail->ErrorInfo);   
        }
       
    } catch (Exception $e) {
        // Fout bij het versturen van de e-mail
       
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Emailfout: " . $e->getMessage());
        echo json_encode(['success' => false, 'servererror' => 'De mail kan niet worden verzonden: aanvraag is niet vewerkt!']);
        exit();
    
    }

} catch (PDOException $e) {
    // Foutafhandeling bij databaseproblemen
    error_log("Databasefout: " . $e->getMessage());


    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    

    echo json_encode([
        'success' => false,
        'servererror' => 'Er is een fout opgetreden bij het verwerken van de aanvraag!'
    ]);
    exit();


}    
?>
