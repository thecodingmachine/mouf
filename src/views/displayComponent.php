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
<script type="text/javascript">


lastPropDisplayed = "";
dropDownCnt = 0;


2
3
4
56
7
8
9
1011
12
13
14
1516
17

	
// Escapes single quote, double quotes and backslash characters in a string with backslashes  
function addslashes (str) {
	return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
}

/*
 * Adds a new drowdown list dynamically inside element "element".
 * name is the name of the select box.
 */
function addNewDropDown(element, name, defaultValue, hasKey, defaultKey, type, isInArray, varType) {

	var imageId = name+"_mouf_ajaxload_"+dropDownCnt;
	var str = "";
	str += "<div id='"+name+"_mouf_dropdown_"+dropDownCnt+"'>";
	if (isInArray == true) {
		str += "<div class='moveable'></div>";
	}
	if (defaultValue != "") {
		str += "<span id='"+name+"_mouf_dropdown_text_"+dropDownCnt+"'>";
		if (hasKey) {
			str += defaultKey;
			str += "=&gt;";
		}
		str += "<a href='<?php echo ROOT_URL ?>mouf/mouf/displayComponent?name="+defaultValue+"&amp;selfedit=<?php echo $this->selfedit ?>'>"+defaultValue+"</a>";
		str += '<a onclick="fillOptionList(\''+name+"_mouf_dropdown_select_"+dropDownCnt+'\', \''+imageId+'\', \''+varType+'\', \''+defaultValue+'\'); document.getElementById(\''+name+'_mouf_dropdown_text_'+dropDownCnt+'\').style.display=\'none\';document.getElementById(\''+name+"_mouf_dropdown_dropdown_"+dropDownCnt+'\').style.display=\'inline\';" ><img src="<?php echo ROOT_URL; ?>mouf/views/images/pencil.png" alt="edit" /></a>';
		str += "</span>";
		str += "<span id='"+name+"_mouf_dropdown_dropdown_"+dropDownCnt+"' style='display:none'>";
	}
	if (hasKey) {
		str += "<input type='text' name='moufKeyFor"+htmlEntitiesAndQuotes(name)+"[]' value=\""+htmlEntitiesAndQuotes(defaultKey)+"\">";
		str += "=&gt;";
	}
	var arraySuffix = "";
	if (isInArray) {
		arraySuffix="[]";
	}
	str += "<select id='"+name+"_mouf_dropdown_select_"+dropDownCnt+"' name='"+name+arraySuffix+"'  onchange='propertySelectChange(this, \""+addslashes(name)+"\", \""+addslashes(type)+"\")'>";
	/*if (!isInArray) {
		str += "<option value=''></option>";
	}
	str += "<option value='newInstance'>Create New Instance</option>";
	jsonList.each(function(option) {
		var selected = "";
		if (option.id == defaultValue) {
			selected = ' selected="true"';
		}
		str += "<option value='"+option.id+"' "+selected+">"+option.text+"</option>";
	});*/
	// By default, before the options are filled, let's fill the select box with at least the previous value selected:
	if (defaultValue != "") {
		str += "<option value='"+htmlEntitiesAndQuotes(defaultValue)+"'>"+htmlEntitiesAndQuotes(defaultValue)+"</option>"
	}
	str += "</select>";
	str += "<img id='"+imageId+"' src='<?php echo ROOT_URL ?>mouf/views/images/ajax-loader.gif' alt='' />";
	
	if (isInArray == true) {
		str += "<a onclick='$(\""+name+"_mouf_dropdown_"+dropDownCnt+"\").remove()'><img src=\"<?php echo ROOT_URL ?>mouf/views/images/cross.png\"></a>";
	}
	//str += '<a onclick="document.getElementById(\''+name+'_mouf_dropdown_text_'+dropDownCnt+'\').style.display=\'inline\';document.getElementById(\''+name+"_mouf_dropdown_dropdown_"+dropDownCnt+'\').style.display=\'none\';"><img src="<?php echo ROOT_URL ?>mouf/views/images/tick.png"></a>';
	if (defaultValue != "") {
		str += "</span>";
	} else {
		fillOptionList(name+"_mouf_dropdown_select_"+dropDownCnt, imageId, varType, defaultValue);
	}
	str += "</div>";
	element.insert(str);
	/*if (jsonList.length == 0) {
		propertySelectChange(document.getElementById(name+"_mouf_dropdown_select_"+dropDownCnt), name, type);
	}*/
	dropDownCnt++;
	
}

