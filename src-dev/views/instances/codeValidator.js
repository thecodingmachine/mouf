var CodeValidator;

(function(){
"use strict";

/**
 * The CodeValidator class is in charge of validating PHP code and returing the
 * PHP class returned by the PHP code, or the error message returned.
 */
CodeValidator = {
	
	/**
	 * Validates the PHP code.
	 * Returns a promise that returns the PHP type on success and the PHP error message on failure.
	 */
	validate : function(code) {
		var promise = new Mouf.Promise();
		
		jQuery.ajax(MoufInstanceManager.rootUrl+"src/direct/return_type_from_code.php", {
			data: {
				code: code,
				encode: "json",
				selfedit: MoufInstanceManager.selfEdit?"true":"false"
			},
			type: 'POST'
		}).fail(function(e) {
			var msg = e;
			if (e.responseText) {
				msg = "Status code: "+e.status+" - "+e.statusText+"\n"+e.responseText;
			}
			promise.triggerError(window, msg);
		}).done(function(result) {
			if (typeof(result) == "string") {
				promise.triggerError(window, result);
				return;
			} else {
				promise.triggerSuccess(window, result["data"]["class"]?result["data"]["class"]:result["data"]["type"]);
				return;
			}
		});
		return promise;
	}
}

})();