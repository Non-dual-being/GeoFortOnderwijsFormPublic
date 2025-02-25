<?php
session_start();

// Genereer een nieuwe CSRF-token
if(!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64-tekens lange token
    }

/**modules */
require_once 'module_makeConnectionToDataBase.php';
require 'vendor/autoload.php';

ini_set('display_errors', 0); 
ini_set('log_errors', 1);      
error_reporting(E_ALL); 


$bodyClass = "fullBody";



try {
    $pdo = connectToDataBase();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   

} catch (PDOException $e){
    error_log("Geen Databaseverbinding");
    $noConnectionMessageHTML = '';
    $noConnectionMessageHTML .= '<section class="foutMeldingFormulierAlgemeen">';
    $noConnectionMessageHTML .= '<h2 class="foutMeldingFormulierAlgemeen-H2">Inloggen niet beschikbaar</h2>';
    $noConnectionMessageHTML .= '<p class="foutMeldingFormulierAlgemeen-Para">';
    $noConnectionMessageHTML .= 'Er is een technisch probleem waardoor inloggen niet mogelijk is: voor meer info neem contact op met: ';
    $noConnectionMessageHTML .= '<p class = "foutMeldingFormulierAlgemeen-ParaInlog"> ';
    $noConnectionMessageHTML .= '<a href="mailto:kevin@geofort.nl" class="foutMeldingFormulierAlgemeen-ParaLink">kevin@geofort.nl</a>';
    $noConnectionMessageHTML .= 'of';
    $noConnectionMessageHTML .= '<a href="mailto:kevin@geofort.nl" class="foutMeldingFormulierAlgemeen-ParaLink">koen@geofort.nl</a>';
    $noConnectionMessageHTML .= '</p>';
    $noConnectionMessageHTML .= '</p>';
    $noConnectionMessageHTML .= '</section>';
    $connectedToDB = false;  
    $pdo = false;

}

if ($pdo){
        // inactiviteits melding ontvangen vanuit een ingelogde sessie op het dashbaord
    if (isset($_GET['InactivityMessage']) && $_GET['InactivityMessage'] === 'sessie_verlopen' ){
        $inActivityMessage = "U bent uitgelogd na inactiviteit op het dashboard";
    }
        
    

    /** || -- gobal vars ---- || */
    $inActivityMessage = '';
    $connectedToDB = true;
    $noConnectionMessageHTML = '';
    $maxAttempts = 3;
    $Flashmessage_inlogSubmit = ''; 
    $FlashmessageType = ''; 
    $errors = []; 

    $resetServerMessages = [
    'inlogSubmit' => function (&$FlashMessages){
        $FlashMessages['message']['inlogSubmit'] = '';
        $FlashMessages['type'] = '';
    }
    ];

    $FlashMessages = [
    'message' => [
        'inlogSubmit' => '',
    ],
    'type' => ''
    ];


    /**functie */

    // Functie om input te ontsmetten
    function sanitize_input($data, $maxLength, &$errors, $fieldName) {
    $sanitizedData = htmlspecialchars(strip_tags(stripslashes(trim($data))));
    if (strlen($sanitizedData) > $maxLength) {
        $errors[$fieldName] = ucfirst($fieldName) . " mag niet langer zijn dan " . $maxLength . " tekens.";
        return false;
    }
    return $sanitizedData;
    }

    // Functie om het IP-adres op te halen
    function get_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        //deze kan meerdere zijn dus de eerste pakken
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
    }


function displayMessage($target, $message, &$resetServerMessages, &$FlashMessages, $type = 'error') {
    if (array_key_exists($target, $FlashMessages['message'])){
        $resetServerMessages[$target]($FlashMessages);
        $FlashMessages['message'][$target] = $message;
        $FlashMessages['type'] = $type;
    } else {
        error_log("Ongeldige target doorgegeven aan displayMessage");
    }
    return [$FlashMessages['message'][$target], $FlashMessages['type']];
}





$logErrors = []; // Array om fouten te verzamelen


