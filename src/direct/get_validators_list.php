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
require_once 'utils/check_rights.php';


/*if (get_magic_quotes_gpc()==1)
{
	$className = stripslashes($_REQUEST["class"]);
} else {
	$className = $_REQUEST["class"];
}*/

$moufManager = MoufManager::getMoufManager();

$response = array("instances"=>array(), "classes"=>array());

$instanceList = $moufManager->findInstances("Mouf\\Validator\\MoufValidatorInterface");

foreach ($instanceList as $instanceName) {
	$instance = $moufManager->getInstance($instanceName);
	/* @var $instance Mouf\Validator\MoufValidatorInterface */
	try {
		$response["instances"][] = array("title"=>$instance->getValidatorTitle(), "instanceName"=>$instanceName);
	} catch (Exception $e) {
		$response["instances"][] = array("error"=>$e->getTraceAsString(), "instanceName"=>$instanceName);
	}
}

// Now, let's get the full list of absolutely all classes implementing "MoufStaticValidatorInterface".
$classList = Moufspector::getComponentsList("Mouf\\Validator\\MoufStaticValidatorInterface", $selfEdit);

foreach ($classList as $className) {
	try {
		$response["classes"][] = array("title"=>$className::getStaticValidatorTitle(), "className"=>$className);
	} catch (Exception $e) {
		$response["classes"][] = array("error"=>$e->getTraceAsString(), "className"=>$className);
	}
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