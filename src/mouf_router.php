<?php

use Zend\HttpHandlerRunner\RequestHandlerRunner;

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

if (!file_exists(__DIR__.'/../../../../mouf/no_commit/users.php')) {
	
	$rootUrl = $_SERVER['BASE']."/";
	
	if ($_SERVER['REQUEST_URI'] != $rootUrl.'install') {
		define('ROOT_URL', $rootUrl);
		require __DIR__.'/../install_screen.php';
		exit;
	}
}

if (isset($_SERVER['BASE'])) {
    define('ROOT_URL', $_SERVER['BASE'].'/');
} else {
    define('ROOT_URL', '/');
}

//require __DIR__.'/../vendor/mouf/mvc.splash/src/splash.php';
require_once __DIR__.'/../mouf/Mouf.php';


$container = \Mouf\MoufManager::getMoufManager();
$runner = $container->get(RequestHandlerRunner::class);
$runner->run();
