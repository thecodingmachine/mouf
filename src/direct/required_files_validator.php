<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

require_once('../../MoufUniversalParameters.php');

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

// This validator calls the "analyze_includes" file and returns the result adapted to the display of a validator (JSON format).

if (isset($_SERVER['HTTPS'])) {
	$url = "https://".$_SERVER['SERVER_NAME'].ROOT_URL."mouf/direct/analyze_includes.php";
} else {
	$url = "http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].ROOT_URL."mouf/direct/analyze_includes.php";
}

if (isset($_REQUEST['selfedit'])) {
	$url .= "?selfedit=".$_REQUEST['selfedit'];
}

$ch = curl_init();
		
curl_setopt( $ch, CURLOPT_URL, $url);

//curl_setopt( $ch, CURLOPT_HEADER, FALSE );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
//curl_setopt( $ch, CURLOPT_POST, TRUE );
curl_setopt( $ch, CURLOPT_POST, FALSE );
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );

// Let's forward all cookies so the session in preserved.
// Problem: because the session file is locked, we cannot do that without closing the session first
session_write_close();

$cookieArr = array();
foreach ($_COOKIE as $key=>$value) {
	$cookieArr[] = $key."=".urlencode($value);
}
$cookieStr = implode("; ", $cookieArr);
curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);

$response = curl_exec( $ch );

session_start();

if( curl_error($ch) ) { 
	throw new Exception("An error occured: ".curl_error($ch));
}
curl_close( $ch );

// Let's strip the invalid parts:
$arr = explode("\nX4EVDX4SEVX548DSVDXCDSF489\n", $response);
if (count($arr) < 2) {
	// No delimiter: there has been a crash.
	$obj = array("errorType"=>"crash", "errorMsg"=>$response);
} else {
	$msg = $arr[count($arr)-1]; 
	
	// Disable E_NOTICE because unserialize creates a E_NOTICE if unserialize fails.
	//error_reporting(error_reporting() | !E_NOTICE);
	error_reporting(E_ERROR);
	$obj = unserialize($msg);
}

if ($obj === false) {
	$jsonObj['code'] = "error";
	$jsonObj['html'] = "Error while running the required files validator:<br/> <pre>".htmlspecialchars($msg)."</pre>";
	echo json_encode($jsonObj);
	exit;
}

$jsonObj = array();
if (isset($obj['errorType'])) {
	$jsonObj['code'] = "error";
	$jsonObj['html'] = "Error while running the required files validator:<br/> <pre>".htmlspecialchars($obj['errorMsg'])."</pre>";
} else {
	$jsonObj['code'] = "ok";
	$jsonObj['html'] = "PHP included files: OK";
}

echo json_encode($jsonObj);

?>