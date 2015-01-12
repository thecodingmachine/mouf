<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use Mouf\MoufClassExplorer;

use Mouf\MoufManager;

ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	define('ROOT_URL', $_SERVER['BASE']."/../../../");
	
	require_once '../../../../../mouf/Mouf.php';
	$selfedit = false;
} else {
	define('ROOT_URL', $_SERVER['BASE']."/");
	
	require_once '../../mouf/Mouf.php';
	$selfedit = true;
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]=="json") {
	$encode = "json";
}

$exportMode = "all";
if (isset($_REQUEST["export_mode"])) {
	$exportMode = $_REQUEST["export_mode"];
}

$moufManager = MoufManager::getMoufManager();

$classExplorer = new MoufClassExplorer($selfedit);
$classNameList = array_keys($classExplorer->getClassMap()); 

//MoufManager::getMoufManager()->forceAutoload();

//$classNameList = Moufspector::getComponentsList();
$classList = array();

foreach ($classNameList as $className) {
	$classDescriptor = $moufManager->getClassDescriptor($className);
	if ($classDescriptor->isInstantiable()) {
		while ($classDescriptor != null && !isset($classList[$classDescriptor->getName()])) {
			$classList[$classDescriptor->getName()] = $classDescriptor->toJson($exportMode);
			$classDescriptor = $classDescriptor->getParentClass();
		}
	} 
}

$response = array();
$response["classes"] = $classList;
$response["errors"] = $classExplorer->getErrors();

if ($encode == "php") {
	echo serialize($response);
} elseif ($encode == "json") {
	header("Content-type: application/json");
	echo json_encode($response);
} else {
	echo "invalid encode parameter";
}

?>