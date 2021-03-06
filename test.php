<?php
include_once 'etl.php';
include 'config.php';

/** 
 * Create log file for test results in logs/ directory
 */
$today = date("Y-m-d");
$LOGFILE = 'test_results_'.$today.'.log';
if(file_exists('logs/'.$LOGFILE) == false){
  $loghandle = fopen('logs/'.$LOGFILE, 'w') or die('cannot open file');
}

/** 
 * Test ETL function - writing data to database
 */
$filePath = "../DOHMH_New_York_City_Restaurant_Inspection_Results.csv";
$file = new etl($filePath);
// $result = $file->csv_to_db();  TODO: uncomment it to test etl function 

/** 
 * Test database connection 
 */
function checkDBConnection(){
  GLOBAL $LOGFILE;
  //Connect to Heroku database
  GLOBAL $SERVER, $USERNAME, $PASSWORD, $DB;
  $conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DB);
  
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test heroku database connection: checkDBConnection()----------\n");
  
  //Print db credentials
  fwrite($loghandle, "host: $SERVER \n");
  fwrite($loghandle, "username: $USERNAME \n");
  fwrite($loghandle, "password: $PASSWORD \n");
  fwrite($loghandle, "database name: $DB \n");
  
  if ($conn->connect_error) {
    fwrite($loghandle, "Connection failed: " . $conn->connect_error);
    ("Connection failed: $conn->connect_error \n\n");
    return false;
  }
  fwrite($loghandle, "Heroku database connected \n\n");
  return true;
}
checkDBConnection();


/** 
 * duplicate test for different tables in the db
 * Make sure cuisine, violation, inspection type, restaurant aren't duplicated in database
 */
function checkDupliate() {
  GLOBAL $LOGFILE;
  //Connect to Heroku database
  GLOBAL $SERVER, $USERNAME, $PASSWORD, $DB;
  $conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DB);
  if ($conn->connect_error) {
    return("Connection failed: " . $conn->connect_error);
  } 
  
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test duplicate: checkDupliate()----------\n");
 
  $success = true;
  //Finding dupicate in cuisine
  $result = $conn->query(
    "SELECT description, COUNT(*) AS c FROM cuisine GROUP BY description HAVING c >1 ");
  if ($result->num_rows > 0) {
      // output data of each row
      while($row = $result->fetch_assoc()) {
	 fwrite($loghandle, "Duplicated description: ".$row['description']. ", in cuisine\n");
      }
      $success = false;
  }

  //Find ducplicate in violation code
  $result = $conn->query(
    "SELECT code, COUNT(*) AS c FROM violation GROUP BY code having c>1;");
  if ($result->num_rows > 0) {
      // output data of each row
      while($row = $result->fetch_assoc()) {
	 fwrite($loghandle, "Duplicated description: ".$row['code']. ", in violation\n");
      }
      $success = false;
  }
  //Find duplicate in inspection type
  $result = $conn->query(
    "SELECT type_name, COUNT(*) AS c FROM inspection_type GROUP BY type_name having c>1");
  if ($result->num_rows > 0) {
      // output data of each row
      while($row = $result->fetch_assoc()) {
	 fwrite($loghandle, "Duplicated description: ".$row['type_name']. ", in inspection_type\n");
      }
      $success = false;
  }
  //Find duplicate in restaurant names
  $result = $conn->query(
    "SELECT camis, count(*) AS c FROM restaurant GROUP BY camis having c>1");
  if ($result->num_rows > 0) {
      // output data of each row
      while($row = $result->fetch_assoc()) {
	 fwrite($loghandle, "Duplicated description: ".$row['camis']. ", in restaurant\n");
      }
      $success = false;
  }
  if($success == true){
    fwrite($loghandle, "No duplicated data in tables: restaurant, inspection_type, violation, cuisine\n");
  }
  fwrite($loghandle, "\n");
  return $success;
}
//Call checkDupliate function
$dup_result = checkDupliate();

/** 
 * Test data accuracy- Check random points in test dataset to make sure it matches database values
 * input data: 
 * 	Array( camis, dba, boro, building, street, zipcode, phone, description, inspection_date, action,violation, code,
 * 		violation_description, socre, grade, grade_date, record_date, inspection, inspection_type)
 */	