// Controleer of er fouten zijn en log deze
if (!empty($logErrors)) {
    foreach ($logErrors as $error) {
        error_log("[LOGIN ERROR] " . $error);
    }}


    if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['loginSubmit']) 
    && !empty($_POST['email']) 
    && !empty($_POST['password']) 
    && !empty($_POST['csrf_token']) 
    && isset($_SESSION['csrf_token']) 
    && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])

    ) {
        unset($_SESSION['csrf_token']); // Token direct verwijderen
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Nieuw token genereren
  
            // Controleer of er al een CSRF-token is, anders genereer er een

        /** email en wachtwoord valideren */
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Ongeldig e-mailadres.";
        } else {
            $email = sanitize_input($_POST['email'], 50, $errors, 'email');
        }

        if (empty($_POST['password'])) {
            $errors['password'] = "Voer een wachtwoord in.";
        } else {
            $password = sanitize_input($_POST['password'], 50, $errors, 'password');
        }

        if (empty($errors)){
            $blocked = false;
            $ip_address = get_ip_address();
            if (!$ip_address){
                list($Flashmessage_inlogSubmit, $FlashmessageType) = displayMessage('inlogSubmit', 'inloggen is geblokkeert', $resetServerMessages, $FlashMessages, 'error');
                
            }
            $stmt = $pdo->prepare("SELECT blocked FROM login_attempts WHERE ip_address = :ip_address");
            $stmt->execute([':ip_address' => $ip_address]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
            if ($result && $result['blocked'] == 1) {
                list($Flashmessage_inlogSubmit, $FlashmessageType) = displayMessage('inlogSubmit', 'inloggen is geblokkeert', $resetServerMessages, $FlashMessages, 'error');
                $blocked = true;
            }

            if (!$blocked) {
                $ToMuchAttempts = false;
                // Controleer op mislukte pogingen binnen 1 uur
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip_address AND attempt_time > (NOW() - INTERVAL 1 HOUR)");
                $stmt->execute([':ip_address' => $ip_address]);
                $attempt_count = $stmt->fetchColumn();

                if ($attempt_count >= $maxAttempts -1) {
                    // Blokkeer het IP-adres
                    $stmt = $pdo->prepare("UPDATE login_attempts SET blocked = 1 WHERE ip_address = :ip_address");
                    $stmt->execute([':ip_address' => $ip_address]);
                    $ToMuchAttempts = true;
                }

                if (!$ToMuchAttempts){
                    // Haal de gebruiker op uit de database
                    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE email = :email LIMIT 1");
                    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                        
                        
                        // Sessie-ID regenereren voor beveiliging
                        session_regenerate_id(true);
                        /**
                         * Genereert een nieuwe sessie id en verwijderd de gegevens van de oude sessie
                         * Op die manier kan de oude sessie niet meer misbruikt worden door anderen
                         * Met argument true verwijder je alle gegevens van de oude sessie
                         */
                        $_SESSION['username'] = "GeoFort Planner";
                        $_SESSION['loggedin'] = true;
                        session_write_close(); // Sla sessie op en laat andere requests doorgaan
                        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip_address");
                        $stmt->execute([':ip_address' => $ip_address]);
                        
                        // **Doorverwijzing naar dashboard.php**
                        header("Location: db_dashBoard.php");
                        exit();
                
                    } else {
                        // Log de mislukte poging
                        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (:ip_address, NOW())");
                        $stmt->execute([':ip_address' => $ip_address]);
                        list($Flashmessage_inlogSubmit, $FlashmessageType) = displayMessage('inlogSubmit', 'ongeldige inlogpoging', $resetServerMessages, $FlashMessages, 'error');
                    }
                } else {
                    list($Flashmessage_inlogSubmit, $FlashmessageType) = displayMessage('inlogSubmit', 'inloggen is geblokkeert', $resetServerMessages, $FlashMessages, 'error');
                }
            }

        } else {
            list($Flashmessage_inlogSubmit, $FlashmessageType) = displayMessage('inlogSubmit', 'verkeerde inloggegevens opgegeven', $resetServerMessages, $FlashMessages, 'error');
        }
        
    } else {
        if((empty($_POST['email']) || empty($_POST['password'])) 
        && ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginSubmit']))){
            list($Flashmessage_inlogSubmit, $FlashmessageType) = displayMessage('inlogSubmit', 'vul zowel het mailadres als het wachtwoord in', $resetServerMessages, $FlashMessages, 'error');
        } 
    }
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GeoFort Inlog Admin Panel</title>
    <link rel="stylesheet" href="styles.css">
    <script defer src="db_inlogPagina.js"></script>
</head>
<body 
    class = <?php if (!$connectedToDB) {echo htmlspecialchars($bodyClass);} ?>
>
<?php if (!$connectedToDB) : ?>
    <h1>INLOG PAGINA DASHBOARD</h1>
    <?php if (!empty($noConnectionMessageHTML)){echo $noConnectionMessageHTML;} ?>
<?php endif; ?> 

<?php if ($connectedToDB) : ?>
<h1 class="inlogh1">INLOG PAGINA DASHBOARD</h1>
<form method="POST" class="login-form" id="login-form">
    <div id="recieve-inactivity-message" class="flash-message succes">
        <?php echo htmlspecialchars($inActivityMessage); ?>
    </div>
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" placeholder="Email" autocomplete="email" required>
    
    <label for="password">Wachtwoord</label>
    <input type="password" autocomplete="current-password" id="password" name="password" placeholder="Wachtwoord" required>
    <!--De autocompletion is voor browsers zodat ze op een correcte manier het wachtwoord kunnen automatisch invulen-->

    <div id="recieve-message-inlogSubmit" class="flash-message <?php echo htmlspecialchars($FlashmessageType); ?>">
        <?php echo htmlspecialchars($Flashmessage_inlogSubmit); ?>
    </div>
    <button type="submit" id="verzendknop" name="loginSubmit"class="submit-button">Inloggen</button>
</form>
<?php endif; ?> 
</body>
</html>
