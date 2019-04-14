<?php
/** 
 * Curl request for get_restaurants()
 */
$url = 'https://find-my-thai-food.herokuapp.com/api.php';
$username = 'testuser';
$password = 'asdf';

$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, $username.":".$password);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json", "Content-type: application/json"));
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = curl_exec($ch);
curl_close($ch);


