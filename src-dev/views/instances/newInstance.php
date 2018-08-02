<?php 
/* @var $this Mouf\Controllers\MoufController */

?>
<div id="messages"></div>


<form id="createInstanceForm" class="form-horizontal">
<input type="hidden" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit); ?>" />


<legend>Create a new instance</legend>

<div class="control-group">
	<label for="instanceName" class="control-label">Instance name:</label>
	<div class="controls">
		<input type="text" name="instanceName" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" placeholder="Your instance name" />
	</div>
</div>

<div class="control-group">
	<label class="control-label">Class:</label>
	<div class="controls">
		<button id="selectaclassbutton" class="btn btn-primary" type="button" disabled="disabled"><i class="loading-icon"></i> Please wait, loading class list...</button>
		<div id="selectedclasscontainer"></div>
	</div>
</div>

<input type="hidden" name="instanceClass" value="<?php echo plainstring_to_htmlprotected($this->instanceClass) ?>" />

<div class="control-group">
	<div class="controls">
		<button type="submit" value="Create" class="btn btn-danger">Create</button>
	</div>
</div>

</form>

<div class="row">
	<div class="span12">
		<div class="alert alert-info">
			<strong>Cannot find your class in the class list?</strong> Click on this button to purge the
			code cache and refresh the class list:
			<a href="refreshNewInstance?selfedit=<?php echo $this->selfedit ?>&instanceName=<?php htmlentities($this->instanceName, ENT_QUOTES, "UTF-8") ?>&instanceClass=<?php htmlentities($this->className, ENT_QUOTES, "UTF-8") ?>" class="btn btn-success"><i class="icon-white icon-refresh"></i> Purge code cache and refresh</a>
		</div>
		<div class="alert alert-info">
			<strong>Still cannot find your class in the class list?</strong> An error might prevent Mouf from loading
			it. <a href="../includes/?selfedit=<?php echo $this->selfedit ?>">Head over to the class analyzer and check that your class has no errors</a>.
		</div>
		<div class="alert alert-info">
			<strong>Still nothing?</strong> It is likely Composer cannot find your class. You might not have configured the <a href="http://getcomposer.org/doc/01-basic-usage.md#autoloading">composer autoloader
			correctly</a>, or you might need to <a href="http://getcomposer.org/doc/03-cli.md#dump-autoload">dump the autoloader</a> after changing the settings or your class might not respect the <a href="https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md">PSR-0 standard</a> or the <a href="https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md">PSR-4 standard</a>...
		</div>
	</div>
</div>

<div id="classesList" style="display:none"></div>

<script type="text/javascript">
jQuery(document).ready(function() {

	var openSelectClass = function() {
		jQuery( "#classesList" ).dialog({
			height: jQuery(window).height()*0.9,
			width: jQuery(window).width()*0.95,
			zIndex: 20000,
			title: "Select a class",
			modal: true /*,
			close: function() {
				container.remove();
			}*/
		});

		return false;
	}
	
	//MoufUI.displayInstanceOfType("#classesList", "NoRenderer", false, true);
	MoufUI.renderClassesList({
		onSelect: function(classDescriptor, classElem) {
			jQuery( "#classesList" ).dialog("close");
			jQuery("#selectedclasscontainer").empty();
			classDescriptor.render().appendTo("#selectedclasscontainer").click(openSelectClass);
			jQuery("input[name=instanceClass]").val(classDescriptor.getName());

			jQuery("#selectaclassbutton").hide();
		},
		onReady: function() {
			$("#selectaclassbutton").attr("disabled", false).text("Click here to select a class");
		}
	}).appendTo("#classesList");

	jQuery("#selectaclassbutton").click(openSelectClass);

	<?php 
	if ($this->instanceClass) {		
	?>
	jQuery("#selectaclassbutton").hide();
	MoufInstanceManager.getClass(<?php echo json_encode($this->instanceClass); ?>).then(function(classDescriptor) {
		classDescriptor.render().appendTo("#selectedclasscontainer").click(openSelectClass);
	});
	<?php 
	}
	?>

	jQuery("#createInstanceForm").submit(function() {
		if (jQuery("input[name=instanceName]").val() == "") {
			alert("Please enter an instance name");
			return false;
		}
		if (jQuery("input[name=instanceClass]").val() == "") {
			alert("Please select a class");
			return false;
		}

		
		jQuery("#createInstanceForm button").attr("disabled", true);
		
		var classDescriptor = jQuery("#selectedclasscontainer div").data("class");

		if (classDescriptor.getExportMode() != 'all') {
			MoufInstanceManager.getClass(classDescriptor.getName()).then(function(fullClassDescriptor) {
				MoufInstanceManager.newInstance(fullClassDescriptor, jQuery("input[name=instanceName]").val(), false);
			});
		} else {
			MoufInstanceManager.newInstance(classDescriptor, jQuery("input[name=instanceName]").val(), false);
		}
		
		return false;
	});

	MoufSaveManager.onSaveStatusChange(function(status) {
		// If the status changes, it is that we are in the process of saving...
		if (status == "saved") {
			window.location = "../ajaxinstance/?name="+jQuery("input[name=instanceName]").val()+"&selfedit=<?php echo $this->selfedit ?>";
		}
	});
});

</script>