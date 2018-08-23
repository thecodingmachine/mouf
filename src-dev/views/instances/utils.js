Mouf = {};


/**
 * The ArrayUtils object contains utility functions to extend arrays.
 * @class {private} ArrayUtils
 */
Mouf.ArrayUtils = {};

/**
 * This function takes one array in parameter.
 * It will transform it innto a "super array", with additional methods, like foreach, etc...
 */
Mouf.ArrayUtils.empower = function(arr) {
	/**
	 * Calls a function for each object of the array.
	 * Please note that if the function returns "false", this will break the loop.
	 * 
	 * @function {public void} ArrayUtils.?
	 * @param {function} fn The function to call
	 * @param {Object} thisObj The current object of the array.
	 */
	arr.forEach = function(fn, thisObj) {
	    var scope = thisObj || window;
	    for ( var i=0, j=this.length; i < j; ++i ) {
			var result = fn.call(scope, this[i], i, this);
			if (result === false) {
				break;
			}
	    }
	};
	
	/**
	 * Removes all elements from the array.
	 * 
	 * @function {public void} ArrayUtils.?
	 */
	arr.clear = function() {
	    for ( var i=0, j=this.length; i < j; ++i ) {
	    	delete this[i];
	    }		
	} 

	arr.filter = function(fn, thisObj) {
	    var scope = thisObj || window;
	    var a = [];
	    for ( var i=0, j=this.length; i < j; ++i ) {
	        if ( !fn.call(scope, this[i], i, this) ) {
	            continue;
	        }
	        a.push(this[i]);
	    }
	    return a;
	};
	
	arr.map = function(fn, thisObj) {
	    var scope = thisObj || window;
	    var a = [];
	    for ( var i=0, j=this.length; i < j; ++i ) {
	        a.push(fn.call(scope, this[i], i, this));
	    }
	    return a;
	};
	
	arr.indexOf = function(el, start) {
	    var start = start || 0;
	    for ( var i=0; i < this.length; ++i ) {
	        if ( this[i] === el ) {
	            return i;
	        }
	    }
	    return -1;
	};
	
	arr.lastIndexOf = function(el, start) {
	    var start = start || this.length;
	    if ( start >= this.length ) {
	        start = this.length;
	    }
	    if ( start < 0 ) {
	         start = this.length + start;
	    }
	    for ( var i=start; i >= 0; --i ) {
	        if ( this[i] === el ) {
	            return i;
	        }
	    }
	    return -1;
	};
}


/**
 * The Observer class is used to implement to Observer model (publish/subscribe).
 * Thanks to http://www.dustindiaz.com/javascript-observer-class/
 * @class {private} Observer
 */
Mouf.Observer = function() {
    this.fns = [];
    Mouf.ArrayUtils.empower(this.fns);
}

Mouf.Observer.prototype = {
	/**
	 * Subscribes a function to the observer.
	 * 
	 * @function {public} Observer.?
	 * @param {Object} fn
	 * @param {Object} scope An optional scope for the function.
	 */
    subscribe : function(fn, scope) {
        this.fns.push({
			fn: fn,
			scope: scope
		});
    },
	/**
	 * Unsubscribes a function from the observer.
	 * 
	 * @function {public} Observer.?
	 * @param {Object} fn
	 */
    unsubscribe : function(fn) {
        this.fns = this.fns.filter(
            function(el) {
                if ( el.fn !== fn ) {
                    return el;
                }
            }
        );
    },
    /**
     * The fire function takes the scope as a first argument. Then, all arguments added are passed to the functions registered.
     * Most of the time, the scope can be "window".
     * If a scope has been passed in parameter in the "subscribe" function, this scope will be used instead of the scope passed to "fire".
     * It returns the number of fired functions.
	 * If a fired function returns "false", the next functions to be fired won't be called.
     * 
     * @function {public} Observer.?
	 * @param {Object} thisObj
	 */
    fire : function(thisObj) {
        var scope = thisObj;
        var args = []; // empty array
        // copy all other arguments we want to "pass through"
        for(var i = 1; i < arguments.length; i++)
        {
            args.push(arguments[i]);
        }
        
		var nbFires = 0;
        this.fns.forEach(
            function(el) {
				var myScope = el.scope?el.scope:scope;
				var result = el.fn.apply(myScope, args);
				nbFires++;
				return result;
            }
        );
		return nbFires;
    },
    /**
     * Removes all the event handlers bound.
     * 
     * @function {public} Observer.?
     */
    clear: function() {
    	this.fns.clear();
    }
};