function fillOptionList(selectId, imageId, varType, defaultValue) {
	var toSelect = defaultValue;
	var toSelectId = selectId;
	var ajaxLoadImageId = imageId;
	jQuery.getJSON("<?php echo ROOT_URL ?>mouf/direct/get_instances.php", {class: varType, selfedit: jQuery("#selfedit").val(), encode:"json"}, function(j){
      var options = '<option value=""></option>';
      options += "<option value='newInstance'>Create New Instance</option>";
      for (var i = 0; i < j.length; i++) {
        options += '<option value="' + htmlEntitiesAndQuotes(j[i]) + '"' ;
        if (toSelect == j[i]) {
        	options += 'selected="true"';
        }
        options += '>' + htmlEntitiesAndQuotes(j[i]) + '</option>';
      }
      jQuery("#"+toSelectId).html(options);
      jQuery("#"+ajaxLoadImageId).hide();
    })
}

/*
 * Adds a new textbox dynamically inside element "element".
 * name is the name of the select box.
 * defaultvalue its default value
 */
function addNewTextBox(element, name, defaultValue, hasKey, defaultKey) {
	var str = "";
	str += "<div id='"+name+"_mouf_dropdown_"+dropDownCnt+"'>";
	str += "<div class='moveable'></div>";
	if (hasKey) {
		str += "<input type='text' name='moufKeyFor"+htmlEntitiesAndQuotes(name)+"[]' value=\""+htmlEntitiesAndQuotes(defaultKey)+"\">";
		str += "=&gt;";
	}
	str += "<input type='text' name='"+htmlEntitiesAndQuotes(name)+"[]' value=\""+htmlEntitiesAndQuotes(defaultValue)+"\">";
	str += "<a onclick='$(\""+name+"_mouf_dropdown_"+dropDownCnt+"\").remove()'><img src=\"<?php echo ROOT_URL ?>mouf/views/images/cross.png\"></a>";
	str += "</div>";
	element.insert(str);
	dropDownCnt++;
}

/**
 * Protects special HTML chars.
 */
function htmlEntities(text) {
	return jQuery('<div/>').text(text).html();
}

/**
 * Protects special HTML chars and quotes.
 */
