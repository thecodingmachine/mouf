<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
/**
 * Returns a serialized string representing an instance.
 */

use Mouf\MoufManager;

ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	define('ROOT_URL', $_SERVER['BASE']."/../../../");
	
	require_once '../../../../../mouf/Mouf.php';
} else {
	define('ROOT_URL', $_SERVER['BASE']."/");
	
	require_once '../../mouf/Mouf.php';
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]=="json") {
	$encode = "json";
}

$moufManager = MoufManager::getMoufManager();
// FIXME: the getInstanceDescriptor is calling the getClassDescriptor that itself is making a CURL call.
// In this case, the CURL call is not needed since the getInstanceDescriptor and the getClassDescriptor are
// in the same scope.
$instanceDescriptor = $moufManager->getInstanceDescriptor($_REQUEST["name"]);


$response = array();

$response["instances"][$instanceDescriptor->getIdentifierName()] = $instanceDescriptor->toJson();


// We send back class data with instance data... this saves one request.
// Now, let's embed the class and all the parents with this instance.
if ($instanceDescriptor->getClassName()) {
	$classDescriptor = $instanceDescriptor->getClassDescriptor();
	$classArray = array();
	do {
		$classArray[$classDescriptor->getName()] = $classDescriptor->toJson();
		$classDescriptor = $classDescriptor->getParentClass();
	} while ($classDescriptor != null);
	$response["classes"] = $classArray;
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