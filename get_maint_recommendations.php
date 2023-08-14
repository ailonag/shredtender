<?php
// Start the session
session_start();

// Get the gear_id from the request
//$gear_id = "b8105977";
$gear_id = $_POST['gear_id'];
if (!isset($gear_id)) {
    echo "Error: gear_id not found in the request.";
    exit;
}
require_once 'configs/config.php';
require_once 'vendor/autoload.php';
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


// get last service date
$sql = "SELECT last_service_date FROM suspension_settings WHERE gear_id = ?;";
$stmt = $pdo->prepare($sql);
$stmt->execute([$gear_id]); // Bind the parameter
$last_service_date = $stmt->fetchColumn();

// Fetch the bike stats from the database
$sql = "SELECT activities.gear_id, activities.gear_name, SUM(moving_time)/3600 AS 'Total Hours', SUM(distance * 0.000621371) AS 'Total miles', AVG(average_watts) AS 'Average Watts', AVG(suffer_score) AS 'Average Suffer Score', SUM(total_elevation_gain * 3.281) AS 'Total Elevation Gain (ft)', AVG(max_speed) AS 'Average Max Speed', SUM(CASE WHEN workout_type = 10 or workout_type is null  or workout_type = 0 THEN 1 ELSE 0 END) AS 'Normal Rides', SUM(CASE WHEN workout_type = 12 THEN 1 ELSE 0 END) AS 'Workouts', SUM(CASE WHEN workout_type = 11 THEN 1 ELSE 0 END) AS 'Races', suspension_settings.type, suspension_settings.last_service_date 
FROM activities
join suspension_settings on activities.gear_id = suspension_settings.gear_id
WHERE activities.gear_id = ? and activity_date >= ? GROUP BY activities.gear_id, activities.gear_name;";
$stmt = $pdo->prepare($sql);
$stmt->execute([$gear_id, $last_service_date]); // Bind the parameters
$bikedetails = $stmt->fetch();

// Call the OpenAI API with the stats to get the maintenance recommendation
// ...
// Initialize the OpenAI client

use GuzzleHttp\Client;

$client = new Client();
$apiKey = "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";

$prompt = "Based on my {$bikedetails['type']} and strava stats: Total Hours: {$bikedetails['Total Hours']}, Total Miles: {$bikedetails['Total miles']}, Average Watts: {$bikedetails['Average Watts']}, Average Suffer Score: {$bikedetails['Average Suffer Score']}, Total Elevation Gain (ft): {$bikedetails['Total Elevation Gain (ft)']}, Average Max Speed: {$bikedetails['Average Max Speed']}, Normal Rides: {$bikedetails['Normal Rides']}, Workouts: {$bikedetails['Workouts']}, Races: {$bikedetails['Races']} and last Service date being {$bikedetails['last_service_date']} Give me major maintenance I need to do on my bike. Be specific on what components need to be checked and why. Include the recommended maintenance schedule and my actual hour/miles used for each component recommendation in your response. \n Assume I am doing routine components checks like chain and tire pressure and tire wear. \n
Always format your response in HTML with just the Component and recommendations in a <div> and  bullet points <ul>";

$messages = [
    ["role" => "system", "content" => "you are my helpful bike maintenance assistant\n\n"],
    ["role" => "user", "content" => $prompt],
    ["role" => "assistant", "content" => ""]
];


$response = $client->post('https://api.openai.com/v1/chat/completions', [
    'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $apiKey,
    ],
    'json' => [
        'model' => 'gpt-4',
        'messages' => $messages,
        'temperature' => 1,
        'max_tokens' => 456,
        'top_p' => 1,
        'frequency_penalty' => 0,
        'presence_penalty' => 0
    ],
]);

$recommendation = json_decode($response->getBody(), true)['choices'][0]['message']['content'];
//echo recommendation
echo $recommendation;

//write $recommondation to logfile
$myfile = fopen("logs/maintenance_recommendations.log", "a") or die("Unable to open file!");
$txt = date("Y-m-d H:i:s") . " - " . $gear_id .  "\n Prompt:" .  $prompt . "\n Recommendation: \n" . $recommendation . "\n";
fwrite($myfile, $txt);
fclose($myfile);

?>