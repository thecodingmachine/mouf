<?php
$rootUrl = $_REQUEST['root_url'];
$installPackage = $_REQUEST['install_package'];
$installFile = $_REQUEST['install_file'];
$selfedit = $_REQUEST['selfedit'];

define('ROOT_URL', $rootUrl);

if ($selfedit == "true") {
	chdir(__DIR__.'/../../vendor/'.$installPackage);
	require_once __DIR__.'/../../vendor/'.$installPackage.'/'.$installFile;
} else {
	chdir(__DIR__.'/../../../../../vendor/'.$installPackage);
	require_once __DIR__.'/../../../../../vendor/'.$installPackage.'/'.$installFile;
}
