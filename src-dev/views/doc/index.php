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
	if ($docPages) {
		$extra = $package->getExtra();
		if (isset($extra['mouf']['logo'])) {
			$imgUrl = ROOT_URL.'../../'.$package->getName()."/".$extra['mouf']['logo'];
		} else {
			$imgUrl = ROOT_URL.'src-dev/views/images/package.png';
		}

		?>
		<div class="media">
	    	<a class="pull-left" href="#">
	    		<img class="media-object" src="<?php echo $imgUrl ?>">
	    	</a>
	    	<div class="media-body">
	    		<h4 class="media-heading">
				<?php echo $package->getPrettyName() ?> <small><?php echo $package->getPrettyVersion() ?></small>
				</h4>
		
		<?php 
		$this->displayDocDirectory($docPages, $package->getName());
		?>
			</div>
		</div>
		<?php 
	}

endforeach;
?>