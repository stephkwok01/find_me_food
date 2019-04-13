<?php
//Model function to fetch data from db
include 'dblib.php';

/**
 * Gets a list of restaurants that satisfy the criteria 
 * @param string $type  - the type of cuisine 
 * @param rating $grade - the minimum grade to accept 
 * @return mixed  FALSE OR array (can be empty)
 */
  function get_restaurants($type, $grade){
    $GLOBALS['last_error'] = '';
    try{
      DBConnect();
//       $result = DBQuery(
// 	"");

    } catch(Exception $e){
      $GLOBALS['last_error'] = $e->getMessage();
      return FALSE;
    }
  }
