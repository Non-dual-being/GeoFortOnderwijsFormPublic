<?php
function connectToDataBase() {
    // Database inloggegevens
    $host = '127.0.0.1';
    $dbname = 'school_db';
    $user = 'root';
    $pass = '';
    $port = '3306';

    try {
        // Maak een nieuwe PDO-verbinding
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname", 
            $user, 
            $pass
        );
        
        // Stel de foutafhandelingsmodus in op Exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $pdo;
    } catch (PDOException $e) {
        // Log de fout voor debugging
        error_log("Databasefout: " . $e->getMessage());
        
        // Gooi de fout opnieuw om de applicatie te stoppen
        throw new PDOException("Kan geen verbinding maken met de database:");
    }
}
?>