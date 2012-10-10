<?php /* @var $this PackageController */
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
<h2>You selected this package:</h2>

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
	if ($this->package->getCurrentLocation() != null) {
		echo "<br/>This package will be downloaded from repository '".plainstring_to_htmlprotected($this->package->getCurrentLocation()->getName())."'";
	}
	echo "</div>";
}	
echo "</div></div>";

if ($this->moufDependencies):
?>
<h2>The following packages needs to be installed too:</h2>

<div id="packageList" class="packageList">
<?php 
$oldGroup = "";
foreach ($this->moufDependencies as $scope=>$innerList) {
	echo "<h2>Dependencies to be installed in $scope scope</h2>";
	foreach ($innerList as $package) {
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
			if ($package->getCurrentLocation() != null) {
				echo "<br/>This package will be downloaded from repository '".plainstring_to_htmlprotected($package->getCurrentLocation()->getName())."'";
			}
			echo "</div>";
		}
		
		echo "</div></div>";
	}
}
endif;

if ($this->upgradePackageList):
?>
<h2>The following packages needs to be updated:</h2>
<div id="updateList" class="packageList">

<?php 
$oldGroup = "";
foreach ($this->upgradePackageList as $scope=>$innerList) {
	echo "<h2>Dependencies to be upgraded in $scope scope</h2>";
	foreach ($innerList as $package) {
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
			if ($package->getCurrentLocation() != null) {
				echo "<br/>This package will be downloaded from repository '".plainstring_to_htmlprotected($package->getCurrentLocation()->getName())."'";
			}
			echo "</div>";
		}
		
		echo "</div></div>";
	}
}
?>

</div>
<?php 
endif;

//$packageXmlPath = $this->package->getDescriptor()->getPackageXmlPath();

