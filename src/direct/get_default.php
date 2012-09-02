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
 * Returns the default value for a class property, as a serialized PHP string. 
 */


ini_set('display_errors', 1);
// Add E_ERROR to error reporting it it is not already set
error_reporting(E_ERROR | error_reporting());

require_once '../../Mouf.php';
require_once 'utils/check_rights.php';

// FIXME; moyen secure ça!
$instance = new $_REQUEST["class"]();
$property = $_REQUEST["property"];

echo serialize($instance->$property);

?>