

/**
 * The default renderer if no renderer is to be found.
 * This renderer is in charge of rendering instances (small, medium, big) and classes.
 */
var MoufDefaultRenderer = (function () {

	/**
	 * Returns the CSS class applying to a class descriptor (used for drag'n'drop)
	 */
	var getCssClassFromClassDescriptor = function(classDescriptor) {
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
		return cssClass;
	}
	
	/**
	 * Returns the wrapper DIV element in which the class will be stored.
	 * The wrapper DIV will have appropriate CSS classes to handle drag'n'drop.
	 * The wrapper is returned as an "in-memory" jQuery element.
	 */
	var getClassWrapper = function(classDescriptor) {
		return jQuery("<div/>").addClass(getCssClassFromClassDescriptor(classDescriptor)).data("class", classDescriptor);
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
		var currentType = moufInstanceProperty.getType();
		//var moufProperty = moufInstanceProperty.getMoufProperty();
		var elem = jQuery("<div/>");
		var sortable = jQuery("<div/>").addClass('array');
		sortable.appendTo(elem);
		var subtype = currentType.getSubType().getType();

		if (!currentType.isAssociativeArray())  {
			// If this is a known primitive type
			if (fieldsRenderer[subtype]) {
			
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
				
				
				// If this is a known primitive type, let's display a "add a value" button
				addDiv.html("<i class='icon-plus icon-white'></i> Add a value");
		
			} else {
				// This is an object, in a non associative array.
				// Let's display an optimized drag'n'drop strategy.

				sortable.addClass("nonassociativearray");
				
				// First, let's make sure we can drag directly in the sortable.
				MoufInstanceManager.getClass(subtype).then(function(classDescriptor) {
					//sortable.addClass(getCssClassFromClassDescriptor(classDescriptor));
					sortable.addClass(MoufUI.getCssNameFromType(classDescriptor.getName()));
					
					if (values instanceof Array) {
						moufInstanceProperty.forEachArrayElement(function(instanceSubProperty) {
							var rowElem = renderField(instanceSubProperty).data("key", instanceSubProperty.getKey());
							rowElem.appendTo(sortable);
						});
					}
					
					//var addDiv = jQuery("<div/>").addClass('addavalue')
					var addDiv = jQuery("<a/>").addClass('btn btn-mini btn-success')
						.html("<i class='icon-plus icon-white'></i> Add an instance")
						.appendTo(elem)
						.click(function() {
							MoufUI.displayInstanceOfType("#instanceList", moufInstanceProperty.getType().getSubType(), true, true);
						});
					
				})
				
				
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
		//var _dragStartedInSortable = true;
		sortable.sortable({
			start: function(event, ui) {
				_startPosition = jQuery(ui.item).index();
				MoufUI.onDroppedInBin(function() {
					// When an element graphically is dropped in the bin, let's apply the change in the instances list.
					moufInstanceProperty.removeArrayElement(_startPosition);
					jQuery(ui.item).remove();
				});
				// Let's display the bin, unless the drag comes from outside the sortable.
				if (jQuery(event.currentTarget).hasClass('array')) {
					MoufUI.showBin();
				}
			},
			stop: function(event, ui) {
				MoufUI.hideBin();
			},
			receive: function(event, ui) {
				//_dragStartedInSortable = false;
				// We are receiving on element from the outside world!
				var droppedInstance = jQuery( ui.item ).data("instance");
				
				if (droppedInstance) {
					// If an instance was dropped
					
					// We add at the end of the array
					moufInstanceProperty.addArrayElement(null, droppedInstance.getName());
					
					
					/*elem.html("");
					renderInstanceInField(droppedInstance);*/
				} else {
					// If not, it's a class that has been dropped
					var droppedClass = jQuery( ui.item ).data("class");
					
					//elem.html("");

					var timestamp = new Date();
					var newInstance = MoufInstanceManager.newInstance(droppedClass, "__anonymous_"+timestamp.getTime(), true);
					
					// We add at the end of the array
					moufInstanceProperty.addArrayElement(null, newInstance.getName());
					
					
					//renderInstanceInField(newInstance);
				}
				

				// The "update" event will be triggered after the "receive" event
				// and will reorder the elements if we give it the right start position.
				_startPosition = moufInstanceProperty.arraySize()-1;
				
				// Finally, let's trigger a full field reload.
				// TODO: detect elem in the DOM, remove it and put it back.
				
				setTimeout(function() {
					//var fullFieldPos = elem.parent().index();
					var parent = elem.parent();
					elem.remove();
					renderArrayField(moufInstanceProperty).appendTo(parent);
				}, 100);
			},
			update: function(event, ui) {
				//if (_dragStartedInSortable) {
					// When moving an element graphically, let's apply the change in the instances list.
					var newPosition = jQuery(ui.item).index();
					moufInstanceProperty.reorderArrayElement(_startPosition, newPosition);
					// This is because the "remove" trigger might be called after the "update" trigger. In that case, _startPosition must point to the new position.
					_startPosition = newPosition;
					
				/*} else {
					// We are receiving on element from the outside world!
					// We don't need to refresh position.
					_dragStartedInSortable = true;
				}*/
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
		//var type = moufInstanceProperty.getMoufProperty().getType();
	
		var parentElem = jQuery('<div/>');
		
		MoufInstanceManager.getInstance(value).then(function(instance) {
			// Let's do that in a setTimeout.
			// This way, we can be sure other instances are already in the DOM before displaying our instance.
			
			setTimeout(function() {
				var found = isInstanceDisplayed(instance);
				var displayType = found?"small":"medium";
				
				// Exception case: if we are in a non associative array, let's not make it draggable.
				// The container will do that for us.
				//var parentProperty = moufInstanceProperty.getMoufProperty().getParent();
				var drag = true;
				if (moufInstanceProperty.getParent && moufInstanceProperty.getParent().getType().isArray() && !moufInstanceProperty.getParent().getType().isAssociativeArray()) {
						drag = false;
				}
				
				var renderedInstance = instance.render(displayType).appendTo(parentElem);
				
				if (drag) {
					renderedInstance.draggable({
						revert: "invalid",
						containment: "window",
						start: function(event, ui) { 
							MoufUI.onDroppedInBin(function() {
								moufInstanceProperty.setValue(null);
								// Let's redraw the elem.
								refreshField(moufInstanceProperty);
								
								MoufUI.hideBin();
							});
							MoufUI.showBin();
						},
						stop: function(event, ui) {
							MoufUI.hideBin();
						}
					})
				}
			}, 0);
		}).onError(function(e) {
			addMessage("<pre>"+e+"</pre>", "error");
		})
		
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
	var getFieldRenderer = function(type) {
		var coreType = type.getType();
		if (coreType == null) {
			return renderStringField;
		} else if (fieldsRenderer[coreType]) {
			return fieldsRenderer[coreType];
		} else {
			// TODO: manage subtype and keytype
			// TODO: default should be to display the corresponding renderer.
			return renderInstanceField;
		}
	}
	
	/**
	 * Returns true if the MoufType passed in parameter represents an object
	 * (i.e. it is not a primitive nor an array type).
	 */
	var isObjectType = function(type) {
		var maintype = type.getType();
		if (maintype == null) {
			return false;
		} else if (fieldsRenderer[maintype]) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Returns true if the MoufType passed in parameter represents an array of objects
	 */
	var isArrayOfObjectType = function(type) {
		var subtype = type.getSubType();
		if (subtype == null) {
			return false;
		} else if (fieldsRenderer[subtype]) {
			return false;
		} else {
			return true;
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
				wrapper.css("background-image", "url("+MoufInstanceManager.rootUrl+"../../../"+renderer.smallLogo+")");
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
		fieldWrapper.data('moufInstanceProperty', moufInstanceProperty);
		
		refreshField(moufInstanceProperty, fieldWrapper);
		
		return fieldWrapper;
	}
	
	/**
	 * Refreshes the HTML matching the moufInstanceProperty.
	 * Very useful to refresh display when property has been updated.
	 * 
	 * If target is not passed, we will search on the whole page.
	 */
	var refreshField = function(moufInstanceProperty, target) {
		if (target == null) {
			// Let's find our target
			$('.fieldWrapper').each(function(index, elem) {
				var $elem = $(elem);
				var elemMoufInstanceProperty = $elem.data('moufInstanceProperty');
				if (elemMoufInstanceProperty == moufInstanceProperty) {
					target = $elem;
				}
			})
			if (target != null) {
				target.empty();
			} else {
				throw "Unable to find matching HTML element for this moufInstanceProperty";
			}
		}		

		var fieldInnerWrapper = jQuery("<div>").addClass('fieldInnerWrapper');
		fieldInnerWrapper.appendTo(target);
		
		var moufProperty = moufInstanceProperty.getMoufProperty();
		var currentType = moufInstanceProperty.getType();
		
		var makeDroppable = function(elem) {
			var type;
			if (!currentType.isArray()) {
				type = currentType.getType();
			} else {
				type = currentType.getSubType().getType();
			}
			elem.droppable({
				accept: "."+MoufUI.getCssNameFromType(type),
				activeClass: "stateActive",
				hoverClass: "stateHover",
				greedy: true,
				tolerance: "intersect",
				drop: function( event, ui ) {
					
					// Workaround to prevent false drop:
					//if (!$(event.srcElement).hasClass("ui-draggable-dragging")) {
					/*if (!$(ui.draggable).hasClass("ui-draggable-dragging")) {
						return;
					} */
					
					var droppedInstance = jQuery( ui.draggable ).data("instance");
					
					if (droppedInstance) {
						// If an instance was dropped
						
						if (!moufInstanceProperty.getType().isArray()) {
							moufInstanceProperty.setValue(droppedInstance.getName());	
						} else {
							// If we dropped in a null/default value array:
							moufInstanceProperty.addArrayElement(null, droppedInstance.getName());
						}
						
						refreshField(moufInstanceProperty);
						
						// Also, if this comes from a drag'n'drop from another property of the class,
						// let's perform a "move" by setting to "null".
						// But let's do this in a setTimeout, so the stop draggable event can be triggered
						var originalMoufInstanceProperty = ui.draggable.closest(".fieldWrapper").data('moufInstanceProperty');
						
						if (originalMoufInstanceProperty != null) {
							setTimeout(function() {
								originalMoufInstanceProperty.setValue(null);
								refreshField(originalMoufInstanceProperty);
							}, 0);
						}
					} else {
						// If not, it's a class that has been dropped
						var droppedClass = jQuery( ui.draggable ).data("class");
						
						if (droppedClass == null) {
							//throw "Error! The dropped item is neither an instance nor a class!";
							console.error("Error! The dropped item is neither an instance nor a class!");
							return;
						}
						
						var timestamp = new Date();
						var newInstance = MoufInstanceManager.newInstance(droppedClass, "__anonymous_"+timestamp.getTime(), true);
						
						if (!currentType.isArray()) {
							moufInstanceProperty.setValue(newInstance.getName());
						} else {
							moufInstanceProperty.addArrayElement(null, newInstance.getName());
						}
						
						refreshField(moufInstanceProperty);
					}
				}
			});
		}
		
		// Let's check if we can drop something in the "null" or "default" buttons of this property.
		var isDroppable = isObjectType(currentType) || (isArrayOfObjectType(currentType) && !currentType.isAssociativeArray());
		var isPartOfNonAssociativeObjectArray = false;
		// If this is a sub instance property
		if (moufInstanceProperty.getParent) {
			var parentInstanceProperty = moufInstanceProperty.getParent();
			if (parentInstanceProperty.getType().isArray() && !parentInstanceProperty.getType().isAssociativeArray() && isObjectType(currentType)) {
				isDroppable = false;
				isPartOfNonAssociativeObjectArray = true;
			}
		}
		
		var onClickNullOrNotSetField = function() {
			if (isObjectType(currentType)) {
				// Null field for an object
				MoufUI.displayInstanceOfType("#instanceList", moufInstanceProperty.getType(), true, true);
			} else {
				// Null field for a primitive type / array
				fieldInnerWrapper.empty();
				var field = renderInnerField(moufInstanceProperty);
				field.appendTo(fieldInnerWrapper);
				// If this is an array, let's display the instance type.
				if (isArrayOfObjectType(currentType)) {
					MoufUI.displayInstanceOfType("#instanceList", moufInstanceProperty.getType().getSubType(), true, true);
				}
			}
		}
		
		var getNullField = function() {
			var field = jQuery("<div/>");
			if (isPartOfNonAssociativeObjectArray) {
				var moveable = jQuery("<div class='moveable' />").appendTo(field);
			}
			var button = jQuery("<button class='btn btn-mini btn-info nullValue' rel='tooltip' title='Click to set value'>Null</button>")
				.click(onClickNullOrNotSetField).appendTo(field);
			// Droppable if related to an object and that object is not in a "sortable".
			if (isDroppable) {
				makeDroppable(button);
			}
			
			return field;
		}
		var getNotSetField = function() {
			var field = jQuery("<button class='btn btn-mini btn-warning defaultValue'><em>Click to set value</em></button>")
				.click(onClickNullOrNotSetField);
			if (isDroppable) {
				//makeDroppable(fieldInnerWrapper);
				makeDroppable(fieldInnerWrapper);
			}
			

			// FIXME: peut être que le problème vient de là:
			// makeDroppable rend le fieldInnerWrapper droppable.
			// Quand on clique dessus, on a une array qui apparaît. Il faudrait être sur que le droppable disparaît.
			
			return field;
		}
		var getConfigConstantField = function() {
			var field = jQuery("<button class='btn btn-mini btn-info' rel='tooltip' title='Click to set value'></button>").text(moufInstanceProperty.getValue()).click(function() {
				MoufUI.chooseConfigConstant(function(constant) {
					moufInstanceProperty.setValue(constant, 'config');
					
					fieldInnerWrapper.empty();
					var field = getConfigConstantField();
					field.appendTo(fieldInnerWrapper);
				}, MoufInstanceManager.selfEdit);
			});
			return field;
		}
		
		var isSubProperty = moufInstanceProperty instanceof MoufInstanceSubProperty;
		
		var field;
		if (moufInstanceProperty.getOrigin() == 'config') {
			field = getConfigConstantField();
		} else if (!isSubProperty && !moufInstanceProperty.isSet()) {
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
			menuDescriptor.push({
				label: "Use config constant",
				click: function() {
					MoufUI.chooseConfigConstant(function(constant) {
						moufInstanceProperty.setValue(constant, 'config');

						fieldInnerWrapper.empty();
						var field = getConfigConstantField();
						field.appendTo(fieldInnerWrapper);
					}, MoufInstanceManager.selfEdit);
				}
			});
		}
		
		
		var menu = MoufUI.createMenuIcon(menuDescriptor, target);
		menu.appendTo(target);
	}
	
	/**
	 * Renders a field (without the label, just the moufInstanceProperty).
	 * Does not render the dropdown menu (this is performed by renderField)
	 * 
	 * The focus parameter decides if the clicked element should have focus or not.
	 */
	var renderInnerField = function(moufInstanceProperty) {
		//var moufProperty = moufInstanceProperty.getMoufProperty();
		var fieldRenderer = getFieldRenderer(moufInstanceProperty.getType());
		
		var field = fieldRenderer(moufInstanceProperty);
		return field;
	}
	
	
	/**
	 * Renders as a jQuery element the list of types and binds the callback passed in parameter when one is clicked.
	 * The callback passes a MoufType object in parameter.
	 * 
	 * @param moufTypes MoufTypes
	 * @param currentType MoufType The current type to put in bold
	 */
	var renderTypesSelector = function(moufTypes, currentType, onclick) {
		var typesElem = $("<ul/>").addClass('nav nav-pills mouftypes');
		var first = true;
		var types = moufTypes.getTypes();
		_.each(types, function(type) {
			var selected = false;
			if (type == currentType) {
				selected = true;
			}
			
			if (first != true) {
				typesElem.append("<li class='disabled'><a href='javascript:void(0)'>|</a></li>");
			}
			first = false;
			
			
			var typeElem = $("<li>");
			if (selected) {
				// Todo: change this for some button
				typeElem.addClass('active');
			}
			var innerLink = $('<a href="#">').appendTo(typeElem).append(renderType(type));
			if (onclick && !selected) {
				innerLink.click(function() { onclick(type); return false; });
			} else {
				innerLink.click(function() { return false; });
			}
			typeElem.appendTo(typesElem);
		})
		return $("<small>").append(typesElem);
	}
	
	/**
	 * Returns a jQuery object representing a <span> containing a moufType
	 */
	var renderType = function(moufType) {
		var fullTypeName = moufType.getType();
		var lastIndex = fullTypeName.lastIndexOf("\\");
		if (lastIndex == -1) {
			var typeName = fullTypeName;
			var namespace = null;
		} else {
			var typeName = fullTypeName.substr(lastIndex+1);
			var namespace = fullTypeName.substr(0, lastIndex);
		}
		
		
		var span = $("<span>")
		if (namespace == null) {
			span.text(typeName);
		} else {
			$("<span>").text(typeName).attr("rel", "tooltip").attr("title", namespace).appendTo(span);
		}
		
		if (moufType.getSubType()) {
			span.append("&lt;");
			if (moufType.getKeyType()) {
				span.append(moufType.getKeyType()+",");
			}
			span.append(renderType(moufType.getSubType()));
			span.append("&gt;");
		}
		return span;
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
									// If there is a variable name after @Important (for instance @Important $varName), let's
									// check that we are applying it to the right parameter (useful for @Important constructor parameters)
									var applyImportant = false;
									_.each(isImportant, function(importantString) {
										if (importantString.indexOf("$") == 0) {
											var importantVar = importantString.substr(1);
											var regex = /^([\w]+)/;
											var importantVars = regex.exec(importantVar);
											if (importantVars.length > 0) {
												importantVar = importantVars[0];
												if (importantVar == moufProperty.getName()) {
													applyImportant = true;
												}
											}
										} else {
											applyImportant = true;
										}
										// If we have a @Important IfSet, we must display the property only if it is set.
										if (importantString.match(/\bIfSet/)) {
											var moufInstanceProperty = moufProperty.getMoufInstanceProperty(instance);
											if (moufInstanceProperty.getValue() == null || moufInstanceProperty.isSet() == false) {
												applyImportant = false;
											}
										}
									});
									
									if (!applyImportant) {
										continue;
									}
									
									var fieldGlobalElem = jQuery("<div/>").appendTo(propertiesList);;
									var displayInnerField = function(moufProperty) {
										jQuery("<label/>").text(moufProperty.getPropertyName()).appendTo(fieldGlobalElem);
										var fieldElem = jQuery("<div/>").addClass('fieldContainer')
											.data("moufProperty", moufProperty)
											.appendTo(fieldGlobalElem);
		
										var types = moufProperty.getTypes();
										var moufInstanceProperty = moufProperty.getMoufInstanceProperty(instance);
										
										// Let's find the current type, if any
										var currentType = types.findType(moufInstanceProperty.getType());
										
										var warningMsg = null;
										if (moufInstanceProperty.getWarningMessage() != null) {
											warningMsg = moufInstanceProperty.getWarningMessage()+"<br/>";
										}
										if (types.getWarningMessage() != null) {
											warningMsg = types.getWarningMessage()+"<br/>";
										}
										if (warningMsg) {
											$("<div/>").addClass("alert").html(warningMsg).appendTo(fieldElem);
										}
										
										// Function called when another type is clicked.
										var onChangeType = function(newType) {
											fieldGlobalElem.empty();
											moufInstanceProperty.setType(newType);
											displayInnerField(moufProperty);
										}
										
										if (types.getTypes().length > 1) {
											renderTypesSelector(types, currentType, onChangeType).appendTo(fieldElem);
										}
										
										if (currentType != null) {
											var fieldContainer = jQuery("<div/>").addClass('fieldContainer').appendTo(fieldElem);
											renderField(moufInstanceProperty).appendTo(fieldContainer);
										} else {
											// Display a warning message.
											var alertbox = $("<div/>").addClass("alert").text("Error while displaying this value. The value stored does not match the declared type.").appendTo(fieldElem);
											$("<button/>").addClass("btn btn-danger").text("Reset this property")
												.click(function() {
													onChangeType(types.getTypes()[0]);
												})
												.appendTo(alertbox);
										}
										
										
										//renderField(moufInstanceProperty).appendTo(fieldElem);
										
									}
									displayInnerField(moufProperty);
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
						// Let's find any extended action
						var annotations = classDescriptor.getAnnotations();
						var extendedActions = annotations['ExtendedAction'];
						if (extendedActions) {
							for (var i=0; i<extendedActions.length; i++) {
								var extendedActionStr = extendedActions[i];
								var extendedAction = jQuery.parseJSON(extendedActionStr);
								var actionname = extendedAction.name;
								var actionurl = extendedAction.url;
								
								(function(actionurl) { // This is used to scope actionurl that would be overwritten in the loop.
									jQuery("<button class='btn btn-success' />").html(actionname).appendTo(btnToolbar)
									.click(function() {
										window.location.href = MoufInstanceManager.rootUrl + actionurl + "?name=" + instance.getName();
									});
								})(actionurl)
								
							}
						}
						
						
						var containerForm = jQuery("<form/>")
							.submit(function() {return false;})
							.addClass("form-horizontal")
							.appendTo(wrapper);
						
						jQuery("<div/>").addClass("classComment").html(classDescriptor.getComment()).appendTo(containerForm);
						
						var displayField = function(moufProperty) {
							var fieldGlobalElem = jQuery("<div/>").addClass("control-group").appendTo(propertiesList);
							var displayInnerField = function() {
								var label = jQuery("<label/>").text(moufProperty.getPropertyName())
									.addClass("control-label")
									.appendTo(fieldGlobalElem);
								
								var fieldElem = jQuery("<div/>").addClass('controls')
									.data("moufProperty", moufProperty).appendTo(fieldGlobalElem);
	
								
								var types = moufProperty.getTypes();
								var moufInstanceProperty = moufProperty.getMoufInstanceProperty(instance);
								
								// Let's find the current type, if any
								var currentType = types.findType(moufInstanceProperty.getType());
								
								var warningMsg = null;
								if (moufInstanceProperty.getWarningMessage() != null) {
									warningMsg = moufInstanceProperty.getWarningMessage()+"<br/>";
								}
								if (types.getWarningMessage() != null) {
									warningMsg = types.getWarningMessage()+"<br/>";
								}
								if (warningMsg) {
									$("<div/>").addClass("alert").html(warningMsg).appendTo(fieldElem);
								}
								
								// Function called when another type is clicked.
								var onChangeType = function(newType) {
									fieldGlobalElem.empty();
									moufInstanceProperty.setType(newType);
									displayInnerField();
								}
								
								var typesList = types.getTypes();
								if (typesList.length == 1) {
									$("<br/>").appendTo(label);
									$("<small class='type'>").appendTo(label)
										.html(renderType(typesList[0]));
								} else {
									renderTypesSelector(types, currentType, onChangeType).appendTo(fieldElem);
								}
								
								if (currentType != null) {
									var fieldContainer = jQuery("<div/>").addClass('fieldContainer').appendTo(fieldElem);
									renderField(moufInstanceProperty).appendTo(fieldContainer);
								} else {
									// Display a warning message.
									// Display a warning message.
									var alertbox = $("<div/>").addClass("alert").text("Error while displaying this value. The value stored does not match the declared type.").appendTo(fieldElem);
									$("<button/>").addClass("btn btn-danger").text("Reset this property")
										.click(function() {
											onChangeType(types.getTypes()[0]);
										})
										.appendTo(alertbox);
								}
								
								jQuery("<span class='help-block'>").html(moufProperty.getComment()).appendTo(fieldElem);
							}
							displayInnerField();
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
					jQuery(wrapper).css("background-image", "url("+MoufInstanceManager.rootUrl+"../../../"+renderer.smallLogo+")");
				}
			}

			//wrapper.appendTo(parent);
			return wrapper;
		}
	}
})();