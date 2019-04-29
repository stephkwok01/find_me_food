<?php
//Model function to fetch data from db
include 'dblib.php';

/**
 * Gets a list of restaurants that satisfy the criteria
 * @return array  FALSE or array (can be empty)
 */
  function get_restaurants(){
    $GLOBALS['last_error'] = '';
    try{
      DBConnect();
      $result = DBQuery(
	"SELECT restaurant_id, name, boro, building, street, zipcode, phone, MAX(grade_date) as 'grade_date', grade FROM restaurant
	LEFT JOIN cuisine ON FK_cuisine_id = cuisine_id
	LEFT JOIN inspection ON restaurant_id = FK_restaurant_id
	WHERE (grade = 'A' OR grade = 'B') AND cuisine.description = 'Thai'
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

/** 
 * Gets a list of restaurants of any cuisine that kind of fits the criteria
 * @param $cuisine - Type of cuisine
 * @return array FALSE or array (can be empty)
 */
 function get_restaurants_by_cuisine($cuisine){
  $GLOBALS['last_error'] = '';
  try{
    DBConnect();
    $result= DBQuery(
      "SELECT restaurant_id, name, boro, building, street, zipcode, phone, MAX(grade_date) as 'grade_date', grade FROM restaurant
	LEFT JOIN cuisine ON FK_cuisine_id = cuisine_id
	LEFT JOIN inspection ON restaurant_id = FK_restaurant_id
	WHERE (grade = 'A' OR grade = 'B') AND cuisine.description LIKE ?
	GROUP BY restaurant_id", '%'.$cuisine.'%');
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
