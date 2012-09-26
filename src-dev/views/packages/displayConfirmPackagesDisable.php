<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
?>
<h1>Packages List</h1>
<h2>You selected this package for removal:</h2>

<?php 
	echo "<div class='group'>Group: <b>".htmlentities($this->package->getDescriptor()->getGroup())."</b></div>";
	echo "<div class='outerpackage'>";
	echo "<div class='package'><span class='packagename'>".htmlentities($this->package->getDisplayName())."</span> <span class='packgeversion'>(version ".htmlentities($this->package->getDescriptor()->getVersion()).")</span>";
	if ($this->package->getShortDescription() || $this->package->getDocUrl()) {
		echo "<div class='packagedescription'>";
		echo $this->package->getShortDescription();
		if ($this->package->getShortDescription() && $this->package->getDocUrl()) {
			echo "<br/>";
		}
		if ($this->package->getDocUrl()) {
			echo "Documentation URL: <a href='".htmlentities($this->package->getDocUrl())."'>".$this->package->getDocUrl()."</a>";
		}
		echo "</div>";
	}	
	echo "</div></div>";
?>

<?php 
if (count($this->moufDependencies)>1) {
?>
<h2>The following packages needs to be disabled too:</h2>

<div id="packageList">
<?php 
$oldGroup = "";
foreach ($this->moufDependencies as $package) {
	if ($this->package != $package) { 
		if ($package->getDescriptor()->getGroup() != $oldGroup) {
			echo "<div class='group'>Group: <b>".htmlentities($package->getDescriptor()->getGroup())."</b></div>";
			$oldGroup = $package->getDescriptor()->getGroup();
		}
		echo "<div class='outerpackage'>";
		echo "<div class='package'><span class='packagename'>".htmlentities($package->getDisplayName())."</span> <span class='packgeversion'>(version ".htmlentities($package->getDescriptor()->getVersion()).")</span>";
		if ($package->getShortDescription() || $package->getDocUrl()) {
			echo "<div class='packagedescription'>";
			echo $package->getShortDescription();
			if ($package->getShortDescription() && $package->getDocUrl()) {
				echo "<br/>";
			}
			if ($package->getDocUrl()) {
				echo "Documentation URL: <a href='".htmlentities($package->getDocUrl())."'>".$package->getDocUrl()."</a>";
			}
			echo "</div>";
		}
		
		echo "</div></div>";
	}
}
}
?>
<h2>The following instances will be deleted:</h2>
<?php 

foreach ($this->toDeleteInstance as $instanceName=>$className) {
	echo $instanceName." - ".$className."<br/>";
}
?>

<?php 
$packageXmlPath = $this->package->getDescriptor()->getPackageXmlPath();
echo "<form action='disablePackage' method='POST'>";
echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
echo "<input type='hidden' name='group' value='".htmlentities($this->package->getDescriptor()->getGroup())."' />";
echo "<input type='hidden' name='name' value='".htmlentities($this->package->getDescriptor()->getName())."' />";
echo "<input type='hidden' name='version' value='".htmlentities($this->package->getDescriptor()->getVersion())."' />";
echo "<input type='hidden' name='confirm' value='true' />";
echo "<button>Disable all listed packages</button>";
echo "</form>";
?>



</div>
<br/>
<br/>