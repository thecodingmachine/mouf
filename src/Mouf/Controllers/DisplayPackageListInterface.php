<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Controllers;

use Mouf\Splash\Controller;

/**
 * An interface implemented by controllers that can display a package list (using the displayGroup function).
 */
interface DisplayPackageListInterface {
	
	/**
	 * Display the rows of buttons below the package list.
	 * 
	 * @param MoufPackage $package The package to display
	 * @param string $enabledVersion The version of that package that is currently enabled, if any.
	 */
	function displayPackageActions(MoufPackage $package, $enabledVersion);
}