<?php
    // Start the session
    session_start();
    
    // Check if the request is coming from a logged-in user, etc.

    // Path to your shell script
    $script_path = '/volume2/web/AilonaTuner/run_script.sh';

    // Path to your Python script
    $python_script_path = '/volume2/web/AilonaTuner/get_stravadata.py';

    // Execute the shell script with the Python script as an argument
    $output = null;
    $resultCode = null;
    exec($script_path . " 2>&1", $output, $resultCode);
    if ($resultCode !== 0) {
        echo "Error executing command: " . implode("\n", $output);
    }

    if ($resultCode == 0) {
        echo "Sync successful!";
    } else {
        echo "An error occurred during sync.";
        echo "Error executing command: " . implode("\n", $output);
    }
    // pause before redirect to display message
    sleep(5);

    // Redirect back to the account page or handle the response as needed
    header("Location: account.php");
    exit;

?>