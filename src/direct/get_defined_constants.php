<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	$fileName = dirname(__FILE__)."/../../config.php";
} else {
	$fileName = dirname(__FILE__)."/../../mouf/config.php";
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';


$constants_list = null;

// If no config file exist, there is no constants defined. Let's return an empty list.
if (!file_exists($fileName)) {
	echo serialize(array());
	exit;
}

require_once $fileName;

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}

$allConstants = get_defined_constants(true);

// No custom constants? Let's return an empty list.
if (!isset($allConstants['user'])) {
	echo serialize(array());
	exit;
}

// Some custom constants? They come from config.php.
// Let's return those.
//echo serialize($allConstants['user']);

if ($encode == "php") {
	echo serialize($allConstants['user']);
} elseif ($encode == "json") {
	echo json_encode($allConstants['user']);
} else {
	echo "invalid encode parameter";
}

?>