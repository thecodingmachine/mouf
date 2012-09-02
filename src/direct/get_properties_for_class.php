<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

require_once '../../Mouf.php';
require_once '../Moufspector.php';

// Note: checking rights is done after loading the required files because we need to open the session
// and only after can we check if it was not loaded before loading it ourselves...
require_once 'utils/check_rights.php';

$res = MoufManager::getMoufManager()->findInstances($_REQUEST["class"]);


?>