<?php
//module
require_once('module_makeConnectionToDataBase.php');
header('Content-type: application/json');


// Definieer de geldige waarden voor schooltype en keuzemodule
$onderwijsModules = [
    'primairOnderwijs' => [
        'standaard' => ["Klimaat-Experience", "Klimparcours", "Voedsel-Innovatie", "Dynamische-Globe"],
        'keuze' => ["Minecraft-Klimaatspeurtocht", "Earth-Watch","Stop-de-Klimaat-Klok","Minecraft-Programmeren"]
    ],
    'voortgezetOnderbouw' => [
        'standaard' => ["Klimaat-Experience", "Voedsel-Innovatie", "Dynamische-Globe", "Earth-Watch"],
        'keuze' => ["Minecraft-Windenergiespeurtocht", "Stop-de-Klimaat-Klok","Minecraft-Programmeren"]
    ],
    'voortgezetBovenbouw' => [
        'standaard' => ["Klimaat-Experience", "Voedsel-Innovatie", "Dynamische-Globe", "Earth-Watch", "Stop-de-Klimaat-Klok"],
        'keuze' => ["Crisismanagement"]
    ]
];

// Mapping van schooltype voor database
$schooltypeMapping = [
        'primairOnderwijs' => 'primair',
        'voortgezetOnderbouw' => 'onderbouw',
        'voortgezetBovenbouw' => 'bovenbouw'
];



function handleRoosterRequest($pdo, $schooltypeMapping, $onderwijsModules, $schooltype, $keuzemodule, $aantalLeerlingen){
    // Mapping van schooltype naar de vereiste waarde
    if (isset($schooltypeMapping[$schooltype])) {
        $schooltype = $schooltypeMapping[$schooltype];
    }

     // Voorbereid SQL-commando om de afbeelding op te halen
     $sql = 
        "SELECT 
            afbeelding 
        FROM 
            roosters 
        WHERE 
            schooltype = :schooltype 
        AND 
            keuzemodule = :keuzemodule 
        AND 
            leerlingen_min <= :aantalLeerlingen 
        AND 
            leerlingen_max >= :aantalLeerlingen
        ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':schooltype', $schooltype);
    $stmt->bindParam(':keuzemodule', $keuzemodule);
    $stmt->bindParam(':aantalLeerlingen', $aantalLeerlingen, PDO::PARAM_INT); // Bind als integer
    $stmt->execute();
    $rooster = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($rooster) {
       
        // Verwijder 'images/' uit het pad en vervang .png met .pdf
        $afbeeldingZonderImages = str_replace('images/', '', $rooster['afbeelding']);
        $pdfFileName = str_replace('.png', '.pdf', $afbeeldingZonderImages);
        $pdfFilePath = 'Roosters/' . $pdfFileName;  // Het volledige pad naar de PDF
    
        // Stuur zowel de afbeelding als het PDF-bestandspad terug in JSON-formaat
        echo json_encode([
            'success' => true,
            'afbeelding' => $rooster['afbeelding'], // Afbeelding zoals in de database
            'pdf' => $pdfFilePath                   // Correcte PDF-bestandspad
        ]);
        exit;
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Geen rooster gevonden voor de opgegeven criteria.',
            'afbeeldingAlter' => 'images/Geen_Rooster.jpeg'
        ]);
        exit;
    }
}


//verbindingen maken met db
try {
    $pdo = connectToDataBase();
} catch (PDOException $e){
    error_log("Databasefout: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Databasefout'
    ]);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schooltype']) && isset($_POST['lesmodule']) && isset($_POST['aantalleerlingen'])){
    // Verkrijg parameters van het AJAX-verzoek
    $schooltype = $_POST['schooltype'] ?? '';
    $keuzemodule = $_POST['lesmodule'] ?? '';
    $aantalLeerlingen = (int)($_POST['aantalleerlingen'] ?? 0); // Cast naar integer

    if ($aantalLeerlingen < 40 || $aantalLeerlingen > 160){
        echo json_encode([
            'success' => false,
            'message' => 'Aantal leerlingen moet tussen 40 en 160 liggen.'
            
        ]);
        exit;     
    } elseif (!array_key_exists($schooltype, $onderwijsModules)){
        echo json_encode([
            'success' => false,
            'message' => 'Ongeldig schooltype.',
            'afbeeldingAlter' => 'images/Geen_Rooster.jpeg'
        ]);
        exit;
    } else {
        $isValidModule = in_array($keuzemodule, $onderwijsModules[$schooltype]['keuze']);
        if (!$isValidModule) {
            echo json_encode([
                'success' => false,
                'message' => 'Ongeldige keuzemodule.',
                'afbeeldingAlter' => 'images/Geen_Rooster.jpeg'
            ]);
            exit;
        }
    }

} else {
    echo json_encode([
        'success' => false,
        'message' => 'onvolledig gegevens ontvagen',
        'afbeeldingAlter' => 'images/Geen_Rooster.jpeg'
    ]);
    exit;
}

handleRoosterRequest($pdo, $schooltypeMapping, $onderwijsModules, $schooltype, $keuzemodule, $aantalLeerlingen);



/**
 * exit zorgt ervoor dat je na de json response het script meteen stopt 
 * todo: dat is best practise in php
 * */

?>
