<?php
require_once 'configs/config.php';
require_once 'functions.php';
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

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    die('Error: User ID not found in the session.');
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// check if post data has multple values in the array



// Get the suspension settings from the POST data
$gear_ids = is_array($_POST['gear_id']) ? $_POST['gear_id'] : array($_POST['gear_id']);
$air_pressures = is_array($_POST['air_pressure']) ? $_POST['air_pressure'] : array($_POST['air_pressure']);
$rebound_high_speeds = is_array($_POST['rebound_high_speed']) ? $_POST['rebound_high_speed'] : array($_POST['rebound_high_speed']);
$rebound_low_speeds = is_array($_POST['rebound_low_speed']) ? $_POST['rebound_low_speed'] : array($_POST['rebound_low_speed']);
$compression_high_speeds = is_array($_POST['compression_high_speed']) ? $_POST['compression_high_speed'] : array($_POST['compression_high_speed']);
$compression_low_speeds = is_array($_POST['compression_low_speed']) ? $_POST['compression_low_speed'] : array($_POST['compression_low_speed']);
$last_service_dates = is_array($_POST['last_service_date']) ? $_POST['last_service_date'] : array($_POST['last_service_date']);
$last_servicers = is_array($_POST['last_servicer']) ? $_POST['last_servicer'] : array($_POST['last_servicer']);
$makemodel = is_array($_POST['make_and_model']) ? $_POST['make_and_model'] : array($_POST['make_and_model']);
$type = is_array($_POST['bike_type']) ? $_POST['bike_type'] : array($_POST['bike_type']);




// Loop through each gear ID
foreach ($gear_ids as $i => $gear_id) {
    $index = array_search($gear_id, $_POST['gear_id']); // Find the actual index of the gear_id in the POST data
   
    $air_pressure = isset($air_pressures[$index]) && $air_pressures[$index] !== '' ? (int) $air_pressures[$index] : null;
    $air_pressure = isset($air_pressures[$i]) && $air_pressures[$i] !== '' ? (int) $air_pressures[$i] : null;
    $rebound_high_speed = isset($rebound_high_speeds[$i]) && $rebound_high_speeds[$i] !== '' ? (int) $rebound_high_speeds[$i] : null;
    $rebound_high_speed = isset($rebound_high_speeds[$index]) && $rebound_high_speeds[$index] !== '' ? (int) $rebound_high_speeds[$index] : null;
    $rebound_low_speed = isset($rebound_low_speeds[$index]) && $rebound_low_speeds[$index] !== '' ? (int) $rebound_low_speeds[$index] : null;
    $compression_high_speed = isset($compression_high_speeds[$index]) && $compression_high_speeds[$index] !== '' ? (int) $compression_high_speeds[$index] : null;
    $compression_low_speed = isset($compression_low_speeds[$index]) && $compression_low_speeds[$index] !== '' ? (int) $compression_low_speeds[$index] : null;
    $last_service_date = isset($last_service_dates[$index]) && $last_service_dates[$index] !== '' ? $last_service_dates[$index] : null;
    $last_servicer = isset($last_servicers[$index]) && $last_servicers[$index] !== '' ? $last_servicers[$index] : null;
    $make_and_model = isset($makemodel[$index]) && $makemodel[$index] !== '' ? $makemodel[$index] : null;
    $bike_type = isset($type[$index]) && $type[$index] !== '' ? $type[$index] : null;

    // Insert the suspension settings
    $sql = "INSERT INTO suspension_settings (user_id, gear_id, air_pressure, rebound_high_speed, rebound_low_speed, compression_high_speed, compression_low_speed, last_service_date, last_servicer, model, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $gear_id, $air_pressure, $rebound_high_speed, $rebound_low_speed, $compression_high_speed, $compression_low_speed, $last_service_date, $last_servicer, $make_and_model, $bike_type]);

}

 #echo " $sql ";

// if stmt successful then redirect to sync_activities_full.php
if ($stmt) {
    //send pushbullet notification with new user sign up info
    // Send the Pushbullet notification
    $title = "New Shred tender";
    $message = "Email: $username, ";
    sendPushbulletNotification($title, $message);

    header('Location: sync_activities_full.php');
    exit;
} else {
    echo "Error: " . $sql . "<br>" . $pdo->error;
}

?>