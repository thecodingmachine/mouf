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
 * Returns a serialized string representing the array for all components declares (classes with the @Component annotation)
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


if (get_magic_quotes_gpc()==1)
{
	$className = stripslashes($_REQUEST["class"]);
} else {
	$className = $_REQUEST["class"];
}

//if ($selfEdit) {
//	$moufManager = MoufManager::getMoufManagerHiddenInstance();
//} else {
	$moufManager = MoufManager::getMoufManager();
//}

if (strpos($className, '\\') === 0) {
	$className = substr($className, 1);
}
	

$instanceList = $moufManager->findInstances($className);

$response = array();
$instancesArray = array();
$instanceArray = array();

foreach ($instanceList as $instanceName) {
	$instanceDescriptor = $moufManager->getInstanceDescriptor($instanceName);
	if (!$instanceDescriptor->isAnonymous()) {
		$response["instances"][$instanceDescriptor->getIdentifierName()] = $instanceDescriptor->toJson();
	}
}

// Now, let's get the full list of absolutely all classes implementing "class".
$classList = Moufspector::getComponentsList($className, $selfEdit);
$classArray = array();
$childClassArray = array();

foreach ($classList as $childClassName) {
	$childClassArray[] = $childClassName;
	$classDescriptor = new MoufReflectionClass($childClassName);
	
	do {
		$classArray[$classDescriptor->getName()] = $classDescriptor->toJson();
		$classDescriptor = $classDescriptor->getParentClass();
	} while ($classDescriptor != null && !isset($classArray[$classDescriptor->getName()]));
}

// List of all classes that might be relevant
$response["classes"] = $classArray;
// List of all classes that are a subclass of the main class / interface
$response["childrenClasses"] = $childClassArray;

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