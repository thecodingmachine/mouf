<?php
$moufUI = getenv('MOUF_UI');
if ($moufUI !== false) {
    $moufUI = (bool) $moufUI;
    if (!$moufUI) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Error! Access to Mouf UI is forbidden on this environment (env variable MOUF_UI is set to 0)';
        exit;
    }
}
unset($moufUI);

if (!file_exists(__DIR__.'/../../../../mouf/no_commit/MoufUsers.php')) {
	
	$rootUrl = $_SERVER['BASE']."/";
	
	if ($_SERVER['REQUEST_URI'] != $rootUrl.'install') {
		define('ROOT_URL', $rootUrl);
		require '../install_screen.php';
		exit;
	}
}

require __DIR__.'/../vendor/mouf/mvc.splash/src/splash.php';