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

if (get_magic_quotes_gpc()==1)
{
	$classMapJson = stripslashes($_REQUEST["classMap"]);
} else {
	$classMapJson = $_REQUEST["classMap"];
}

$classMap = json_decode($classMapJson, true);
if(json_last_error() != JSON_ERROR_NONE ){
    throw new Exception('Corrupted JSON ClassMap while analyzing includes...');
}
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
        echo "X4EVDX4SEVX5_BEFOREINCLUDE\n";
        echo $className."\n";

        if (!class_exists($className)) {
            echo "Error. Could not load class '".$className."' from file '".$fileName."'. This is probably an autoloader issue.
                      Please check that your class respects PSR-0 or PSR-4 naming standards";
        }

        // If we manage to get here, there has been no error loading $className. Youhou, let's output an encoded "OK"
        echo "DSQRZREZRZER__AFTERINCLUDE\n";
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
