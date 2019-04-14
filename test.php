<?php
include_once 'etl.php';
include_once 'dblib.php';

/** test **/
$filePath = "../DOHMH_New_York_City_Restaurant_Inspection_Results.csv";
$file = new etl($filePath);
// $result = $file->csv_to_db();

//API test
//testing wrong credentials 
function testapi(){
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
return $result;
}
$result = testapi();
($result['error'] == 'Unauthorized Access') ? var_dump('test successful') : var_dump("Test failed");


//Test query 
function testquery(){
DBConnect();
$result = DBQuery(
  "SELECT restaurant_id, name, boro, building, street, zipcode, phone, MAX(grade_date) as 'grade_date', grade FROM restaurant
  JOIN cuisine ON FK_cuisine_id = cuisine_id
  JOIN inspection ON restaurant_id = FK_restaurant_id
  WHERE (grade = 'A' OR grade = 'B') AND cuisine.description = 'thai'
  GROUP BY restaurant_id");
return $result;
}
$result = testquery();
(count($result) == 289) ? var_dump('success') : var_dump("fail");




