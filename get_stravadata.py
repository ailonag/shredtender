import requests
import mysql.connector
import datetime
import logging
import sys

logging.basicConfig(filename='strava_sync.log', level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

userid = '35'
logging.info('Starting the script')

# Connect to the MariaDB
db = mysql.connector.connect(
    host="10.0.0.66",
    user="tunerapp",
    password="sN*mnswB7SHipiRB",
    database="suspension_tuner",
    port=3307,
)

cursor = db.cursor()

#

# Get the Strava athlete ID and token for userid 
cursor.execute("SELECT users.id, strava_token, last_service_date FROM users LEFT JOIN suspension_settings ON users.id = suspension_settings.user_id WHERE user_id = %s AND last_service_date IS NOT NULL", (userid,))
#pass userid to query
users = cursor.fetchall()


for user in users:
    user_id, strava_token, last_service_date = user

    # Convert date to datetime at midnight
    last_service_datetime = datetime.datetime.combine(
        last_service_date, datetime.time())
    #get unique gear id's and names
    

    


    # Get the activities since the last service
    response = requests.get(
        "https://www.strava.com/api/v3/athlete/activities",
        headers={"Authorization": f"Bearer {strava_token}"},
        params={"after": last_service_datetime.timestamp(), "per_page": 200}
    )
    activities = response.json()
    newactivitycount = 0

    print(len(activities))
    # Store the new activities in the database
    for activity in activities:
        
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
            newactivitycount = newactivitycount + 1

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
                
                gear_name = gears[0][0]
                #print(gear_name)

                if gear_name is None:
                    print("querying strava for gear name")
                    gear_name_response = requests.get(
                        f"https://www.strava.com/api/v3/gear/b8105977",
                        headers={"Authorization": f"Bearer {strava_token}"}
                    )
                    gear_name = gear_name_response.json().get('name')
                cursor.execute(
                    "INSERT INTO activities (user_id, strava_activity_id, moving_time, activity_date, distance, gear_id, gear_name, total_elevation_gain, average_speed, max_speed, workout_type, average_watts, average_heartrate, max_heartrate, suffer_score) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                    (user_id, activity['id'], activity['moving_time'], activity_date, activity['distance'], gear_id, gear_name, activity['total_elevation_gain'], activity['average_speed'], activity['max_speed'], activity['workout_type'], activity['average_watts'], activity['average_heartrate'], activity['max_heartrate'], activity['suffer_score'])
                )
                print(f"Added activity {activity['id']}")
            else:
                print(f"Updating Activity {activity['id']} ")
                # Check for suffer_score instead of max_suffer_score
                cursor.execute("SELECT suffer_score FROM activities WHERE strava_activity_id = %s", (activity['id'],))
                suffer_score = cursor.fetchone()
                if suffer_score is not None and suffer_score[0] is None:
                    # update null data
                    cursor.execute(
                    "UPDATE activities SET total_elevation_gain = %s, average_speed = %s, max_speed = %s, workout_type = %s, average_watts = %s, average_heartrate = %s, max_heartrate = %s, suffer_score = %s WHERE strava_activity_id = %s",
                    (activity['total_elevation_gain'], activity['average_speed'], activity['max_speed'], activity['workout_type'], activity['average_watts'], activity['average_heartrate'], activity['max_heartrate'], activity['suffer_score'], activity['id'])
                )

db.commit()
logging.info('Finished successfully')
logging.info(f"Added {newactivitycount} new activities")
print(f"Added {newactivitycount} new activities")
print("Done")

