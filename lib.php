<?php
//Model function to fetch data from db
include 'dblib.php';

/**
 * Gets a list of restaurants that satisfy the criteria 
 * @param string $type  - the type of cuisine 
 * @param rating $grade - the minimum grade to accept 
 * @return arrray  FALSE or array (can be empty)
 */
  function get_restaurants(){
    $GLOBALS['last_error'] = '';
    try{
      DBConnect();
      $result = DBQuery(
	"SELECT restaurant_id, name, boro, building, street, zipcode, phone, MAX(grade_date) as 'grade_date', grade FROM restaurant
	JOIN cuisine ON FK_cuisine_id = cuisine_id
	JOIN inspection ON restaurant_id = FK_restaurant_id
	WHERE (grade = 'A' OR grade = 'B') AND cuisine.description = 'thai'
	GROUP BY restaurant_id");
      if (count($result)) {
	$restaurants = array();
	foreach($result as $res){
	  $restaurants[] = $res;
	}
	return $restaurants;
      } else {
	//no restaurants found
	$GLOBALS['last_error'] = "no restaurant found";
	return FALSE;
      }
    } catch(Exception $e){
      $GLOBALS['last_error'] = $e->getMessage();
      return FALSE;
    }
  }

