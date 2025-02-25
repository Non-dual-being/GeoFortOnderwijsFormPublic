<?php

//modules
require_once('module_makeConnectionToDataBase.php');

/**||--[globalen]---*/
$pdo;
$connectedToDB = true;
$noConnectionMessageHTML = '';
$calenderData = [];
$MessageType = '';



//**formstodownload */
$forms = [
    //po
    ["label"    => "Primair Onderwijs", 
     "image"    => "GeoFort-Onderwijs-formulier-Primair-onderwijs_2025_jan_v2.pdf.png", 
     "file"     => "GeoFort-Onderwijs-formulier-Primair-onderwijs_2025_jan_v2.pdf",
     "message"  => "PO-GFFormToDownload"],
    //vo_onder
    ["label"   => "VO Onderbouw", 
     "image"   => "GeoFort-Onderwijs-formulier-VO-Onderbouw_2025_jan_v2.pdf.png", 
     "file"    => "GeoFort-Onderwijs-formulier-VO-Onderbouw_2025_jan_v2.pdf",
     "message" => "VO_onder-GFFormToDownload"],
     //vo_boven
    ["label"   => "VO Bovenbouw", 
     "image"   => "GeoFort-Onderwijs-formulier-VO-Bovenbouw_2025_jan_v2.pdf.png", 
     "file"    => "GeoFort-Onderwijs-formulier-VO-Bovenbouw_2025_jan_v2.pdf",
     "message" => "VO_boven-GFFormToDownload"]
];

$Messages = [
    "PO-GFFormToDownload" => '',
    "VO_onder-GFFormToDownload" =>  '',
    "VO_boven-GFFormToDownload" => ''
];

/**---------initiatie voorwaardelijke html variabelen-------*/
$bodyClass = "fullBody";
$footerClasstoBottom= "main-footer toBottom";
$footerGeneral="main-footer";

function SetAndResetMessageToDownload($target, $message, $forms, &$Messages){
    if (empty($target) || empty($message) || !is_string($target) || !is_string($message)){
        return;
    }



   $filteredForms = array_filter($forms, function($forms) use ($target){
        return $forms['file'] === $target;
    });

    $filteredForm = reset($filteredForms); // Pak het eerste element




  



     /*
        * 1️⃣ array_filter() doorloopt de array `$forms` en controleert elk element met een callbackfunctie.
        *
        * 2️⃣ De callbackfunctie ontvangt één element van `$forms` tegelijk (in `$form`) en vergelijkt `$form['file']` met `$fileName`.
        *
        * 3️⃣ De `use ($fileName)` zorgt ervoor dat `$fileName` beschikbaar is binnen de anonieme functie.
        *
        * 4️⃣ Als `$form['file'] === $fileName`, wordt dit element in `$result` opgenomen.
        *
        * 5️⃣ `$result` bevat nu een array met alle overeenkomende elementen (kan leeg zijn als er geen match is).

        * [6] Array filter werkt dus op basis van true false en geeft array terug
        
 */


    if (empty($filteredForm) || !array_key_exists('message', $filteredForm)){
        return;
    }

    $MessageAsIndex = $filteredForm['message'];

    $Messages[$MessageAsIndex] = $message;



}

