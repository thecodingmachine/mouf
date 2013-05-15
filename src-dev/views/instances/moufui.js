// A mini jQuery plugin to be able to use the draggable feature in "live" mode.
(function ($) {
   $.fn.liveDraggable = function (opts) {
      this.live("mouseover", function() {
         if (!$(this).data("init")) {
            $(this).data("init", true).draggable(opts);
         }
      });
      return $();
   };
}(jQuery));

/**
 * A simple Javascript plugin that puts an element in fixed position, but only when starting scrolling below
 * its first position.
 */
$.fn.fixedFromPos = function ( ) {
    var $this = this,
        $window = $(window);

    var topPos = $this.position().top;
    
    var repos = function(e){
        if ($window.scrollTop() <= topPos) {
            $this.css({
                "margin-top": 0,
                height: $window.height() - topPos + $window.scrollTop() - 10,
                "overflow-y": "auto",
                "background-color":"white"
            });
        } else {
            $this.css({
                "margin-top": $window.scrollTop() - topPos,
                height: $window.height() - 10,
                "overflow-y": "auto",
                "background-color":"white"
            });
        }
    }
    
    $window.scroll(repos);
    $window.resize(repos);
};

// Enable Bootstrap tooltips by default.
$(document).ready(function() {
	/*$('body').tooltip({
	    selector: '[rel=tooltip]'
	});*/
	
	/* Now, let's correct an enoying tooltip bug:
		When the container element is destroyed,
		the tooltip stays forever (because the mouseleave event
		of the container is never triggered.
		Let's solve this with timeouts */
	
	/*$('[rel=tooltip]').live('mouseenter', function() {
		var parentElem = $(this);
		parentElem.tooltip('show')
		var deleteTimer = setInterval(function() {
			if (parentElem.is(':hidden')) {
				parentElem.tooltip('hide');
				clearInterval(deleteTimer);
			}
		}, 2000);
		
		$(this).mouseleave(function() {
			clearInterval(deleteTimer);
		})
	});*/
	
	$(document).on('mouseenter', '[rel=tooltip]', function() {
		// If the tooltip was already created, let's quit.
		var api = $(this).data('qtip');
		if (api) {
			return;
		}
		
		$(this).qtip({
			hide: {
				fixed: true,
				delay: 200
			},
			position: {
				my: 'bottom center',
				at: 'top center'
			},
			style: {
				classes: 'qtip-dark qtip-rounded qtip-shadow'
			}
		});
		var api = $(this).data('qtip');
		// It might sound surprising, but "api" can actually be null, especially
		// during jQuery drag'n'drops
		if (api) {
			api.show();
		}
		
		/*var parentElem = $(this);
		parentElem.tooltip('show')
		var deleteTimer = setInterval(function() {
			if (parentElem.is(':hidden')) {
				parentElem.tooltip('hide');
				clearInterval(deleteTimer);
			}
		}, 2000);
		
		$(this).mouseleave(function() {
			clearInterval(deleteTimer);
		})*/
	});
	
})

/**
 * The MoufUI object is used to display recurrent parts of the Mouf interface
 */
