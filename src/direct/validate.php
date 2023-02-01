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
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | error_reporting());

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	define('ROOT_URL', $_SERVER['BASE']."/../../../");

        // Force loading autoload from mouf's version of PhpParser
        require_once __DIR__.'/../../vendor/nikic/php-parser/lib/bootstrap.php';

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

if (isset($_REQUEST["class"])) {
	$className = $_REQUEST["class"];

	$result = $className::validateClass();
} else {
	$instanceName = $_REQUEST["instance"];

	$moufManager = MoufManager::getMoufManager();
	$instance = $moufManager->getInstance($instanceName);
	/* @var $instance Mouf\Validator\MoufValidatorInterface */
	$result = $instance->validateInstance();
}

$response = $result->toJson();

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
