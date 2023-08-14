<head>
    <meta charset="UTF-8">
    <meta name="description" content="Tuner and Suspension Servicing App">
    <meta name="keywords" content="HTML, CSS, JavaScript, PHP, MySQL">
    <meta name="author" content="Erich Gellert">
    <link rel="preload" as="style" href="style.css" onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="style.css">
    </noscript> <noscript>
        <link rel="stylesheet" href="styles.css">
    </noscript>
    <script async src="https://umami.ailona.com/script.js" data-website-id="684edd19-f7fa-4d0c-873d-bc0f76ae2b7a"></script>
    <title>Welcome to Tuner and Suspension Servicing App</title>
    <style>
        .content {
            margin: 15px;
        }

        .button {
            display: inline-block;
            background-color: #008CBA;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            font-size: 36px;
            margin: 4px 2px;
            cursor: pointer;
            border: none;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 15px 20px;
            margin: 8px 0;
            display: inline-block;
            border: 1px solid #ccc;
            box-sizing: border-box;
            font-size: 36px;
            /* Increase font size */
        }

        @media screen and (max-width: 600px) {
            .content {
                padding: 0 20px;
            }

            input[type="text"],
            input[type="password"] {
                width: 100%;
            }

            font-size: 40px;
            /* Increase font size */
        }
    </style>
</head>
<?php
require_once 'configs/config.php';
// Start the session
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

    // Assuming you have a connection to your database
    $desired_user_id = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING); // Get the user ID from the form input

    // Prepare a SQL statement to check if the user ID exists
    $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$desired_user_id]);
    $result = $stmt->fetch();

    // Check if the user ID exists
    if ($result['count'] > 0) {
        echo "<p class='error'>The username is already taken. Please choose a different username.</p>";
    } else {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password


        // Insert the user
        $sql = "INSERT INTO users (username, password, reg_date) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $password, date("Y-m-d H:i:s")]);
        $user_id = $pdo->lastInsertId();

        // Store the user ID in a session variable
        $_SESSION['user_id'] = $user_id;
        $_session['username'] = $username;

        // Redirect to the OAuth process
        header("Location: /strava_oauth.php");
        exit;
    }
}
?>

<body>
    <div class="header">
        <h1>Account Registration</h1>
    </div>
    <div class="content">
        <h2>Registration Instructions</h2>
        <p>Welcome to the registration process! Here's what you need to know:</p>
        <ol>
            <li><strong>Strava Connection:</strong> As part of the registration, you will be redirected to Strava to
                allow access to your activities. This is a necessary step to import your basic ride data.</li>
            <li><strong>Data Imported:</strong> We only import essential data such as moving time, activity date,
                distance, and gear data. Rest assured, no location data or other sensitive information will be imported.
            </li>
            <li><strong>Gear Data Requirement:</strong> To better track your ride times, gear data must be added to each
                ride activity. This information helps us provide you with accurate and personalized insights.</li>
            <li><strong>How to Add Gear Data:</strong> If you need assistance with adding gear data to your Strava
                activities, please refer to the <a
                    href="https://support.strava.com/hc/en-us/articles/216918727-Adding-Gear-to-Your-Activities-on-Strava#:~:text=From%20the%20Strava%20website%2C%20hover,can%20also%20be%20edited%20later."
                    target="_blank">official Strava support guide</a>.</li>
        </ol>
        <p>If you have any questions or need further assistance, please don't hesitate to contact us. Happy riding!</p>

        <form method="POST">
            <label style="input" for="email">Email Address:</label><br>
            <input type="text" id="username" name="username"><br>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password"><br>
            <input type="submit" value="Submit" class="button" onclick="trackFormSubmission()">
        </form>
        <script>
            function trackFormSubmission() {
                // Track form submission event using Umami
                window.umami.track('Reg btn clicked', { id: '101', email: username });
                #logMessage('reg button clicked');// log the start of the sessio
                $title = "New Shred tender started";
                 $message = "Email: $username, ";
                sendPushbulletNotification($title, $message);


            }

        </script>
        
    </div>
</body>


</html>