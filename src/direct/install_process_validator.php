<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
// This validator checks that no installation step is pending.


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

/*if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	require_once '../../Mouf.php';
	$selfEdit = "false";
} else {*/
	require_once '../../MoufComponents.php';
	require_once '../MoufManager.php';
	MoufManager::initMoufManager();
	require_once '../../MoufUniversalParameters.php';
	MoufManager::switchToHidden();
	require_once '../MoufAdmin.php';
	$selfEdit = "true";
//}

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

$moufManager = MoufManager::getMoufManager();

$multiStepActionService = $moufManager->getInstance('installService');
/* @var $multiStepActionService MultiStepActionService */



$jsonObj = array();
if (!$multiStepActionService->hasRemainingAction()) {
	$jsonObj['code'] = "ok";
	$jsonObj['html'] = "No pending install actions to execute.";
} else {
	$jsonObj['code'] = "warn";
	$jsonObj['html'] = "An installation process did not complete. Please <a href='".ROOT_URL."mouf/install/?selfedit=".$selfEdit."'>resume the install process</a>.";
}

echo json_encode($jsonObj);
?>