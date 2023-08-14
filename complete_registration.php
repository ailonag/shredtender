<?php
session_start();
?>

<head>
    <meta charset="UTF-8">
    <title>Complete Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="description" content="Tuner and Suspension Servicing App">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preload" as="style" href="style.css" onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="style.css">
    </noscript>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="js/scripts.js"></script>
    <script async src="https://umami.ailona.com/script.js"
        data-website-id="684edd19-f7fa-4d0c-873d-bc0f76ae2b7a"></script>

</head>
<style>
    .suspension_type {
        width: 100%;
        padding: 20px 20px;
        margin: 8px 0;
        display: inline-block;
        border: 1px solid #ccc;
        box-sizing: border-box;
        font-size: 26px;
    }

    .select {
        width: 100%;
        padding: 20px 20px;
        margin: 8px 0;
        display: inline-block;
        border: 1px solid #ccc;
        box-sizing: border-box;
        font-size: 26px;
    }

    @media screen and (max-width: 600px) {
        .container {
            width: 100%;
        }
    }
</style>

<body>
    <?php
    echo "<div class='header'>";
    echo "<h1>Complete Registration </h1>";
    echo "</div>";
    echo "<div class='content'>";
    echo " Congrats! You have successfully linked your strava account, please complete the following information to complete your registration";
    echo "<h3> Please select bike(s) to track maintainance and settings: </h3>";
    require_once 'configs/config.php';

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

    // Check if the Strava access token is set in the session
    if (!isset($_SESSION['strava_access_token'])) {
        die('Error: Strava access token not found in the session.');
    }

    // Get the Strava access token from the session
    $access_token = $_SESSION['strava_access_token'];

    // Get the activities
    $activities = getStravaActivities($access_token, date('Y-m-d H:i:s', strtotime('-3 year')));
    //$after = date('Y-m-d H:i:s', strtotime('-2 year'));
