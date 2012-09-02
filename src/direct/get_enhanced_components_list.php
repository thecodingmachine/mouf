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
 * Returns a serialized string representing the array for all components declarations (classes with the @Component annotation),
 * along additional interesting infos (subclasses, name of the declaration file, etc...)
 */


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

// Disable output buffering
while (ob_get_level() != 0) {
	ob_end_clean();
}

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	require_once '../../Mouf.php';
} else {
	require_once '../MoufManager.php';
	MoufManager::initMoufManager();
	require_once '../../MoufUniversalParameters.php';
	require_once '../MoufAdmin.php';
}
require_once '../Moufspector.php';

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

MoufManager::getMoufManager()->forceAutoload();

$file=null;
$line=null;
$isSent = headers_sent($file, $line);

if ($isSent) {
	echo "\n<error>Error! Output started on line ".$line." in file ".$file."</error>";
	exit;
}

$type = null;
if (isset($_REQUEST["type"])) {
	$type = $_REQUEST["type"];
}

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}

if ($encode == "php") {
	echo serialize(Moufspector::getEnhancedComponentsList($type));
} elseif ($encode == "json") {
	echo json_encode(Moufspector::getEnhancedComponentsList($type));
} else {
	echo "invalid encode parameter";
}
?>