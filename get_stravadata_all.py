import requests
import mysql.connector
import datetime
import logging
import sys

logging.basicConfig(filename='strava_sync.log', level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')



user_id = sys.argv[1]
#user_id = '64'
# Connect to the MariaDB
db = mysql.connector.connect(
    host="10.0.0.66",
    user="tunerapp",
    password="sN*mnswB7SHipiRB",
    database="suspension_tuner",
    port=3307,
)

cursor = db.cursor()
client_id = '104692'
client_secret = '56b2353bfa03688593000011e5204fd168dfb6e3'

#
logging.info('Starting the FULL Sync for user_id:')
# Get the Strava athlete ID and token for userid 
cursor.execute("SELECT id, strava_token, refresh_token, first_name, last_activity_sync, profile_pic FROM users WHERE id = %s ", (user_id,))
#pass userid to query
users = cursor.fetchall()


newactivitycount = 0
updated_activity_count = 0
for user in users:
    user_id, strava_token, refresh_token, first_name, last_activity_sync, profile_pic = user
    logging.info(user_id)


    # Convert date to datetime at midnight


    # Get the activities since the last service
    response = requests.get(
        "https://www.strava.com/api/v3/athlete/activities",
        headers={"Authorization": f"Bearer {strava_token}"},
        params={"page": 1, "per_page": 200}
    )
    activities = response.json()
    # Check for an authorization error
    if 'message' in activities and activities['message'] == 'Authorization Error':
        # Refresh the token
        refresh_response = requests.post(
            "https://www.strava.com/oauth/token",
            data={
                'client_id': client_id,
                'client_secret': client_secret,
                'refresh_token': refresh_token,
                'grant_type': 'refresh_token'
            }
        )
        refresh_data = refresh_response.json()
        new_strava_token = refresh_data['access_token']
        strava_token = new_strava_token
        # Update the token in the database
        cursor.execute(
            "UPDATE users SET strava_token = %s WHERE id = %s", (new_strava_token, user_id))
        db.commit()

    

        # Retry the original request with the new token
        response = requests.get(
            "https://www.strava.com/api/v3/athlete/activities",
            headers={"Authorization": f"Bearer {strava_token}"},
            params={"page": 1, "per_page": 200}
        )
        activities = response.json()
        
        
    #print(activities)


    # Store the new activities in the database
    for activity in activities:
        print(activity['type'])
        #get gear id from activity
        
        #check if gear id is in gear table
       

        #convert date to datetime
        activity_date = datetime.datetime.strptime(activity['start_date'], '%Y-%m-%dT%H:%M:%SZ')
        
        # Check if type is 'Ride' and if the activity already exists
        if activity['type'] == 'Ride' or activity['type'] == 'Mountain Bike':
            gear_id = activity['gear_id']
        # Get detailed information about the activity
            #activity_detail_response = requests.get(
                #f"https://www.strava.com/api/v3/activities/{activity['id']}",
                #headers={"Authorization": f"Bearer {strava_token}"}
            #)
            #activity_detail = activity_detail_response.json()
            gear_id = activity.get('gear_id')
            

            #print activity suffer_score
            #print(activity)
            if activity['has_heartrate'] == False:
                activity['average_heartrate'] = 0
                activity['max_heartrate'] = 0
                activity['suffer_score'] = 0
            # if average_watts not exisit in activinty set to 0 
            if activity.get('average_watts') is None:
                activity['average_watts'] = 0
            

            # Check if the activity already exists
            cursor.execute(
                "SELECT id FROM activities WHERE strava_activity_id = %s", (activity['id'],))
            if cursor.fetchone() is None and gear_id is not None:
                # Optionally, get the gear name
                #check if gear_id is in gear table
                cursor.execute("SELECT DISTINCT gear_name FROM activities WHERE gear_id = %s", (gear_id,))
                gears = cursor.fetchall()
                
                if gears:
                    gear_name = gears[0][0]
                else:
                    gear_name = None
                    print(f"No gear_name found for gear_id {gear_id}")

                if gear_name is None:
                    print("querying strava for gear name")
                    gear_name_response = requests.get(
                        f"https://www.strava.com/api/v3/gear/{gear_id}",
                        headers={"Authorization": f"Bearer {strava_token}"}
                    )
                    gear_name = gear_name_response.json().get('name')

                try:
                    suffer_score = activity['suffer_score']
                except KeyError:
                    suffer_score = 0 # some default value or logic here

                newactivitycount = newactivitycount + 1

                cursor.execute(
                    "INSERT INTO activities (user_id, strava_activity_id, moving_time, activity_date, distance, gear_id, gear_name, total_elevation_gain, average_speed, max_speed, workout_type, average_watts, average_heartrate, max_heartrate, suffer_score, sport_type) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                    (user_id, activity['id'], activity['moving_time'], activity_date, activity['distance'], gear_id, gear_name, activity['total_elevation_gain'], activity['average_speed'], activity['max_speed'], activity['workout_type'], activity['average_watts'], activity['average_heartrate'], activity['max_heartrate'], suffer_score, activity['sport_type'])
                )
                print(f"Added activity {activity['id']}")
            else:
                print(f"Updating Activity {activity['id']} ")
                updated_activity_count = updated_activity_count + 1
                # Check for suffer_score instead of max_suffer_score
                cursor.execute("SELECT sport_type FROM activities WHERE strava_activity_id = %s", (activity['id'],))
                suffer_score = cursor.fetchone()
                if suffer_score is not None and suffer_score[0] is None:
                    # update null data
                    cursor.execute(
                    "UPDATE activities SET total_elevation_gain = %s, average_speed = %s, max_speed = %s, workout_type = %s, average_watts = %s, average_heartrate = %s, max_heartrate = %s, suffer_score = %s, sport_type = %s WHERE strava_activity_id = %s",
                    (activity['total_elevation_gain'], activity['average_speed'], activity['max_speed'], activity['workout_type'], activity['average_watts'], activity['average_heartrate'], activity['max_heartrate'], activity['suffer_score'], activity['sport_type'], activity['id'])
                )
            if first_name is None:
                # get user profile infomartion
                profile_response = requests.get(
                    "https://www.strava.com/api/v3/athlete",
                    headers={"Authorization": f"Bearer {strava_token}"}
                )
                profile_data = profile_response.json()
                print(profile_data)
                first_name = profile_data['firstname']
                profile_pic = profile_data['profile_medium']
                #update database with first name
                cursor.execute(
                    "UPDATE users SET first_name = %s, profile_pic = %s WHERE id = %s", (first_name, profile_pic, user_id))
            #update last_activity_sync date
            # update last_activity_sync date
            cursor.execute(
                "UPDATE users SET last_activity_sync = %s WHERE id = %s", (datetime.datetime.now(), user_id))
                
db.commit()
logging.info('Finished successfully')
logging.info(f"Added {newactivitycount} new activities")

print(f"Updated {updated_activity_count} activities")
print("Done")