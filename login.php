<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Tuner and Suspension Servicing App">
    <meta name="keywords" content="HTML, CSS, JavaScript, PHP, MySQL">
    <meta name="author" content="Erich Gellert">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preload" as="style" href="style.css" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="style.css"></noscript>
    <script async src="https://umami.ailona.com/script.js" data-website-id="684edd19-f7fa-4d0c-873d-bc0f76ae2b7a"></script>

    <title>Log in</title>

</head>
<?php
session_start();

require_once 'configs/config.php';
require_once 'functions.php';

$pdo = connectToDatabase();


// Get the current date and time
    $now = new DateTime();

    // Subtract one year from the current date and time
    $oneYearAgo = $now->sub(new DateInterval('P1Y'));

    // Convert the date and time to a Unix timestamp
    $after = $oneYearAgo->getTimestamp();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get the user
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    // if user doesn't exist, redirect to registration page
    if (!$user) {
        header("Location: register.php");
        exit();
    }

    if ($user && password_verify($password, $user['password'])) {
        // Password is correct. Log the user in.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
    } else {
        // Invalid username or password
        echo "Invalid username or password!";
    }
}

if (isset($_SESSION['user_id'])) {
    // User is logged in. Display their data.
    $sql = "SELECT * FROM users JOIN suspension_settings ON users.id = suspension_settings.user_id WHERE users.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    

    // Get the Strava access token
    $stravaAccessToken = $user['strava_token'];
    //store username in session
    $_SESSION['username'] = $user['username'];

    // Get the activities
    //$activities = getStravaActivities($stravaAccessToken, $user['last_service_date']);

    // Store the activities in the database
    //storeActivitiesInDatabase($pdo, $_SESSION['user_id'], $activities, $stravaAccessToken);

 // send to account.php
    header("Location: account.php");
    exit();
    
} else {
    // User is not logged in. Show the login form.
    ?>
    <p>Aloha! Please login .</p>
        <h2>Login</h2>
    <form method="POST">
        <label for="username">Email:</label><br>
        <input type="text" id="username" name="username"><br>
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password"><br>
        <input type="submit" value="Log in">
    </form>
    <?php
}




?>
