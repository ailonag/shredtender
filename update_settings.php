<?php
require_once 'configs/config.php';
session_start();

$host = sqlhost;
$db   = sqldb;
$user = sqlaccount;
$pass = sqlpassword;
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $gear_ids = $_POST['gear_id'];
    $air_pressures = $_POST['air_pressure'];
    $high_speed_compressions = $_POST['compression_high_speed'];
    $low_speed_compressions = $_POST['compression_low_speed'];
    $high_speed_rebounds = $_POST['rebound_high_speed'];
    $low_speed_rebounds = $_POST['rebound_low_speed'];
    $last_service_dates = $_POST['last_service_date'];
    $last_servicers = $_POST['last_servicer'];
    $model = $_POST['model'];

    // Prepare the SQL statement outside the loop
    $sql = "UPDATE suspension_settings SET air_pressure = ?, rebound_high_speed = ?, compression_high_speed = ?, compression_low_speed = ?, rebound_low_speed = ?, last_service_date = ?, last_servicer = ?, model = ? WHERE gear_id = ?";

try {
    $stmt = $pdo->prepare($sql);

    // Loop through each gear item and update the settings
    foreach ($gear_ids as $index => $gear_id) {
        echo "Updating gear_id: $gear_id with model: " . $model_value . "\n";
        $air_pressure = $air_pressures[$index];
        $high_speed_rebound = $high_speed_rebounds[$index]; // Moved this line up
        $high_speed_compression = $high_speed_compressions[$index];
        $low_speed_compression = $low_speed_compressions[$index];
        $low_speed_rebound = $low_speed_rebounds[$index];
        $last_service_date = $last_service_dates[$index];
        $last_servicer = $last_servicers[$index];
        $model_value = $model[$index]; // Renamed the variable to avoid overwriting the array

        $stmt->execute([
            $air_pressure,
            $high_speed_rebound, // Moved this line up
            $high_speed_compression,
            $low_speed_compression,
            $low_speed_rebound,
            $last_service_date,
            $last_servicer,
            $model_value,
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
