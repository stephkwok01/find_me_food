<?php
include 'config.php';

///////////////////////////////////////////////////////////////////
// GENERAL DB VARS AND FUNCTIONS LIBRARY FOR CONNECTING WITH DATABASE
$GLOBALS['LAST_QUERY'] = FALSE;
class DBException extends Exception {
  public function __construct($message = null, $query = '', $code = 0) {
    parent::__construct($message, $code);

    // MAKE SURE THE FILE IS SETUP CORRECTLY
    @touch(dirname(__DIR__).'/logs/errors');
    @chmod(dirname(__DIR__).'/logs/errors', 0666);

    if (@($fp = fopen(dirname(__DIR__).'/logs/errors', 'a+'))) {
      // LOG THE ERROR AND PROVIDE A BACKTRACE
      ob_start();
      debug_print_backtrace();
      fwrite($fp, date('r').': ('.$code.')'.' '.$message.' - '.$query."\nBACKTRACE:\n".preg_replace('/^#(\d+)/me', '\'#\' . ($1 - 1)', preg_replace('/^#0\s+' . 'DBException->__construct' . 
"[^\n]*\n/", '', ob_get_contents(), 1))."\n");
      ob_end_clean(); 
      fclose($fp);
    }
  }
}

function DBConnect() {
  if (!isset($GLOBALS['DATABASE']['host'], $GLOBALS['DATABASE']['user'], $GLOBALS['DATABASE']['pass'], $GLOBALS['DATABASE']['db'])) throw new DBException('Invalid database settings');
  if (!($GLOBALS['DATABASE']['conn'] = @mysql_connect($GLOBALS['DATABASE']['host'], $GLOBALS['DATABASE']['user'], $GLOBALS['DATABASE']['pass']))) {
    throw new DBException('Could not connect to host '.$GLOBALS['DATABASE']['host']);
  }
  if (!(@mysql_select_db($GLOBALS['DATABASE']['db'], $GLOBALS['DATABASE']['conn']))) {
    throw new DBException('Could not connect to database '.$GLOBALS['DATABASE']['db'].' on host '.$GLOBALS['DATABASE']['host']);
  }
}

function DBClose() {
  mysql_close($GLOBALS['DATABASE']['conn']);
}

function DBQuery($query) {
  $result = array();

  // PARSE ANY ADDITIONAL ARGS AS PARAMS
  $query = trim($query);
  if ($args = array_splice(func_get_args(), 1)) {
    $query = preg_replace_callback('/\?/', function($match) use(&$args) {
      if (($arg = array_shift($args)) !== NULL) {
        return '"'.mysql_real_escape_string($arg, $GLOBALS['DATABASE']['conn']).'"';
      } else {
        return 'NULL';
      }
    }, $query);
  }
  $GLOBALS['LAST_QUERY'] = $query;

  if ($dbresult = mysql_query($query, $GLOBALS['DATABASE']['conn'])) {
    if (strncasecmp('SELECT', $query, 6) == 0) {
      while ($data = mysql_fetch_array($dbresult, MYSQL_ASSOC)) {
        $result[] = $data;
      }
    } else if (strncasecmp('INSERT', $query, 6) == 0) {
      if (($result = mysql_insert_id($GLOBALS['DATABASE']['conn'])) === FALSE) {
        throw new DBException(mysql_error($GLOBALS['DATABASE']['conn']), $query);
      }
    } else {
      $result = mysql_affected_rows($GLOBALS['DATABASE']['conn']);
    }
  } else {
    throw new DBException(mysql_error($GLOBALS['DATABASE']['conn']), $query);
  }
  return $result;
}

function DBQueryString($query) {
  // PARSE ANY ADDITIONAL ARGS AS PARAMS
  if ($args = array_splice(func_get_args(), 1)) {
    $query = preg_replace_callback('/\?/', function($match) use(&$args) {
      if (($arg = array_shift($args)) !== NULL) {
        return '"'.mysql_real_escape_string($arg, $GLOBALS['DATABASE']['conn']).'"';
      } else {
        return 'NULL';
      }
    }, $query);
  }
  return $query;
}

function DBEscape($arg) {
  return mysql_real_escape_string($arg, $GLOBALS['DATABASE']['conn']);
}

