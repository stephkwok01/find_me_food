<?php
/** DATABASE CONFIG **/
$url = parse_url("mysql://ba3b92dc9036d8:95cf1774@us-cdbr-iron-east-02.cleardb.net/heroku_8d7e8477e71c47a?reconnect=true");

$SERVER = $url["host"];
$USERNAME = $url["user"];
$PASSWORD = $url["pass"];
$DB = substr($url["path"], 1);


$UNAME = 'test@test.com';
$PASS = 'asdf';
