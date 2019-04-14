<?php
include 'dblib.php';

class etl{
  private $filename;
  
  //create new etl object 
  public function __construct($filename)
    {
        if (!file_exists($filename)) {
            throw new Exception("File not found");
        }
        $this->filename = $filename;
    }
 
  //Read csv file, transform date, and insert into DOHMH db
  function csv_to_db(){
    try{
      //if item is in array, it's in the database also
      $csv = array();
      $violation = array();
      $inspection_type = array();
      $cuisine = array();
      $camis = array();
      
      DBConnect();
      $start_time = new datetime();
      //Read csv file
      if (($handle = fopen($this->filename, 'r')) !== FALSE)
      {
	while (($row = fgetcsv($handle)) !== FALSE) 
	{
	  if($rowNum >0){
	    //Check if cuisine exists in db; insert if doesn't exist
	    if(empty($cuisine) || (in_array($row[7], $cuisine) == false)){
	      $cuisine_id = DBQuery("INSERT INTO cuisine (description) VALUES (?)", $row[7]);
	      $cuisine[$cuisine_id] = $row[7];
	    } else {
	      $cuisine_id = NULL;
	    }
	    //Check if violation exists in db; insert if doesn't exist
	    if(empty($violation) || in_array($row[10], array_column($violation, 'code')) == false){
	      $violation_id = DBQuery("INSERT INTO violation (`code`, `description`) VALUES (?,?)", $row[10], $row[11]);
	      $violation[$violation_id] = array(
		'code'=>$row[10],
		'description'=>$row[11]
	      );
	    } else {
	      $violation_id = NULL;
	    }
	    //Check if inspection type exists; insert if doesn't exist
	    if(empty($inspection_type) || (in_array($row[17], $inspection_type) == false )){
	      $inspection_id = DBQuery("INSERT INTO inspection_type(`type_name`) VALUES (?)", $row[17]);
	      $inspection_type[$inspection_id] = $row[17];
	    }
	    //Insert restaurant information 
	    //check if restaurant camis exists in database
	    if(empty($camis) || (in_array($row[0], $camis) == false)){
	      //Check if cuisine exist in db
	      if($cuisine_id != NULL){
		$FK_cuisine_id = $cuisine_id;
	      } else {
		$result = DBQuery(
		  "SELECT cuisine_id FROM cuisine 
		  WHERE description = ?", $row[7]);
		$FK_cuisine_id = $result[0]['cuisine_id'];
	      }
	      
	      $restaurant_id = DBQuery(
	      "INSERT INTO restaurant (camis, name, boro, building, street, zipcode, phone, FK_cuisine_id)
	      VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6],$FK_cuisine_id);
	      //add restaurant id as key in camis array
	      $camis[$restaurant_id] = $row[0];
	    }
	    //Insert insepction information 
	    //Check violation data
	    if($violation_id != NULL){
	      $FK_violation_id = $violation_id;
	    } else {
	      $result = DBQuery(
		"SELECT violation_id FROM violation
		WHERE code = ?", $row[10]);
	      $FK_violation_id = $result[0]['violation_id'];
	    }
	    if($inspection_id != NULL){
	      
	      $FK_inspection_id = $inspection_id;
	    } else {
	      $result = DBQuery(
		"SELECT inspection_type_id FROM inspection_type
		WHERE type_name = ?", $row[17]);
	      $FK_inspection_id = $result[0]['inspection_type_id'];
	    }
	    //check if dates are empty in csv; convert to mysql DATE format
	    ($row[15] == '') ? $grade_date = NULL : $grade_date = date("Y-m-d", strtotime($row[15]));
	    ($row[16] == '') ? $record_date = NULL: $record_date = date("Y-m-d", strtotime($row[16]));
	    
	    $inpection_id = DBQuery(
	      "INSERT INTO inspection (FK_restaurant_id, date, action, FK_violation_id, critical_flag, score, 
		grade, grade_date, record_date, FK_inspection_type_id) VALUES (?,?,?,?,?,?,?,?,?,?)",
		$restaurant_id, date("Y-m-d", strtotime($row[8])), $row[9], $FK_violation_id, $row[12], $row[13], $row[14], $grade_date, $record_date, 
$FK_inspection_id);
	  }
	  $rowNum++;
	}
	$end_time = new datetime();
	return $end_time - $start_time;
      } else {
	return false;
      }
    } catch(Exception $e){
      echo($e);
      return false;
    
    }
  }
}

/** test **/
$filePath = "../DOHMH_New_York_City_Restaurant_Inspection_Results.csv";
$file = new etl($filePath);
$result = $file->csv_to_db();
var_dump($result);

