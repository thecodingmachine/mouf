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
	if (isset($_SERVER['BASE'])) {
		define('ROOT_URL', $_SERVER['BASE']."/");
	} else {
		define('ROOT_URL', "/");
	}
	
	require_once '../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH."mouf/";
	$selfEdit = true;	
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';


$composerService = new ComposerService($selfEdit);
$classMap = $composerService->getClassMap();

$file = $line = null;
$sent = headers_sent($file, $line);
if ($sent){
    throw new \Mouf\MoufException("Error while calling get_class_map : output started at $file on line $line");
}

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}

if ($encode == "php") {
	echo serialize($classMap);
} elseif ($encode == "json") {
	echo json_encode($classMap);
} else {
	echo "invalid encode parameter";
}
