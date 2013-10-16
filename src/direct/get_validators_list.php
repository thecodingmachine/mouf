<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2013 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
/**
 * Returns a serialized string representing the list of instances implementing the MoufValidatorInterface
 * and classes implementing the MoufStaticValidatorInterface
 */


use Mouf\Reflection\MoufReflectionClass;

use Mouf\Moufspector;

use Mouf\MoufManager;

ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	if (function_exists('apache_getenv')) {
		define('ROOT_URL', apache_getenv("BASE")."/../../../");
	}
	require_once '../../../../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH;
	$selfEdit = false;
	
} else {
	if (function_exists('apache_getenv')) {
		define('ROOT_URL', apache_getenv("BASE")."/");
	}
	require_once '../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH."mouf/";
	$selfEdit = true;	
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';


/*if (get_magic_quotes_gpc()==1)
{
	$className = stripslashes($_REQUEST["class"]);
} else {
	$className = $_REQUEST["class"];
}*/

$moufManager = MoufManager::getMoufManager();

$response = array("instances"=>array(), "classes"=>array());

define('PROFILE_MOUF', false);

if (PROFILE_MOUF) {
	error_log("PROFILING: Starting get_validators_list: ".date('H:i:s', time()));
}

$instanceList = $moufManager->findInstances("Mouf\\Validator\\MoufValidatorInterface");
$response["instances"] = $instanceList;

if (PROFILE_MOUF) {
	error_log("PROFILING: findInstance done, starting getComponentsList: ".date('H:i:s', time()));
}

// Now, let's get the full list of absolutely all classes implementing "MoufStaticValidatorInterface".
$classList = Moufspector::getComponentsList("Mouf\\Validator\\MoufStaticValidatorInterface", $selfEdit);
$response["classes"] = $classList;

if (PROFILE_MOUF) {
	error_log("PROFILING: Ending get_validators_list: ".date('H:i:s', time()));
}


$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]=="json") {
	$encode = "json";
}

if ($encode == "php") {
	echo serialize($response);
} elseif ($encode == "json") {
	header("Content-type: application/json");
	echo json_encode($response);
} else {
	echo "invalid encode parameter";
}

?>