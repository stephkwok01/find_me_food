
# Find me food 
## This is an app for a friend who loves classy thai food in New York City 

This app does the following: 
1) Loads the New York City Restaurant Inspection results csv file into a sql database (dohmh). The database to store this data locates in deployed heroku app environment (ClearDB). Database can be 
replicated using 
the schema provided.
The csv file can be found at: 
https://data.cityofnewyork.us/api/views/43nn-pn8j/rows.csv?accessType=DOWNLOAD

2) Performs a lookup in the database for a list of Thai restaurants in New York with a rating of B or higher. This can be done through calling the get_restaurants() function through the application front-end, or through an api call using a curl request. 
To run the curl request, simply put https://find-my-thai-food.herokuapp.com/curl.php in the url. The result will automatically populate the screen

3) Performs a lookup in the database for a list of any restaruants that satisfy the same criteria. This is done through a POST curl request passing cuisine information to the API.

## main scripts
etl.php - etl class for user to pass in csv filename to load into dohmh database. It reads csv file line by line, checks if violation code, inspection type, and cuisine exist in database, and inserts 
data accordingly

lib.php - Includes two funtions: get_restaurants() - function for getting a list of thai restaurants, including query for getting required restaurants 
  get_restaurants_by_cuisine($cuisine) - function for getting any restaurants based on the same criteria

curl.php - performs curl request to api to get list of restaurants. It passes username and password for authentication. For testing purposes, the username and password are included in the function.

api.php - api request to get restaurants. It checks for username and password for security. Then calls function in lib.php to query and return data. 

test.php - test script to check database connection, data duplication, data accuracy, data completeness, the use of array in data transformation, and api user validation. Results of these tests are 
written to logs

## Logs
logs are written from the tests results from test.php. These logs are organized by date, meaning there is a new log written each day if user decides to run the test script

## Schema
There's a .mwb schema provided (find_me_food.mwb) The mysql schema includes 5 tables: restaurant, inspection, cuisine, violation, and inspection_type. 