// Store the activities in the database
    storeActivitiesInDatabase($pdo, $user_id, $activities, $access_token);

    // Get the unique gear IDs from the activities
    $gear_ids = array_unique(array_column($activities, 'gear_id'));

    if (empty($gear_ids)) {
        // No gear IDs found, prompt the user to manually enter the gear name
        echo "<p>No gear data found. Please enter your bike information manually.</p>";
        echo "<form action='store_settings.php' method='post'>";

        // Generate a random gear ID
        $random_gear_id = uniqid();
        echo "<input type='hidden' name='gear_id[]' value='" . htmlspecialchars($random_gear_id) . "'>";
        echo "<div class='gear-settings'>";
        echo "<h2>Enter Suspension Settings</h2>"; // Generic title since gear name is unknown
        echo "<label for='bike_type'>Bike Type:</label><br>";
        echo "<select id='bike_type' class='select' name='bike_type[]'>";
        echo "<option value=''>Select...</option>";
        echo "<option value='Mountain Bike'>Mountain Bike</option>";
        echo "<option value='Road Bike'>Road Bike</option>";
        echo "</select><br>";
        echo "<label for='Fork Make and Model'>Fork Make and Model:</label><br>";
        echo "<input type='text' id='make_and_model' name='make_and_model[]'><br>";
        echo "<label for='last_service_date'>Last Service Date:</label><br>";
        echo "<input type='date' id='last_service_date' name='last_service_date[]'><br>";
        echo "<label for='last_servicer'>Last Servicer:</label><br>";
        echo "<input type='text' id='last_servicer' name='last_servicer[]'><br>";
        echo "<p>*** optional values:  To track setting you can enter just the air pressure to start </p>";
        echo "<label for='air_pressure'>Air Pressure:</label><br>";
        echo "<input type='text' id='air_pressure' name='air_pressure[]'><br>";
        echo "<label for='rebound_high_speed'>Rebound High Speed:</label><br>";
        echo "<input type='text' id='rebound_high_speed' name='rebound_high_speed[]'><br>";
        echo "<label for='rebound_low_speed'>Rebound Low Speed:</label><br>";
        echo "<input type='text' id='rebound_low_speed' name='rebound_low_speed[]'><br>";
        echo "<label for='compression_high_speed'>Compression High Speed:</label><br>";
        echo "<input type='text' id='compression_high_speed' name='compression_high_speed[]'><br>";
        echo "<label for='compression_low_speed'>Compression Low Speed:</label><br>";
        echo "<input type='text' id='compression_low_speed' name='compression_low_speed[]'><br>";

        echo "<br>";
        echo "<input type='submit' value='Submit'>";
        echo "</form>";
    } else {

        // Get the gear information for the current user
        $sql = "SELECT DISTINCT gear_id, gear_name, activity_date FROM activities WHERE user_id = ? group by gear_id, gear_name order by activity_date desc;";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $results = $stmt->fetchAll();


        // Check if there are any results
        if (empty($results)) {
            echo "<p>No gear data found. Please make sure your Strava account has the necessary information.</p>";
        } else {
            echo "<form action='store_settings.php' method='post'>";


            if (count($results) == 1) {
                // If there's only one gear_id, display the form without checkboxes
    
                $result = $results[0];
                print_r($results[0]);
                echo "<div class='gear-selection'>";
                echo "<input type='hidden' name='gear_id[]' value='" . htmlspecialchars($result['gear_id']) . "'>";
                echo "<div class='gear-settings' id='settings_" . htmlspecialchars($result['gear_id']) . "';'>";
                echo "<h2>Suspension Settings for " . htmlspecialchars($result['gear_name']) . "</h2>"; // Moved inside the gear-settings div
                echo "<label for='bike_type'>Bike Type:</label><br>";
                echo "<select id='bike_type'class='select' name='bike_type[]'>";
                echo "<option value=''>Select...</option>";
                echo "<option value='Mountain Bike'>Mountain Bike</option>";
                echo "<option value='Road Bike'>Road Bike</option>";
                echo "</select><br>";
                echo "<label for='Fork Make and Model'>Fork Make and Model:</label><br>";
                echo "<input type='text' id='make_and_model' name='make_and_model[]'><br>";
                echo "<label for='last_service_date'>Last Service Date:</label><br>";
                echo "<input type='date' id='last_service_date' name='last_service_date[]'><br>";
                echo "<label for='last_servicer'>Last Servicer:</label><br>";
                echo "<input type='text' id='last_servicer' name='last_servicer[]'><br>";
                echo "<p>*** optional values:  To track setting you can enter just the air pressure to start</p>";
                echo "<label for='air_pressure'>Air Pressure:</label><br>";
                echo "<input type='text' id='air_pressure' name='air_pressure[]'><br>";
                echo "<label for='rebound_high_speed'>Rebound High Speed:</label><br>";
                echo "<input type='text' id='rebound_high_speed' name='rebound_high_speed[]'><br>";
                echo "<label for='rebound_low_speed'>Rebound Low Speed:</label><br>";
                echo "<input type='text' id='rebound_low_speed' name='rebound_low_speed[]'><br>";
                echo "<label for='compression_high_speed'>Compression High Speed:</label><br>";
                echo "<input type='text' id='compression_high_speed' name='compression_high_speed[]'><br>";
                echo "<label for='compression_low_speed'>Compression Low Speed:</label><br>";
                echo "<input type='text' id='compression_low_speed' name='compression_low_speed[]'><br>";
                echo "</div>"; // Close gear-settings div
                echo "</div>"; // Close gear-selection div
    


            } else {
                // If there's more than one gear_id, display checkboxes
    
                foreach ($results as $result) {
                    echo "<div class='gear-selection'>";
                    //echo "<input type='hidden' name='gear_id[]' value='" . htmlspecialchars($result['gear_id']) . "'>";
                    echo "<input type='checkbox' id='gear_" . htmlspecialchars($result['gear_id']) . "' name='gear_id[]' value='" . htmlspecialchars($result['gear_id']) . "'>";
                    echo "<label for='gear_" . htmlspecialchars($result['gear_id']) . "'><b>" . htmlspecialchars($result['gear_name']) . "</b>  - last activity:" . htmlspecialchars($result['activity_date']) . "</label>";
                    echo "<div class='gear-settings' id='settings_" . htmlspecialchars($result['gear_id']) . "' style='display:none;'>";
                    echo "<h2>Suspension Settings for " . htmlspecialchars($result['gear_name']) . "</h2>"; // Moved inside the gear-settings div
                    echo "<label for='bike_type'>Bike Type:</label><br>";
                    echo "<select id='bike_type'class='select' name='bike_type[]'>";
                    echo "<option value=''>Select...</option>";
                    echo "<option value='Mountain Bike'>Mountain Bike</option>";
                    echo "<option value='Road Bike'>Road Bike</option>";
                    echo "</select><br>";
                    echo "<label for='Fork Make and Model'>Fork Make and Model:</label><br>";
                    echo "<input type='text' id='make_and_model' name='make_and_model[]'><br>";
                    echo "<label for='last_service_date'>Last Service Date:</label><br>";
                    echo "<input type='date' id='last_service_date' name='last_service_date[]'><br>";
                    echo "<label for='last_servicer'>Last Servicer:</label><br>";
                    echo "<input type='text' id='last_servicer' name='last_servicer[]'><br>";
                    echo "<p>*** optional values:  To track setting you can enter just the air pressure to start </p>";
                    echo "<label for='air_pressure'>Air Pressure:</label><br>";
                    echo "<input type='text' id='air_pressure' name='air_pressure[]'><br>";
                    echo "<label for='rebound_high_speed'>Rebound High Speed:</label><br>";
                    echo "<input type='text' id='rebound_high_speed' name='rebound_high_speed[]'><br>";
                    echo "<label for='rebound_low_speed'>Rebound Low Speed:</label><br>";
                    echo "<input type='text' id='rebound_low_speed' name='rebound_low_speed[]'><br>";
                    echo "<label for='compression_high_speed'>Compression High Speed:</label><br>";
                    echo "<input type='text' id='compression_high_speed' name='compression_high_speed[]'><br>";
                    echo "<label for='compression_low_speed'>Compression Low Speed:</label><br>";
                    echo "<input type='text' id='compression_low_speed' name='compression_low_speed[]'><br>";


                    echo "</div>"; // Close gear-settings div
                    echo "</div>"; // Close gear-selection div
                }


            }
            echo "<br>";
            echo "<input type='submit' value='Submit' >";
            echo "</form>";
        }
    }
    ?>

    <script>
        $(document).ready(function () {
            $('input[type="checkbox"]').change(function () {
                var gear_id = $(this).attr('id').split('_')[1];
                if ($(this).prop('checked')) {
                    $('#settings_' + gear_id).show();
                } else {
                    $('#settings_' + gear_id).hide();
                }
            });
        });
    </script>
    </div>
