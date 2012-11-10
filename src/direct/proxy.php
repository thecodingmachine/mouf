<?php
use Mouf\MoufManager;

use Mouf\MoufUtils;

/**
 * The proxy server.
 * Executes a passed database method and returns the result.
 * The user must be logged in Mouf to be able to run this script. 
 */

// Disable output buffering
while (ob_get_level() != 0) {
	ob_end_clean();
}

ini_set('display_errors', 1);
// Add E_ERROR to error reporting if it is not already set
error_reporting(E_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	require_once '../../../../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH;
	$selfEdit = false;
} else {
	require_once '../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH."mouf/";
	$selfEdit = true;
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
MoufUtils::checkRights();

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}

$instance = $_REQUEST["instance"];
$method = $_REQUEST["method"];
$args = $_REQUEST["args"];
if (get_magic_quotes_gpc()==1)
{
	$instance = stripslashes($instance);
	$method = stripslashes($method);
	$args = stripslashes($args);
}

$instanceObj = MoufManager::getMoufManager()->getInstance($instance);

if ($encode == "php") {
	$arguments = unserialize($args);
} elseif ($encode == "json") {
	$arguments = json_decode($args);
} else {
	echo "invalid encode parameter";
	exit;
}

$result = call_user_func_array(array($instanceObj, $method), $arguments);

if ($encode == "php") {
	echo serialize($result);
} elseif ($encode == "json") {
	echo json_encode($result);
}

?>