<?php
require_once 'configs/config.php';
session_start();
// Get the authorization code from the query string
$code = $_GET['code'];

// Get the Strava client ID and client secret from the config
$client_id = stravaclientid;
$client_secret = stravasecret;

// Set up the cURL request
$ch = curl_init("https://www.strava.com/oauth/token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'code' => $code,
    'grant_type' => 'authorization_code'
]);

// Execute the cURL request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    die('Error: ' . curl_error($ch));
}

// Close the cURL request
curl_close($ch);

// Decode the response
$data = json_decode($response, true);


// Check if the access token is present in the response
if (!isset($data['access_token'])) {
    die("Error: Access token not found in the response from Strava.");
}

// Get the access token
$access_token = $data['access_token'];
$refresh_token = $data['refresh_token'];

// Connect to the database
$db = mysqli_connect('localhost', sqlaccount, sqlpassword, sqldb, 3307);
if (!$db) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    die('Error: User ID not found in the session.');
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Store the access token in the database
$sql = "UPDATE users SET strava_token = ?, refresh_token = ? WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("ssi", $access_token, $refresh_token , $user_id);
$stmt->execute();

// Store the user ID in a session variable
$_SESSION['user_id'] = $user_id;
$_SESSION['strava_access_token'] = $access_token;
$_SESSION['strava_refresh_token'] = $refresh_token;

// if $_SESSION['username'] not set get from database
if (!isset($_SESSION['username'])) {
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $_SESSION['username'] = $row['username'];
}



// Redirect to the suspension settings page
header('Location: complete_registration.php');
exit;
?>