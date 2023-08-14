<?php
require_once 'configs/config.php';
// Strava client ID
$client_id = stravaclientid;
$_SESSION['user_id'] = $user_id;
// Strava redirect URI
$redirect_uri = 'https://tuner.ailona.com/strava_oauth_callback.php';

// Strava OAuth URL
$url = "https://www.strava.com/oauth/authorize?client_id={$client_id}&response_type=code&redirect_uri={$redirect_uri}&approval_prompt=force&scope=activity:read_all";

// Redirect the user to the Strava OAuth process
header("Location: {$url}");
?>
