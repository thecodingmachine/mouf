<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

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

// Let's load the Mouf file, and the MoufAdmin file.
// The MoufAdmin will replace the Mouf configuration file.
if (file_exists(dirname(__FILE__).'/../MoufComponents.php')) {
	require_once dirname(__FILE__).'/../MoufComponents.php';
}
require_once dirname(__FILE__).'/../MoufUniversalParameters.php';

MoufManager::switchToHidden();
require_once 'MoufAdmin.php';
if (isset($_REQUEST['selfedit']) && $_REQUEST['selfedit']=="true") {
	require_once 'MoufAdminUI.php';
} else {
	// Check file existence just to be sure.
	if (file_exists(dirname(__FILE__).'/../MoufUI.php')) {
		require_once dirname(__FILE__).'/../MoufUI.php';
	}
}

$splashUrlPrefix = ROOT_URL."mouf/";
require_once '../plugins/mvc/splash/3.2/splash.php';

?>