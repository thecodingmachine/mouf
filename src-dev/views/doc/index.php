<?php /* @var $this DocumentationController */
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
var_export($package->getExtra());
	/* @var $package PackageInterface */
	$docPages = $package->getExtra();
	if ($docPages):
		?><h2><?php echo $package->getDisplayName() ?></h2>
		<p>Package <?php echo $package->getDescriptor()->getGroup()."/".$package->getDescriptor()->getName()." Version: ".$package->getDescriptor()->getVersion() ?></p>
		<?php 
		$this->displayDocDirectory($docPages);
		?>
		<?php
	endif;
endforeach;
?>