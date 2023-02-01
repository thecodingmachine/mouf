<?php
use Mouf\MoufManager;
use Mouf\CodeValidatorService;
use Mouf\MoufUtils;

/**
 * The proxy server.
 * Executes a passed method of an instance and returns the result.
 * The user must be logged in Mouf to be able to run this script.
 */

// Disable output buffering
while (ob_get_level() != 0) {
	ob_end_clean();
}

ini_set('display_errors', 1);
// Add E_ERROR to error reporting if it is not already set
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
MoufUtils::checkRights();

$encode = "php";
if (isset($_REQUEST["encode"]) && $_REQUEST["encode"]="json") {
	$encode = "json";
}

$code = $_REQUEST["code"];

$result = [
	"status" => "success",
	"data" => []
];
try {
	CodeValidatorService::validateCode($code);
} catch (PhpParser\Error $e) {
	$result = [
		"status" => "fail",
		"data" => [
			"line" => $e->getRawLine(),
			"message" => $e->getRawMessage()
		]
	];
}

if ($encode == "php") {
	echo serialize($result);
} elseif ($encode == "json") {
	echo json_encode($result);
}

?>
