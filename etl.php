<?php
function get_csv($file){
  try{
    $csv = array();
    $handle = fopen($file, "r");
    while(($data = fgetcsv($handle, 100, ",")) !== FALSE)
    {
	$num = count($csv);
	var_dump($num);
	var_dump($csv[$num-1]);
        $csv[] = $data;
    }

        fclose($handle);
  
  } catch(Exception $e){
    return false;
  
  }
  
  return $csv;

}

// $result = get_csv('DOHMH_New_York_City_Restaurant_Inspection_Results.csv');
// var_dump($result);