function test_data_accuracy(){
  GLOBAL $LOGFILE;
  //Connect to Heroku database
  GLOBAL $SERVER, $USERNAME, $PASSWORD, $DB;
  $conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DB);
  if ($conn->connect_error) {
    return("Connection failed: " . $conn->connect_error);
  } 
  
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test data accuracy: test_data_accuracy()----------\n");
  
  //Open test csv file 
  $filename = 'test_data_DOHMH.csv';
  $rowNum = 0;
  $success = true;

  if (($handle = fopen($filename, 'r')) !== FALSE)
  {
    while (($row = fgetcsv($handle)) !== FALSE)
    {
      if($rowNum > 0){
	$camis = $row[0];
	$violation_code = $row[10];
	$date = $row[8];
	//Convert date format
	$newDate = substr($date, 6,4).'-'.substr($date,0,2).'-'.substr($date,3,2);
	//Create array for csv file to compare with query result
	$csvData = array(
	  "name"=>$row[1],
	  "phone"=>$row[6], 
	  "description"=>$row[7],
	  "date"=>$newDate,
	  "inspection_type"=>$row[17],
	  'violation_code'=>$row[10],
	  'grade'=>$row[14]
	);
	//Get restaurant info from database to see if it matches
	$result = $conn->query(
	  "SELECT name, phone, cuisine.description, date, inspection_type.type_name as inspection_type,
	  violation.code AS violation_code, grade
	  FROM restaurant
	  JOIN cuisine ON FK_cuisine_id = cuisine_id
	  JOIN inspection ON FK_restaurant_id = restaurant_id
	  JOIN violation ON FK_violation_id = violation_id 
	  JOIN inspection_type ON FK_inspection_type_id = inspection_type_id
	  WHERE camis = '".$camis."' AND date = '".$newDate."' AND violation.code = '".$violation_code."'");
	if ($result->num_rows == 1) {
	  while($res = $result->fetch_assoc()) {
	    $diff = array_diff($csvData, $res);
	    if(!empty($diff)){
	      $success = false;
	      fwrite($loghandle, "difference between csv data and database for camis ".$camis.":\n" );
	      foreach($diff as $key=>$val){
		fwrite($loghandle, $key."-> ".$val."\n");
	      }
	      fwrite($loghandle,"\n");
	    }
	  }
	}
      }
      $rowNum++;
    }
  }
  if($success == true){
    fwrite($loghandle, "No difference between db data and csv data\n\n");
  }
  return $success;
}
$accuracy_res = test_data_accuracy();

/** 
 * Check upload completeness
 * Complete data would have 383522 inspections in database
 */
function check_data_completeness(){
  GLOBAL $LOGFILE;
  //Connect to Heroku database
  GLOBAL $SERVER, $USERNAME, $PASSWORD, $DB;
  $conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DB);
  if ($conn->connect_error) {
    return("Connection failed: " . $conn->connect_error);
  } 
  
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test data completeness: check_data_completeness()----------\n");
  
  //Connect to database and check count
  $result = $conn->query(
    "SELECT COUNT(*) AS count FROM inspection");
  if ($result->num_rows != 1) {
    fwrite($loghandle, "Error: DB error has occurred\n");
    return false;
  }
  // output data of each row
  while($row = $result->fetch_assoc()) {
    $count = $row['count'];
    if($count == 383522){
      fwrite($loghandle, "Complete set of data\n\n");
      return true;
    } else {
      fwrite($loghandle, "Incomplete set of data\n");
      fwrite($loghandle, "Data count: $count \n\n");
    }
  }

  return false;
}
$complete_res = check_data_completeness();

/** 
 * Test data transformation using arrays 
 * Reads a new csv file and make sure information from each row will be upload to the right db
 * It follows the general etl function but without database inserts
 * Instead it counts the number of rows to be inserted to make sure it matches data from csv file
 */
function test_data_transformation(){
  GLOBAL $LOGFILE;
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test data transformation: test_data_transformation()----------\n");
  
  //Initialized count var & array
  $filename = 'test_transformation.csv';
  $rowNum = 0;
  $violation_count = 0;
  $cuisine_count = 0;
  $inspection_type_count = 0;
  
  $violation = array();
  $inspection_type = array();
  $cuisine = array();
  $camis = array();
      
  if (($handle = fopen($filename, 'r')) !== FALSE)
  {
    while (($row = fgetcsv($handle)) !== FALSE)
    {
      if($rowNum > 0){
	//Check if cuisine exists in db; insert if doesn't exist
	if(empty($cuisine) || (in_array($row[7], $cuisine) == false)){
	    $cuisine_count++;
	  $cuisine[] = $row[7];
	} else {
	  $cuisine_id = NULL;
	}
	
	//Check if violation exists in db; insert if doesn't exist
	if(empty($violation) || in_array($row[10], array_column($violation, 'code')) == false){
	  $violation_count++;
	  $violation[] = array(
	    'code'=>$row[10],
	    'description'=>$row[11]
	  );
	} else {
	  $violation_id = NULL;
	}
	//Check if inspection type exists; insert if doesn't exist
	if(empty($inspection_type) || (in_array($row[17], $inspection_type) == false )){
	  $inspection_type_count++;
	  $inspection_type[] = $row[17];
	} else {
	  $inspection_id = NULL;
	}
      }
      $rowNum++;
    }
  }
  fwrite($loghandle, "Number of cuisine rows written in db: $cuisine_count \n");
  fwrite($loghandle, "Number of violation rows written in db: $violation_count \n");
  fwrite($loghandle, "Number of inspections type rows written in db: $inspection_type_count \n\n");
}
test_data_transformation();


/** 
 * Test api call with wrong credentials to make sure that users are properly authenticated
 */
function testapi(){
  GLOBAL $LOGFILE;
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test API call with wrong credentials: testapi()----------\n");
  $url = 'http://charles.plumgroup.com/~skwok/personal/find_me_food/api.php';
  
  $username = 'wronguser';
  $password = 'asdf';

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
  curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json", "Content-type: application/json"));
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $result = json_decode(curl_exec($ch),true);
  if($result['error']){
    fwrite($loghandle, "Unauthorized Access; test successful\n\n");
    return true;
  }
  fwrite($loghandle, "authentication failed\n\n");
  return false;
 
}
$result = testapi();


 


 
 
 
 
 
