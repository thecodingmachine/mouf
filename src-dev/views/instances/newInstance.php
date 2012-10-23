<?php 
/* @var $this Mouf\Controllers\MoufController */

?>
<div id="messages"></div>


<form action="createComponent" method="post" id="createInstanceForm" class="form-horizontal">
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
		<a id="selectaclasslink" href="#">Click here to select a class</a>
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

			jQuery("#selectaclasslink").hide();
		}
	}).appendTo("#classesList");

	jQuery("#selectaclasslink").click(openSelectClass);

	<?php 
	if ($this->instanceClass) {		
	?>
	jQuery("#selectaclasslink").hide();
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
		MoufInstanceManager.newInstance(classDescriptor, jQuery("input[name=instanceName]").val(), false);

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