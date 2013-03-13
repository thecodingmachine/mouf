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
 * Analyses all included PHP files to detect whether one is not behaving correctly (outputing some text, which is strictly forbidden)
 */

use Mouf\Reflection\MoufReflectionClass;
use Mouf\Composer\ComposerService;

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
require_once 'utils/check_rights.php';


$classMap = json_decode($_REQUEST['classMap'], true);

/*$forbiddenClasses = isset($_REQUEST["forbiddenClasses"])?$_REQUEST["forbiddenClasses"]:array();
// Put the values in key for faster search
$forbiddenClasses = array_flip($forbiddenClasses);

$composerService = new ComposerService($selfEdit);
$classMap = $composerService->getClassMap();

$componentsList = array();
*/
echo "FDSFZEREZ_STARTUP\n";

if (is_array($classMap)) {
	foreach ($classMap as $className => $fileName) {
		//if (!isset($forbiddenClasses[$className])) {
			echo "X4EVDX4SEVX5_BEFOREINCLUDE\n";
			echo $className."\n";
			$refClass = new MoufReflectionClass($className);
			// Let's also serialize to check all the parameters, fields, etc...
			$refClass->toJson();
			
			// If we manage to get here, there has been no error loading $className. Youhou, let's output an encoded "OK"
			echo "DSQRZREZRZER__AFTERINCLUDE\n";
		//}
	}
}

// Another line breaker to mark the end of class loading. If we make it here, everything went according to plan.
echo "SQDSG4FDSE3234JK_ENDFILE\n";



$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}

// Note: we return the classmap, including $forbiddenClasses
if ($encode == "php") {
	echo serialize($classMap);
} elseif ($encode == "json") {
	echo json_encode($classMap);
} else {
	echo "invalid encode parameter";
}
