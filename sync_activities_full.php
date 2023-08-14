<?php
// Start the session
session_start();

// Check if the user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    die('Error: User ID not found in the session.');
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];
echo "Sycning activities for user ID: " . $user_id . "\n";
// Path to your shell script
$script_path = '/volume2/web/AilonaTuner/run_full_sync.sh';

// Execute the shell script with the user ID as an argument
$output = null;
$resultCode = null;
exec($script_path . " " . escapeshellarg($user_id) . " 2>&1", $output, $resultCode);
if ($resultCode !== 0) {
    echo "Error executing command: " . implode("\n", $output);
}

if ($resultCode == 0) {
    echo "Sync successful!";
    header("Location: account.php");
    exit;
} else {
    echo "An error occurred during sync.";
    echo "Error executing command: " . implode("\n", $output);
}
// pause before redirect to display message
#sleep(5);
//store username in sessions
$_SESSION['username'] = $username

// Redirect back to the account page or handle the response as needed
#header("Location: account.php");
#exit;
?>