function buildHTML(){
    global $forms, $Messages;
    $noConnectionMessageHTML = '';
    $noConnectionMessageHTML .= '<section class="foutMeldingFormulierAlgemeen">';
    $noConnectionMessageHTML .= '<h2 class="foutMeldingFormulierAlgemeen-H2">Technisch onderhoud</h2>';
    $noConnectionMessageHTML .= '<p class="foutMeldingFormulierAlgemeen-Para">';
    $noConnectionMessageHTML .= 'Het online boekingssysteem is tijdelijk niet beschikbaar. U kunt een schoolbezoek boeken door een aanvraagformulier te downloaden en per e-mail in te dienen bij: ';
    $noConnectionMessageHTML .= '<a href="mailto:onderwijs@geofort.nl" class="foutMeldingFormulierAlgemeen-ParaLink">onderwijs@geofort.nl</a>';
    $noConnectionMessageHTML .= '</p>';
    $noConnectionMessageHTML .= '<div class="download-grid">';
    
    foreach ($forms as $form) {
        $noConnectionMessageHTML .= '<div id="'. htmlspecialchars($form['message']).'-item" class="download-item">';
        $noConnectionMessageHTML .= '<span class="download-label">' . $form["label"] . '</span>';
        $noConnectionMessageHTML .= '<img src="onderwijsFormsToDownload/images/' . $form["image"] . '" alt="' . $form["label"] . '" class="download-image">';
        $noConnectionMessageHTML .= '<form class="download-Form"action="" method="post">';
        $noConnectionMessageHTML .= '<input type="hidden" name="file" value="' . $form["file"] . '">';
        $noConnectionMessageHTML .= '<div id="' . htmlspecialchars($form['message']) . '" class="foute-invoermelding" style="opacity: 0; display: none;">' . htmlspecialchars($Messages[$form['message']] ?? '') . '</div>';
        $noConnectionMessageHTML .= '<button type="submit" class="download-button" id="'. htmlspecialchars($form['message']).'-download">Download</button>';
        $noConnectionMessageHTML .= '</form>';
        $noConnectionMessageHTML .= '</div>';
    }
    
    $noConnectionMessageHTML .= '</div>';
    $noConnectionMessageHTML .= '</section>';
    return $noConnectionMessageHTML;

}


//verbinding maken met database
try {
    $pdo = connectToDataBase();
} catch (PDOException $e){
$noConnectionMessageHTML .= '<section class="foutMeldingFormulierAlgemeen">';
$noConnectionMessageHTML .= '<h2 class="foutMeldingFormulierAlgemeen-H2">Technisch onderhoud</h2>';
$noConnectionMessageHTML .= '<p class="foutMeldingFormulierAlgemeen-Para">';
$noConnectionMessageHTML .= 'Het online boekingssysteem is tijdelijk niet beschikbaar. U kunt een schooldag boeken door een aanvraagformulier te downloaden en per e-mail in te dienen bij: ';
$noConnectionMessageHTML .= '<a href="mailto:onderwijs@geofort.nl" class="foutMeldingFormulierAlgemeen-ParaLink">onderwijs@geofort.nl</a>';
$noConnectionMessageHTML .= '</p>';
$noConnectionMessageHTML .= '<div class="download-grid">';

foreach ($forms as $form) {
    $noConnectionMessageHTML .= '<div id="'. htmlspecialchars($form['message']).'-item" class="download-item">';
    $noConnectionMessageHTML .= '<span class="download-label">' . $form["label"] . '</span>';
    $noConnectionMessageHTML .= '<img src="onderwijsFormsToDownload/images/' . $form["image"] . '" alt="' . $form["label"] . '" class="download-image">';
    $noConnectionMessageHTML .= '<form class="download-Form"action="" method="post">';
    $noConnectionMessageHTML .= '<input type="hidden" name="file" value="' . $form["file"] . '">';
    $noConnectionMessageHTML .= '<div id="' . htmlspecialchars($form['message']) . '" class="foute-invoermelding" style="opacity: 0; display: none;">' . htmlspecialchars($Messages[$form['message']] ?? '') . '</div>';
    $noConnectionMessageHTML .= '<button type="submit" id="'. htmlspecialchars($form['message']).'-download" class="download-button">Download</button>';
    $noConnectionMessageHTML .= '</form>';
    $noConnectionMessageHTML .= '</div>';
}

$noConnectionMessageHTML .= '</div>';
$noConnectionMessageHTML .= '</section>';
$connectedToDB = false;


}

