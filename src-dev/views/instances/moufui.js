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
			MoufInstanceManager.getInstanceListByType(type).then(function(instances, classes) {
				jQuery("<h1/>").text("Type "+type).appendTo(targetSelector);
				var divFilter = jQuery("<div>Filter: </div>").appendTo(targetSelector);
				var inputFilter = jQuery("<input/>").addClass("instanceFilter").appendTo(divFilter);
				jQuery("<h2/>").text("Instances").appendTo(targetSelector);
				var instanceListDiv = jQuery("<div/>").addClass("instanceList").appendTo(targetSelector);
				for (var key in instances) {
					var instance = instances[key];
					instance.render().draggable({
						revert: "invalid", // when not dropped, the item will revert back to its initial position
						//containment: $( "#demo-frame" ).length ? "#demo-frame" : "document", // stick to demo-frame if present
						helper: "clone",
						cursor: "move" /*,
						connectToSortable: ".todo"*/
					}).appendTo(instanceListDiv);
				}
				jQuery("<h2/>").text("Classes").appendTo(targetSelector);
				var classListDiv = jQuery("<div/>").addClass("classList").appendTo(targetSelector);
				for (var key in classes) {
					var classDescriptor = classes[key];
					classDescriptor.render().draggable({
						revert: "invalid", // when not dropped, the item will revert back to its initial position
						//containment: $( "#demo-frame" ).length ? "#demo-frame" : "document", // stick to demo-frame if present
						helper: "clone",
						cursor: "move" /*,
						connectToSortable: ".todo"*/
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
				url: MoufInstanceManager.rootUrl+"direct/get_source_file.php",
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
		createMenuIcon: function(items) {
			var div = jQuery("<div/>").addClass("inlinemenuicon");
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
			
			return div;
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
		 *  onSelect: function(classDescriptor, jQueryElement)
		 * }
		 */
		renderClassesList : function(options) {
			var containerDiv = jQuery("<div/>");
			var filterDiv = jQuery("<div/>").addClass("classesFilter").appendTo(containerDiv);
			jQuery("<label/>").text("Filter:").appendTo(filterDiv);
			var inputFilter = jQuery("<input/>").appendTo(filterDiv);
			var classListDiv = jQuery("<div/>").addClass("classesList").appendTo(containerDiv);
			
			MoufInstanceManager.getComponents().then(function(classes) {
				_.each(classes, function(classDescriptor) {
					var classElem = classDescriptor.render().appendTo(classListDiv);
					if (options) {
						if (options.onSelect) {
							classElem.click(function() {
								options.onSelect(classDescriptor, classElem);
							})
						}
					}
				});

				var applyFilter = function() {
					var filterText = inputFilter.val().toLowerCase();
					
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

						if (!found) {
							// Look in the class name and any of the parent classname:
							do {
								var className = classDescriptor.getName().toLowerCase();
								if (className.indexOf(filterText) != -1) {
									found = true;
									break;
								}
								classDescriptor = classDescriptor.getParentClass(); 
							} while (classDescriptor != null);
						}
						if (found) {
							jQuery(child).show();
						} else {
							jQuery(child).hide();
						}
					})
				}
				 
				inputFilter.keyup(applyFilter);

				applyFilter();
				
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
		} 

	}
})();
