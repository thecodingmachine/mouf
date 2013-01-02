<?php /* @var $this Mouf\Controllers\DocumentationController */
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

use Mouf\Composer\PackageInterface;
  ?>
<h1>Documentation for installed packages</h1>

<?php 


foreach ($this->packageList as $package):
//var_export($package->getPrettyName());echo "<br/>";
//var_export($package->getExtra());echo "<br/>";echo "<br/>";
	/* @var $package PackageInterface */
	$docPages = $this->getDocPages($package);
	?><h2><?php echo $package->getPrettyName() ?></h2>
	<p>Package <?php echo $package->getName()." ".$package->getVersion() ?></p>
	<?php 
	$this->displayDocDirectory($docPages, $package->getName());
	?>
	<?php

endforeach;
?>