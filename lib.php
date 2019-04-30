<?php
//Model function to fetch data from db	
include 'config.php';

GLOBAL $SERVER, $USERNAME, $PASSWORD, $DB;
  $conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DB);
  if ($conn->connect_error) {
    return("Connection failed: " . $conn->connect_error);
  } 
  $sql = "DELETE FROM inspection_type";
  $result = $conn->query($sql);
  var_dump($result);
//   while($row = $result->fetch_assoc()) {
// 	var_dump($row);
//       }

/**
 * Gets a list of restaurants that satisfy the criteria
 * @return array  FALSE or array (can be empty)
 */
  function get_restaurants(){
  GLOBAL $SERVER, $USERNAME, $PASSWORD, $DB;
  $conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DB);
  if ($conn->connect_error) {
    return("Connection failed: " . $conn->connect_error);
  } 
    $GLOBALS['last_error'] = '';
    try{
      $restaurants = array();
      $result = $conn->query(
	"SELECT restaurant_id, name, boro, building, street, zipcode, phone, MAX(grade_date) as 'grade_date', grade FROM restaurant
	LEFT JOIN cuisine ON FK_cuisine_id = cuisine_id
	LEFT JOIN inspection ON restaurant_id = FK_restaurant_id
	WHERE (grade = 'A' OR grade = 'B') AND cuisine.description = 'Thai'
	GROUP BY restaurant_id");
      while($row = $result->fetch_assoc()) {
	$restaurants[] = $row;
      }
      return $restaurants;
      
    } catch(Exception $e){
      $GLOBALS['last_error'] = $e->getMessage();
      return FALSE;
    }
  }
/** 
 * Gets a list of restaurants of any cuisine that kind of fits the criteria
 * @param $cuisine - Type of cuisine
 * @return array FALSE or array (can be empty)
 */
 function get_restaurants_by_cuisine($cuisine){
  GLOBAL $SERVER, $USERNAME, $PASSWORD, $DB;
  $conn = new mysqli($SERVER, $USERNAME, $PASSWORD, $DB);
  if ($conn->connect_error) {
    return("Connection failed: " . $conn->connect_error);
  } 
  $GLOBALS['last_error'] = '';
  try{
    $restaurants = array();
    $result = $conn->query(
      "SELECT restaurant_id, name, boro, building, street, zipcode, phone, MAX(grade_date) as 'grade_date', grade FROM restaurant
	LEFT JOIN cuisine ON FK_cuisine_id = cuisine_id
	LEFT JOIN inspection ON restaurant_id = FK_restaurant_id
	WHERE (grade = 'A' OR grade = 'B') AND cuisine.description LIKE '%".$cuisine."%'
	GROUP BY restaurant_id");
    while($row = $result->fetch_assoc()) {
      $restaurants[] = $row;
    }
    return $restaurants;

  } catch(Exception $e){
    $GLOBALS['last_error'] = $e->getMessage();
    return FALSE;
  }
}