if ($connectedToDB){

    /**||--------------------[Op voorwaarde succesvolle db-verbindingne de logica uitschrijven om kalenderdata en roosters aan pagina te geven]------------------------------------------- || */

    /**||---------------------------[Functions]---------------|| */
    //kalender vullen
    function getDataForBookingCalender($pdo){
        $data = [];
        $sql= "
            SELECT
                bezoekdatum,
                SUM(aantal_leerlingen) AS totale_leerlingen,
                COUNT(id) AS aantal_definitieve_boekingen
            FROM 
                aanvragen
            WHERE
                status = 'Definitief'
                AND
                bezoekdatum BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 12 MONTH)
            GROUP BY
                bezoekdatum
            ORDER BY
                bezoekdatum ASC    
        ";   
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($data) && is_array($data)){
            foreach ($data as &$row){
                $aantal_leerlingen = (int) $row['totale_leerlingen'];
                $aantal_definitieve_boekingen = (int) $row['aantal_definitieve_boekingen'];

                if ($aantal_leerlingen > 159 || $aantal_definitieve_boekingen > 1) {
                    $row['status'] = 'volgeboekt';
                } elseif ($aantal_definitieve_boekingen === 1 && $aantal_leerlingen > 120) {
                    $row['status'] = 'volgeboekt';
                } elseif ($aantal_definitieve_boekingen === 1) {
                    $row['status'] = 'beperkt beschikbaar';
                } elseif ($aantal_definitieve_boekingen === 0) {
                    $row['status'] = 'beschikbaar';
                }
            }
            unset($row); // Unset de referentie om mogelijke bugs te voorkomen
            return $data;
        } else {
            return [];
        }  
    }

    /**---------[functies gebruiken]-------------------------- */

    $calenderData = getDataForBookingCalender($pdo);
} else {
     /**||-----------------[handle the post request to downlaond the pdf forms] ---------------|| */
 
     if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["file"])) {
        $file = basename($_POST["file"]);
        $file_isValid = in_array($file, array_column($forms, 'file'));

        /**array column zet alle file waarden in 1 array */

        if ($file_isValid){
            $filePath = __DIR__ . "/onderwijsFormsToDownload/" . $file;
            $extensionisValid = true;


            if (pathinfo($file, PATHINFO_EXTENSION) !== "pdf") {
                SetAndResetMessageToDownload($file, 'Download niet beschikbaar', $forms, $Messages);
                $extensionisValid = false;  
                $noConnectionMessageHTML = buildHTML();

            }

            if ($extensionisValid){
                if (file_exists($filePath)) {
                    setcookie('download_started', '1', time() + 60, "/");

                    /**
                     * 'download started is de naam'
                     * '1' wil is de waarde en geeft aan dat de download is gestart
                     * time() + 60 wil zeggen dat de cookie na 60 seconden verwijderd wordt
                     * "/" de cookie is geldig voor de hele website 
                     */


                    header("Content-Type: application/pdf");
                    header("Content-Disposition: attachment; filename=" . $file);
                    header("Content-Length: " . filesize($filePath));
                    readfile($filePath);
                    exit;
                }
            }
            } else {
                SetAndResetMessageToDownload($file, 'Download niet beschikbaar', $forms, $Messages);
            }
        } 

    }      


?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoFort Onderwijsaanvraag</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <?php if ($connectedToDB) : ?>
        <script defer src="aanvraagscript.js"></script>
    <?php endif; ?> 
    <?php if (!$connectedToDB) : ?>
        <script defer src="aanvraagscriptAlter.js"></script>
    <?php endif; ?> 

    <script> window.calenderData = <?php echo json_encode($calenderData); ?>;</script>
</head>
<body 
    class = <?php if (!$connectedToDB) {echo htmlspecialchars($bodyClass);} ?>
>
<?php if (!$connectedToDB) : ?>
    <h1>ONDERWIJS AANVRAAGFORMULIER</h1>
    <?php if (!empty($noConnectionMessageHTML)){echo $noConnectionMessageHTML;} ?>
<?php endif; ?> 

