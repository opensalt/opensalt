Firebase
--------

In order to set up the real-time notification feature for editors there
has to be at least one supported transport available.

Google's Firebase realtime database is the initial transport
implementation that has been developed.  The free tier should suffice
for low volume use, it includes 100 simulateous connections, 1 GB of
data storage, and 10 GB of data transfer per month.

To set up firebase:

1. Go to https://console.firebase.google.com/ to create a project
   (requires a Google account)
2. Click "Add project"
3. Give the project a name
4. Click "CREATE PROJECT"
5. Click "Add Firebase to your web app"
   - Copy the config information to somewhere you can find it
6. Click "Database" on the left menu
7. Click "GET STARTED" to setup the database
8. Click "RULES" in the top menu
9. Set the rules to the following and click "PUBLISH"
    ``` json
    {
      "rules": {
        ".read": true,
        ".write": false,
        "doc": {
          "$docid": {
            "notification": {
              ".indexOn": ["at"]
            }
          }
        },
        "$serverpfx": {
          "doc": {
            "$docid": {
              "notification": {
                ".indexOn": ["at"]
              }
            }
          }
        }
      }
    }
    ```
10. Click the gear icon next to "Project Overview" and select "Project Settings"
11. Click "SERVICE ACCOUNTS" in the top menu
12. Click "GENERATE NEW PRIVATE KEY"
13. Save the generated file
14. Open the generated file and config information copied earlier
15. Edit `docker/.env` and set the following environment variables (do not put quotes around the values):
    - FIREBASE_API_KEY to be the **apiKey** from the copied config
    - FIREBASE_AUTH_DOMAIN to be the **authDomain** from the copied config
    - FIREBASE_DB_URL - to be the **databaseURL** from the copied config
    - FIREBASE_PROJECT_ID - to be the **project_id** from the key file
    - FIREBASE_CLIENT_ID - to be the **client_id** from the key file
    - FIREBASE_CLIENT_EMAIL - to be the **client_email** from the key file
    - FIREBASE_PRIVATE_KEY - to be the **private_key** from the key file
16. Restart the docker containers to have the environment variables read
    - `docker-compose down -v; docker-compose up -d`
