<?php
/** 
 * GET request to get a list of thai restaurants that satisfy the criteria 
 */
include('lib.php');
header("Content-type: text/plain");

//Validate API caller user & password, return false if not verified
//usually check username & pw in db
if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] != 'testuser' || $_SERVER['PHP_AUTH_PW'] != 'asdf') {
  header("HTTP/1.0 401 Unauthorized");
  die(json_encode(['success'=>FALSE, 'error'=>'Unauthorized Access']));
  exit;
}

//To get post data from curl request
$post = json_decode(@file_get_contents("php://input"), true);

//POST call to get other types of restaurants
if(isset($post['cuisine'])){
  $cuisine = $post['cuisine'];
    //Check headers & content type
  if ($_SERVER['HTTP_ACCEPT'] != 'application/json') die(json_encode(['success'=>false, 'error'=>"Invalid Accept header supplied. Expecting application/json"]));
  if ($_SERVER["CONTENT_TYPE"] != 'application/json') die(json_encode(['success'=>false, 'error'=>"Invalid Content-Type header supplied. Expecting application/json"]));
  //call lib.php get_restaurants function
  $restaurants = get_restaurants_by_cuisine($cuisine);
  if($restaurants == FALSE){
    die(json_encode(['success'=>FALSE, 'error'=>'No restaurants available']));
  }
  die(json_encode(['success'=>TRUE, 'restaurants'=>$restaurants]));
} 
//GET request to only get thai restaurants
else {
  //Check headers & content type
  if ($_SERVER['HTTP_ACCEPT'] != 'application/json') die(json_encode(['success'=>false, 'error'=>"Invalid Accept header supplied. Expecting application/json"]));
  if ($_SERVER["CONTENT_TYPE"] != 'application/json') die(json_encode(['success'=>false, 'error'=>"Invalid Content-Type header supplied. Expecting application/json"]));
  //call lib.php get_restaurants function
  $restaurants = get_restaurants();
  if($restaurants == FALSE){
    die(json_encode(['success'=>FALSE, 'error'=>'No restaurants available']));
  }
  die(json_encode(['success'=>TRUE, 'restaurants'=>$restaurants]));
}








