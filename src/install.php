<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
/**
 * This file is used to install the Mouf framework by creating the .htaccess file.
 */
$old = umask(0);

$uri = $_SERVER["REQUEST_URI"];

$installPos = strpos($uri, "/src/install.php");
if ($installPos !== FALSE) {
	$uri = substr($uri, 0, $installPos);
	$uriWithoutMouf = substr($uri, 0, -16);
}

$oldUmask = umask();
umask(0);

// Now, let's write the basic Mouf files:
if (!file_exists("../../../../mouf")) {
	mkdir("../../../../mouf", 0775);
}
if (!file_exists("../../../../mouf/no_commit")) {
	mkdir("../../../../mouf/no_commit", 0775);
}


// Write Mouf.php:
if (!file_exists("../../../../mouf/Mouf.php")) {
	$moufStr = "<?php
define('ROOT_PATH', realpath(__DIR__.'/..').DIRECTORY_SEPARATOR);
//require_once __DIR__.'/../config.php';
define('MOUF_URL', ROOT_URL.'vendor/mouf/mouf/');
			
require_once __DIR__.'/../vendor/autoload.php';

require_once 'MoufComponents.php';
?>";
	
	file_put_contents("../../../../mouf/Mouf.php", $moufStr);
	chmod("../../../../mouf/Mouf.php", 0664);
}



// Write MoufComponents.php:
if (!file_exists("../../../../mouf/MoufComponents.php")) {
	$moufComponentsStr = "<?php
/**
 * This is a file automatically generated by the Mouf framework. Do not modify it, as it could be overwritten.
 */

use Mouf\MoufManager;
MoufManager::initMoufManager();
\$moufManager = MoufManager::getMoufManager();

?>";
	
	file_put_contents("../../../../mouf/MoufComponents.php", $moufComponentsStr);
	chmod("../../../../mouf/MoufComponents.php", 0664);
}

// Finally, let's generate the MoufUI.php file:
if (!file_exists("../../../../mouf/MoufUI.php")) {
	$moufUIStr = "<?php
/**
 * This is a file automatically generated by the Mouf framework. Do not modify it, as it could be overwritten.
 */
	
	?>";
	
	file_put_contents("../../../../mouf/MoufUI.php", $moufUIStr);
	chmod("../../../../mouf/MoufUI.php", 0664);
}

// Finally 2, let's generate the config.php file:
if (!file_exists("../../../../config.php")) {
	$moufConfig = "<?php
/**
 * This is a file automatically generated by the Mouf framework. Do not modify it, as it could be overwritten.
 * Use the UI to edit it instead.
 */

?>";
	
	file_put_contents("../../../../config.php", $moufConfig);
	chmod("../../../../config.php", 0664);
}

// Finally 3 :), let's generate the MoufUsers.php file:
if (!file_exists("../../../../mouf/no_commit/MoufUsers.php")) {
	$moufConfig = "<?php
/**
 * This contains the users allowed to access the Mouf framework.
 */
\$users[".var_export(install_userinput_to_plainstring($_REQUEST['login']), true)."] = array('password'=>".var_export(sha1(install_userinput_to_plainstring($_REQUEST['password'])), true).", 'options'=>null);
	
?>";
	
	file_put_contents("../../../../mouf/no_commit/MoufUsers.php", $moufConfig);
	chmod("../../../../mouf/no_commit/MoufUsers.php", 0664);
}

function install_userinput_to_plainstring($str) {
	if (get_magic_quotes_gpc()==1)
	{
		$str = stripslashes($str);
		// Rajouter les slashes soumis par l'utilisateur
		//$str = str_replace('\\', '\\\\', $str);
		return $str;
	}
	else
		return $str;
}

umask($oldUmask);

header("Location: ".$uri."/");
