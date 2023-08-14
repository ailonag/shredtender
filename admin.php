<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    die('You must be logged in to access this page.');
}

// Check if the logged-in user's email matches your email
if ($_SESSION['username'] !== 'ailona@ailona.com') {
    die('You do not have permission to access this page.');
}
require_once 'configs/config.php';
require_once 'functions.php';
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

// Fetch all usernames
$sql = "SELECT id, username FROM users";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll();

// Display usernames as links
echo "<h1>Select a User to Simulate</h1>";
echo "<ul>";
foreach ($users as $user) {
    echo "<li><a href='account_godmodey.php?user_id=" . $user['id'] . "'>" . htmlspecialchars($user['username']) . "</a></li>";
}
echo "</ul>";
?>