// USED TO REPORT SERIOUS ERRORS
function log_error($str) {
  // MAKE SURE THE FILE IS SETUP CORRECTLY
  @touch(dirname(__DIR__).'/logs/errors');
  @chmod(dirname(__DIR__).'/logs/errors', 0666);

  // LOG THE PASSED STRING
  $log_entry = date('r').': '.$str."\n";

  // WRITE THE ENTRY TO THE LOG FILE
  if (@($fp = fopen(dirname(__DIR__).'/logs/errors', 'a+'))) {
    fwrite($fp, $log_entry);
    fclose($fp);
  }

  // ECHO THIS DEBUGGING INFO
  if ($GLOBALS['DEBUG']) {
    echo(trim($log_entry)."\n");
    debug_print_backtrace();
  }
}

// USED TO REPORT CRON EVENTS
function log_debug($str) {
  // CREATE THE LOG FILE NAME
  $logfile = dirname(__DIR__).'/logs/debug.'.date('Ymd').'.log';

  // MAKE SURE THE FILE IS SETUP CORRECTLY
  @touch($logfile);
  @chmod($logfile, 0666);

  // WRITE THE ENTRY TO THE LOG FILE
  @file_put_contents($logfile, date('r').': '.$str."\n", FILE_APPEND);
}

// GLOBAL VARIABLES USED TO STASH DEBUGGING INFO
$LAST_REST_REQUEST = NULL;
$LAST_REST_RESPONSE = NULL;

// UTILITY FUNCTION FOR ACCESSING A GENERIC RESTFUL SERVICE
function REST($url, $userpwd=NULL, $get_params=NULL, $post_params=NULL, $files=NULL, $is_json=FALSE) {
  // SETUP REST AND FILTER STYLE GET VARIABLES
  if (is_array($get_params)) {
    foreach ($get_params as $key=>$val) {
      $url = str_replace('{'.$key.'}', urlencode($val), $url, $count);
      if ($count > 0) unset($get_params[$key]);
    }
    if (sizeof($get_params) > 0) $url .= '?'.http_build_query($get_params);
  }

  // FOR FILES JUST ENCODE AS AN ARRAY
  if (is_array($files)) {
    // ADD THE FILES TO THE EXISTING POST VARS ARRAY IF IT EXISTS
    if (!is_array($post_params)) $post_params = array();
    foreach ($files as $key=>$file) {
      $post_params[$key] = curl_file_create($file);
    }
  } else if (is_array($post_params)) {
    // OTHERWISE ENCODE THE POST AS A STRING
    $post_params = http_build_query($post_params);
  }

  // SAVE THE GLOBAL REQUEST INFO AND RESET THE GLOBAL RESPONSE DATA
  $GLOBALS['LAST_REST_REQUEST'] = array('url'=>$url, 'post_params'=>(is_array($post_params) ? http_build_query($post_params) : $post_params));
  $GLOBALS['LAST_REST_RESPONSE'] = array('headers'=>'', 'body'=>'');

  // SET THE NECESSARY CURL OPTIONS
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_TIMEOUT, 600);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_URL, $url);
  if ($userpwd != NULL) curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
  if ($is_json) curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
  if ($post_params !== NULL) {
    curl_setopt($ch, CURLOPT_POST, 1);
    if ($post_params !== TRUE) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_params);
    }
  } else {
    curl_setopt($ch, CURLOPT_POST, 0);
  }

  // EXECUTE THE CURL REQUEST
  $ret = curl_exec($ch);

  // HANDLE GENERAL CURL ERRORS
  if ($ret === FALSE) {
    $error = curl_error($ch);
    curl_close($ch);
    throw new Exception($error);
  }

  // BREAK THE RESPONSE INTO HEADER AND BODY
  $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
  $raw_headers = preg_split('/$\R?^/m', trim(substr($ret, 0, $header_size)));
  $body = substr($ret, $header_size);
  $http_response = array_shift($raw_headers);
  $headers = array();
  foreach ($raw_headers as $raw_header) {
    $parts = explode(":", $raw_header, 2);
    if (sizeof($parts) == 2) {
      $headers[trim($parts[0])] = trim($parts[1]);
    }
  }

  // SAVE LAST RESPONSE DATA FOR DEBUGGING
  $GLOBALS['LAST_REST_RESPONSE'] = array('headers'=>$headers, 'body'=>$body);

  // HANDLE HTTP SPECIFIC ERRORS
  if (curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
    $error = $http_response;
    curl_close($ch);
    throw new Exception($error);
  }

  // CLOSE THE CURL HANDLE
  curl_close($ch);

  return $body;
}


