# Find me food 
## This is an app for a friend who loves classy thai food

This app does the following: 
1) Loads the New York City Restaurant Inspection results csv file into a sql database (DOHMH). The database to store this data locates in a remote development server from my current work.
The csv can be found at: 
https://data.cityofnewyork.us/api/views/43nn-pn8j/rows.csv?accessType=DOWNLOAD

2) Performs a lookup in the database for a list of Thai restaurants in New York with a rating of B or higher. This can be done through calling the get_restaurants() function through an application front-end, or through an api call using a curl request.

## main scripts
dblib.php - database library for mysql db functions
etl.php - etl class for user to pass in csv filename to load into DOHMH database. It reads csv file line by line, checks if violation code, inspection type, and cuisine exist in database, and inserts data accordingly
lib.php - function for getting a list of thai restaurants, including query for getting required restaurants
curl.php - performs curl request to api to get list of restaurants. It passes username and password for authentication
api.php - api request to get restaurants. It checks for username and password for security. 

## Schema
The database schema is included (DOHMH.png). This mysql schema includes 5 tables, restaurant, inspection, cuisine, violation, and inspection_type. 
