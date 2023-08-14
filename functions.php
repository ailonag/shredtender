<?php
require_once 'configs/config.php';
function getStravaActivities($accessToken)
{
    // Calculate the timestamp for one year ago
    $oneYearAgo = strtotime('-2 year');

    // Construct the URL with the 'after' parameter
    $url = "https://www.strava.com/api/v3/athlete/activities?after=" . $oneYearAgo;

    $options = [
        'http' => [
            'header' => "Authorization: Bearer " . $accessToken
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    // Check if the request failed
    if ($response === false) {
        // Handle the error, e.g., by redirecting to a page where the user can refresh their token
        header("Location: refresh_token.php");
        exit;
    }

    return json_decode($response, true);
}

function storeActivitiesInDatabase($pdo, $userId, $activities, $accessToken)
{
    $sql = "INSERT INTO activities (user_id, strava_activity_id, moving_time, distance, gear_id, gear_name, activity_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    $sqlCheck = "SELECT * FROM activities WHERE strava_activity_id = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);

    foreach ($activities as $activity) {
        if ($activity['type'] === 'Ride' && isset($activity['gear_id'])) {
            // Check if the activity already exists in the database
            $stmtCheck->execute([$activity['id']]);
            $existingActivity = $stmtCheck->fetch();

            if (!$existingActivity) {
                // If the activity does not exist, insert it
                $gearname = getGearDetails($activity['gear_id'], $accessToken);
                $activity_date = new DateTime($activity['start_date_local']);
                $activity_date = $activity_date->format('Y-m-d H:i:s');

                $stmt->execute([$userId, $activity['id'], $activity['moving_time'], $activity['distance'], $activity['gear_id'], $gearname, $activity_date]);
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
function getUserProfile($accesstoken)
{
    $url = "https://www.strava.com/api/v3/athlete";
    $options = $options = [
        'http' => [
            'header' => "Authorization: Bearer " . $accesstoken
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);


    if ($response === false) {
        // There was an error with the API call
        error_log("Error getting gear description for user: ");
        return null;
    }

    $userdata = json_decode($response, true);
    return $userdata;

}
function sendPushbulletNotification($title, $message)
  {
    $apiKey = API_KEY;

    $data = array(
      'type' => 'note',
      'title' => $title,
      'body' => $message
    );

    $ch = curl_init('https://api.pushbullet.com/v2/pushes');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt(
      $ch,
      CURLOPT_HTTPHEADER,
      array(
        'Content-Type: application/json',
        'Access-Token: ' . $apiKey
      )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpCode !== 200) {
      //logError('Failed to send Pushbullet notification');
    }
  }

function connectToDatabase()
{
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
    return new PDO($dsn, $user, $pass, $opt);
}

$pdo = connectToDatabase();




function displayGearForm($result)
{
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
    echo "<p>*** optional values *** </p>";
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
}


?>