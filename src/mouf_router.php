<?php 
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerBuilder;

if (file_exists(__DIR__.'/../vendor-harmony/autoload.php')) {
	require_once __DIR__.'/../vendor-harmony/autoload.php';
}

if (!file_exists(__DIR__.'/../../../../mouf/no_commit/MoufUsers.php')) {
	
	$rootUrl = $_SERVER['BASE']."/";
	
	if ($_SERVER['REQUEST_URI'] != $rootUrl.'install') {
		define('ROOT_URL', $rootUrl);
		require '../install_screen.php';
		exit;
	}
}

require __DIR__.'/../vendor/mouf/mvc.splash/src/splash.php';