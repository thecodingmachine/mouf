<?php
use Mouf\Reflection\TypesDescriptor;

use Mouf\Reflection\TypeDescriptor;

use Mouf\MoufException;

/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 

use Mouf\MoufManager;

ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | error_reporting());


// Force loading autoload from mouf's version of PhpParser
require_once __DIR__.'/../../vendor/nikic/php-parser/lib/bootstrap.php';

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	define('ROOT_URL', $_SERVER['BASE']."/../../../");
        
	require_once '../../../../../mouf/Mouf.php';
	$selfEdit = false;
} else {
	define('ROOT_URL', $_SERVER['BASE']."/");
	
	require_once '../../mouf/Mouf.php';
	$selfEdit = true;
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';


$changesList = $_REQUEST["changesList"];

$moufManager = MoufManager::getMoufManager();

function mouf_convert_json_ordered_array_to_php(array $jsonArr, $associativeArray) {
	$phpArr = array();
	if ($associativeArray) {
		foreach ($jsonArr as $key=>$value) {
			// TODO: add support for recursive arrays.
			if (isset($value['isNull'])) {
				$phpArr[$value['key']] = null;
			} else {
				if (isset($value['isBoolean']) && $value['isBoolean'] == 'true') {
					$val = ($value['value']=="true")?true:false;
				} else {
					$val = $value['value'];
				}
				$phpArr[$value['key']] = $val;
			}
		}
	} else {
		foreach ($jsonArr as $key=>$value) {
			if (isset($value['isNull'])) {
				$phpArr[] = null;
			} else {
				if (isset($value['isBoolean']) && $value['isBoolean'] == 'true') {
					$val = ($value['value']=="true")?true:false;
				} else {
					$val = $value['value'];
				}
				$phpArr[] = $val;
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
			$source = $command['source'];
			$instanceDescriptor = $moufManager->getInstanceDescriptor($instanceName);
			
			// Source can be "constructor", "property" or "setter".
			switch ($source) {
				case "constructor":
					$property = $instanceDescriptor->getConstructorArgumentProperty($propertyName);
					break;
				case "setter":
					$property = $instanceDescriptor->getSetterProperty($propertyName);
					break;
				case "property":
					$property = $instanceDescriptor->getPublicFieldProperty($propertyName);
					break;
				default:
					throw new MoufException("Unknown source '".$source."' while saving parameter ".$propertyName);
			}
			
			//$propertyDescriptor = $property->getPropertyDescriptor();
			
			if ($command['origin'] == 'config') {
				$property->setOrigin('config');
				$property->setValue($command['value']);
			} elseif ($command['origin'] == 'php') {
				$property->setOrigin('php');
				$property->setValue($command['value']);
			} else if ($command['isset'] == "false") {
				// Let's unset completely the property
				//$property->setOrigin('string');
				$property->unsetValue();
			} else {
				$types = TypesDescriptor::parseTypeString($command['type'])->getTypes();
				$type = $types[0];
				
				$property->setOrigin('string');
				// Let's set the value
				if ($command['isNull'] == "true") {
					$value = null;
				} else {
					$value = isset($command['value'])?$command['value']:null;
					
					if ($type->isArray()) {
						if ($value === null) {
							$value = array();
						}
						if (!$type->isAssociativeArray()) {
							$value = mouf_convert_json_ordered_array_to_php($value, false);
						} else {
							$value = mouf_convert_json_ordered_array_to_php($value, true);
						}
					} else {
						if (isset($command['isBoolean']) && $command['isBoolean'] == 'true') {
							$value = ($value=="true")?true:false;
						}
					}
				}
				
				// FIXME: do not use setParameter/setParameterViaSetter directly!
				// use the source!
				
				
				if ($type->isPrimitiveTypesOrRecursiveArrayOfPrimitiveTypes()) {
					$property->setValue($value);
				} else {
					if (!is_array($value)) {
						if ($value != null) {
							$property->setValue($moufManager->getInstanceDescriptor($value));
						} else {
							$property->setValue(null);
						}
					} else {
						$arrayOfString = $value;
						if ($arrayOfString !== null){
							$arrayOfDescriptors = array();
							foreach ($arrayOfString as $key=>$instanceName) {
								if ($instanceName != null) {
									$arrayOfDescriptors[$key] = $moufManager->getInstanceDescriptor($instanceName);
								} else {
									$arrayOfDescriptors[$key] = null;
								}
							}
						}else{
							$arrayOfDescriptors = null;
						}
						$property->setValue($arrayOfDescriptors);
					}
				}
			}
			break;
		case "newInstance":
			$instanceDescriptor = $moufManager->createInstance($command['class']);
			$instanceDescriptor->setName($command['name']);
			$instanceDescriptor->setInstanceAnonymousness($command['isAnonymous'] == "true");
			break;
		case "renameInstance":
			$instanceDescriptor = $moufManager->getInstanceDescriptor($command['oldname']);
			$instanceDescriptor->setName($command['newname']);
			$instanceDescriptor->setInstanceAnonymousness($command['isAnonymous'] == "true");
			break;
		case "duplicateInstance":
			$moufManager->duplicateInstance($command['name'], $command['duplicateName']);
			$instanceDescriptor = $moufManager->getInstanceDescriptor($command['duplicateName']);
			$instanceDescriptor->setInstanceAnonymousness(false);
			$moufManager->setInstanceWeakness($command['duplicateName'], false);
			break;
		case "deleteInstance":
			$instanceDescriptor = $moufManager->removeComponent($command['name']);
			break;
		case "setInstanceCode":
			$instanceDescriptor = $moufManager->getInstanceDescriptor($command['name']);
			$instanceDescriptor->setCode($command['code']);
			break;
		default:
			throw new Exception("Unknown command");
	}
}

$moufManager->rewriteMouf();

$response = array();
$response["status"] = "ok";

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
