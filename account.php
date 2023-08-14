<?php
session_start();
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Account</title>
    <link rel="preload" as="style" href="style.css" onload="this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet" href="style.css">
    </noscript>
    <meta name="description" content="Tuner and Suspension Servicing App">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script async src="https://umami.ailona.com/script.js"
        data-website-id="684edd19-f7fa-4d0c-873d-bc0f76ae2b7a"></script>


</head>

<body>
    <?php
    //session_start();
    
    // Check if user_id is set in the session
    if (!isset($_SESSION['user_id'])) {
        // User is not logged in, redirect to login page
        header("Location: login.php");
        exit;
    }
    $userid = $_SESSION['user_id'];


    // Include your database configuration file
    
    require_once 'configs/config.php';
    require_once 'functions.php';
    // user function to connect to database
    $pdo = connectToDatabase();
    // Get the user data from the database
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userid]);
    $user = $stmt->fetch();


    // Check if user_id is set in the session
    if (isset($_SESSION['user_id'])) {
        // User is logged in. Display their data.
        // Get the Strava access token
        $stravaAccessToken = $user['strava_token'];
        #
        try {
            $username = $_SESSION['username'];
        } catch (Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }

        $sql = "SELECT users.username, suspension_settings.*, activities.gear_id, activities.gear_name, SUM(activities.moving_time)/3600 AS total_moving_time, SUM(activities.distance * 0.000621371) AS 'Total miles', MAX(activities.activity_date) AS last_activity_date FROM users JOIN suspension_settings ON users.id = suspension_settings.user_id JOIN activities ON suspension_settings.gear_id = activities.gear_id  WHERE users.id = ? GROUP BY activities.gear_id, users.username, suspension_settings.user_id, suspension_settings.gear_id, suspension_settings.air_pressure, suspension_settings.rebound_high_speed, suspension_settings.rebound_low_speed, suspension_settings.compression_high_speed, suspension_settings.compression_low_speed, suspension_settings.last_service_date, suspension_settings.last_servicer, suspension_settings.model, suspension_settings.type ORDER by activities.total_elevation_gain desc";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetchAll(); // Fetch all rows, not just one
        $username = $user[0]['username'];


        // Check if the query returned any results
        if (!empty($user)) {

            ?>

            <div class="header">
                <div class="left-section">

                    <h1>Account Page</h1>
                </div>
                <div class="right-section">

                    <div class="username">Username:
                        <?php echo $username; ?>
                    </div>
                    <img src="api_logo_pwrdBy_strava_horiz_light.png" alt="powered by strava" class="strava-logo">
                </div>
            </div>
            <?php
            echo "<div class='content'>";

            echo "<div class='container'>"; // Start of container div
            echo "<h2>Ride Stats since last Servicing:</h2>";
            // Loop through each bike and display the data
            // only if the user has a last servicing date
            foreach ($user as $gear) {
                $totalRideHours = $gear['total_moving_time'];

                // Check if last_service_date is set
                // display basic gear data and last service update button and recommendations in table 
                if (isset($gear['last_service_date']) && ($gear['track_settings'] == 1)) {
                    echo "<form method='POST' action='update_maint.php'>";
                    echo "<table>";
                    echo "<tr><th>Bike</th><td>" . htmlspecialchars($gear['gear_name']) . "</td></tr>";
                    echo "<input type='hidden' name='gear_id[]' value='" . htmlspecialchars($gear['gear_id']) . "'>"; // Include gear_id as hidden input
                    echo "<tr><th>Bike Type</th><td>" . htmlspecialchars($gear['type']) . "</td></tr>";
                    echo "<tr><th>Total Ride Hours:</th><td>" . round(htmlspecialchars($gear['total_moving_time']), 2) . " hours</td></tr>";
                    echo "<tr><th>Total Miles:</th><td>" . round(htmlspecialchars($gear['Total miles']), 2) . " miles</td></tr>";
                    echo "<tr><th>Last Service Date</th><td><input type='date' name='last_service_date[]' value='" . htmlspecialchars($gear['last_service_date']) . "'></td></tr>";
                    echo "<tr><th><button type='button' class='recommendation-button' onclick='getMaintenanceRecommendation(\"" . $gear['gear_id'] . "\", \"" . htmlspecialchars($gear['gear_name']) . "\")'>Get Maintenance Recommendations</button></th><td><input type='submit' value='Update service date'></td></tr>";
                    echo "</table>";
                    echo "</form>"; // Close the form for maintenance update
    
                    //if air_pressure is null 
                    if (isset($gear['air_pressure']) && !empty($gear['air_pressure'])) {
                        $sql = "SELECT service_item, serv_int, component_type FROM sus_service_interval WHERE component_type = 'Suspension'";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute();
                        $serviceIntervals = $stmt->fetchAll();


                        echo "<h3>Suspension Service Items due:</h3>";
                        foreach ($serviceIntervals as $interval) {
                            if ($totalRideHours >= $interval['serv_int']) {
                                echo "<p><strong>Warning:</strong> " . htmlspecialchars($interval['service_item']) . " is due for " . htmlspecialchars($interval['component_type']) . " (every " . htmlspecialchars($interval['serv_int']) . " hours).</p>";
                            }

                        }
                    }

                    //echo "<br>"; // Add a break between each table
                    //echo "<button class='recommendation-button' onclick='getMaintenanceRecommendation(\"" . $gear['gear_id'] . "\", \"" . htmlspecialchars($gear['gear_name']) . "\")'>Get Component Maintenance Recommendations</button>";
    
                    ?>
                    <div id="loading_<?php echo $gear['gear_id']; ?>" style="display:none;">
                        <h3>Loading...</h3>
                        <p>using your strava activity data to generate personalized maintenance recommendations:</p>
                    </div>
                    <div id="recommendations_<?php echo $gear['gear_id']; ?>">
                    </div>
                    <?php
                    //echo "</div>"; // End of container div
    
                }
            }
            #$totalRideHours = // your calculation here;
    
            echo "</div>"; // End of container div
    
            echo "<div>";
            // get all gears and show details
    
            $sql = "SELECT gear_id, gear_name, SUM(moving_time)/3600 AS 'Total Hours', SUM(distance * 0.000621371) AS 'Total miles', AVG(average_watts) AS 'Average Watts', AVG(suffer_score) AS 'Average Suffer Score', SUM(total_elevation_gain * 3.281) AS 'Total Elevation Gain (ft)', AVG(max_speed) AS 'Average Max Speed', SUM(CASE WHEN workout_type = 10 or workout_type is NULL  THEN 1 ELSE 0 END) AS 'Normal Rides', SUM(CASE WHEN workout_type = 12 THEN 1 ELSE 0 END) AS 'Workouts', SUM(CASE WHEN workout_type = 11 THEN 1 ELSE 0 END) AS 'Races' FROM `activities` WHERE user_id = ? GROUP BY gear_id, gear_name ORDER BY total_elevation_gain;";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
            $bikedetails = $stmt->fetchAll();

            echo "<div class='container'>"; // Start of container div
            echo "<h3>Bike Details:</h3>";
            if ($bikedetails) { // If there are bike details
                echo "<table>";
                $headers = ["Gear ID", "Total Hours", "Total Miles", "Average Watts", "Average Suffer Score", "Total Elevation Gain (ft)", "Average Max Speed", "Normal Rides", "Workouts", "Races"];
                foreach ($bikedetails as $bike) {
                    echo "<tr><th colspan='3'>" . htmlspecialchars($bike['gear_name']) . "</th></tr>";
                    echo "<tr><td>" . $headers[0] . "</td><td>" . htmlspecialchars($bike['gear_id']) . "</td><td rowspan='10'>"; // rowspan is set to 10 to span the button across all rows for this gear
                    echo "<form method='post' action='update_track_settings.php'>";
                    echo "<input type='hidden' name='gear_id' value='" . htmlspecialchars($bike['gear_id']) . "'>";
                    echo "<button type='submit' name='track_settings' value='1'>Enable Maintainance Tracking </button>";
                    echo "</form>";
                    // Button to enable suspension settings tracking
                    echo "<form method='post' action='update_track_settings.php' style='margin-bottom: 10px;'>";
                    echo "<input type='hidden' name='gear_id' value='" . htmlspecialchars($bike['gear_id']) . "'>";
                   // echo "<input type='hidden' name='air_pressure' value=''" . htmlspecialchars($bike['air_pressure']) . "'>";
                    echo "<button type='submit' name='air_pressure' value='70'>Enable Suspension settings </button>";
                    echo "</form>";

                    // Button to disable tracking
                    echo "<form method='post' action='update_track_settings.php'>";
                    echo "<input type='hidden' name='gear_id' value='" . htmlspecialchars($bike['gear_id']) . "'>";
                    echo "<button type='submit' name='track_settings' value='0'>Remove Tracking</button>";
                    echo "</form>";
                    echo "<tr><td>" . $headers[1] . "</td><td>" . htmlspecialchars($bike['Total Hours']) . "</td></tr>";
                    echo "<tr><td>" . $headers[2] . "</td><td>" . round(htmlspecialchars($bike['Total miles']), 2) . " miles</td></tr>";
                    echo "<tr><td>" . $headers[3] . "</td><td>" . round(htmlspecialchars($bike['Average Watts']), 2) . " watts</td></tr>";
                    echo "<tr><td>" . $headers[4] . "</td><td>" . round(htmlspecialchars($bike['Average Suffer Score']), 2) . "</td></tr>";
                    echo "<tr><td>" . $headers[5] . "</td><td>" . round(htmlspecialchars($bike['Total Elevation Gain (ft)']), 2) . " ft</td></tr>";
                    echo "<tr><td>" . $headers[6] . "</td><td>" . round(htmlspecialchars($bike['Average Max Speed']), 2) . " mph</td></tr>";
                    echo "<tr><td>" . $headers[7] . "</td><td>" . htmlspecialchars($bike['Normal Rides']) . "</td></tr>";
                    echo "<tr><td>" . $headers[8] . "</td><td>" . htmlspecialchars($bike['Workouts']) . "</td></tr>";
                    echo "<tr><td>" . $headers[9] . "</td><td>" . htmlspecialchars($bike['Races']) . "</td></tr>";

                }

                echo "</table>";
            }
            echo "<h2> Update Suspension and Maintainance details:";
            foreach ($user as $gear) {
                $totalRideHours = $gear['total_moving_time'];

                // Check if last_service_date is set
                if (isset($gear['air_pressure']) && !empty($gear['air_pressure'])) {
                    echo "<form method='POST' action='update_settings.php'>";
                    echo "<table>";
                    echo "<tr><th>Bike</th><td>" . htmlspecialchars($gear['gear_name']) . "</td></tr>";
                    echo "<input type='hidden' name='gear_id[]' value='" . htmlspecialchars($gear['gear_id']) . "'>"; // Include gear_id as hidden input
                    echo "<tr><th>Model</th><td><input type='text' name='model[]' value='" . htmlspecialchars($gear['model']) . "'></td></tr>";
                    echo "<tr><th>Air Pressure</th><td><input type='text' name='air_pressure[]' value='" . htmlspecialchars($gear['air_pressure']) . "'></td></tr>";
                    echo "<tr><th>Rebound High Speed</th><td><input type='text' name='rebound_high_speed[]' value='" . htmlspecialchars($gear['rebound_high_speed']) . "'></td></tr>";
                    echo "<tr><th>Rebound Low Speed</th><td><input type='text' name='rebound_low_speed[]' value='" . htmlspecialchars($gear['rebound_low_speed']) . "'></td></tr>";
                    echo "<tr><th>Compression High Speed</th><td><input type='text' name='compression_high_speed[]' value='" . htmlspecialchars($gear['compression_high_speed']) . "'></td></tr>";
                    echo "<tr><th>Compression Low Speed</th><td><input type='text' name='compression_low_speed[]' value='" . htmlspecialchars($gear['compression_low_speed']) . "'></td></tr>";
                    echo "<tr><th>Last Service Date</th><td><input type='date' name='last_service_date[]' value='" . htmlspecialchars($gear['last_service_date']) . "'></td></tr>";
                    echo "<tr><th>Last Servicer</th><td><input type='text' name='last_servicer[]' value='" . htmlspecialchars($gear['last_servicer']) . "'></td></tr>";
                    echo "<tr><th>Total Ride Hours:</th><td>" . round(htmlspecialchars($gear['total_moving_time']), 2) . " hours</td></tr>";
                    echo "<tr><th>Total Miles:</th><td>" . round(htmlspecialchars($gear['Total miles']), 2) . " miles</td></tr>";
                    echo "<tr><th></th><td><input type='submit' value='Update Suspension Settings'></td></tr>";
                    echo "</table>";
                    echo "</form>";
                    //echo "<br>"; // Add a break between each table
                }
            }



            ?>

            <?php

        } else {
            echo "No data found for this user.";
        }
    } else {
        echo "User ID not found in the session.";
    }


    ?>
    <script>
        function getMaintenanceRecommendation(gearId, gearName) {
            // Prepare the data to send to the server
            var data = {
                gear_id: gearId
            };

            // Show the loading message for this specific gear and include the gear name
            $('#loading_' + gearId).html("<h3>Loading...</h3><p>Using your Strava activity data to generate personalized maintenance recommendations for " + gearName + ":</p>");
            $('#loading_' + gearId).show();

            // Make an AJAX call to the server
            $.ajax({
                url: 'get_maint_recommendations.php', // URL of the server-side script
                type: 'POST',
                data: data,
                success: function (response) {
                    // Insert the HTML response directly into the recommendations div for this gear
                    $('#recommendations_' + gearId).html(response);
                },
                error: function () {
                    alert('An error occurred while fetching the maintenance recommendations.');
                },
                complete: function () {
                    // Hide the loading message for this specific gear
                    $('#loading_' + gearId).hide();
                }
            });
        }

    </script>
    <form action="sync_activities_full.php" method="post">
        <input type="submit" value="Sync Activities">
    </form>
    <form method="POST" action="logout.php">
        <input type="submit" value="Log out">
    </form>

    </div>
</body>

</html>