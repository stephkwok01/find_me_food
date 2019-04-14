<?php
/** 
 * Curl request for get_restaurants()
*/
//Curl request url points to my local server
$url = 'http://charles.plumgroup.com/~skwok/personal/find_me_food/api.php';

//Added username and pw in curl script for test purposes
$username = 'testuser';
$password = 'asdf';

$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json", "Content-type: application/json"));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = json_encode(curl_exec($ch), true);
curl_close($ch);

var_dump($result);



