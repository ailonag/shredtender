<?php
// update_track_settings.php
session_start();
// Start session and include necessary files
require_once 'configs/config.php';
require_once 'functions.php';
connectToDatabase();


// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    die('Error: User ID not found in the session.');
} else {
    // Get the user ID from the session
    $user_id = $_SESSION['user_id'];
    //set $last_updated to current time
    $last_serviced = date("Y-m-d H:i:s");
    $gear_id = $_POST['gear_id'];
    $track_settings = $_POST['track_settings']; // This will be either 1 (for enable) or 0 (for disable)

}

if (isset($_POST['track_settings']) && isset($_POST['gear_id'])) {

   

    // check if gear)id is in suspension_settings table
    $sql = "SELECT gear_id FROM suspension_settings WHERE gear_id = ? ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$gear_id]);
    $gear_exists = $stmt->fetchColumn();
    echo $gear_exists;
    echo $_POST['gear_id'];
    echo "<br>";
    echo $track_settings;



    if ($track_settings == 1 and $gear_exists == $gear_id) {
        // Enable tracking
        $sql = "UPDATE suspension_settings SET track_settings = 1 WHERE gear_id = ? ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$gear_id]);
    } else if ($track_settings == 1 and $gear_exists != $gear_id) {
        // Enable tracking
        $sql = "INSERT INTO suspension_settings (user_id, gear_id, air_pressure, last_service_date, track_settings) VALUES (?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $gear_id, NULL, $last_serviced]);
    }

} 

if ($track_settings == 0) {
    // Disable tracking and set air_pressure to NULL
    $sql = "UPDATE suspension_settings SET track_settings = 0, air_pressure = NULL WHERE gear_id = ? ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$gear_id]);

    echo "Track settings disabled";
}


if (isset($_POST['air_pressure']) && isset($_POST['gear_id'])) {
    $gear_id = $_POST['gear_id'];
    $air_pressure = $_POST['air_pressure'];
    // check if gear)id is in suspension_settings table
    $sql = "SELECT gear_id FROM suspension_settings WHERE gear_id = ? ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$gear_id]);
    $gear_exists = $stmt->fetchColumn(); // check if gear)id is in suspension_settings table
    $sql = "SELECT gear_id FROM suspension_settings WHERE gear_id = ? ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$gear_id]);
    $gear_exists = $stmt->fetchColumn();
    //if gear_exists update air_pressure  else insert new row
    if ($gear_exists) {
        $sql = "UPDATE suspension_settings SET air_pressure = ? WHERE gear_id = ? ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$air_pressure, $gear_id]);
    } else {
        $sql = "INSERT INTO suspension_settings (user_id, gear_id, air_pressure, last_service_date, track_settings) VALUES (?, ?, ?, ?, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $gear_id, $air_pressure, $last_serviced]);
    }
    // echo if sql stmt was succesfull or not 
    echo $stmt === false ? 'Error running SQL statement: ' . $pdo->errorInfo()[2] : 'Successfully updated air pressure';


    // Redirect back to the previous page or display a success message
    header('Location: account.php'); // replace 'your_previous_page.php' with the name of your current page
    exit;
}

if ($stmt === false) {
    die('Error running SQL statement: ' . $pdo->errorInfo()[2]);
} else {
    // Redirect back to the previous page or display a success message
    header('Location: account.php'); // replace 'your_previous_page.php' with the name of your current page
    exit;
}
?>