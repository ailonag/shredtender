<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Tuner and Suspension Servicing App">
    <meta name="keywords" content="HTML, CSS, JavaScript, PHP, MySQL">
    <meta name="author" content="Erich Gellert">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preload" as="style" href="style.css" onload="this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="style.css"></noscript>
    <script async src="https://umami.ailona.com/script.js" data-website-id="684edd19-f7fa-4d0c-873d-bc0f76ae2b7a"></script>

    <title>Suspension Servicing App</title>
    <style>
        


    </style>
</head>

<?php
    session_start();
    if (isset($_SESSION['username'])) {
        header("Location: /login.php");
        exit();
    }
?>

<body>
<div class="header">
        <div class="right-section">
            <h1>The Shred Tender</h1>
            <img src="api_logo_pwrdBy_strava_horiz_light.png" alt="powered by strava" class="strava-logo">
        </div>
    
    </div>
    <div class="content">
        <p>Aloha! Please login or If you are new click  register to continue.</p>
        <h2>Login</h2>
        <form action="login.php" method="post">
            <label for="username">Email Address:</label><br>
            <input type="text" id="username" name="username"><br>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password"><br>
            <input type="submit" value="Login" class="button" onclick="trackFormSubmission()">
        </form>

        <a href="/register.php" class="button">Register</a>
    </div>
</body>
<script>
    function trackFormSubmission() {
        // Track form submission event using Umami
        window.umami.track('Reg btn clicked', { id: '101', eventValue: '1' });
        logMessage('reg button clicked') ;// log the start of the sessio
        
    }

</script>

</html>