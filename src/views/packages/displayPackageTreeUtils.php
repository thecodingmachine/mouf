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
 * This file contains utility functions to display a package tree.
 */

/**
 * Displays the package tree, strating with the MoufGroupDescriptor passed in parameter.
 * 
 * @param unknown_type $name
 * @param unknown_type $group
 * @param unknown_type $fullName
 * @param unknown_type $controller
 */
function displayGroup($name, MoufGroupDescriptor $group, $fullName, $controller) {
	if ($fullName != '') {
		echo "<div class='treegroup'>\n";
		echo "<div class='group groupplus'>";
		echo "<div style='float:right'>";
		echo "Group name: ".$fullName;
		echo "</div><b>";
		echo $name;
		echo "</b></div>";
		echo "<div class='groupcontainer' style='display:none'>";
	}
	foreach ($group->subGroups as $subgroupname=>$subgroup) {
		if ($fullName == "") {
			$newFullName = $subgroupname;
		} else {
			$newFullName = $fullName."/".$subgroupname;
		}
		displayGroup($subgroupname, $subgroup, $newFullName, $controller);
	}
	foreach ($group->packages as $packagename=>$packageversionscontainer) {
		displayPackageVersionContainer($packagename, $packageversionscontainer, $controller);
	}
	if ($fullName != '') {	
		echo "</div>";
		echo "</div>";
	}
}

function displayPackageVersionContainer($packagename, MoufPackageVersionsContainer $packageversionscontainer, DisplayPackageListInterface $controller) {
	
	$enabledVersion = false;
	
	// First, let's get through the versions, and see if one is enabled...
	foreach ($packageversionscontainer->packages as $package) {
		/* @var $package MoufPackage */
		
		$packageXmlPath = $package->getDescriptor()->getPackageXmlPath();
		$isPackageEnabled = $controller->moufManager->isPackageEnabled($packageXmlPath);

		if ($isPackageEnabled) {
			$enabledVersion = $package->getDescriptor()->getVersion();
			break;
		}
	}
	
	$isFirst = true;

	echo "<div class='packageversions'>";
	foreach ($packageversionscontainer->packages as $package) {
		/* @var $package MoufPackage */
		
		if (($isFirst && $enabledVersion === false) || $enabledVersion == $package->getDescriptor()->getVersion()) {
			$display = true;
		} else {
			$display = false;
		}
		
		$packageXmlPath = $package->getDescriptor()->getPackageXmlPath();
		$isPackageEnabled = $controller->moufManager->isPackageEnabled($packageXmlPath);
		
		echo "<div class='outerpackage' ".(($display)?"":"style='display:none'").">";
		echo "<div class='package ".(($isPackageEnabled)?"packageenabled":"packagedisabled")."'>";
		echo "<div class='packageicon'>";
		if ($package->getLogoPath() != null) {
			if (strpos($package->getLogoPath(), "http://") === 0 || strpos($package->getLogoPath(), "https://") === 0) {
				echo "<img alt='' style='float:left' src='".$package->getLogoPath()."'>";
			} else {
				echo "<img alt='' style='float:left' src='".ROOT_URL."plugins/".$package->getPackageDirectory()."/".$package->getLogoPath()."'>";
			}
		}
		echo "</div>";
		echo "<div class='packagetext'>";
		echo "<span class='packagename'>".htmlentities($package->getDisplayName())."</span> <span class='packgeversion'>(version ".htmlentities($package->getDescriptor()->getVersion()).")</span>";
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
		if ($display && count($packageversionscontainer->packages) > 1) {
			echo "<a href='javascript:void(0)' class='viewotherversions'>View other versions</a>";
		}
		
		$controller->displayPackageActions($package, $enabledVersion);
		echo "</div></div></div>";
		$isFirst = false;
	}
	echo "</div>";
}


?>