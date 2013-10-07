<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use Mouf\MoufManager;

ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

// This validator checks that all the config parameters defined are present in the config.php file.

if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	if (function_exists('apache_getenv')) {
		define('ROOT_URL', apache_getenv("BASE")."/../../../");
	}
	require_once '../../../../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH;
	$selfEdit = false;
	
} else {
	if (function_exists('apache_getenv')) {
		define('ROOT_URL', apache_getenv("BASE")."/");
	}
	require_once '../../mouf/Mouf.php';
	$mouf_base_path = ROOT_PATH."mouf/";
	$selfEdit = true;	
}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

$moufManager = MoufManager::getMoufManager();

$configManager = $moufManager->getConfigManager();

$constants = $configManager->getDefinedConstants(); 
$definedConfigConstants = array_keys($constants);

$availableConfigConstants = array_keys($configManager->getConstantsDefinitionArray());

$missingAvailableConstants = array_diff($definedConfigConstants, $availableConfigConstants);

$missingDefinedConstants = array_diff($availableConfigConstants, $definedConfigConstants);

$jsonObj = array();
if (empty($missingDefinedConstants) && empty($missingAvailableConstants)) {
	$jsonObj['code'] = "ok";
	$jsonObj['html'] = "All parameters have been configured in <code>config.php</code>.";
} else {
	if (empty($constants)) {
		$jsonObj['code'] = "warn";
		$jsonObj['html'] = "Your <code>config.php</code> is empty. Please <a href='".MOUF_URL."config/?selfedit=".$selfEdit."'>configure your application</a>.";
	} else {
		
		$msg = "";
		if (!empty($missingAvailableConstants)) {
			$jsonObj['code'] = "warn";
			$msg .= "Your <code>config.php</code> file contains constants that have not been defined in Mouf.
			It is important to define these parameters, so that you will be reminded to create them in other environments when you deploy your application.
			<ul>";
			foreach ($missingAvailableConstants as $missingAvailableConstant) {
				$msg .= "<li><a href='".MOUF_URL."config/register?name=".urlencode($missingAvailableConstant)."&value=".urlencode($constants[$missingAvailableConstant])."&defaultvalue=".urlencode($constants[$missingAvailableConstant])."&selfedit=".$selfEdit."'>Define parameter ".$missingAvailableConstant."</a></li>";
			}
			$msg .= "</ul><br/> ";
		}
		if (!empty($missingDefinedConstants)) {
			$jsonObj['code'] = "error";
			$msg .= "Your <code>config.php</code> file is missing one or more parameter. Parameter(s) missing:
			<ul>";
			foreach ($missingDefinedConstants as $missingDefinedConstant) {
				$msg .= "<li>".$missingDefinedConstant."</li>";
			}
			$msg .= "</ul>
			<a href='".MOUF_URL."config/?selfedit=".$selfEdit."'>Configure those parameters.</a>";
		}
		$jsonObj['html'] = $msg;
	}
}

echo json_encode($jsonObj);
?>