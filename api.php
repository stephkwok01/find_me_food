<?php
include('lib.php');
header("Content-type: text/plain");

//Validate API caller
if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) || (($user = api_validate_user($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) === FALSE)) {
  header("HTTP/1.0 401 Unauthorized");
  echo("Unauthorized Access");
  exit;
}
