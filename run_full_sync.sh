#!/bin/bash
source /volume2/homes/ailona/env311/bin/activate
# Get the user ID from the command-line arguments
user_id="$1"

# Path to your Python script
python_script_path="/volume2/web/AilonaTuner/get_stravadata_all.py"

# Execute the Python script with the user ID as an argument
python3 "$python_script_path" "$user_id"