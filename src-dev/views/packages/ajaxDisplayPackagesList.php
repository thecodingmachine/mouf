<?php 
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
require_once 'displayPackageTreeUtils.php';

static $repositoryNb=0;
$repositoryNb++;
?>
<div id="innerrepository_<?php echo $repositoryNb ?>">
<p>
<a href="javascript:void(0)" class="toggleall">Toggle all</a>
<a href="javascript:void(0)" class="viewinstalled">View installed packages only</a>
<a href="javascript:void(0)" class="viewavailable" style="display:none">View all available packages</a>
</p>
<br/>
<?php 
displayGroup('.', $this->moufPackageRoot, '', $this); 

?>
</div>
<script type="text/javascript">
jQuery(document).ready(function() {

	jQuery("#innerrepository_<?php echo $repositoryNb ?> .treegroup .group").click(function(evt) {
		jQuery(evt.currentTarget).parent().children(".groupcontainer").slideToggle('normal');
		if (jQuery(evt.currentTarget).hasClass('groupminus')) {
			jQuery(evt.currentTarget).addClass('groupplus');
			jQuery(evt.currentTarget).removeClass('groupminus');
		} else {
			jQuery(evt.currentTarget).addClass('groupminus');
			jQuery(evt.currentTarget).removeClass('groupplus');
		}
	});

	jQuery("#innerrepository_<?php echo $repositoryNb ?> .toggleall").click(function() {
		jQuery('.treegroup .group').removeClass('groupplus');
		jQuery('.treegroup .group').addClass('groupminus');
		jQuery('.groupcontainer').show();
	});

	jQuery("#innerrepository_<?php echo $repositoryNb ?> .viewinstalled").click(function() {
		jQuery('.treegroup .group').removeClass('groupplus');
		jQuery('.treegroup .group').addClass('groupminus');
		jQuery('.groupcontainer').show();
		jQuery('.packagedisabled').hide();
		jQuery('#viewinstalled').hide();
		jQuery('#viewavailable').show();		
	});
	
	jQuery("#innerrepository_<?php echo $repositoryNb ?> .viewavailable").click(function() {
		jQuery('.packagedisabled').show();
		jQuery('#viewinstalled').show();
		jQuery('#viewavailable').hide();		
	});

	jQuery("#innerrepository_<?php echo $repositoryNb ?> .viewotherversions").click(function(evt) {
		jQuery(evt.currentTarget).parent().parent().parent().parent().children(".outerpackage").slideDown();
		jQuery(evt.currentTarget).hide();
	});

});

</script>