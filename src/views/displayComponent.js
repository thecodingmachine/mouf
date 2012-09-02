function propertySelectChange(dropdown, propertyName, type) {
	if (dropdown.value == 'newInstance') {
		// new instance was selected, let's display a dialog box to create the instance.
		dropdown.selectedIndex=0;
		displayCreateInstanceDialog(dropdown, propertyName, type);
	}
}

function displayCreateInstanceDialog(dropdown, propertyName, type) {
    jQuery("#noMatchingComponent").hide();
	jQuery.getJSON("../direct/get_components_list.php",{type: type, encode:"json", selfedit:jQuery('#selfedit').val(), ajax: 'true'}, function(j){
		
	      var options = '';
	      for (var i = 0; i < j.length; i++) {
	        options += '<option value="' + j[i] + '">' + j[i] + '</option>';
	      }
	      jQuery("select#instanceClassDialog").html(options);
	      
	      if (j.length == 0) {
		      jQuery("#noMatchingComponent").html("You have no class with the @Component annotation that inherits/implements '"+type+"'. You should try to <a href='../packagetransfer/'>download</a>/<a href='../packages/'>enable</a> a package that provides a component implement the "+type+" class/interface.");
		      jQuery("#noMatchingComponent").show();
	      }
	});

	lastSelectBox = dropdown;
	
	jQuery("#newInstanceName").val("");
	jQuery("#bindToProperty").val(propertyName);
	
	jQuery("#dialog").dialog("open");
}

/**
 * Called when the user clicks the 'duplicate instance' button.
 * @return
 */
function displayDuplicateInstanceDialog() {
	jQuery("#duplicateInstanceNameDialog").val("");
	
	jQuery("#duplicateDialog").dialog("open");
}

/**
 * Called when the user clicks the 'create new instance' button.
 * @return
 */
function onCreateNewInstance() {
	
	// Let's modify the select box to have it contain the new instance that will be created.
	// TODO protect against script injection.
	jQuery(lastSelectBox).html("<option value='"+jQuery("#newInstanceNameDialog").val()+"'>"+jQuery("#newInstanceNameDialog").val()+"</option>")
	
	jQuery("#createNewInstance").val("true");
	jQuery("#newInstanceName").val(jQuery("#newInstanceNameDialog").val());
	jQuery("#instanceClass").val(jQuery("#instanceClassDialog").val());
	
	jQuery("#componentForm").submit();
}

/**
 * Called when the user selects the 'create new instance' option.
 * @return
 */
function onDuplicateInstance() {
		
	jQuery("#duplicateInstance").val("true");
	jQuery("#newInstanceName").val(jQuery("#duplicateInstanceNameDialog").val());
	
	jQuery("#componentForm").submit();
}

/**
 * Called when the select dropdown for the source of the property is modified.
 * 
 * @param selectBox
 * @return
 */
function onSourceChange(selectDropDown) {
	if (selectDropDown.value == "string") {
		jQuery("#propertySourceDiv").show();
		jQuery("#requestSourceDiv").hide();
		jQuery("#sessionSourceDiv").hide();
		jQuery("#configSourceDiv").hide();
	} else if (selectDropDown.value == "request") {
		jQuery("#propertySourceDiv").hide();
		jQuery("#requestSourceDiv").show();
		jQuery("#sessionSourceDiv").hide();
		jQuery("#configSourceDiv").hide();
	} else if (selectDropDown.value == "session") {
		jQuery("#propertySourceDiv").hide();
		jQuery("#requestSourceDiv").hide();
		jQuery("#sessionSourceDiv").show();
		jQuery("#configSourceDiv").hide();
	} else if (selectDropDown.value == "config") {
		jQuery("#propertySourceDiv").hide();
		jQuery("#requestSourceDiv").hide();
		jQuery("#sessionSourceDiv").hide();
		jQuery("#configSourceDiv").show();
		loadConstantsInDropDown();
	}
}

/**
 * Called when the user clicks on the toolbox to edit a "string" property (and bind it to a config option/request parameter/...)
 * 
 * @param propertyName
 * @return
 */
function onPropertyOptionsClick(propertyName) {
	jQuery("#editedPropertyName").val(propertyName);
	var type = jQuery("#moufpropertytype_"+propertyName).val();
	
	jQuery("#propertySource").val(type);
	onSourceChange(document.getElementById("propertySource"));
	
	// TODO: initialize
	
	jQuery("#dialogPropertyOptions").dialog("open");
}

/**
 * Sets the property (called when the property option dialog is closed).
 * This function will change the type of the property.
 * 
 * @return
 */
function onSetProperty() {
	var type = jQuery("#propertySource").val();
	var propertyName = jQuery("#editedPropertyName").val();
	
	jQuery("#moufpropertytype_"+propertyName).val(type);
	
	jQuery("#moufpropertyblock_"+propertyName).find('.sessionmarker').hide();
	jQuery("#moufpropertyblock_"+propertyName).find('.configmarker').hide();
	jQuery("#moufpropertyblock_"+propertyName).find('.requestmarker').hide();
	if (type == "session") {
		jQuery("#moufpropertyblock_"+propertyName).find('.sessionmarker').show();	
	}
	if (type == "config") {
		jQuery("#moufpropertyblock_"+propertyName).find('.configmarker').show();
		jQuery("#moufproperty_"+propertyName).val(jQuery("#configValue").val());
	}
	if (type == "request") {
		jQuery("#moufpropertyblock_"+propertyName).find('.requestmarker').show();	
	}
	
	jQuery("#dialogPropertyOptions").dialog("close");
}

var constantsLoadedInDropDown = false;

/**
 * Loads the constants in the dropdown, if they are not loaded.
 * 
 * @return
 */
function loadConstantsInDropDown() {
	if (!constantsLoadedInDropDown) {
		jQuery.getJSON("../direct/get_defined_constants.php",{encode:"json", selfedit:jQuery('#selfedit').val(), ajax: 'true'}, function(msg){
		      var options = '';
		      for (var key in msg) {
		        options += '<option value="' + key + '">' + key + '</option>';
		      }
		      jQuery("select#configValue").html(options);
		});		
		
		constantsLoadedInDropDown = true;
	}
}
