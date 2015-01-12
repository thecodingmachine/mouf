<?php
use Mouf\MoufCache;
use Mouf\MoufUtils;

// This file purges all the code cache from Mouf.

// Disable output buffering
while (ob_get_level() != 0) {
	ob_end_clean();
}

ini_set('display_errors', 1);
// Add E_ERROR to error reporting if it is not already set
error_reporting(E_ERROR | error_reporting());

define('ROOT_URL', $_SERVER['BASE']."/../../../");

require_once '../../../../../mouf/Mouf.php';

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
MoufUtils::checkRights();

$moufCache = new MoufCache();
$moufCache->purgeAll();

exit;
?>