<?php

use Zend\HttpHandlerRunner\RequestHandlerRunner;

if (!file_exists(__DIR__.'/../../../../mouf/no_commit/MoufUsers.php')) {
	
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
$container->get(RequestHandlerRunner::class);
$runner->run();
