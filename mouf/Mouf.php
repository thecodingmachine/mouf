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
if (file_exists(__DIR__.'/../../../vendor/autoload.php')) {
	require_once __DIR__.'/../../../vendor/autoload.php';
}

?>