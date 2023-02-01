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
 * Returns the source code of a file passed in parameter.
 */


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | error_reporting());

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

$file = $_REQUEST["file"];

if (strpos($file, "..") !== false) {
	echo "Error, invalid file name";
	die("Error, invalid file name");
}

readfile(ROOT_PATH.$file);
