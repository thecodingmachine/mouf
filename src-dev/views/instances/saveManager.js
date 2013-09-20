/**
 * The MoufSaveManager class is in charge of saving any changes performed to the instances.
 * It will locally save those changes, then send the changes to the server by batch.
 * It makes sure only one batch is handled at a time.
 */
var MoufSaveManager = (function () {
	
	/**
	 * Whether a save request is in progress or not.
	 */
	var _saveInProgress = false;
	
	/**
	 * The ID of the timer to be triggered before we perform the save (to pile some other commands to be send
	 * and send them in a batch)
	 */
	var _timerBeforeSave = null;
	
	/**
	 * This event handler sends messages when save starts or ends.
	 * The callback passes "saving" as the first argument if Mouf is currently saving stuff
	 * and passes "saved" when everything is saved.
	 */
	var _saveStatusChangedEventHandler = new Mouf.Observer();
	
	/**
	 * The list of changes that needs to be sent to the server.
	 * Each item can be a JSON object:
	 * {
	 * 	"command": "setProperty",
	 *  "instance": "instanceName",
	 *  "property": "propertyName",
	 *  "key": "optional key if inside an array"
	 * }
	 */
	var _changesList = [];
	
	/**
	 * The list of callbacks to be called after the save is successfull.
	 */
	var _callbackList = [];
	
	var _serializeSubProperties = function(moufInstanceProperty) {
		
		var serializedArray = [];
		moufInstanceProperty.forEachArrayElement(function(moufInstanceSubProperty) {
			var key = moufInstanceSubProperty.getKey();
			var value = moufInstanceSubProperty.getValue();
			var finalValue = null;
			var obj = {
				key: key
			}
			if (value instanceof Array) {
				obj.value = _serializeSubProperties(moufInstanceSubProperty);
			} else if (value === null) {
				obj.value = "";
				obj.isNull = true;
			} else if (value === true || value === false) {
				obj.value = value?"true":"false";
				obj.isBoolean = true;
			} else {
				obj.value = value;
			}
			serializedArray.push(obj);
		})
		return serializedArray;
	}
	
	/**
	 * Callback called when a property instance is changed
	 */
	var _onPropertyChange = function(moufInstanceProperty) {
		
		var value = moufInstanceProperty.getValue();
		
		var isBoolean = false;
		var finalValue = null;
		if (moufInstanceProperty.getType().isArray()) {
			finalValue = _serializeSubProperties(moufInstanceProperty);
		} else {
			if (value === true || value === false) {
				finalValue = value?"true":"false";
				isBoolean = true;
			} else {
				finalValue = value;
			}
		}
		
		var command = {
			"command": "setProperty",
			"instance": moufInstanceProperty.getInstance().getName(),
			"property": moufInstanceProperty.getMoufProperty().getName(),
			"value": finalValue,
			"isBoolean": isBoolean,
			"origin": moufInstanceProperty.getOrigin(),
			"isset": moufInstanceProperty.isSet(),
			"source": moufInstanceProperty.getSource(),
			"isNull": (finalValue === null),
			"type": moufInstanceProperty.getType().toString()
		};
				
		_changesList.push(command);
				
		_save();
	}
	
	/**
	 * Callback called when a new instance is created
	 */
	var _onNewInstance = function(instance) {
		
		var command = {
			"command": "newInstance",
			"name": instance.getName(),
			"class": instance.getClassName(),
			"isAnonymous": instance.isAnonymous()
			// Note: we don't need to pass the default values, they will be applied automatically.
		};
		
		_changesList.push(command);
		_save();
	}
	
	/**
	 * Callback called when an instance is renamed
	 */
	var _onRenameInstance = function(instance, oldName, callback) {
		
		var command = {
			"command": "renameInstance",
			"newname": instance.getName(),
			"oldname": oldName,
			"isAnonymous": instance.isAnonymous()
		};
		if (callback) {
			_callbackList.push(callback);
		}
		
		_changesList.push(command);
		_save();
	}
	
	/**
	 * Callback called when an instance is deleted
	 */
	var _onDeleteInstance = function(instance, callback) {
		
		var command = {
			"command": "deleteInstance",
			"name": instance.getName()
		};
		if (callback) {
			_callbackList.push(callback);
		}
		
		_changesList.push(command);
		_save();
	}
	
	/**
	 * Sends the Ajax save request if no other request is pending.
	 */
	var _save = function() {
		
		if (_timerBeforeSave == null) {
			_timerBeforeSave = setTimeout(function() {
				_timerBeforeSave = null;
				
				if (_saveInProgress == false) {
					_sendSaveRequest();
				}
				
			}, 300);
		}
		_saveStatusChangedEventHandler.fire(window,"saving");
	}
	
	/**
	 * Sends the Ajax save request
	 */
	var _sendSaveRequest = function() {
		_saveInProgress = true;
		
		var changes = _changesList;
		var callbacks = _callbackList;
		
		jQuery.ajax(MoufInstanceManager.rootUrl+"src/direct/save_changes.php", {
			data: {
				changesList: _changesList,
				encode: "json",
				selfedit: MoufInstanceManager.selfEdit?"true":"false"
			},
			type: 'POST'
		}).fail(function(e) {
			var msg = e;
			if (e.responseText) {
				msg = "Status code: "+e.status+" - "+e.statusText+"\n"+e.responseText;
			}
			addMessage("<pre>"+msg+"</pre>", "error");
		}).done(function(result) {
			_saveInProgress = false;
			
			if (typeof(result) == "string") {
				addMessage("<pre>"+result+"</pre>", "error");
				return;
			} else {
				// Let's call any callback to say we are complete.
				_.each(callbacks, function(callback) {
					callback();
				})
			}
			
			// If more changes have piled up, let's save again. 
			if (_changesList.length != 0) {
				_save();
			} else {
				_saveStatusChangedEventHandler.fire(window,"saved");
			}
		});
		_changesList = [];
		_callbackList = [];

	}
	
	return {
		init: function() {
			// Let's bind the _onPropertyChange to the propertyChange event.
			MoufInstanceManager.onPropertyChange(_onPropertyChange);
			// Let's bind the _onNewInstance to the newInstance event.
			MoufInstanceManager.onNewInstance(_onNewInstance);
			// Let's bind the _onRenameInstance to the renameInstance event.
			MoufInstanceManager.onRenameInstance(_onRenameInstance);
			// Let's bind the _onDeleteInstance to the deleteInstance event.
			MoufInstanceManager.onDeleteInstance(_onDeleteInstance);
		},
		onSaveStatusChange: function(callback, scope) {
			_saveStatusChangedEventHandler.subscribe(callback, scope);
		}
	};
})();

MoufSaveManager.init();

// Let's implement the Mouf Save icon
jQuery(function() {
	var _icon = jQuery("<div/>").addClass("saveIcon")
	_icon.appendTo(jQuery("body"));
	_icon.hide();
		
	
	var _disappearTimeout = null;
	
	var _onSaveStatusChange = function(status) {
		if (_disappearTimeout) {
			clearTimeout(_disappearTimeout);
			_disappearTimeout = null;
		}
		_icon.show();
		if (status == "saving") {
			_icon.text("Saving...");
			_icon.addClass("saving").removeClass("saveDone");
		} else if (status == "saved") {
			_icon.text("Save done");
			_icon.addClass("saveDone").removeClass("saving");
			_disappearTimeout = setTimeout(function() {
				_icon.hide("fast");
			}, 2000);
		} else {
			console.error("Unknown status while saving")
		}
	}

	MoufSaveManager.onSaveStatusChange(_onSaveStatusChange);
});
