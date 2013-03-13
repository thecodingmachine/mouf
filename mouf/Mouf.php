<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use Mouf\MoufManager;

// Let's add to the project's autoloader to the Mouf classes.
// For Mouf classes to be detected before the projet's classes, projects classes must be autoloaded first.
if (!class_exists('Composer\\Autoload\\ClassLoader')) {
	require __DIR__.'/../vendor/composer/ClassLoader.php';
}

$loader = new \Composer\Autoload\ClassLoader();
if (file_exists(__DIR__ . '/../../../composer/autoload_namespaces.php')) {
	$map = require __DIR__ . '/../../../composer/autoload_namespaces.php';
	foreach ($map as $namespace => $path) {
		$loader->add($namespace, $path);
	}
}

if (file_exists(__DIR__ . '/../../../composer/autoload_classmap.php')) {
	$classMap = require __DIR__ . '/../../../composer/autoload_classmap.php';
	if ($classMap) {
		$loader->addClassMap($classMap);
	}
}
$loader->register(true);


// Now, let's use Mouf autoloader.
require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__.'/../../../../mouf/MoufComponents.php';

// FIXME: rewrite this to support many MoufComponents!!!
// Maybe with a "default" environment (first loaded) and a "getMoufManagerByName()" that loads on the fly?
// Scopes: APP - MOUF - DEFAULT? (first loaded)
// Autre idée: getMoufManager("adresse du fichier!")
MoufManager::switchToHidden();
require_once 'MoufComponents.php';


define('ROOT_PATH', realpath(__DIR__."/..").DIRECTORY_SEPARATOR);

require_once __DIR__.'/../config.php';

define('MOUF_URL', ROOT_URL);


// We are part of mouf, let's chain with the main autoloader if it exists.
/*if (file_exists(__DIR__.'/../../../../vendor/autoload.php')) {
	require_once __DIR__.'/../../../../vendor/autoload.php';
}*/

// Finally, let's include the MoufUI if it exists.
// Note: acting on the _REQUEST is not the cleanest thing to do!!!
if (isset($_REQUEST['selfedit']) && $_REQUEST['selfedit'] == 'true') {
	if (file_exists(__DIR__.'/MoufUI.php')) {
		require_once __DIR__.'/MoufUI.php';
	}
} else {
	if (file_exists(__DIR__.'/../../../../mouf/MoufUI.php')) {
		require_once __DIR__.'/../../../../mouf/MoufUI.php';
	}
}


// And let's start the session
MoufAdmin::getSessionManager()->start();
?>