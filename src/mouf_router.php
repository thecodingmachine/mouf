<?php 
if (!file_exists(__DIR__.'/../../../../mouf/no_commit/MoufUsers.php')) {
	
	if (function_exists('apache_getenv')) {
		$rootUrl = apache_getenv("BASE")."/";
	}
	
	if ($_SERVER['REQUEST_URI'] != $rootUrl.'install') {
		define('ROOT_URL', $rootUrl);
		require '../install_screen.php';
		exit;
	}
}

require __DIR__.'/../vendor/mouf/mvc.splash/src/splash.php';