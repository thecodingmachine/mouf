<?php 


?>
<div id="messages"></div>


<form action="createComponent" method="post" id="createInstanceForm">
<input type="hidden" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit); ?>" />


<h1>Create a new instance</h1>

<div>
<label for="instanceName">Instance name:</label><input type="text" name="instanceName" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
</div>

<div>
<label>Class:</label>
<a id="selectaclasslink" href="#">Click here to select a class</a>
<div id="selectedclasscontainer"></div>
</div>

<input type="hidden" name="instanceClass" value="<?php echo plainstring_to_htmlprotected($this->instanceClass) ?>" />


<input type="submit" value="Create" />

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

		jQuery("#selectaclasslink").hide();
		
		return false;
	}
	
	//MoufUI.displayInstanceOfType("#classesList", "NoRenderer", false, true);
	MoufUI.renderClassesList({
		onSelect: function(classDescriptor, classElem) {
			jQuery( "#classesList" ).dialog("close");
			jQuery("#selectedclasscontainer").empty();
			classDescriptor.render().appendTo("#selectedclasscontainer").click(openSelectClass);
			jQuery("input[name=instanceClass]").val(classDescriptor.getName());
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
	});
});

</script>