function htmlEntitiesAndQuotes(text) {
	html = htmlEntities(text);
	html=html.replace(/"/g, "&quot;") ;
	html=html.replace(/'/g, "&#146;") ;
	return html;
}

/*
 * Adds a new checkbox dynamically inside element "element".
 * name is the name of the select box.
 * defaultvalue its default value
 */
function addNewCheckBox(element, name, defaultValue, hasKey, defaultKey) {
	var str = "";
	str += "<div id='"+name+"_mouf_dropdown_"+dropDownCnt+"'>";
	str += "<div class='moveable'></div>";
	if (hasKey) {
		str += "<input type='text' name='moufKeyFor"+name+"[]' value=\""+defaultKey+"\">";
		str += "=&gt;";
	}
	str += "<input type='checkbox' name='"+name+"[]' value=\"true\" "+(defaultValue?"checked=\"checked\"":"")+"\" >";
	str += "<a onclick='$(\""+name+"_mouf_dropdown_"+dropDownCnt+"\").remove()'><img src=\"<?php echo ROOT_URL ?>mouf/views/images/cross.png\"></a>";
	str += "</div>";
	element.insert(str);
	dropDownCnt++;
}

function deleteInstance() {
	if (window.confirm("Are you sure you want to delete this instance?")) { // Clic sur OK
        document.getElementById("delete").value="1";
        document.getElementById("componentForm").submit();
    }
}


function displayProperties(propertyName) {
	if (lastPropDisplayed != "") {
		jQuery('#'+lastPropDisplayed+'_doc_div_mouf_all').hide();
		//jQuery('#'+lastPropDisplayed+'_doc_div_mouf_resume').show();
	}
	//jQuery('#'+propertyName+'_doc_div_mouf_resume').hide();
	jQuery('#'+propertyName+'_doc_div_mouf_all').show();
	lastPropDisplayed = propertyName;
}

// Apply style to autogrow:
jQuery(document).ready (function() {
	
	jQuery('textarea.string').autogrow();							
});
	
</script>

<form action="saveComponent" method="post" id="componentForm" accept-charset="UTF-8">

	<h1>Component 
		<span id="instanceNameText"><?php echo $this->instanceName ?></span>
		<span id="instanceNameTextbox" style="display:none" ><input type="text" name="instanceName" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" /></span>
	</h1>
	<div>

		<input type="hidden" name="originalInstanceName" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
		<input type="hidden" name="delete" id="delete" value="0" />
		<input type="hidden" name="selfedit" id="selfedit" value="<?php echo $this->selfedit; ?>" />
		
        <a id="modifyInstanceLink" href="javascript:void(0)" class="button modify" onclick="document.getElementById('modifyInstanceLink').style.visibility='hidden';document.getElementById('instanceNameText').style.display='none';document.getElementById('instanceNameTextbox').style.display='inline';">Rename</a>
		<a href="javascript:void(0)" class="button delete" onclick="deleteInstance()">Delete</a>
		<a href="javascript:void(0)" class="button duplicate" onclick="displayDuplicateInstanceDialog()">Duplicate</a>
	</div>
	<div>
		<br/>
		<?php if ($this->canBeWeak) { ?>
		 <input type="checkbox" name="weak" <?php if ($this->weak) { echo "checked='true'"; } ?> />Weak instance
		 <?php } else { ?>
		 <input type="checkbox" name="weak" disabled="disabled" />Weak instance
		 <?php } ?>
	</div>
	<div class="instance_parameter">
		<h2>Class <?php echo $this->className ?></h2>
		
		<div><?php echo $this->reflectionClass->getDocCommentWithoutAnnotations(); ?></div>
	</div>
	<table id="instance_properties">
		<tr>
			<td style="width: 59%">
				<h3>Properties:</h3>
			</td>
			<td style="width: 39%"></td>
		</tr>
		<?php 
			foreach ($this->properties as $property) {
				echo '<tr>';
				echo '<td>';
				echo "<div id='moufpropertyblock_".$property->getName()."' class='moufpropertyblock' onmouseover='displayProperties(\"".$property->getName()."\")'>\n";
				
				$compulsory = "";
				if ($property->hasAnnotation("Compulsory")) {
					$compulsory = '<span class="compulsory">*</span>';
				}
				$propertyName = $property->getName();
				echo '<label for="'.$propertyName.'">'.$propertyName.$compulsory."</label>";
				
				if ($property->hasAnnotation("OneOf")) {
					$oneOfs = $property->getAnnotations("OneOf");
					$oneOfValues = $oneOfs[0]->getPossibleValues();
					if ($property->hasAnnotation("OneOfText")) {
						$oneOfTexts = $property->getAnnotations("OneOfText");
						$oneOfTextValues = $oneOfTexts[0]->getPossibleValues();
					} else {
						$oneOfTextValues = $oneOfValues;
					}
					
					// TODO: YAAARGL: C'est plus que la default value qu'il faut!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
					// Il faut aussi la valeur de l'instance, la vraie!
					
					//$defaultValue = $this->instance->$propertyName;
			
					$defaultValue = $this->getValueForProperty($property);
			
					// TODO: add support for request/session/config for OneOf values
					echo '<input type="hidden" id="moufpropertytype_'.$property->getName().'" name="moufpropertytype_'.$property->getName().'" value="string"/>';
					
					//$defaultValue = MoufDefaultValueGetter::getDefaultValue($this->className, $this->propertyName);
			//var_dump($defaultValue);	
					echo '<select id="moufproperty_'.$property->getName().'" name="'.$property->getName().'" >';
					echo '<option value=""></option>';
					for ($i=0; $i<count($oneOfValues); $i++) {
						if ($oneOfValues[$i] == $defaultValue) {
							$selected = 'selected="true"';
						} else {
							$selected = '';
						}
						echo '<option value="'.plainstring_to_htmlprotected($oneOfValues[$i]).'" '.$selected.'>'.$oneOfTextValues[$i].'</option>';
					}
					echo '</select>';
					
				} else if ($property->hasType()) {
					//$varTypes = $property->getAnnotations("var");
					//$varTypeAnnot = $varTypes[0];
					$varType = $property->getType();
					$lowerVarType = strtolower($varType);
					if ($lowerVarType == "string" || $lowerVarType == "bool" || $lowerVarType == "boolean" || $lowerVarType == "int" || $lowerVarType == "integer" || $lowerVarType == "double" || $lowerVarType == "float" || $lowerVarType == "real" || $lowerVarType == "mixed" || $lowerVarType == "callback") {
						$defaultValue = $this->getValueForProperty($property);
						$defaultType = $this->getTypeForProperty($property);
						$metaData = $this->getMetadataForProperty($property);
					
						echo '<input type="hidden" id="moufpropertytype_'.$property->getName().'" name="moufpropertytype_'.$property->getName().'" value="'.$defaultType.'"/>';
							
						if ($lowerVarType == "bool" || $lowerVarType == "boolean") {
							echo '<input type="checkbox" id="moufproperty_'.$property->getName().'" name="'.$property->getName().'" value="true" '.($defaultValue?"checked='checked'":"").'"/>';
						} else {
							
							$this->displayFieldToolboxButton($property);
							
							// TODO: use metadata to display either a textarea or a textbox.
			
							echo '<textarea class="string" id="moufproperty_'.$property->getName().'" name="'.$property->getName().'">'.plainstring_to_htmlprotected($defaultValue).'</textarea>';
							//echo '<input type="text" id="moufproperty_'.$property->getName().'" name="'.$property->getName().'" value="'.plainstring_to_htmlprotected($defaultValue).'"/>';
							
						}
					} else if ($lowerVarType == "array") {
						//$recursiveType = $varTypeAnnot->getSubType();
						$recursiveType = $property->getSubType();
						
						if ($recursiveType == "string" || $recursiveType == "bool" || $recursiveType == "boolean" || $recursiveType == "int" || $recursiveType == "integer" || $recursiveType == "double" || $recursiveType == "float" || $recursiveType == "real" || $recursiveType == "mixed" || $recursiveType == "callback") {
							
							echo "<div class='moufFormList'>";
							// The div that will contain each array.
							echo "<div id='".$property->getName()."_mouf_array'>";
							
							echo "</div>";
							
							echo "<script>";
							echo "Event.observe(window, 'load', function() {\n";
							//$defaultValues = $this->instance->$propertyName;
							//$defaultValues = $this->reflectionClass->getDefault();
							$defaultValues = $this->getValueForProperty($property);
							//$isAssociative = $varTypeAnnot->isAssociativeArray();
							$isAssociative = $property->isAssociativeArray();
							
							if (is_array($defaultValues)) {
								foreach ($defaultValues as $defaultKey=>$defaultValue) {
									if ($isAssociative) {
										echo "addNewTextBox($(\"".addslashes($property->getName())."_mouf_array\"), \"".addslashes($property->getName())."\", \"".addslashes($defaultValue)."\", true, \"".addslashes($defaultKey)."\");\n";
									} else {
										echo "addNewTextBox($(\"".addslashes($property->getName())."_mouf_array\"), \"".addslashes($property->getName())."\", \"".addslashes($defaultValue)."\", false, \"\");\n";
									}
								}
							}
							
							echo "jQuery('#".addslashes($property->getName())."_mouf_array').sortable({handle:'.moveable'});";
			
							echo "\n});\n";
							echo "</script>";
							echo "<a onclick='addNewTextBox($(\"".addslashes($property->getName())."_mouf_array\"), \"".addslashes($property->getName())."\", \"\", ".(($isAssociative)?"true":"false").", \"\");'>Add a value</a>";
							echo "</div>";
							echo "<div style='clear:both'></div>";
							
						} else {
							// Ok, an array of objects, gogogo!
							// note: we do not handle array of arrays, sorry....
							
							// Let's try to find any instances that could match this type.
							/*$instances = $this->findInstances($recursiveType);
							// FIXME: findInstances est le responsable des performances déplorables sur les gros contrôleurs!
							// Il faudrait l'utiliser en Ajax, avec requête juste au moment où il faut!!!!!
							$instanceNameArray = array();
							// Let's build a JSON object from it:
							foreach ($instances as $instance) {
								$instanceNameArray[] = array("id"=>$instance, "text"=>$instance);
							}
							$jsonArray = json_encode($instanceNameArray);
							*/
							
							echo "<div class='moufFormList'>";
							// The div that will contain each array.
							echo "<div id='".$property->getName()."_mouf_array'>";
							
							echo "</div>";
							
							echo "<script>";
							echo "Event.observe(window, 'load', function() {\n";
			
							if ($property->isPublicFieldProperty()) {
								$defaultValues = $this->moufManager->getBoundComponentsOnProperty($this->instanceName, $property->getName());
							} else {
								$defaultValues = $this->moufManager->getBoundComponentsOnSetter($this->instanceName, $property->getMethodName());
							}
							//$isAssociative = $varTypeAnnot->isAssociativeArray();
							$isAssociative = $property->isAssociativeArray();
							
											
							if (is_array($defaultValues)) {
								foreach ($defaultValues as $defaultKey=>$defaultValue) {
									if ($isAssociative) {
										echo "addNewDropDown($(\"".addslashes($property->getName())."_mouf_array\"), \"".addslashes($property->getName())."\", \"".addslashes($defaultValue)."\", true, \"".addslashes($defaultKey)."\", \"".addslashes($property->getSubType())."\", true, \"".addslashes(plainstring_to_htmlprotected($recursiveType))."\");\n";
									} else {
										echo "addNewDropDown($(\"".addslashes($property->getName())."_mouf_array\"), \"".addslashes($property->getName())."\", \"".addslashes($defaultValue)."\", false, \"\", \"".addslashes($property->getSubType())."\", true, \"".addslashes(plainstring_to_htmlprotected($recursiveType))."\");\n";
									}
								}
							}
							
			
							echo "jQuery('#".$property->getName()."_mouf_array').sortable({handle:'.moveable'});";
							
							echo "\n});\n";
							echo "</script>";
							
							
							//$jsonArray = addslashes($jsonArray);
							//[{id:0, text:\"toto\"}, {id:1, text:\"tata\"}]
							echo "<a onclick='addNewDropDown($(\"".$property->getName()."_mouf_array\"), \"".$property->getName()."\", \"\", ".(($isAssociative)?"true":"false").", \"\", \"".addslashes($property->getSubType())."\", true, \"".addslashes(plainstring_to_htmlprotected($recursiveType))."\");'>Add a component</a>";
							
							echo "</div>";
							echo "<div style='clear:both'></div>";
							
						}
					} else {
						// Ok, there is a type, and it's not an array of types
						// Let's try to find any instances that could match this type.
						/*$instances = $this->findInstances($varType);
						// FIXME: findInstances est le responsable des performances déplorables sur les gros contrôleurs!
						// Il faudrait l'utiliser en Ajax, avec requête juste au moment où il faut!!!!!
						$instanceNameArray = array();
						// Let's build a JSON object from it:
						foreach ($instances as $instance) {
							$instanceNameArray[] = array("id"=>$instance, "text"=>$instance);
						}
						$jsonArray = json_encode($instanceNameArray);*/
						
						if ($property->isPublicFieldProperty()) {
							$defaultValue = $this->moufManager->getBoundComponentsOnProperty($this->instanceName, $property->getName());
						} else {
							$defaultValue = $this->moufManager->getBoundComponentsOnSetter($this->instanceName, $property->getMethodName());
						}
						
						/*$defaultDisplaySelect = "";
						if ($defaultValue != null) {
							echo '<span id="'.$property->getName().'_mouf_link" >';
							echo '<a href="'.ROOT_URL.'mouf/mouf/displayComponent?name='.plainstring_to_htmlprotected($defaultValue).'&amp;selfedit='.$this->selfedit.'">'.$defaultValue.'</a>';
							echo '<a onclick="document.getElementById(\''.$property->getName().'_mouf_link\').style.display=\'none\';document.getElementById(\'moufproperty_'.$property->getName().'\').style.display=\'inline\';" ><img src="'.ROOT_URL.'mouf/views/images/pencil.png" alt="edit" /></a>';
							echo "</span>\n";
							$defaultDisplaySelect = 'style="display:none"';
						}
						
						echo '<select id="moufproperty_'.$property->getName().'" name="'.$property->getName().'" '.$defaultDisplaySelect.' onchange="propertySelectChange(this, \''.$property->getName().'\', \''.$property->getType().'\')">';
						echo '<option value=""></option>';
						echo '<option value="newInstance">Create New Instance</option>';
						foreach ($instances as $instanceName) {
							if ($instanceName == $defaultValue) {
								$selected = 'selected="true"';
							} else {
								$selected = '';
							}
							echo '<option value="'.plainstring_to_htmlprotected($instanceName).'" '.$selected.'>'.$instanceName.'</option>';
						}
						echo '</select>';*/
						
						echo "<div id='".$property->getName()."_mouf'>";
							
						echo "</div>";
						
						echo "<script>\n";
						echo "jQuery(document).ready(function() {\n";
						echo "addNewDropDown($(\"".$property->getName()."_mouf\"), \"".$property->getName()."\", \"$defaultValue\", false, \"\", \"".addslashes($property->getType())."\", false, \"".addslashes(plainstring_to_htmlprotected($varType))."\");\n";
						
						echo "\n});\n";
						echo "</script>\n";
					}
					
					
				} else {
					//var_dump($property);
					//$defaultValue = $this->instance->$propertyName;
					//$defaultValue = $this->reflectionClass->getProperty($propertyName)->getDefault();
					$defaultValue = $this->getValueForProperty($property);
					
					echo '<input type="text" id="moufproperty_'.$property->getName().'" name="'.$property->getName().'" value="'.plainstring_to_htmlprotected($defaultValue).'" />';
					
					// TODO: uncomment to enable
					//echo '<a onclick="onPropertyOptionsClick(\''.$property->getName().'\')" href="javascript:void(0)" ><img src="'.ROOT_URL.'mouf/views/images/bullet_wrench.png" alt="Options" /></a>';
				}
				
				echo '</td>';
				echo '<td>';
				echo "<div class='instance_doc_div_mouf' style='position: relative;' onmouseover='displayProperties(\"".$property->getName()."\")'>\n";
				if(strpos($property->getDocCommentWithoutAnnotations(), '.') !== false)
					$resume = substr($property->getDocCommentWithoutAnnotations(), 0, strpos($property->getDocCommentWithoutAnnotations(), '.') + 1);
				else
					$resume = $property->getDocCommentWithoutAnnotations();
				$resume = strip_tags($resume);
				echo "<b>Property ".$property->getName()."</b><br />";
				
				if(strcmp(trim($resume), trim($property->getDocCommentWithoutAnnotations()))) {
					echo "<div id='".$property->getName()."_doc_div_mouf_all' style='position: absolute; z-index: 1000; top: 14px; background-color: #EEEEEE; border: 1px solid #CCCCCC; width: 100%; display: none'>\n";
					echo $property->getDocCommentWithoutAnnotations();
					echo "</div>\n";
				}
				else
					echo "<div id='".$property->getName()."_doc_div_mouf_all' style='display: none'></div>";
				echo "<div id='".$property->getName()."_doc_div_mouf_resume'>\n";
				echo $resume."<br />";
				echo "</div>\n";
				echo "</div>\n";
				echo '</td>';
				echo '</tr>';
				//echo "<div>".$property->getDocCommentWithoutAnnotations()."</div>";
			}
			?>
			<tr>
				<td>
					<input type="submit" value="Save" />
					<?php if (get("backto")!=null) {
						echo '<input class="art-button" type="button" value="Back" onclick="window.location=\''.urlencode(get('backto')).'\';return false;" />';
					} ?>
				</td>
		</tr>
	</table>

	<div style="clear: both"></div>
	<?php // The DIV dialog will not stay into the form (it will be moved by jQuery). Therefore, we must duplicate all fields of the dialog ui into the main ui ?>
	<input type="hidden" name="createNewInstance" id="createNewInstance" />
	<input type="hidden" name="duplicateInstance" id="duplicateInstance" />
	<input type="hidden" name="bindToProperty" id="bindToProperty" />
	<input type="hidden" name="newInstanceName" id="newInstanceName" />
	<input type="hidden" name="instanceClass" id="instanceClass" />
	
	<div id="dialog" title="Create a new instance" style="width: 600px; height: 400px">		
		
		<div>
		<label for="instanceNameDialog">Instance name:</label><input type="text" name="newInstanceNameDialog" id="newInstanceNameDialog" />
		</div>
		
		<div>
		<label for="instanceClass">Class:</label>
		<select name="instanceClassDialog" id="instanceClassDialog">
		</select>
		</div>
		
		<div class="error" id="noMatchingComponent" style="display:none"></div>
		
		<input type="button" value="Create" onclick="onCreateNewInstance(); return false;" />
		
	</div>
	<div id="duplicateDialog" title="Duplicate instance">	
		
		<div>
		<label for="instanceNameDialog">Instance name:</label><input type="text" name="duplicateInstanceNameDialog" id="duplicateInstanceNameDialog" />
		</div>
			
		<input type="button" value="Create" onclick="onDuplicateInstance(); return false;" />
		
	</div>
	<div id="dialogPropertyOptions" title="Property source">	
	
		<input type="hidden" id="editedPropertyName" />
	
		<div>
		<label for="propertySource">Source:</label>
		<select name="propertySource" id="propertySource" onchange="onSourceChange(this)"> 
			<option value="string">String</option>
			<option value="request">Request</option>
			<option value="session">Session</option>
			<option value="config">Config</option>
		</select>
		</div>
		
		<div id="propertySourceDiv">
			<div>
				<label for="propertyFieldType">Field type:</label>
				<select name="propertyFieldType" id="propertyFieldType" onchange="onFieldTypeChange(this)"> 
					<option value="field">Field</option>
					<option value="textarea">Textarea</option>
				</select>
			</div>
	
			<div>
				<label for="propertyValue">Property value:</label>
				<input type="text" name="propertyValue" id="propertyValue" />
			</div>
	
		</div>
	
		<div id="requestSourceDiv">
		<label for="requestValue">Request value:</label><input type="text" name="requestValue" id="requestValue" />
		</div>
	
		<div id="sessionSourceDiv">
		<label for="sessionValue">Session value:</label><input type="text" name="sessionValue" id="sessionValue" />
		</div>
	
		<div id="configSourceDiv">
		<label for="configValue">Config value:</label><select type="text" name="configValue" id="configValue" ></select>
		</div>
			
		<input type="button" value="Set property" onclick="onSetProperty(); return false;" />
		
	
	</div>

	<script type="text/javascript">
	jQuery(document).ready( function() {
	
		jQuery(function() {
			jQuery("#dialog").dialog({ autoOpen: false, width: 500 });
			jQuery("#dialogPropertyOptions").dialog({ autoOpen: false, width: 500 });
			jQuery("#duplicateDialog").dialog({ autoOpen: false, width: 500 });
		});
	 	
	});
	</script>

</form>