/**
 * The Promise object represents a way to manage callbacks on asynchronous actions.
 * It is used in server-side script management.
 * 
 * @class {public} Promise
 */

/**
 * @constructor {private} Promise
 */
Mouf.Promise = function() {
	this.successObserver = new Mouf.Observer;
	this.errorObserver = new Mouf.Observer;
	this.pastSuccesses = [];
	this.pastErrors = [];
}

/**
 * Registers a function to be executed when a result arrives.
 * The "then" function takes a callback in parameter that is triggered when
 * an events finishes successfully.
 * The result of the function is passed in parameter.
 * The "then" function returns the same Promise object so you can chain "then" calls.
 * You can optionnally provide a scope.
 * 
 * For instance:
 * 
 * room.exec('serverSideFunction').then(function(result) { alert('success' })
 * 								  .then(function(result) { alert('success again' });
 * 
 * @function {public void} Promise.?
 * @param {function} callback - The function to invoke
 * @param {object} scope (optional) - The scope to run the function in.
 */
Mouf.Promise.prototype.then = function(callback, scope) {
	this.successObserver.subscribe(callback, scope);
	
	// Trigger any success that would have been called BEFORE the promise is fulfilled.
	for (var i=0; i<this.pastSuccesses.length; i++) {
		var scope = this.pastSuccesses[i].scope;
		var scopeAndArgs = this.pastSuccesses[i].scopeAndArgs;
        var args = []; // empty array
        // copy all other arguments we want to "pass through"
        for(var j = 1; j < scopeAndArgs.length; j++)
        {
            args.push(scopeAndArgs[j]);
        }
		var thisScope = window;
		if (scope) {
			thisScope = scope;
		}
		callback.apply(thisScope, args);
	}
	
	return this;
}

/**
 * Registers a function to be executed when a result arrives.
 * The "catch" function takes a callback in parameter that is triggered when
 * an events finishes successfully.
 * An exception object is passed in parameter.
 * The "catch" function returns the same Promise object so you can chain "then" calls.
 * You can optionnally provide a scope.
 * 
 * For instance:
 * 
 * room.exec('serverSideFunction').onError(function(exception) { alert('error' })
 * 								  .onError(function(exception) { alert('error again' });
 * 
 * @function {public void} Promise.?
 * @param {function} callback - The function to invoke
 * @param {object} scope (optional) - The scope to run the function in.
 */
Mouf.Promise.prototype.onError = function(callback, scope) {
	this.errorObserver.subscribe(callback, scope);

	// Trigger any success that would have been called BEFORE the promise is fulfilled.
	for (var i=0; i<this.pastErrors.length; i++) {
		var scope = this.pastErrors[i].scope;
		var scopeAndArgs = this.pastErrors[i].scopeAndArgs;
        var args = []; // empty array
        // copy all other arguments we want to "pass through"
        for(var j = 1; j < scopeAndArgs.length; j++)
        {
            args.push(scopeAndArgs[j]);
        }
		var thisScope = window;
		if (scope) {
			thisScope = scope;
		}
		callback.apply(thisScope, args);
	}
	
	return this;
}

/**
 * Triggers the registered success methods.
 * 
 * @function {private void} Promise.?
 * @param {Object} scope (optional) - The scope to run the callbacks in (unless the callback provides its own scope).
 * Any additional parameter is passed "as is".
 */
Mouf.Promise.prototype.triggerSuccess = function(scope) {
	this.pastSuccesses.push({"scopeAndArgs": arguments,
							 "scope": scope});
	
	var thisScope = window;
	if (scope) {
		thisScope = scope;
	}
	var nbTriggers = this.successObserver.fire.apply(this.successObserver, arguments);
	return nbTriggers;
}

/**
 * Triggers the registered error methods.
 * 
 * @function {private void} Promise.?
 * @param {Object} scope (optional) - The scope to run the callbacks in (unless the callback provides its own scope).
 * Any additional parameter is passed "as is".
 */
Mouf.Promise.prototype.triggerError = function(scope) {
	this.pastErrors.push({"scopeAndArgs": arguments,
						  "scope": scope});

	var thisScope = window;
	if (scope) {
		thisScope = scope;
	}
	var nbTriggers = this.errorObserver.fire.apply(this.errorObserver, arguments);
	return nbTriggers;
}
