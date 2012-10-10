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
 * This file should be included at the beginning of each file of the "/direct" folder.
 * It checks that the rights are ok.
 * The user is allowed access to the file if he is logged, or if he is requesting the file from localhost
 * (because it could be a request from Mouf itself via Curl, and therefore not logged).
 */

// TODO: remove this condition when everything is migrated to the new cookie propagation method.
if ($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR'] /*|| $_SERVER['REMOTE_ADDR'] == '::1'*/) {
	return;
}

if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION['MoufMoufUserId'])) {
	echo 'Error! You must be logged in to access this screen';
	exit;
}

?>