// If there are no packages that we must decide to update, let's propose the "confirm" button.
if (empty($this->toProposeUpgradePackage)) {
	echo "<form action='enablePackage' method='POST'>";
	echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
	echo "<input type='hidden' name='group' value='".htmlentities($this->package->getDescriptor()->getGroup())."' />";
	echo "<input type='hidden' name='name' value='".htmlentities($this->package->getDescriptor()->getName())."' />";
	echo "<input type='hidden' name='version' value='".htmlentities($this->package->getDescriptor()->getVersion())."' />";
	echo "<input type='hidden' name='origin' value='".htmlentities($this->origin)."' />";
	echo "<input type='hidden' name='confirm' value='true' />";
	$i=0;
	foreach ($this->upgradePackageList as $myScope=>$innerPackageList) {
		// List of packages to upgrade.
		foreach ($innerPackageList as $upgradePackage) {
			/* @var $upgradePackage MoufPackage */
			echo "<input type='hidden' name='upgradeList[$myScope][".$i."][group]' value='".plainstring_to_htmlprotected($upgradePackage->getDescriptor()->getGroup())."' />";
			echo "<input type='hidden' name='upgradeList[$myScope][".$i."][name]' value='".plainstring_to_htmlprotected($upgradePackage->getDescriptor()->getName())."' />";
			echo "<input type='hidden' name='upgradeList[$myScope][".$i."][version]' value='".plainstring_to_htmlprotected($upgradePackage->getDescriptor()->getVersion())."' />";
			if ($upgradePackage->getCurrentLocation()) {
				echo "<input type='hidden' name='upgradeList[$myScope][".$i."][origin]' value='".plainstring_to_htmlprotected($upgradePackage->getCurrentLocation())."' />";
			}
			$i++;
		}
	}
	echo "<button>Enable all listed packages</button>";
	echo "</form>";
}
?>
</div>
<?php 
// If there are packages that we must decide to update, let's display the packages list.
if (!empty($this->toProposeUpgradePackage)):
?>
<h2>In order to proceed, you must update some packages:</h2>
<?php 
$j=0;
foreach ($this->toProposeUpgradePackage as $incompatiblePackage) {
	/* @var $incompatiblePackage MoufIncompatiblePackageException */
	
	// For each package, as soon we select the version, we submit the form.
	// This is to be sure the user won't select 2 packages with incompatible versions.

	
	echo "<div class='outerpackage'>";
	echo "<div class='package'><span class='packagename'>".htmlentities($incompatiblePackage->dependencyName)."</span><span class='packagegroup'>(group ".htmlentities($incompatiblePackage->dependencyGroup).")</span>";
	echo "<div class='packagedescription'>";

	echo "This package is currently installed (or requested) in version <b>".htmlentities($incompatiblePackage->inPlaceVersion)."</b>";
	echo "<br/>However, package ".htmlentities($incompatiblePackage->group."/".$incompatiblePackage->name."/".$incompatiblePackage->version)." requires packages ".htmlentities($incompatiblePackage->dependencyGroup)."/".htmlentities($incompatiblePackage->dependencyName)." version to be ".htmlentities($incompatiblePackage->requestedVersion);

	// FIXME: This work for downward problems but not for upwards problems:
	// This fails if the package installed is USED by a parent package
	// In this case, we should check the compatible versions for the PARENT package....
	$versions = $this->getCompatibleVersionsForPackage($incompatiblePackage->dependency);
	
	// FIXME: proposed versions are not necessarily newer, and that might be a problem.
	// TODO: sort the list according to version number.
	
	echo "<form action='enablePackage' method='POST' id='updateform_".$j."'>";
	echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
	echo "<input type='hidden' name='group' value='".htmlentities($this->package->getDescriptor()->getGroup())."' />";
	echo "<input type='hidden' name='name' value='".htmlentities($this->package->getDescriptor()->getName())."' />";
	echo "<input type='hidden' name='version' value='".htmlentities($this->package->getDescriptor()->getVersion())."' />";
	echo "<input type='hidden' name='confirm' value='true' />";
	$i=0;
	foreach ($this->upgradePackageList as $myScope=>$innerPackageList) {
		// List of packages to upgrade.
		foreach ($innerPackageList as $upgradePackage) {
			/* @var $upgradePackage MoufPackage */
			echo "<input type='hidden' name='upgradeList[$myScope][".$i."][group]' value='".plainstring_to_htmlprotected($upgradePackage->getDescriptor()->getGroup())."' />";
			echo "<input type='hidden' name='upgradeList[$myScope][".$i."][name]' value='".plainstring_to_htmlprotected($upgradePackage->getDescriptor()->getName())."' />";
			echo "<input type='hidden' name='upgradeList[$myScope][".$i."][version]' value='".plainstring_to_htmlprotected($upgradePackage->getDescriptor()->getVersion())."' />";
			if ($upgradePackage->getCurrentLocation()) {
				echo "<input type='hidden' name='upgradeList[$myScope][".$i."][origin]' value='".plainstring_to_htmlprotected($upgradePackage->getCurrentLocation())."' />";
			}
			$i++;
		}
	}
	
	// TODO: HTML CODE FOR THE FORM HERE.
	
	echo "<input type='hidden' name='upgradeList[".$incompatiblePackage->scope."][".$i."][group]' value='".$incompatiblePackage->dependency->getGroup()."' id='group_".$j."' />";
	echo "<input type='hidden' name='upgradeList[".$incompatiblePackage->scope."][".$i."][name]' value='".$incompatiblePackage->dependency->getName()."' id='name_".$j."' />";
	echo "<input type='hidden' name='upgradeList[".$incompatiblePackage->scope."][".$i."][version]' value='' id='version_".$j."' />";
	echo "<input type='hidden' name='upgradeList[".$incompatiblePackage->scope."][".$i."][origin]' value='' id='origin_".$j."' />";
	
	echo "<select id='updateselect_".$j."'>";
	echo "<option value=''></option>";
	foreach ($versions as $version=>$package) {
		echo "<option value='".plainstring_to_htmlprotected($version)."'>".plainstring_to_htmlprotected($version)."</option>";
	}
	echo "</select>";
	
	echo "</form>";
	
?>
	<script type="text/javascript">
	
	jQuery("#updateselect_<?php echo $j ?>").change(function() {
		// Let's prepare an object that contains the values to be entered
		// Let's build an array that associates the version to the origin
		<?php $versionToOriginArray = array();
		foreach ($versions as $version=>$package) {
			/* @var $package MoufPackage */
			if ($package->getCurrentLocation() == null) {
				$versionToOriginArray[$version] = null;
			} else {
				$versionToOriginArray[$version] = $package->getCurrentLocation()->getUrl();
			}
		}
		?>
		var versionToOriginArray = <?php echo json_encode($versionToOriginArray); ?>;
		var value = jQuery("#updateselect_<?php echo $j ?>").val();
		jQuery("#version_<?php echo $j ?>").val(value);
		jQuery("#origin_<?php echo $j ?>").val(versionToOriginArray[value]);
		jQuery("#updateform_<?php echo $j ?>").submit();
	});
	</script>
<?php 
	
	// TODO
	// TODO
	// TODO
	// TODO
	// TODO
	// TODO
	// TODO
	// TODO
	// TODO: FROM THE EXCEPTION, WE MUST DISPLAY THE LIST OF VERSIONS OF THE PACKAGE THAT WOULD BE POTENTIAL NEW COMERS
	// WE NEED A METHOD THAT SCANS ALL THE VERSIONS OF A PACKAGE (in all repositories) AND PROPOSES THE LIST OF VERSIONS THAT ARE:
	// 1- newer than the installed version
	// 2- compatible with the requirements.
	// Then, we can display the list of versions.
	// Then, we can propose a checkbox to select the update, and a dropdown to select the version.
	
	echo "</div></div>";
	
	$j++;
	
}

endif;
?>

<br/>
<br/>