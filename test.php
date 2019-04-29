<?php
include_once 'etl.php';
include_once 'dblib.php';

/** 
 * Create log file for test results
 */
$today = date("Y-m-d");
$LOGFILE = 'test_results_'.$today.'.log';

/** 
 * Test ETL function - writing data to database
 */
$filePath = "../DOHMH_New_York_City_Restaurant_Inspection_Results.csv";
$file = new etl($filePath);
// $result = $file->csv_to_db();

/** 
 * duplicate test for different tables in the db
 * Make sure cuisine, violation, inspection type, restaurant aren't duplicated in database
 */
function checkDupliate() {
  GLOBAL $LOGFILE;
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test duplicate: checkDupliate()----------\n");
  
  DBConnect();
  $success = true;
  //Finding dupicate in cuisine
  $result = DBQuery(
    "SELECT description, COUNT(*) AS c FROM cuisine GROUP BY description HAVING c >1 ");
  if(count($result) > 0){
    foreach($result as $res){
      fwrite($loghandle, "Duplicated description: ".$res['description']. ", in cuisine\n");
    }
    $success = false;
  }
  //Find ducplicate in violation code
  $result = DBQuery(
    "SELECT code, COUNT(*) AS c FROM violation GROUP BY code having c>1;");
  if(count($result) > 0){
    foreach($result as $res){
      fwrite($loghandle,"Duplicate code: ".$res['code'].", in violation\n");
    }
    $success = false;
  }
  //Find duplicate in inspection type
  $result = DBQuery(
    "SELECT type_name, COUNT(*) AS c FROM inspection_type GROUP BY type_name having c>1");
  if(count($result) > 0){
    foreach($result as $res){
      fwrite($loghandle,"Duplicate inspection type: ". $res['type_name'].", in inspection_type\n");
    }
    $success = false;
  }
  //Find duplicate in restaurant names
  $result = DBQuery(
    "SELECT camis, count(*) AS c FROM restaurant GROUP BY camis having c>1");
  if(count($result) > 0){
    foreach($result as $res){
      fwrite($loghandle,"Duplicate restaurant camis: ".$res['camis'].", in restaurant\n");
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
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test data accuracy: test_data_accuracy()----------\n");
  
  //Open test csv file 
  $filename = 'test_data_DOHMH.csv';
  $rowNum = 0;
  $success = true;
  DBConnect();
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
	$result = DBQuery(
	  "SELECT name, phone, cuisine.description, date, inspection_type.type_name as inspection_type,
	  violation.code AS violation_code, grade
	  FROM restaurant
	  JOIN cuisine ON FK_cuisine_id = cuisine_id
	  JOIN inspection ON FK_restaurant_id = restaurant_id
	  JOIN violation ON FK_violation_id = violation_id 
	  JOIN inspection_type ON FK_inspection_type_id = inspection_type_id
	  WHERE camis = ? AND date = ? AND violation.code = ?", $camis, $newDate, $violation_code);
	if(count($result) == 1){
	  $diff = array_diff($csvData, $result[0]);
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
  //Open log file 
  $loghandle = fopen('logs/'.$LOGFILE, 'a+') or die('cannot open file');
  fwrite($loghandle, "----------Test data completeness: check_data_completeness()----------\n");
  
  //Connect to database and check count
  DBConnect();
  $result = DBQuery(
    "SELECT COUNT(*) AS count FROM inspection");
  if(count($result) != 1){
    fwrite($loghandle, "Error: DB error has occurred\n");
    return false;
  }
  if($result[0]['count'] == 383522){
    fwrite($loghandle, "Complete set of data\n\n");
    return true;
  }
  fwrite($loghandle, "Incomplete set of data\n\n");
  return false;
}
$complete_res = check_data_completeness();




/** 
 * Test api call with wrong credentials
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


 


 
 
 
 
 
