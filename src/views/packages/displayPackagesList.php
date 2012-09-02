<?php /* @var $this PackageController */
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
require_once 'displayPackageTreeUtils.php';
?>
<h1>Packages List</h1>

<?php 
if ($this->validationMsg != null) {
	echo '<div class="good">';
	if ($this->validationMsg == "enable") {
		echo "Packages successfully enabled: ";
	} else {
		echo "Packages successfully disabled: ";
	}
	echo implode(", ", $this->validationPackageList);
	echo '</div>';
?>
	<script type="text/javascript">
	setTimeout(function() {
		jQuery('.good').fadeOut(3000);
	}, 7000);
	</script>
<?php 
}
?>

<p>
<a href="javascript:void(0)" id="toggleall">Toggle all</a>
<a href="javascript:void(0)" id="viewinstalled">View installed packages only</a>
<a href="javascript:void(0)" id="viewavailable" style="display:none">View all available packages</a>
</p>
<br/>
<div id="packageList">
<?php
displayGroup('.', $this->moufPackageRoot, '', $this); 

?>
<script type="text/javascript">
jQuery(document).ready(function() {

	jQuery(".treegroup .group").click(function(evt) {
		// TODO: toggle with a VERTICAL slide effect
		jQuery(evt.currentTarget).parent().children(".groupcontainer").slideToggle('normal');
		if (jQuery(evt.currentTarget).hasClass('groupminus')) {
			jQuery(evt.currentTarget).addClass('groupplus');
			jQuery(evt.currentTarget).removeClass('groupminus');
		} else {
			jQuery(evt.currentTarget).addClass('groupminus');
			jQuery(evt.currentTarget).removeClass('groupplus');
		}
	});

	jQuery("#toggleall").click(function() {
		jQuery('.treegroup .group').removeClass('groupplus');
		jQuery('.treegroup .group').addClass('groupminus');
		jQuery('.groupcontainer').show();
	});

	jQuery("#viewinstalled").click(function() {
		jQuery('.treegroup .group').removeClass('groupplus');
		jQuery('.treegroup .group').addClass('groupminus');
		jQuery('.groupcontainer').show();
		jQuery('.packagedisabled').hide();
		jQuery('#viewinstalled').hide();
		jQuery('#viewavailable').show();		
	});
	
	jQuery("#viewavailable").click(function() {
		jQuery('.packagedisabled').show();
		jQuery('#viewinstalled').show();
		jQuery('#viewavailable').hide();		
	});

	jQuery(".viewotherversions").click(function(evt) {
		jQuery(evt.currentTarget).parent().parent().parent().parent().children(".outerpackage").slideDown();
		jQuery(evt.currentTarget).hide();
	});

});

</script>



<?php 
//$oldGroup = "";
//$oldName = "";
//$previousWasOldVersion = false;
//foreach ($this->moufPackageList as $package) {
//	$isOldVersion = false;
//	if ($package->getDescriptor()->getGroup()."/".$package->getDescriptor()->getName() == $oldName) {
//		$isOldVersion = true;
//	} else {
//		$oldName = $package->getDescriptor()->getGroup()."/".$package->getDescriptor()->getName();
//		// A container for the packages that have the same name. 
//		//echo "<div class='packagesContainer'>";
//	}
//	if ($package->getDescriptor()->getGroup() != $oldGroup) {
//		echo "<div class='group'>Group: <b>".htmlentities($package->getDescriptor()->getGroup())."</b></div>";
//		$oldGroup = $package->getDescriptor()->getGroup();
//	}
//	
//	// If this is the first package in the list to be the old version of the upper package.
//	if ($previousWasOldVersion == false && $isOldVersion == true) {
//		echo "show older versions";
//	}
//	
//	echo "<div class='outerpackage'>";
//	echo "<div class='package'>";
//	echo "<div class='packageicon'>";
//	if ($package->getLogoPath() != null) {
//		if (strpos($package->getLogoPath(), "http://") === 0 || strpos($package->getLogoPath(), "https://") === 0) {
//			echo "<img alt='' style='float:left' src='".$package->getLogoPath()."'>";
//		} else {
//			echo "<img alt='' style='float:left' src='".ROOT_URL."plugins/".$package->getPackageDirectory()."/".$package->getLogoPath()."'>";
//		}
//	}
//	echo "</div>";
//	echo "<div class='packagetext'>";
//	echo "<span class='packagename'>".htmlentities($package->getDisplayName())."</span> <span class='packgeversion'>(version ".htmlentities($package->getDescriptor()->getVersion()).")</span>";
//	if ($package->getShortDescription() || $package->getDocUrl()) {
//		echo "<div class='packagedescription'>";
//		echo $package->getShortDescription();
//		if ($package->getShortDescription() && $package->getDocUrl()) {
//			echo "<br/>";
//		}
//		if ($package->getDocUrl()) {
//			echo "Documentation URL: <a href='".htmlentities($package->getDocUrl())."'>".$package->getDocUrl()."</a>";
//		}
//		echo "</div>";
//	}
//	$packageXmlPath = $package->getDescriptor()->getPackageXmlPath();
//	if (!$this->moufManager->isPackageEnabled($packageXmlPath)) {
//		echo "<form action='enablePackage' method='POST'>";
//		echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
//		echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
//		echo "<button>Enable</button>";
//		echo "</form>";
//	} else {
//		echo "<form action='disablePackage' method='POST'>";
//		echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
//		echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
//		echo "<button>Disable</button>";
//		echo "</form>";
//	}
//	echo "</div></div></div>";
//	$previousWasOldVersion = $isOldVersion;
//}
?>

</div>
<br/>
<br/>