</body>

</html>
<?php
function getStravaActivities($accessToken)
{
    // Calculate the timestamp for one year ago

    $rides = [];
    $page = 1;
    $perPage = 50;

    // Construct the URL with the 'after' parameter
    $url = "https://www.strava.com/api/v3/athlete/activities?&per_page=" . $perPage . "&page=" . $page;

    $options = [
        'http' => [
            'header' => "Authorization: Bearer " . $accessToken
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}
function storeActivitiesInDatabase($pdo, $userId, $activities, $accessToken)
{
    $sql = "INSERT INTO activities (user_id, strava_activity_id, moving_time, distance, gear_id, gear_name, activity_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    $sqlCheck = "SELECT * FROM activities WHERE strava_activity_id = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $Unk_gear_id = uniqid(); // Auto-generate a unique ID

    foreach ($activities as $activity) {
        if ($activity['type'] === 'Ride') {
            // Check if the activity already exists in the database
            $stmtCheck->execute([$activity['id']]);
            $existingActivity = $stmtCheck->fetch();

            if (!$existingActivity) {
                // If the activity does not exist, insert it
                $activity_date = new DateTime($activity['start_date_local']);
                $activity_date = $activity_date->format('Y-m-d H:i:s');

                if (isset($activity['gear_id'])) {
                    $gearname = getGearDetails($activity['gear_id'], $accessToken);
                    $gear_id = $activity['gear_id'];
                } else {
                    // Handle case where gear_id is empty
                    $gearname = "Unknown Gear"; // You can prompt the user to enter this value
                    $gear_id = $Unk_gear_id;
                }

                $stmt->execute([$userId, $activity['id'], $activity['moving_time'], $activity['distance'], $gear_id, $gearname, $activity_date]);
            }
        }
    }
}

function getGearDetails($gearId, $accessToken)
{
    $url = "https://www.strava.com/api/v3/gear/" . $gearId;
    $options = [
        'http' => [
            'header' => "Authorization: Bearer " . $accessToken
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);


    if ($response === false) {
        // There was an error with the API call
        error_log("Error getting gear description for gear ID: " . $gearId);
        return null;
    }

    $gear = json_decode($response, true);
    return $gear['name'];
}
function trackEvent($type, $value)
{
    echo "<script>
        umami.trackEvent('$value', '$type');
    </script>";
}
?>