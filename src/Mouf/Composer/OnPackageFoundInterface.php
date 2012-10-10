<?php
namespace Mouf\Composer;

use Composer\Package\PackageInterface;

/**
 * Interface containing a callback called when a package is found.
 * 
 * @author David Negrier
 *
 */
interface OnPackageFoundInterface {
	
	function onPackageFound(PackageInterface $package, $score);
}