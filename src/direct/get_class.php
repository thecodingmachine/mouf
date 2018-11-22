<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 

use Mouf\Reflection\MoufReflectionClass;

ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	define('ROOT_URL', $_SERVER['BASE']."/../../../");
	
	require_once '../../../../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH;
	$selfEdit = false;
} else {
	define('ROOT_URL', $_SERVER['BASE']."/");
	
	require_once '../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH."mouf/";
	$selfEdit = true;
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

//$res = MoufManager::getMoufManager()->findInstances($_REQUEST["class"]);
$class = new MoufReflectionClass($_REQUEST["class"]);

if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]=="json") {
	$classArray = array();
	$classArray[$_REQUEST["class"]] = $class->toJson();
	while ($class->getParentClass() != null) {
		$class = $class->getParentClass();
		$classArray[$class->getName()] = $class->toJson();
	}
	 
	$response = array("classes" => $classArray);
	echo json_encode($response);
} else {
	echo $class->toXml();
}

?>