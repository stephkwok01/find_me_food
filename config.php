<?php
//Database configuration
$url = parse_url("mysql://bbbd3156485d7a:8eaae967@us-cdbr-iron-east-02.cleardb.net/heroku_ff5309d0caee90b?reconnect=true");

$SERVER = $url["host"];
$USERNAME = $url["user"];
$PASSWORD = $url["pass"];
$DB = substr($url["path"], 1);

//Credentails for curl request
$UNAME = 'test@test.com';
$PASS = 'asdf';
