<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

/* @var $this Mouf\Controllers\Composer\InstalledPackageController */

use Mouf\Composer\PackageInterface;
?>
<h1>Installed packages</h1>

<?php 
foreach ($this->packageList as $package):
	/* @var $package PackageInterface */
var_export($package->getPrettyName());echo "<br/>";
var_export($package->getExtra());echo "<br/>";echo "<br/>";
endforeach;
?>