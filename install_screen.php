<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012-2013 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

//define('ROOT_URL', $_SERVER["REQUEST_URI"]);

require_once __DIR__.'/mouf/Mouf.php';

MoufAdmin::getMoufInstallController()->index();
exit;
?>