<?php if ($connectedToDB) : ?>
        <h1>ONDERWIJS AANVRAAGFORMULIER</h1>
        <form id="onderwijsFormulier" method="post" novalidate>
            <fieldset>
                <legend>BASISGEGEVENS</legend>
                <label for="schoolnaam">Naam school</label>
                <input type="text" id="schoolnaam" name="schoolnaam" required>
                <div id="schoolnaamFout" class="foute-invoermelding"></div>
                
                <label for="adres">Adres</label>
                <input type="text" id="adres" name="adres" required>
                <div id="adresFout" class="foute-invoermelding"></div>
                
                <label for="postcode">Postcode</label>
                <input type="text" id="postcode" name="postcode" required>
                <div id="postcodeFout" class="foute-invoermelding"></div>

                <label for="plaats">Plaats</label>
                <input type="text" id="plaats" name="plaats" required>
                <div id="plaatsFout" class="foute-invoermelding"></div>


                <label for="schoolTelefoonnummer">Telefoonnummer school</label>
                <input type="tel" id="schoolTelefoonnummer" name="schoolTelefoonnummer" required>
                <div id="schoolTelefoonnummerFout" class="foute-invoermelding"></div>

                <label for="contactTelefoonnummer">Telefoonnummer contactpersoon</label>
                <input type="tel" id="contactTelefoonnummer" name="contactTelefoonnummer" required>
                <div id="contactTelefoonnummerFout" class="foute-invoermelding"></div>


                <div class="meer-informatie-container">
                <a href="#" class="meerInformatieToggle" data-target="telefoonInfo"><span>Meer informatie over telefoonnummers</span></a>
                    <div id="telefoonInfo" class="meerInformatieContent">
                        <p><strong>Telefoonnummer van de school:</strong> Dit nummer wordt gebruikt voor alle communicatie met de school zelf.</p>
                        <p><strong>Telefoonnummer contactpersoon:</strong> Dit nummer is voor de contactpersoon die tijdens het schoolbezoek bereikbaar is.</p>
                    </div>
                </div>
                
                <label for="contactpersoonvoornaam">Voornaam contactpersoon</label>
                <input type="text" id="contactpersoonvoornaam" name="contactpersoonvoornaam" required>
                <div id="contactpersoonvoornaamFout" class="foute-invoermelding"></div>

                <label for="contactpersoonachternaam">Achternaam contactpersoon</label>
                <input type="text" id="contactpersoonachternaam" name="contactpersoonachternaam" required >
                <div id="contactpersoonachternaamFout" class="foute-invoermelding"></div>
                
                <label for="emailadres">E-mailadres</label>
                <input type="email" id="emailadres" name="emailadres" required>
                <div id="emailadresFout" class="foute-invoermelding"></div>
                
                <label for="bezoekdatum">Datum bezoek</label>
                <input type="date" id="bezoekdatum" name="bezoekdatum" required>
                <div id="bezoekdatumFout" class="foute-invoermelding"></div>
                
                <label for="Aankomsttijd">Standaard aankomsttijd</label>
                <div id ="aankomsttijd" class="overview-section-tijden">
                    <p><strong> 09:45</strong></p>
                </div>


                <label for="vertrektijd"> Standaard vertrektijd</label>
                <div id ="vertrektijd" class="overview-section-tijden">
                    <p><strong>15:00</strong></p>
                </div>

            

                <div class="meer-informatie-container">
                <a href="#" class="meerInformatieToggle" data-target="bezoektijdenInfo"><span>Meer informatie over bezoektijden</span></a>
                <div id="bezoektijdenInfo" class="meerInformatieContent">
                    <p>GeoFort hanteert <strong class="highlighted-text">standaard</strong> bezoektijden, in overleg kan bij uitzondering het bezoek afwijken van de standaardtijden.</p>
                </div>

                
                <label for="hoekentGeoFort">Hoe kent u GeoFort?</label>
                <input type="text" id="hoekentGeoFort" name="hoekentGeoFort">
                <div id="hoekentGeoFortFout" class="foute-invoermelding"></div>
            </fieldset>

            <fieldset class="fieldset-informative">
                <legend class="legend-informative">PRAKTISCHE INFORMATIE</legend>
                <div class="informative-text">
                    <h4>Kosten en Voorwaarden</h4>
                    <p>
                        • Minimaal 40 leerlingen, maximaal 160 leerlingen.<br>
                        • €20,- per leerling, per 8 leerlingen is 1 begeleider gratis. <br>
                        • Deze kosten zijn inclusief:
                    
                    </p>
                    <ul class="secondary-list">
                        <li>BTW</li>
                        <li>De indeling van de dag d.m.v. een rooster</li>
                        <li>Begeleiding bij de meeste lesmodules</li>
                        <li>Een vegetarische snack en plantaardige chocolademelk voor de leerlingen (inbegrepen bij de lesmodule voedselinnovatie)</li>
                        <li>Bij aankomst een kop koffie of thee voor de docenten/ begeleiders</li>
                        <li>Een onvergetelijke dag!</li>
                    </ul>
                    <h4>Tijdschema</h4>
                    <p>
                        • Een bezoek vindt plaats tussen 09:45 en 15:00 uur, met een maximum van 5 ronden. Na een welkomswoord start de eerste lesmodule om 10:15 en eindigt de laatste om 14:45.<br>
                        • Museumjaarkaart is niet geldig op onderwijsarrangementen. Cultuurkaart/ CJP zijn wel geldig. Vul bij de opmerkingen naam, pashouder en CJP-nummer in.<br>
                        • In geval van allergieën, deze graag doorgeven via het invulveld <strong><span class="highlighted-text-red">Vragen en opmerkingen</span></strong>.
                    </p>
                </div>
            </fieldset>

            <fieldset class="lesprogramma">
                <legend>LESPROGRAMMA</legend>
                <label for="onderwijsNiveau">Selecteer het schooltype</label>
                <select id="onderwijsNiveau" name="onderwijsNiveau" required>
                    <option value="primairOnderwijs" selected>Primair Onderwijs</option>
                    <option value="voortgezetOnderbouw">Voortgezet Onderwijs - Onderbouw</option>
                    <option value="voortgezetBovenbouw">Voortgezet Onderwijs - Bovenbouw</option>
                </select>
                <div id="onderwijsNiveauFout" class="foute-invoermelding"></div>
                <div class="meer-informatie-container">
                <a href="#" class="meerInformatieToggle" data-target="onderwijsNiveauInfo"><span>Bekijk de standaard lesmodules van Primair Onderwijs</span></a>
                    <div id="onderwijsNiveauInfo" class="meerInformatieContent">
                        <p><lu id="weergaveStandaardModules"><!--standaardmodules worden hier dynamisch weergegeven--></lu></p>
                    </div>
                </div>

                <div id="niveauDiv" style="display: none;">
                    <label for="onderwijsSchooltypeNiveau"><strong><span class="highlighted-text-info" data-info-tip="Houdt Ctrl ingedrukt om meerdere selecties te maken">Kies</span></strong> 1 tot max 3 niveau's</label>
                    <select id="onderwijsSchooltypeNiveau" class="multipleSelectNiveau" name="onderwijsSchooltypeNiveau[]" required multiple>
                        <!-- Dynamische invoer op basis van schooltype -->
                    </select>
                    <div id="niveauOptionsFout" class="foute-invoermelding"></div>
                </div>


                    <!-- Leeftijdsgroepen selectie (dynamisch) -->
                <div id="leeftijdDivPO" style="display: none;">
                    <label for="onderwijsSchooltypeNiveauLeeftijdsGroepenPO" id= "onderwijsSchooltypeNiveauLeeftijdsGroepenLabelPO"><span class="highlighted-text-info-PO" data-info-tip="Klik links om een groep te (de)selecteren">Selecteer</span> een groep of groepen</label>
                    <select id="onderwijsSchooltypeNiveauLeeftijdsGroepenPO" name="onderwijsSchooltypeNiveauLeeftijdsGroepenPO[]" class="multipleSelectNiveau"  required multiple></select>
                    <div id="onderwijsSchooltypeNiveauLeeftijdsGroepenPOFout" class="foute-invoermelding"></div>
                </div>

                <div id="leeftijdDivVO" style="display: none;">
                    <!-- Dynamisch gegenereerde select-velden voor VO komen hier -->
                    <div id="onderwijsSchooltypeNiveauLeeftijdsGroepenFoutVO" class="foute-invoermelding"></div>
                </div>

                        
                <div id="keuzeModuleSelectie">
                    <label for="keuzeModule">Kies een keuzemodule</label>
                    <select id="keuzeModule" name="keuzeModule" required>
                        <!-- Keuzemodules worden hier dynamisch toegevoegd -->
                    </select>
                    <div id="keuzeModuleFout" class="foute-invoermelding"></div>
                </div>
            
                <label for="aantalLeerlingen">Vul het aantal leerlingen in</label>
                <input type="number" id="aantalLeerlingen" name="aantalLeerlingen" min="40" max="160" step="1" required placeholder="min=40, max=160">
                <div id="aantalLeerlingenFout" class="foutmelding"></div>

                <div class="meer-informatie-container">
                <a href="#" class="meerInformatieToggle" data-target="meerInformatieAantalLeerlingen"><span>Meer informatie over het aantal leerlingen</span></a>
                    <div id="meerInformatieAantalLeerlingen" class="meerInformatieContent">
                        <p> Standaard hanteert GeoFort voor een schoolbezoek een minimaal aantal van 40 leerlingen en een maximum van 160.</p>
                        <p> GeoFort brengt minimaal de prijs voor 40 leerlingen in rekening, het bezoek kan wel met minder leerlingen plaatsvinden.</p>
                        <p> Voor Primair Onderwijs is de prijs <strong class="highlighted-text">18 euro </strong> per leerling, voor Voortgezet Onderwijs wordt <strong class="highlighted-text">22 euro </strong> per leerling in rekening gebruikt.</p>
                        <p> Onderaan het aanvraag formulier bij de sectie <strong class="highlighted-text">vragen en opmerkingen </strong> kunt u aangeven als u met minder leerlingen wil komen </p>
                    </div>
                </div>

                <label for="totaalbegeleiders">Aantal begeleiders</label>
                <input type="number" id="totaalbegeleiders" name="totaalbegeleiders" min="1" max="50" step="1" inputmode="numeric" pattern="[0-9]*" required>
                <div id="aantalBegeleidersFout" class="foute-invoermelding"></div>

                <div class="meer-informatie-container">
                    <a href="#" class="meerInformatieToggle" data-target="begeleidersInfo"><span>Meer informatie over aantal begeleiders</span></a>
                    <div id="begeleidersInfo" class="meerInformatieContent">
                        <p>
                            GeoFort verwacht minimaal <strong>één begeleider per groep van 16 leerlingen</strong>. Voor elke <strong>8 leerlingen</strong> is <strong>één begeleider</strong> gratis inbegrepen. Voor aanvullende begeleiders wordt er <strong>€20</strong> per begeleider in rekening gebracht.
                        </p>
                    </div>
                </div>
            </fieldset>
            

            <fieldset class="fieldset-informative" >
                <legend>OVERZICHT ROOSTER</legend>
                <div class="informative-text-rooster">
                    <div id="groepAantalWeergave">
                        <h4>Aantal groepen</h4>
                        <p id="groepAantal">-</p>
                    </div>
                    <div id="geselecteerdeModulesWeergave">
                        <h4>Standaard lesmodules</h4>
                        <ul id="standaardModulesLijst">
                            <!-- Standard modules will be populated here -->
                        </ul>
                        <h4>Keuze lesmodule</h4>
                        <ul id="gekozenKeuzeModule">
                            <li id="gekozenKeuzeModule2"></li>
                        </ul>
                    </div>
                    <div id="roosterWeergave">
                        <h4>Voorbeeldrooster</h4>
                        <div id="roosterAfbeeldingContainer">
                            <img id="roosterAfbeelding" src="" alt="Voorbeeldrooster">
                            <button id="downloadRooster" style="display:none;">Download rooster</button>
                        </div>
                    </div>
                </div>
            </fieldset>
            
            <fieldset class="fieldset-informative">
                <legend >ETEN EN DRINKEN</legend>
                <div class="informative-text">
                    <h4>STANDAARD INBEGREPEN</h4>
                    <ul>
                        <li>Koffie en thee voor begeleiders bij aankomst.</li>
                        <li>Vegetarische snack en plantaardige chocolademelk voor leerlingen tijdens de lesmodule voedselinnovatie.</li>
                    </ul>
                    <h4>OPTIONEEL BIJ TE BOEKEN</h4>
                    <h5>Snacks</h5>
                    <ul>
                        <li>Remise break, Kazerne break en Fortgracht break.</li>
                        <li>Waterijsje en glaasje limonade.</li>
                    </ul>
                    <h5>Lunch</h5>
                    <ul>
                    <li>Remiselunch: tarwebol met vegetarisch beleg <small>(leerlingen en begeleiders).</small></li>
                    <li>Neem je eigen lunch mee.</li>
                    </ul>
                    <h4>EXTRA INFORMATIE</h4>
                    <ul>
                        <li>Het eten en drinken kunt u alleen vooraf bestellen.</li>
                        <li>Het restaurant is tijdens het schoolbezoek dicht.</li>
                    </ul>
                </div>
            </fieldset>
            
            <fieldset>
                <legend >ETEN EN DRINKEN KEUZEMENU</legend>
                    <h4 class="snacks-heading">SNACK AANBOD</h4>
                    <span class="subtitle">Vink aan en vul een aantal in</span>
                    <div class="snacks-section">
                        <div class="snack-option">
                            <label for="remiseBreakCheckbox">Remise break: ontbijtkoek met limonade</label>
                            <span class="subtitle">€2.60</span>
                            <div class="input-group">
                                <input type="checkbox" id="remiseBreakCheckbox" name="snack" value="2.60">
                                <label for="remiseBreakAantal">Aantal:</label>
                                <input type="number" id="remiseBreakAantal" name="remiseBreakAantal" min="0" value="0" disabled>
                                <div id="remiseBreakAantalFout" class="foute-invoermelding"></div> <!-- Foutdiv toegevoegd -->
                            </div>
                        </div>
                        <div class="snack-option">
                            <label for="kazerneBreakCheckbox">Kazerne break: zakje chips met limonade</label>
                            <span class="subtitle">€2.60</span>
                            <div class="input-group">
                                <input type="checkbox" id="kazerneBreakCheckbox" name="snack" value="2.60">
                                <label for="kazerneBreakAantal">Aantal:</label>
                                <input type="number" id="kazerneBreakAantal" name="kazerneBreakAantal" min="0" value="0" disabled>
                                <div id="kazerneBreakAantalFout" class="foute-invoermelding"></div> <!-- Foutdiv toegevoegd -->
                            </div>
                        </div>
                        <div class="snack-option">
                            <label for="fortgrachtBreakCheckbox">Fortgracht break: fruit met limonade</label>
                            <span class="subtitle">€2.60</span>
                            <div class="input-group">
                                <input type="checkbox" id="fortgrachtBreakCheckbox" name="snack" value="2.60">
                                <label for="fortgrachtBreakAantal">Aantal:</label>
                                <input type="number" id="fortgrachtBreakAantal" name="fortgrachtBreakAantal" min="0" value="0" disabled>
                                <div id="fortgrachtBreakAantalFout" class="foute-invoermelding"></div> <!-- Foutdiv toegevoegd -->
                            </div>
                        </div>
                        <div class="snack-option">
                            <label for="waterijsjeCheckbox">Waterijsje</label>
                            <span class="subtitle">€1.00</span>
                            <div class="input-group">
                                <input type="checkbox" id="waterijsjeCheckbox" name="snack" value="1.00">
                                <label for="waterijsjeAantal">Aantal:</label>
                                <input type="number" id="waterijsjeAantal" name="waterijsjeAantal" min="0" value="0" disabled>
                                <div id="waterijsjeAantalFout" class="foute-invoermeldingg"></div> <!-- Foutdiv toegevoegd -->
                            </div>
                        </div>
                        <div class="snack-option">
                            <label for="pakjeDrinkenCheckbox">Limonade</label>
                            <span class="subtitle">€1.00</span>
                            <div class="input-group">
                                <input type="checkbox" id="pakjeDrinkenCheckbox" name="snack" value="1.00">
                                <label for="pakjeDrinkenAantal">Aantal:</label>
                                <input type="number" id="pakjeDrinkenAantal" name="pakjeDrinkenAantal" min="0" value="0" disabled>
                                <div id="pakjeDrinkenAantalFout" class="foute-invoermelding"></div> <!-- Foutdiv toegevoegd -->
                            </div>
                        </div>
                    </div>

                    <h4 class="lunch-heading">LUNCH AANBOD</h4>
                    <span class="subtitle">Vink aan en vul een aantal in</span>
                        <div class="snack-option">
                            <label for="remiseLunchCheckbox">Remiselunch: tarwebol met vegetarisch beleg</label>
                            <span class="subtitle">€3.60</span>
                            <div class="input-group">
                                <input type="checkbox" id="remiseLunchCheckbox" name="lunch" value="3.60">
                                <label for="remiseLunchAantal">Aantal:</label>
                                <input type="number" id="remiseLunchAantal" name="remiseLunchAantal" min="50" max="200" value="50" disabled>
                                <div id="remiseLunchAantalFout" class="foute-invoermelding"></div> <!-- Foutdiv toegevoegd -->
                            </div>
                        </div>
                    <div class="snack-option">
                        <label for="eigenPicknickCheckbox">Nemen eigen lunch mee</label>
                        <input type="checkbox" id="eigenPicknickCheckbox" name="lunch" value="0">
                    </div>
            </fieldset>

            <fieldset class="fieldset-informative">
                <legend class="legend-informative">PRIJS OVERZICHT</legend>
                <div id ="prijs" class="informative-text">
                <div id="prijsFout" class="foute-invoermelding"></div>
                    <div  class="price-summary-section">
                        <div>
                            <h3>Bezoek</h3>
                            <div id="bezoek" class="summary-category">
                                <!-- Selected visit price displayed here -->
                            </div>
                        </div>
                        <div>
                            <h3>Eten en drinken</h3>
                            <div id="foodSummary" class="summary-category">
                                <!-- Selected food options will be displayed here -->
                            </div>
                        </div>
                        <div class="total-price">
                            <span>Totale prijs: €<span id="totalPrice">0.00</span></span>
                        </div>
                    </div>
                </div>
                
            </fieldset>

            <fieldset>
                <legend>VRAGEN EN OPMERKINGEN</legend>
                <label for="vragenOpmerkingen">Vragen en opmerkingen invulveld</label>
                <textarea id="vragenOpmerkingen" name="vragenOpmerkingen" maxlength="600" rows="5"></textarea>
                <div id="vragenOpmerkingenFout" class="foute-invoermelding"></div>
                <div id="tekenTeller">600 tekens over</div>
                <div id="contact">
                <p>Neem contact op via: <a href="mailto:onderwijs@geofort.nl">onderwijs@geofort.nl</a></p>
                </div>
            </fieldset>
            <button type="submit" id="verzendknop">Verzenden</button>
            <div id="verzendknopMeldingdiv" class="foute-invoermelding"></div> <!-- Foutdiv toegevoegd -->
        </form>
    <?php endif; ?> 
    <footer 
        class="<?php if($connectedToDB) {echo $footerGeneral;} else {echo $footerClasstoBottom;} ?>"
    >
    <p id="copy_logo">&copy; <span id="currentYear"></span> GeoFort</p>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>

    <script>
    document.getElementById('currentYear').textContent = new Date().getFullYear();
    </script>
    <div class="footer-logo-container">
        <img src="images/geofort_logo.png" alt="GeoFort Logo" class="footer-logo">
    </div>
</footer>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/nl.js"></script>
</body>
</html>