// UTILITY FUNCTION FOR ACCESSING A RESTFUL JSON SERVICE
function JSON_REST($url, $userpwd=NULL, $get_params=NULL, $post_params=NULL, $files=NULL, $is_json=FALSE) {
  if (($ret = @json_decode(REST($url, $userpwd, $get_params, $post_params, $files, $is_json), TRUE)) === NULL) throw new Exception('invalid response from server');
  return $ret;
}

// SOAP class lifted from plum hosting
class SOAPService {
  /// LOCAL VARIABLES
  protected $wsdl = NULL;
  protected $timeout = NULL;
  protected $login = NULL;
  protected $password = NULL;
  protected $soapClient = NULL;
  public $last_error = NULL;

  /// CONFIGURE THE DATABASE
  public function __construct($wsdl, $timeout, $login=NULL, $password=NULL) {
    $this->wsdl = $wsdl;
    $this->timeout = $timeout;
    $this->login = $login;
    $this->password = $password;
  }

  public function call($function_name, $arguments, $options=NULL) {
    // CREATE THE SOAP CLIENT IF IT DOESN'T ALREADY EXIST
    if ($this->soapClient == NULL) {
      ini_set("soap.wsdl_cache_enabled", $GLOBALS['SOAP_CACHING_ENABLED'] ? "1" : "0");
      try {
        if ($this->login != NULL && $this->password != NULL) {
          $this->soapClient = @(new SoapClient($this->wsdl, array('login'=>$this->login, 'password'=>$this->password, 'trace'=>1, 'connection_timeout'=>$this->timeout)));
        } else {
          $this->soapClient = @(new SoapClient($this->wsdl, array('trace'=>1, 'connection_timeout'=>$this->timeout)));
        }
      } catch (SoapFault $f) {
        $this->last_error = 'SOAPService::call('.$function_name.', $arguments, $options) - '.$f->faultcode.' '.$f->faultstring;
        if ($GLOBALS['DEBUG']) {
          echo($this->last_error."<br>\n");
          debug_print_backtrace();
        }
        return NULL;
      }
    }

    // MAKE THE SOAP CALL
    try {
      $ret = NULL;
      if ($options != NULL) {
        $ret = $this->soapClient->__soapCall($function_name, $arguments, $options);
      } else {
        $ret = $this->soapClient->__soapCall($function_name, $arguments);
      }
      return $ret;
    } catch (SoapFault $f) {
      $this->last_error = 'SOAPService::call('.$function_name.', $arguments, $options) - '.$f->faultcode.' '.$f->faultstring;
      if ($GLOBALS['DEBUG']) {
        echo($this->last_error."<br>\n");
        echo($this->soapClient->__getLastRequest());
        echo($this->soapClient->__getLastResponse());
        debug_print_backtrace();
      }
      return NULL;
    }
  }
}

// Borrowed heavily from the Kohana controller
function load_view($name, $vars) {
  // BUFFER THE LOADING OF THE FILE
  ob_start();

  // IMPORT THE VIEW VARIABLES
  extract($vars, EXTR_SKIP);

  // LOAD THE VIEW THROWING AN EXCEPTION ON FAILURE
  try {
    include('views/'.$name.'.php');
  } catch (Exception $e) {
    ob_end_clean();
    throw $e;
  }

  // RETURN THE BUFFERED OUTPUT
  return ob_get_clean();
}

// COMPATIBILITY PATCH FOR PHP < 5.5
if (!function_exists('curl_file_create')) {
  function curl_file_create($filename, $mimetype = '', $postname = '') {
    return "@$filename;filename=".($postname ? '' : basename($filename)).($mimetype ? ";type=$mimetype" : '');
  }
}

