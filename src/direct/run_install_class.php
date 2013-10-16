<?php
use Mouf\MoufException;

/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

/*
 * This file is in charge of running the install process for one class.
 */

$rootUrl = $_REQUEST['root_url'];
$installPackage = $_REQUEST['install_package'];
$selfedit = $_REQUEST['selfedit'];

define('ROOT_URL', $rootUrl);

if ($selfedit == "true") {
	chdir(__DIR__.'/../../vendor/'.$installPackage);
} else {
	chdir(__DIR__.'/../../../../../vendor/'.$installPackage);
}

require_once __DIR__."/../../../../autoload.php";
//require_once __DIR__."/../../../../../mouf/Mouf.php";

use Mouf\Actions\InstallUtils;
use Mouf\MoufManager;
use Mouf\MoufUtils;

// Let's init Mouf
InstallUtils::init(InstallUtils::$INIT_APP);

// Let's create the instance
$moufManager = MoufManager::getMoufManager();

$name = $_REQUEST['class'];

if (!class_exists($name)) {
	throw new MoufException("Unable to find class '".$name."'.");
}
if (!is_a($name, 'Mouf\\Installer\\PackageInstallerInterface', true)) {
	throw new MoufException("The class '".$name."' must implement interface Mouf\\Installer\\PackageInstallerInterface");
}

$name::install($moufManager);

// Finally, let's continue the install
InstallUtils::continueInstall();
?>
