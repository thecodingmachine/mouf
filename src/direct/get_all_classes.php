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

ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	//echo "mouf";
	require_once '../../Mouf.php';
} else {
	//echo "moufadmin";
	require_once '../MoufManager.php';
	MoufManager::initMoufManager();
	require_once '../../MoufUniversalParameters.php';
	require_once '../MoufAdmin.php';
}
require_once '../Moufspector.php';

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]=="json") {
	$encode = "json";
}

$moufManager = MoufManager::getMoufManager();

MoufManager::getMoufManager()->forceAutoload();

$classNameList = Moufspector::getComponentsList();
$classList = array();

foreach ($classNameList as $className) {
	$classDescriptor = $moufManager->getClassDescriptor($className);
	do {
		$classList[$classDescriptor->getName()] = $classDescriptor->toJson();
		$classDescriptor = $classDescriptor->getParentClass();
	} while ($classDescriptor != null && !isset($classList[$classDescriptor->getName()])); 
}

$response = array();
$response["classes"] = $classList;

if ($encode == "php") {
	echo serialize($response);
} elseif ($encode == "json") {
	header("Content-type: application/json");
	echo json_encode($response);
} else {
	echo "invalid encode parameter";
}

?>