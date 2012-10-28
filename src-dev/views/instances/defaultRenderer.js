

/**
 * The default renderer if no renderer is to be found.
 * This renderer is in charge of rendering instances (small, medium, big) and classes.
 */
var MoufDefaultRenderer = (function () {

	/**
	 * Returns the wrapper DIV element in which the class will be stored.
	 * The wrapper DIV will have appropriate CSS classes to handle drag'n'drop.
	 * The wrapper is returned as an "in-memory" jQuery element.
	 */
	var getClassWrapper = function(classDescriptor) {
		// Note: slice is performing a clone of the array
		var subclassOf = classDescriptor.json["implements"].slice(0);
		var parentClass = classDescriptor;
		do {
			subclassOf.push(parentClass.getName());
			parentClass = parentClass.getParentClass();
		} while (parentClass);
		var cssClass = "";
		for (var i = 0; i<subclassOf.length; i++) {
			cssClass += MoufUI.getCssNameFromType(subclassOf[i]) + " ";
		}
		return jQuery("<div/>").addClass(cssClass).data("class", classDescriptor);
	}
	
	/**
	 * Returns the wrapper DIV element in which the instance will be stored.
	 * The wrapper DIV will have appropriate CSS classes to handle drag'n'drop, the "instance" class,
	 * and additional jQuery "data" attached to find back the name of the instance.
	 * The wrapper is returned as an "in-memory" jQuery element.
	 */
	var getInstanceWrapper = function(instanceDescriptor) {
		var classDescriptor = MoufInstanceManager.getLocalClass(instanceDescriptor.getClassName());

		return getClassWrapper(classDescriptor).addClass("instance").data("instance", instanceDescriptor);
	}
	

	/**
	 * Protects HTML special chars
	 */
	var htmlEncode = function(value){
		return jQuery('<div/>').text(value).html();
	}

	/**
	 * Unprotects HTML special chars
	 */
	var htmlDecode = function(value){
	  return jQuery('<div/>').html(value).text();
	}

	/**
	 * Renders a text input, for the instance "instance", and the property moufProperty.
	 * The "in-memory" jQuery object for the field is returned.
	 */
	var renderStringField = function(moufInstanceProperty) {
		var name = moufInstanceProperty.getName();
		var value = moufInstanceProperty.getValue();
		
		var parentElem = jQuery('<div/>').addClass("stringRenderer");
		
		var elem = jQuery("<input/>").attr('name', name)
			.attr("value", value)
			.change(function() {
				moufInstanceProperty.setValue(jQuery(this).val());
				//alert("value changed in "+findInstance(jQuery(this)).getName() + " for property "+name);
			});
		
		if (value === null) {
			elem.addClass("null");
			elem.val("null");
		}
		
		elem.focus(function() {
			if (elem.hasClass("null")) {
				elem.val("");
				elem.removeClass("null");
			}
		})
		
		
		/*var menu = MoufUI.createMenuIcon([
			{
				label: "Set to <em>null</em>",
				click: function() {
					elem.addClass("null");
					elem.val("null");
					moufInstanceProperty.setValue(null);
				}
			}
		]);*/
		
		elem.appendTo(parentElem);
		//menu.appendTo(parentElem);
		
		return parentElem;
	}
	
	/**
	 * Renders a checkbox field, for the instance "instance", and the property moufProperty.
	 * The "in-memory" jQuery object for the field is returned.
	 */
	var renderBoolField = function(moufInstanceProperty) {
		var name = moufInstanceProperty.getName();
		var value = moufInstanceProperty.getValue();
		
		var parentElem = jQuery('<div/>').addClass("boolRenderer");
		
		var elem = jQuery("<input/>").attr("type", "checkbox").attr('name', name)
			.attr("checked", value)
			.change(function() {
				moufInstanceProperty.setValue(jQuery(this).is(':checked'));
			});
		
		elem.appendTo(parentElem);
		return parentElem;
	}
	
	/**
	 * Renders an array of fields, for the instance "instance", and the property moufProperty.
	 * The "in-memory" jQuery object for the field is returned.
	 * 
	 */
	var renderArrayField = function(moufInstanceProperty) {
		var name = moufInstanceProperty.getName();
		var values = moufInstanceProperty.getValue();
		var moufProperty = moufInstanceProperty.getMoufProperty();
		var elem = jQuery("<div/>");
		var sortable = jQuery("<div/>").addClass('array');
		sortable.appendTo(elem);

		if (!moufProperty.isAssociativeArray())  {
			if (values instanceof Array) {
				moufInstanceProperty.forEachArrayElement(function(instanceSubProperty) {
					var fieldElem = jQuery("<div/>").addClass('fieldContainer')
						.data("key", instanceSubProperty.getKey())
						.appendTo(sortable);
						
					var sortableElem = jQuery("<div/>").addClass('sortable');
					jQuery("<div/>").addClass('moveable').appendTo(fieldElem);
					/*fieldRenderer = getFieldRenderer(instanceSubProperty.getMoufProperty().getType(), instanceSubProperty.getMoufProperty().getKeyType(), instanceSubProperty.getMoufProperty().getSubType());
					var rowElem = fieldRenderer(instanceSubProperty);*/
					var rowElem = renderField(instanceSubProperty);
					rowElem.appendTo(fieldElem);
				});
			}
			var subtype = moufProperty.getSubType();
			// If this is a known primitive type, let's display a "add a value" button
			
			
			//var addDiv = jQuery("<div/>").addClass('addavalue')
			var addDiv = jQuery("<a/>").addClass('btn btn-mini btn-success')
				.appendTo(elem)
				.click(function() {
					// key=null (since we are not an associative array), and we init the value to null too.
					var moufNewSubInstanceProperty = moufInstanceProperty.addArrayElement(null, null);
					
					var fieldElem = jQuery("<div/>").addClass('fieldContainer')
						.data("key", moufNewSubInstanceProperty.getKey())
						.appendTo(sortable);
					
					var sortableElem = jQuery("<div/>").addClass('sortable');
					jQuery("<div/>").addClass('moveable').appendTo(fieldElem);
					
					/*var renderer = getFieldRenderer(subtype, null, null);
					var rowElem = renderer(moufNewSubInstanceProperty);*/
					var rowElem = renderField(moufNewSubInstanceProperty);
					rowElem.appendTo(fieldElem);
				});
			
			
			if (fieldsRenderer[subtype]) {
				//addDiv.text("Add a value");
				addDiv.html("<i class='icon-plus icon-white'></i> Add a value");
			} else {
				addDiv.html("<i class='icon-plus icon-white'></i> Add an instance");
				//addDiv.text("Add an instance");
			}
			
		} else {
			// If this is an associative array
			// Check that value is not null
			if (values instanceof Array) {
				moufInstanceProperty.forEachArrayElement(function(instanceSubProperty) {
					var fieldElem = jQuery("<div/>").addClass('fieldContainer')
						.data("key", instanceSubProperty.getKey())
						.appendTo(sortable);
						
					var sortableElem = jQuery("<div/>").addClass('sortable');
					jQuery("<div/>").addClass('moveable').appendTo(fieldElem);
					//fieldRenderer = getFieldRenderer(instanceSubProperty.getMoufProperty().getType(), instanceSubProperty.getMoufProperty().getKeyType(), instanceSubProperty.getMoufProperty().getSubType());
					//var rowElem = fieldRenderer(instanceSubProperty);
					var rowElem = renderField(instanceSubProperty);
					
					jQuery("<input/>")
						.addClass("key")
						.val(instanceSubProperty.getKey())
						.appendTo(fieldElem)
						.change(function() {
							// Set's the key if changed
							instanceSubProperty.setKey(jQuery(this).val());							
						});
					jQuery("<span>=&gt;</span>").appendTo(fieldElem);
					
					rowElem.appendTo(fieldElem);
				});
			}
			var subtype = moufProperty.getSubType();
			// If this is a known primitive type, let's display a "add a value" button
			
			var addDiv = jQuery("<div/>").addClass('btn btn-mini btn-success')
				.appendTo(elem)
				.click(function() {
					// key="" (since we are an associative array), and we init the value to null too.
					var moufNewSubInstanceProperty = moufInstanceProperty.addArrayElement("", null);
					
					var fieldElem = jQuery("<div/>").addClass('fieldContainer')
						.data("key", moufNewSubInstanceProperty.getKey())
						.appendTo(sortable);
					
					var sortableElem = jQuery("<div/>").addClass('sortable');
					jQuery("<div/>").addClass('moveable').appendTo(fieldElem);
					
					jQuery("<input/>").addClass("key").appendTo(fieldElem).change(function() {
						// Set's the key if changed
							moufNewSubInstanceProperty.setKey(jQuery(this).val());							
						});
						jQuery("<span>=&gt;</span>").appendTo(fieldElem);

					/*var renderer = getFieldRenderer(subtype, null, null);
					var rowElem = renderer(moufNewSubInstanceProperty);*/
					var rowElem = renderField(moufNewSubInstanceProperty); 	
					rowElem.appendTo(fieldElem);
				});
			
			if (fieldsRenderer[subtype]) {
				addDiv.html("<i class='icon-plus icon-white'></i> Add a value");
			} else {
				addDiv.html("<i class='icon-plus icon-white'></i> Add an instance");
			}
		}
		var _startPosition = null;
		sortable.sortable({
			start: function(event, ui) {
				_startPosition = jQuery(ui.item).index();
				MoufUI.onDroppedInBin(function() {
					// When an element graphically is dropped in the bin, let's apply the change in the instances list.
					moufInstanceProperty.removeArrayElement(_startPosition);
					jQuery(ui.item).remove();
				});
				MoufUI.showBin();
			},
			stop: function(event, ui) {
				MoufUI.hideBin();
			},
			update: function(event, ui) {
				// When moving an element graphically, let's apply the change in the instances list.
				var newPosition = jQuery(ui.item).index();
				moufInstanceProperty.reorderArrayElement(_startPosition, newPosition);
				// This is because the "remove" trigger might be called after the "update" trigger. In that case, _startPosition must point to the new position.
				_startPosition = newPosition;
			},
			// Elements of this sortable can be dropped in the bin.
			connectWith: "div.bin"
		});
		
		return elem;
	}
	
	
	/**
	 * Renders a field representing a link to an instance.
	 * The "in-memory" jQuery object for the field is returned.
	 */
	var renderInstanceField = function(moufInstanceProperty) {
		var name = moufInstanceProperty.getName();
		var value = moufInstanceProperty.getValue();
		var type = moufInstanceProperty.getMoufProperty().getType();
	
		var parentElem = jQuery('<div/>').addClass("fieldInstanceRenderer");
				
		var elem = jQuery("<div/>").addClass('instanceReceiver');
		
		// An element containing the text to display when the value is null
		var nullElem = jQuery("<div/>");
		nullElem.addClass("null");
		jQuery("<a href='#'>Drop here a <em>"+MoufUI.getHtmlClassName(type)+"</em> instance</a>").click(function() {
			jQuery("#instanceList").empty();
			MoufUI.displayInstanceOfType("#instanceList", type, true, true);
			jQuery("#instanceList").scrollintoview({duration: "slow", direction: "y"});
			
			return false;
		}).appendTo(nullElem);
		
		var setToNull = function() {
			elem.find("*").remove();
			nullElem.appendTo(elem);
			moufInstanceProperty.setValue(null);
		}
	
		var renderInstanceInField = function(instance) {
			// Let's do that in a setTimeout.
			// This way, we can be sure other instances are already in the DOM before displaying our instance.
			
			setTimeout(function() {
				var found = isInstanceDisplayed(instance);
				var displayType = found?"small":"medium";
				
				instance.render(displayType).appendTo(elem).draggable({
					revert: "invalid",
					containment: "window",
					start: function(event, ui) { 
						MoufUI.onDroppedInBin(function() {
							setToNull();
							MoufUI.hideBin();
						});
						MoufUI.showBin();
					},
					stop: function(event, ui) {
						MoufUI.hideBin();
					}
				}).data('originalElemSetToNull', setToNull);
			}, 0);
		}
		
		
		if (value === null) {
			nullElem.appendTo(elem);
		} else {
			MoufInstanceManager.getInstance(value).then(function(instance) {
				renderInstanceInField(instance);
			}).onError(function(e) {
				addMessage("<pre>"+e+"</pre>", "error");
			})
		}
		
		/*var menu = MoufUI.createMenuIcon([
  			{
  				label: "Set to <em>null</em>",
  				click: setToNull
  			}
  		]);*/
		
		elem.droppable({
			accept: "."+MoufUI.getCssNameFromType(type),
			activeClass: "stateActive",
			hoverClass: "stateHover",
			greedy: true,
			tolerance: "touch",
			drop: function( event, ui ) {
				var droppedInstance = jQuery( ui.draggable ).data("instance");
				
				
				if (droppedInstance) {
					// If an instance was dropped
					moufInstanceProperty.setValue(droppedInstance.getName());
					elem.html("");
					renderInstanceInField(droppedInstance);
					
					// Also, if this comes from a drag'n'drop from another property of the class,
					// let's perform a "move" by setting to "null".
					// But let's do this in a setTimeout, so the stop draggable event can be triggered
					setTimeout(function() {
						var setToNull = jQuery( ui.draggable ).data('originalElemSetToNull');
						if (setToNull != null) {
							setToNull();
						}						
					}, 0);
				} else {
					// If not, it's a class that has been dropped
					var droppedClass = jQuery( ui.draggable ).data("class");
					
					//moufInstanceProperty.setValue(droppedInstance.getName());
					elem.html("");

					var timestamp = new Date();
					var newInstance = MoufInstanceManager.newInstance(droppedClass, "__anonymous_"+timestamp.getTime(), true);
					moufInstanceProperty.setValue(newInstance.getName());
					
					renderInstanceInField(newInstance);
				}
			}
		});
		
  		
  		elem.appendTo(parentElem);
  		//menu.appendTo(parentElem);
  		
  		return parentElem;
	}
	
	/**
	 * A list of primitive type fields that can be renderered.
	 */
	var fieldsRenderer = {
		"string" : renderStringField,
		"int"    : renderStringField,
		"integer"    : renderStringField,
		"number"    : renderStringField,
		"float"    : renderStringField,
		"array"  : renderArrayField,
		"bool"  : renderBoolField,
		"boolean"  : renderBoolField
		// TODO: continue here
	}
	
	/**
	 * Returns the field renderer method for the field whose class is "name"
	 */
	var getFieldRenderer = function(type, subtype, keytype) {
		if (type == null) {
			return renderStringField;
		} else if (fieldsRenderer[type]) {
			return fieldsRenderer[type];
		} else {
			// TODO: manage subtype and keytype
			// TODO: default should be to display the corresponding renderer.
			return renderInstanceField;
		}
	}
	
	/**
	 * This function will return the instance whose "elem" html element is part of.
	 */
	var findInstance = function(elem) {
		var currentElem = elem;
		do {
			var instance = currentElem.data("instance");
			if (!instance) {
				currentElem = currentElem.parent(); 
			}
		} while (!instance && currentElem);
		return instance;
	}
	
	/**
	 * This function will return the moufProperty whose "elem" html element is part of.
	 */
	var findMoufProperty = function(elem) {
		var currentElem = elem;
		do {
			var moufProperty = currentElem.data("moufProperty");
			if (!moufProperty) {
				currentElem = currentElem.parent(); 
			}
		} while (!moufProperty && currentElem);
		return moufProperty;
	} 
	
	/**
	 * Returns the renderer annotation.
	 */
	var getRendererAnnotation = function(classDescriptor) {
		var annotations = classDescriptor.getAnnotations();
		if (annotations) {
			var renderers = annotations['Renderer'];
			if (renderers) {
				var renderer = renderers[0];
				if (renderer != null) {
					try {
						var jsonRenderer = jQuery.parseJSON(renderer);
					} catch (e) {
						throw "Invalid @Renderer annotation sent. The @Renderer must have a JSON object attached.\nAnnotation found: @Renderer "+renderer+"\nError detected:"+e;
					}
					return jsonRenderer;
				}
				return null;
			}
		}
	}
	
	/**
	 * Returns true if one big or medium instance is currently displayed on the screen.
	 * This can be useful to avoid multiplying displaying instances.
	 */
	var isInstanceDisplayed = function(instance) {
		var found = false;
		jQuery("div.mediuminstance,div.biginstance").each(function(index, elem) {
			// Note: in some circumstances, we might have 2 instances retrieved (if we ask twice for the instance at the same time)
			// Therefore, let's compare the names instead of the objects.
			if (jQuery(elem).data("instance").getName() == instance.getName()) {
				found = true;
			}
		});
		return found;
	}
	
	/**
	 * Sets the title and logo for the wrapper (applies to small and medium instances).
	 * 
	 * You can pass one option in a JSON array: { 'addlink': 'true' } that will add a link to the ajax page of the wrapper.
	 * 
	 */
	var setWrapperTitleAndLogo = function(wrapper, instance, options) {
		var classDescriptor = MoufInstanceManager.getLocalClass(instance.getClassName());
		
		var beforeLink = "";
		var afterLink = "";
		if (options && options.addlink) {
			beforeLink = "<a href='"+MoufInstanceManager.rootUrl+"ajaxinstance/?name="+instance.getName()+"&selfedit="+MoufInstanceManager.selfEdit+"'>"
			afterLink = "</a>";
		}
		
		if (instance.isAnonymous()) {
			var title = $("<span class='instancetitle'><em>"+beforeLink+MoufUI.getShortClassName(classDescriptor.getName())+afterLink+"</em><span>").attr("rel", "tooltip").attr("title", "Anonymous instance of type '"+classDescriptor.getName()+"'");
		} else {
			var title = $("<span class='instancetitle'>"+beforeLink+instance.getName()+afterLink+"</span>").attr("rel", "tooltip").attr("title", "Instance of type '"+classDescriptor.getName()+"'");
		}
		wrapper.html(title);
		
		// Let's add the small logo image (if any).
		// Is there a logo to display? Let's see in the smallLogo property of the renderer annotation, if any.
		var renderer = getRendererAnnotation(classDescriptor);
		if (renderer != null) {
			if (renderer.smallLogo != null) {
				wrapper.css("background-image", "url("+MoufInstanceManager.rootUrl+"../"+renderer.smallLogo+")");
			}
		}

	}
	
	/**
	 * Renders a field (without the label, just the moufInstanceProperty).
	 * It will display the moufInstanceProperty using the right field renderer and will render
	 * the dropdown menu to the right to apply actions (set to null, unset value, etc...)
	 */
	var renderField = function(moufInstanceProperty) {
		var fieldWrapper = jQuery("<div>").addClass('fieldWrapper');
		
		var fieldInnerWrapper = jQuery("<div>").addClass('fieldInnerWrapper');
		fieldInnerWrapper.appendTo(fieldWrapper);
		
		var getNullField = function() {
			var field = jQuery("<button class='btn btn-mini btn-info' rel='tooltip' title='Click to set value'>Null</button>").click(function() {
				fieldInnerWrapper.empty();
				var field = renderInnerField(moufInstanceProperty);
				field.appendTo(fieldInnerWrapper);
			});
			return field;
		}
		var getNotSetField = function() {
			var field = jQuery("<button class='btn btn-mini btn-warning' rel='tooltip' title='Click to set value'><em>Default value</em></button>").click(function() {
				fieldInnerWrapper.empty();
				var field = renderInnerField(moufInstanceProperty);
				field.appendTo(fieldInnerWrapper);
			});
			return field;
		}
		
		var isSubProperty = moufInstanceProperty instanceof MoufInstanceSubProperty;
		
		var field;
		if (!isSubProperty && !moufInstanceProperty.isSet()) {
			field = getNotSetField();
		} else if (moufInstanceProperty.getValue() === null) {
			field = getNullField();
		} else {
			field = renderInnerField(moufInstanceProperty);
		}
		field.appendTo(fieldInnerWrapper);
		
		var menuDescriptor = [
                  			{
                				label: "Set to <em>null</em>",
                				click: function() {
                					moufInstanceProperty.setValue(null);
                					fieldInnerWrapper.empty();
                					getNullField().appendTo(fieldInnerWrapper);
                				}
                			}
                		];
		if (!isSubProperty) {
			menuDescriptor.push({
				label: "Unset",
				click: function() {
					moufInstanceProperty.unSet();
					fieldInnerWrapper.empty();
					getNotSetField().appendTo(fieldInnerWrapper);
				}
			});
		}
		menuDescriptor.push({
			label: "Use config constant",
			click: function() {
				alert("todo");
			}
		});
		
		var menu = MoufUI.createMenuIcon(menuDescriptor);
		menu.appendTo(fieldWrapper);
		
		return fieldWrapper;
	}
	
	/**
	 * Renders a field (without the label, just the moufInstanceProperty).
	 * Does not render the dropdown menu (this is performed by renderField)
	 * 
	 * The focus parameter decides if the clicked element should have focus or not.
	 */
	var renderInnerField = function(moufInstanceProperty) {
		var moufProperty = moufInstanceProperty.getMoufProperty();
		var fieldRenderer = getFieldRenderer(moufProperty.getType(), moufProperty.getSubType(), moufProperty.getKeyType());
		
		var field = fieldRenderer(moufInstanceProperty);
		return field;
	}
	
	return {
		/**
		 * Returns the list of renderers supported by this renderer.
		 */
		getRenderers : function() {
			return {
				"small" : {
					title: "Small",
					/**
					 * Renders the instance in "small" version.
					 * Result is returned as a jQuery in-memory DOM object
					 */
					renderer: function(instance) {
						var wrapper = getInstanceWrapper(instance).addClass("smallinstance");
						setWrapperTitleAndLogo(wrapper, instance);						
						return wrapper;
					}
				},
				"medium" : {
					title: "Medium",
					renderer: function(instance, parent) {
						var classDescriptor = MoufInstanceManager.getLocalClass(instance.getClassName());
						var wrapper = getInstanceWrapper(instance).addClass("mediuminstance");
						setWrapperTitleAndLogo(wrapper, instance, {addlink: true});
						
						var propertiesList = jQuery("<div/>").addClass('propertieslist');

						// For each Mouf property, let's display a field... if it is marked as "@Important".
						var moufProperties = classDescriptor.getAllInjectableProperties();
						for (var i=0; i<moufProperties.length; i++) {
							var moufProperty = moufProperties[i];
							var annotations = moufProperty.getAnnotations();
							if (annotations) {
								var isImportant = annotations['Important'];
								if (isImportant) {
									var fieldGlobalElem = jQuery("<div/>");
									jQuery("<label/>").text(moufProperty.getPropertyName()).appendTo(fieldGlobalElem);
									var fieldElem = jQuery("<div/>").addClass('fieldContainer')
										.data("moufProperty", moufProperty)
										.appendTo(fieldGlobalElem);
	
									/*var fieldRenderer = getFieldRenderer(moufProperty.getType(), moufProperty.getSubType(), moufProperty.getKeyType());
									var moufInstanceProperty = moufProperty.getMoufInstanceProperty(instance);
									fieldRenderer(moufInstanceProperty).appendTo(fieldElem);*/
									var moufInstanceProperty = moufProperty.getMoufInstanceProperty(instance);
									renderField(moufInstanceProperty).appendTo(fieldElem);
									
									fieldGlobalElem.appendTo(propertiesList);
								}
							}

						}
						propertiesList.appendTo(wrapper);
						
						
						return wrapper;
					}
				},
				"big" : {
					title: "Big",
					renderer: function(instance) {
						var classDescriptor = MoufInstanceManager.getLocalClass(instance.getClassName());
						
						var wrapper = getInstanceWrapper(instance).addClass("biginstance");
						
						var title = jQuery("<h1/>")
						if (!instance.isAnonymous()) {
							title.text('Instance "'+instance.getName()+'"');
						} else {
							title.text('Anonymous instance');
						}
						title.appendTo(wrapper);
						jQuery("<small/>").html(' from class "'+MoufUI.getHtmlClassName(instance.getClassName())+'"').appendTo(title);
						
						var btnToolbar = jQuery('<div class="btn-toolbar"/>').appendTo(wrapper);
						
						jQuery("<button class='btn btn-info'><i class='icon-pencil icon-white'></i> Rename</button>").appendTo(btnToolbar)
							.click(function() {
								MoufUI.renameInstance(instance);
							});
						jQuery("<button class='btn btn-danger'><i class='icon-remove icon-white'></i> Delete</button>").appendTo(btnToolbar)
							.click(function() {
								MoufUI.deleteInstance(instance);
							});
						
						
						var containerForm = jQuery("<form/>")
							.submit(function() {return false;})
							.addClass("form-horizontal")
							.appendTo(wrapper);
						
						jQuery("<div/>").addClass("classComment").html(classDescriptor.getComment()).appendTo(containerForm);
						
						var displayField = function(moufProperty) {
							var fieldGlobalElem = jQuery("<div/>").addClass("control-group");
							jQuery("<label/>").text(moufProperty.getPropertyName())
								.addClass("control-label")
								.appendTo(fieldGlobalElem);
							var fieldElem = jQuery("<div/>").addClass('fieldContainer controls')
								.data("moufProperty", moufProperty).appendTo(fieldGlobalElem);

							
							/*var fieldRenderer = getFieldRenderer(moufProperty.getType(), moufProperty.getSubType(), moufProperty.getKeyType());
							var moufInstanceProperty = moufProperty.getMoufInstanceProperty(instance);
							fieldRenderer(moufInstanceProperty).appendTo(fieldElem);*/
							var moufInstanceProperty = moufProperty.getMoufInstanceProperty(instance);
							renderField(moufInstanceProperty).appendTo(fieldElem);
							
							jQuery("<span class='help-block'>").html(moufProperty.getComment()).appendTo(fieldElem);
							
							fieldGlobalElem.appendTo(propertiesList);
						}
						
						var moufProperties = classDescriptor.getInjectableConstructorArguments();
						if (moufProperties.length != 0) {
							jQuery("<h3/>").text('Constructor arguments').appendTo(containerForm);
							var propertiesList = jQuery("<div/>").addClass('propertieslist');
							// For each Mouf property, let's display a field.
							for (var i=0; i<moufProperties.length; i++) {
								var moufProperty = moufProperties[i];
								displayField(moufProperty);
							}
							propertiesList.appendTo(containerForm);
						}

						var moufProperties = classDescriptor.getInjectableSetters();
						if (moufProperties.length != 0) {
							jQuery("<h3/>").text('Setters').appendTo(containerForm);
							var propertiesList = jQuery("<div/>").addClass('propertieslist');
							// For each Mouf property, let's display a field.
							for (var i=0; i<moufProperties.length; i++) {
								var moufProperty = moufProperties[i];
								displayField(moufProperty);
							}
							propertiesList.appendTo(containerForm);
						}

						var moufProperties = classDescriptor.getInjectablePublicProperties();
						if (moufProperties.length != 0) {
							jQuery("<h3/>").text('Public properties').appendTo(containerForm);
							var propertiesList = jQuery("<div/>").addClass('propertieslist');
							// For each Mouf property, let's display a field.
							for (var i=0; i<moufProperties.length; i++) {
								var moufProperty = moufProperties[i];
								displayField(moufProperty);
							}
							propertiesList.appendTo(containerForm);
						}

						//wrapper.appendTo(parent);
						return wrapper;
					}
				}
			}
		},
		/**
		 * Renders the class described be "classDescriptor" in the "parent" css selector.
		 */
		renderClass : function(classDescriptor) {
			var wrapper = getClassWrapper(classDescriptor).addClass("class smallclass")
			   										 .html("new <b>"+MoufUI.getHtmlClassName(classDescriptor.getName())+"</b>()");
			
			var renderer = getRendererAnnotation(classDescriptor);
			if (renderer != null) {
				if (renderer.smallLogo != null) {
					jQuery(wrapper).css("background-image", "url("+MoufInstanceManager.rootUrl+"../"+renderer.smallLogo+")");
				}
			}

			//wrapper.appendTo(parent);
			return wrapper;
		}
	}
})();