var MoufUI = (function () {
	
	/**
	 * The callback to be called when an object is dropped in the bin.
	 */
	var _binCallback = null;
	
	var _bin = jQuery("<div/>")
				.addClass("bin")
				.hide();
	jQuery(function() {
		_bin.appendTo(jQuery("body"));
		
	});
	jQuery("<div/>").text("Drop here to delete")
		.addClass("binText")
		.appendTo(_bin);
	/*
	// jQuery seems lost when we move scrollable containers after scrolling has started.
	// We should move the bin in a fixed position.
	// See: http://www.serkey.com/jquery-when-moving-a-droppable-after-draggable-has-started-drop-spots-don-t-come-with-it-bbst33.html
	_bin.sortable({
		receive: function(event, ui) {
			jQuery(ui.item).remove();
		}
	});*/
	_bin.droppable({
		greedy: true,
		tolerance: "touch",
		drop: function( event, ui ) {
			if (_binCallback) {
				_binCallback(event, ui);
			}
		}
	});
	
	
	return {
		/**
		 * Displays the list of instances and/or the list of classes that
		 * are a subtype of "type"
		 */
		displayInstanceOfType : function(targetSelector, type, displayInstances, displayClasses) {
			$(targetSelector).empty();
			$("<div/>").addClass('loading').text("Loading").appendTo($(targetSelector));
			
			MoufInstanceManager.getInstanceListByType(type.getType()).then(function(instances, classes) {
				$(targetSelector).empty();
				
				jQuery("<h2/>").html("Type "+MoufUI.getHtmlClassName(type.getType())).appendTo(targetSelector);
				var divFilter = jQuery("<div>Filter: </div>").appendTo(targetSelector);
				var inputFilter = jQuery("<input/>").addClass("instanceFilter").appendTo(divFilter);
				jQuery("<h3/>").text("Instances").appendTo(targetSelector);
				var instanceListDiv = jQuery("<div/>").addClass("instanceList").appendTo(targetSelector);
				
				/**
				 * Utility function that returns the CSS classes we should bind draggables to.
				 */
				var getCssSelectorFromClassDescriptor = function(classDescriptor) {
					// Note: slice is performing a clone of the array
					var subclassOf = classDescriptor.json["implements"].slice(0);
					var parentClass = classDescriptor;
					do {
						subclassOf.push(parentClass.getName());
						parentClass = parentClass.getParentClass();
					} while (parentClass);
					
					for (var i = 0; i<subclassOf.length; i++) {
						subclassOf[i] = '.'+MoufUI.getCssNameFromType(subclassOf[i]);
					}
					var cssSelector = subclassOf.join(',');
					return cssSelector;
				}
				
				for (var key in instances) {
					var instance = instances[key];
					instance.render().draggable({
						revert: "invalid", // when not dropped, the item will revert back to its initial position
						//containment: $( "#demo-frame" ).length ? "#demo-frame" : "document", // stick to demo-frame if present
						helper: "clone",
						cursor: "move",
						connectToSortable: getCssSelectorFromClassDescriptor(instance.getLocalClass())
					}).appendTo(instanceListDiv);
				}
				jQuery("<h3/>").text("Classes").appendTo(targetSelector);
				var classListDiv = jQuery("<div/>").addClass("classList").appendTo(targetSelector);
				for (var key in classes) {
					var classDescriptor = classes[key];
					classDescriptor.render().draggable({
						revert: "invalid", // when not dropped, the item will revert back to its initial position
						//containment: $( "#demo-frame" ).length ? "#demo-frame" : "document", // stick to demo-frame if present
						helper: "clone",
						cursor: "move",
						connectToSortable: getCssSelectorFromClassDescriptor(classDescriptor)
					}).appendTo(classListDiv);
				}
				
				inputFilter.keyup(function(event) {
					var filterText = inputFilter.val().toLowerCase();
					instanceListDiv.children().each(function(cnt, child) {
						var instance = jQuery(child).data('instance');
						var instanceName = instance.getName().toLowerCase();
						if (instanceName.indexOf(filterText) != -1) {
							jQuery(child).show();
						} else {
							jQuery(child).hide();
						}
					})
					classListDiv.children().each(function(cnt, child) {
						var classDescriptor = jQuery(child).data('class');
						var className = classDescriptor.getName().toLowerCase();
						if (className.indexOf(filterText) != -1) {
							jQuery(child).show();
						} else {
							jQuery(child).hide();
						}
					})
				})
				
			}).onError(function(e) {
				addMessage("<pre>"+e+"</pre>", "error");
			});
		},
		showBin: function() {
			//_bin.slideDown();
			// There is a bug in jQuery UI if I use slideDown instead of show: the droppable does not work anymore....
			_bin.show();
		},
		hideBin: function() {
			//_bin.hide();
			_bin.slideUp();
		},
		/**
		 * Displays the PHP class file in a popup.
		 */
		showSourceFile: function(fileName, line) {
			var container = jQuery("<div/>").attr('title', fileName);
			jQuery.ajax({
				url: MoufInstanceManager.rootUrl+"src/direct/get_source_file.php",
				data: {
					file: fileName
				}
			}).fail(function(e) {
				var msg = e;
				if (e.responseText) {
					msg = "Status code: "+e.status+" - "+e.statusText+"\n"+e.responseText;
				}
				addMessage("<pre>"+msg+"</pre>", "error");
			}).done(function(result) {
				var pre = jQuery("<pre/>").text(result).addClass("brush:php").appendTo(container);
				container.appendTo(jQuery("body"));
				$( container ).dialog({
					height: jQuery(window).height()*0.9,
					width: jQuery(window).width()*0.9,
					zIndex: 20000,
					modal: true,
					close: function() {
						container.remove();
					}
				});
				SyntaxHighlighter.highlight();
			});
		},
		/**
		 * Creates and returns a jQuery element representing an inline menu.
		 * You have to hover over the small button to see the full menu.
		 * 
		 * You pass to this function an array of items to be displayed.
		 * For instance:
		 * 
		 *  MoufUI.createMenuIcon([
		 *  	{
		 *  		label: "item1"
		 *  	},
		 *  	{
		 *  		label: "item2",
		 *  		click: function() {
		 *  
		 *  		}
		 *  	}
		 *  ]);
		 */
		createMenuIcon: function(items, bindTo) {
			/*var div = jQuery("<div/>").addClass("inlinemenuicon");
			// Sadly, we cannot pass UL the in the same HTML block because the containing block might be "overflow: hidden" 
			// and that would propagate to our element. So let's put the UL at the top of the document, and let's position it
			// by Javascript.
			var ul = jQuery("<ul/>").addClass("inlinemenu").appendTo(jQuery("body")).css("display", "none");
			_.each(items, function(item) {
				var li = jQuery("<li/>")
				if (!item.click) {
					li.html(item.label);
				} else {
					var a = jQuery("<a href='#'/>").html(item.label);
					a.appendTo(li);
					a.click(function() {
						item.click(item);
						return false;
					});
				}
				li.appendTo(ul);
			});
			
			// TODO: check if we should use mouseenter or mouseXXX (search the other way to catch hover stuff with jQuery)
			div.mouseenter(function(evt) {
				// Use the offset of the div for the ul element
				var offset = div.offset();
				//ul.offset(offset);
				ul.css("top", offset.top - 16);
				ul.css("left", offset.left);
				ul.show();
			});
			ul.mouseleave(function() {
				ul.hide();
			})
			
			return div;*/
			
			var div = jQuery("<div/>").addClass("btn-group").css({"visibility": "hidden"});
			
			var buttonDropdownToggle = jQuery("<button><i class='icon-wrench'></i></span></button>").addClass("btn btn-mini dropdown-toggle").attr("data-toggle", "dropdown").appendTo(div);
			var ul = jQuery("<ul/>").addClass("dropdown-menu").appendTo(div);
			_.each(items, function(item) {
				var li = jQuery("<li/>")
				if (!item.click) {
					li.html(item.label);
				} else {
					var a = jQuery("<a href='#'/>").html(item.label);
					a.appendTo(li);
					a.click(function() {
						item.click(item);
						return false;
					});
				}
				li.appendTo(ul);
			});
			
			$(bindTo).mouseenter(function() {
				div.css({"visibility": "visible"});
			});
			$(bindTo).mouseleave(function() {
				div.css({"visibility": "hidden"});
			});
			// Let's make a glow arround the targetted elements
			div.mouseenter(function() {
				div.siblings().first().css({
					"transition": "all 0.25s ease-in-out",
					"box-shadow": "0 0 5px rgba(100, 100, 255, 1)"
				});
			})
			div.mouseleave(function() {
				div.siblings().first().css({
					"box-shadow": "none"
				});
			})
			
			return div;
		    /*<div class="btn-group">
		    <button class="btn">Action</button>
		    <button class="btn dropdown-toggle" data-toggle="dropdown">
		    <span class="caret"></span>
		    </button>
		    <ul class="dropdown-menu">
		    <!-- dropdown menu links -->
		    </ul>
		    </div>*/
			
		},
		
		/**
		 * Sets the callback to be called when an object is dropped in the bin.
		 * Note: this cancels the previous registered callback.
		 */
		onDroppedInBin: function(callback) {
			_binCallback = callback;
		},
		
		/**
		 * Generate the list of all classes and returns the jQuery div element representing those classes.
		 * 
		 * @param options A list of options to be passed to the class list;
		 * {
		 *  onSelect: function(classDescriptor, jQueryElement),
		 *  onReady: function() // triggered when the class list is loaded.
		 * }
		 */
		renderClassesList : function(options) {
			var containerDiv = jQuery("<div/>");
			
			
			/*var containerForm = jQuery("<form class='form-inline form-search'>")
				.submit(function() {return false;})
				.appendTo(containerDiv);
			
			var inputPrependDiv = jQuery("<div class='input-prepend'>").appendTo(containerForm);
			jQuery('<button type="submit" class="btn" disabled="true">Search</button>').appendTo(inputPrependDiv);
			jQuery('<input type="text" class="span2 search-query">').appendTo(inputPrependDiv);
			*/
			
			var filterDiv = jQuery("<div/>").addClass("classesFilter").appendTo(containerDiv);
			jQuery("<label/>").text("Filter:").appendTo(filterDiv);
			var inputFilter = jQuery("<input/>").appendTo(filterDiv);
			
			var filterDiv2 = jQuery("<div/>").addClass("classesFilter").appendTo(containerDiv);
			var componentAnnotationFilter = jQuery("<input/>").attr("type","checkbox").appendTo(filterDiv2);
			jQuery("<label/>").text("Only display the classes with the @Component annotation").appendTo(filterDiv2);
			
			
			var classListDiv = jQuery("<div/>").addClass("classesList").appendTo(containerDiv);
			
			MoufInstanceManager.getComponents().then(function(classes) {
				_.each(classes, function(classDescriptor) {
					if (classDescriptor.isInstantiable()) {
						var classElem = classDescriptor.render().appendTo(classListDiv);
						if (options) {
							if (options.onSelect) {
								classElem.click(function() {
									options.onSelect(classDescriptor, classElem);
								})
							}
						}
					}
				});

				var applyFilter = function() {
					var filterText = inputFilter.val().toLowerCase();
					var onlyComponents = componentAnnotationFilter.is(':checked')
					
					classListDiv.children().each(function(cnt, child) {
						var classDescriptor = jQuery(child).data('class');

						// Look into any instances
						var interfaces = classDescriptor.getImplementedInterfaces();
						var found = _.any(interfaces, function(interfaceName) {
							if (interfaceName.toLowerCase().indexOf(filterText) != -1) {
								return true;
							} else {
								return false;
							}
						});

						var hasComponentAnnotation = false;
						if (!found) {
							// Look in the class name and any of the parent classname:
							do {
								if (onlyComponents) {
									var annotations = classDescriptor.getAnnotations();
									if (annotations && annotations['Component']) {
										hasComponentAnnotation = true;
									}
								}
								
								var className = classDescriptor.getName().toLowerCase();
								if (className.indexOf(filterText) != -1) {
									found = true;
									if (!onlyComponents) {
										break;
									}
								}
								classDescriptor = classDescriptor.getParentClass(); 
							} while (classDescriptor != null);
						}
						
						if (onlyComponents && !hasComponentAnnotation) {
							found = false;
						}
						
						if (found) {
							jQuery(child).show();
						} else {
							jQuery(child).hide();
						}
					})
				}
				 
				inputFilter.keyup(applyFilter);
				componentAnnotationFilter.change(applyFilter);

				applyFilter();
				
				if (options && options.onReady) {
					options.onReady();
				}
				
			}).onError(function(e) {
				addMessage("<pre>"+e+"</pre>", "error");
			});

			return containerDiv;
		},
		
		/**
		 * Transforms a class or interface name into a CSS classname (to play with drag n drop).
		 */
		getCssNameFromType: function(type) {
			// Let's drop the starting \
			if (type.indexOf("\\") == 0) {
				type = type.substr(1);
			}
			return "mouftype_"+type.replace(/\\/g, "___");
		},

		/**
		 * Returns the HTML to display a class with an hover effect that displays a tooltip with the namespace.
		 * @param className string
		 */
		getHtmlClassName: function(className) {
			if (className.indexOf("\\") == 0) {
				className = className.substr(1);
			}
			var pos = className.lastIndexOf("\\"); 
			if (pos == -1) {
				return className;
			}
			var packageName = className.substr(0, pos);
			var singleClassName = className.substr(pos+1);
			return "<span rel='tooltip' title='Namespace: "+packageName+"'>"+singleClassName+"</span>";
		},
		
		/**
		 * Returns only the name of the class, without the namespace.
		 */
		getShortClassName: function(className) {
			var pos = className.lastIndexOf("\\"); 
			if (pos == -1) {
				return className;
			}
			return className.substr(pos+1);
		},
		
		/**
		 * Displays a popup to rename an instance.
		 * The MoufInstance object must be passed in parameter.
		 */
		renameInstance: function(instance) {
			var modal = MoufUI.openPopup("Rename instance");
			var inputField;
			var formElem = jQuery('<form class="form-horizontal">').submit(function() {
				instance.rename(inputField.val(), function() {
					// When save is performed, let's reload the page with the new URL.
					window.location.href = MoufInstanceManager.rootUrl+"ajaxinstance/?name="+encodeURIComponent(instance.getName())+"&selfedit="+(MoufInstanceManager.selfEdit?"true":"false");
				});
				return false;
			}).appendTo(modal);
			
			var modalBody = jQuery('<div class="modal-body">').appendTo(formElem);
			
			var divControlGroup = jQuery('<div class="control-group">').appendTo(formElem);
			var label = jQuery('<label class="control-label" for="name">').text("New instance name ").appendTo(divControlGroup);
			var divControls = jQuery('<div class="controls">').appendTo(divControlGroup);
			inputField = jQuery('<input type="text" placeholder="Anonymous instance">')
				.val(instance.getName())
				.appendTo(divControlGroup);
						
			
			var modalFooter = jQuery('<div class="modal-footer">').appendTo(formElem);
			jQuery("<button type='button'/>").addClass("btn").attr("data-dismiss", "modal").attr("aria-hidden", "true").text("Close").appendTo(modalFooter);
			jQuery("<button type='submit'/>").addClass("btn btn-primary").text("Save changes").appendTo(modalFooter);
		},
		
		/**
		 * Displays a popup to rename an instance.
		 * The MoufInstance object must be passed in parameter.
		 */
		chooseConfigConstant: function(callback, selfedit) {
			var modal = MoufUI.openPopup("Choose a config constant");
			
			var modalBody = jQuery('<div class="modal-body">').appendTo(modal);
			
			var formElem = jQuery('<form class="form-horizontal">').appendTo(modalBody);
			var divControlGroup = jQuery('<div class="control-group">').appendTo(formElem);
			var label = jQuery('<label class="control-label" for="name">').text("Config constant ").appendTo(divControlGroup);
			var divControls = jQuery('<div class="controls">').appendTo(divControlGroup);
			var selectField = jQuery('<select>').appendTo(divControlGroup);
			
			
			if (typeof definedConstants == 'undefined') {
				jQuery.getJSON(MoufInstanceManager.rootUrl+"src/direct/get_defined_constants.php",{encode:"json", selfedit:selfedit, ajax: 'true'}, function(msg){
					definedConstants = msg;
				    var options = '';
				    for (var key in msg) {
				    	jQuery('<option/>').attr('value', key).text(key).appendTo(selectField);
				    }
				}).fail(function(msg) {
					addMessage("<pre>"+msg.responseText+"</pre>", "error");
				});
			} else {
				for (var key in definedConstants) {
			    	jQuery('<option/>').attr('value', key).text(key).appendTo(selectField);
			    }
			}
			
			var modalFooter = jQuery('<div class="modal-footer">').appendTo(modal);
			jQuery("<button/>").addClass("btn").attr("data-dismiss", "modal").attr("aria-hidden", "true").text("Cancel").appendTo(modalFooter);
			jQuery("<button/>").addClass("btn btn-primary").attr("data-dismiss", "modal").text("Ok").click(function() {
				callback(selectField.val());
			}).appendTo(modalFooter);
		},
		
		/**
		 * Displays a confirmation popup to delete an instance.
		 * The MoufInstance object must be passed in parameter.
		 */
		deleteInstance: function(instance) {
			var modal = MoufUI.openPopup("Delete instance");
			
			var modalBody = jQuery('<div class="modal-body">').appendTo(modal);
			modalBody.text("Are you sure you want to delete this instance?");
			
			var modalFooter = jQuery('<div class="modal-footer">').appendTo(modal);
			jQuery("<button/>").addClass("btn").attr("data-dismiss", "modal").attr("aria-hidden", "true").text("Cancel").appendTo(modalFooter);
			jQuery("<button/>").addClass("btn btn-danger").text("Yes, delete instance").click(function() {
				MoufInstanceManager.deleteInstance(instance, function() {
					window.location.href = MoufInstanceManager.rootUrl+"mouf/?selfedit="+(MoufInstanceManager.selfEdit?"true":"false");
				});
			}).appendTo(modalFooter);
		},
		
		/**
		 * Opens a popup with the title passed in parameter.
		 * The modal-body and modal-footer section are set in the popup, but empty.
		 * The modal jQuery object is returned.
		 */
		openPopup: function(title) {
			var modal = jQuery('<div class="modal" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" />');
			
			var modalHeader = jQuery('<div class="modal-header"></div>')
				.appendTo(modal);
			
			var closeButton = jQuery('<button type="button" data-dismiss="modal" class="close" aria-hidden="true">×</button>')
				.appendTo(modalHeader);
			var title = jQuery('<h3 id="myModalLabel"></h3>').text(title).appendTo(modalHeader);
						
			modal.appendTo(jQuery('body'));
			jQuery(modal).modal();
			jQuery(modal).bind("hidden", function() {
				jQuery(this).remove();
			})
			return modal;
		}
	}
})();
