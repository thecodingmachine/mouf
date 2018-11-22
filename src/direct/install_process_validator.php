<?php
use Mouf\Installer\AbstractInstallTask;

use Mouf\Installer\ComposerInstaller;

use Mouf\MoufManager;

/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

// This validator checks that no installation step is pending.
use Mouf\MoufUtils;


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | error_reporting());

define('ROOT_URL', $_SERVER['BASE']."/");

require_once '../../mouf/Mouf.php';


// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
//require_once 'utils/check_rights.php';
MoufUtils::checkRights();

MoufAdmin::getSessionManager()->writeClose();


if (!isset($_REQUEST["selfedit"]) || $_REQUEST["selfedit"]!="true") {
	$selfEdit = false;
} else {
	$selfEdit = true;
}

$installService = new ComposerInstaller($selfEdit == 'true');
$installs = $installService->getInstallTasks();
//var_dump($installs);exit;
$countNbTodo = 0;
foreach ($installs as $installTask) {
	if ($installTask->getStatus() == AbstractInstallTask::STATUS_TODO) {
		$countNbTodo++;
	}
}

$jsonObj = array();
if ($countNbTodo == 0) {
	$jsonObj['code'] = "ok";
	$jsonObj['html'] = "No pending install tasks to execute.";
} else {
	$jsonObj['code'] = "warn";
	$jsonObj['html'] = "<p>$countNbTodo pending install action(s) detected.</p><p><a href='".ROOT_URL."installer/?selfedit=".json_encode($selfEdit)."' class='btn btn-success btn-large'>Run install tasks</a></p>";
}

echo json_encode($jsonObj);
?>