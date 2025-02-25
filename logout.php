<?php
session_start();
session_unset(); // Verwijder alle sessievariabelen
session_destroy(); // Vernietig de sessie
// Stuur de gebruiker terug naar de inlogpagina
header("Location: db_inlogPagina.php");
exit();
?>


