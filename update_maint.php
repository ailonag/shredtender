<?php
require_once 'configs/config.php';
session_start();

$host = sqlhost;
$db = sqldb;
$user = sqlaccount;
$pass = sqlpassword;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gear_ids = $_POST['gear_id'];
    $last_service_dates = $_POST['last_service_date'];
    //$last_servicers = $_POST['last_servicer'];

    // Prepare the SQL statement outside the loop
    $sql = "UPDATE suspension_settings SET last_service_date = ? WHERE gear_id = ?";

    try {
        $stmt = $pdo->prepare($sql);

        // Loop through each gear item and update the settings
        foreach ($gear_ids as $index => $gear_id) {
            $last_service_date = $last_service_dates[$index];

            echo "Updating gear_id: $gear_id with last_service_date: " . $last_service_date . "\n";

            $stmt->execute([
                $last_service_date,
                $gear_id
            ]);
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }


    // Redirect back to the account page
    header("Location: account.php");
    exit;
}

?>