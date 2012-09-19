<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

require_once 'MoufComponents.php';

define('ROOT_PATH', realpath(__DIR__."/..").DIRECTORY_SEPARATOR);

if (file_exists(__DIR__.'/../config.php')) {
	require_once __DIR__.'/../config.php';
}

// We are part of mouf, let's chain with the main autoloader if it exists.
if (file_exists(__DIR__.'/../../../vendor/autoload.php')) {
	require_once __DIR__.'/../../../vendor/autoload.php';
}

?>