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


if (get_magic_quotes_gpc()==1)
{
	// FIXME: add suppport for arrays (see "get")
	$changesList = $_REQUEST["changesList"];
	//$changesList = stripslashes($_REQUEST["changesList"]);
} else {
	$changesList = $_REQUEST["changesList"];
}

$moufManager = MoufManager::getMoufManager();

function mouf_convert_json_ordered_array_to_php(array $jsonArr, $associativeArray) {
	$phpArr = array();
	if ($associativeArray) {
		foreach ($jsonArr as $key=>$value) {
			// TODO: add support for recursive arrays.
			if (isset($value['isNull'])) {
				$phpArr[$value['key']] = null;
			} else {
				$phpArr[$value['key']] = $value['value'];
			}
		}
	} else {
		foreach ($jsonArr as $key=>$value) {
			if (isset($value['isNull'])) {
				$phpArr[] = null;
			} else {
				$phpArr[] = $value['value'];
			}
		}
	}
	return $phpArr;
}

foreach ($changesList as $command) {
	switch($command['command']) {
		case "setProperty":
			$instanceName = $command['instance'];
			$propertyName = $command['property'];
			$instanceDescriptor = $moufManager->getInstanceDescriptor($instanceName);
			$property = $instanceDescriptor->getProperty($propertyName);
			$propertyDescriptor = $property->getPropertyDescriptor();
			
			
			if ($command['isNull'] == "true") {
				$value = null;
			} else {
				$value = isset($command['value'])?$command['value']:null;
				if ($propertyDescriptor->isArray()) {
					if ($value === null) {
						$value = array();
					}
					if (!$propertyDescriptor->isAssociativeArray()) {
						$value = mouf_convert_json_ordered_array_to_php($value, false);
					} else {
						$value = mouf_convert_json_ordered_array_to_php($value, true);
					}
				}
			}
			
			
			/*if ($propertyDescriptor->is)
			$property->setValue($value)*/
			if ($propertyDescriptor->isPrimitiveType() || $propertyDescriptor->isArrayOfPrimitiveTypes()) {
				if ($propertyDescriptor->isPublicFieldProperty()) {
					$moufManager->setParameter($instanceName, $propertyName, $value);
				} else {
					$moufManager->setParameterViaSetter($instanceName, $propertyDescriptor->getMethodName(), $value);
				}
			} else {
				if ($propertyDescriptor->isPublicFieldProperty()) {
					$moufManager->bindComponent($instanceName, $propertyName, $value);
				} else {
					$moufManager->bindComponentsViaSetter($instanceName, $propertyDescriptor->getMethodName(), $value);
				}
			}
			break;
		case "newInstance":
			$instanceDescriptor = $moufManager->createInstance($command['class']);
			$instanceDescriptor->setName($command['name']);
			$instanceDescriptor->setInstanceAnonymousness($command['isAnonymous'] == "true");
			break;
		default:
			throw new Exception("Unknown command");
	}
}

$moufManager->rewriteMouf();

$response = array();
$response["status"] = "ok";
/*$instanceList = $moufManager->findInstances($className);

$response = array();
$instancesArray = array();
$instanceArray = array();

foreach ($instanceList as $instanceName) {
	$instanceDescriptor = $moufManager->getInstanceDescriptor($instanceName);
	$response["instances"][$instanceDescriptor->getName()] = $instanceDescriptor->toJson();
}

// Now, let's get the full list of absolutely all classes implementing "class".
$classList = Moufspector::getComponentsList($className);
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
*/

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