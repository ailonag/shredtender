<?php
require_once 'configs/config.php';
session_start();
// Connect to the database
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

// Get the refresh token from the database or session
$user_id = $_SESSION['user_id'];
$sql = "SELECT refresh_token FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$refresh_token = $user['refresh_token'];

// Set the endpoint and parameters for the refresh request
$endpoint = "https://www.strava.com/oauth/token";
$params = [
    'client_id' => stravaclientid,
    'client_secret' => stravasecret,
    'refresh_token' => $refresh_token,
    'grant_type' => 'refresh_token'
];

// Make the POST request
$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($params)
    ]
];
$context = stream_context_create($options);
$response = file_get_contents($endpoint, false, $context);

if ($response === false) {
    // Handle error
    die("An error occurred while refreshing the token.");
}

// Decode the JSON response
$data = json_decode($response, true);

// Update the access token and refresh token in the database
$new_access_token = $data['access_token'];
$new_refresh_token = $data['refresh_token'];
$sql = "UPDATE users SET strava_token = ?, refresh_token = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$new_access_token, $new_refresh_token, $user_id]);

// Redirect back to the account page
header("Location: account.php");
exit;
?>
