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
ValidatorsCounter = {
	global: 0,
	success: 0,
	warn: 0,
	error: 0,

	incrementGlobal: function() {
		ValidatorsCounter.global++;
		$('#globalCount').text(ValidatorsCounter.global);
	},

	incrementSuccess: function() {
		ValidatorsCounter.success++;
		$('#successCount').text(ValidatorsCounter.success);
	},

	incrementWarn: function() {
		ValidatorsCounter.warn++;
		$('#warnBtn').text(ValidatorsCounter.warn);
	},

	incrementError: function() {
		ValidatorsCounter.error++;
		$('#errorBtn').text(ValidatorsCounter.error);
	}
};

ValidatorMessages = {
	displayMode: "all", // displayMode can be "all", "warn" or "error"
		
	addLoadingMessage: function(text) {
		ValidatorsCounter.incrementGlobal();

		var $validatorsDiv = $('#loadedValidatorsIndicators');
		var container = $("<div/>").appendTo($validatorsDiv);
		$("<div/>").addClass("loading")
			.text(text)
			.appendTo(container);
		return container;
	},
	turnMessageIntoSuccess: function(container, text) {
		ValidatorsCounter.incrementSuccess();
		container.html("<div class='alert alert-success validatorSuccess'>"+text+"</div>");
		if (ValidatorMessages.displayMode != "all") {
			container.hide();
		}
		return container;
	},
	turnMessageIntoWarn: function(container, text) {
		ValidatorsCounter.incrementWarn();
		container.html("<div class='alert validatorWarning'>"+text+"</div>");
		if (ValidatorMessages.displayMode == "error") {
			container.hide();
		}
		return container;
	},
	turnMessageIntoError: function(container, text) {
		ValidatorsCounter.incrementError();
		container.html("<div class='alert alert-error validatorError'>"+text+"</div>");

		return container;
	}
}

$(document).ready(function() {
	$("#successBtn").click(function() {
		$(".validatorSuccess").show();
		$(".validatorWarning").show();
	});

	$("#warnBtn").click(function() {
		$(".validatorSuccess").hide();
		$(".validatorWarning").show();
	});
	
	$("#errorBtn").click(function() {
		$(".validatorSuccess").hide();
		$(".validatorWarning").hide();
	});
});
</script>

<h1>Mouf status</h1>

<div class="row well">
	<div class="span2 offset3">
		<img src="<?php echo ROOT_URL ?>src-dev/views/images/success.png" alt="Success">
		<button id="successBtn" class="btn btn-success btn-large"><span id="successCount">0</span>/<span id="globalCount">0</span></button>
	</div>
	<div class="span2">
		<img src="<?php echo ROOT_URL ?>src-dev/views/images/warn.png" alt="Warning">
		<button id="warnBtn" class="btn btn-warning btn-large">0</button>
	</div>
	<div class="span2">
		<img src="<?php echo ROOT_URL ?>src-dev/views/images/error.png" alt="Error">
		<button id="errorBtn" class="btn btn-danger btn-large">0</button>
	</div>
</div>

<?php
/* @var $this MoufValidatorController */

$this->validatorService->toHtml();

?>
<div class="loading" id="loadingValidatorsIndicator">Loading validators for classes and instances</div>
<div id="loadedValidatorsIndicators"></div>

<script type="text/javascript">
$(document).ready(function() {
	jQuery
	.ajax(
			MoufInstanceManager.rootUrl
					+ "src/direct/get_validators_list.php",
			{
				data : {
					encode : "json",
					selfedit : this.selfEdit ? "true" : "false"
				}
			})
	.fail(
			function(e) {
				var msg = e;
				if (e.responseText) {
					msg = "Status code: " + e.status + " - "
							+ e.statusText + "\n"
							+ e.responseText;
				}
				addMessage("<pre>"+msg+"</pre>", "error");
			})
	.done(
			function(result) {
				if (typeof (result) == "string") {
					addMessage("<pre>"+msg+"</pre>", "error");
					return;
				}

				
				$('#loadingValidatorsIndicator').hide();
				
				_.each(result.classes, function(className) {

					var container = ValidatorMessages.addLoadingMessage("Running validator for class '"+className+"'");

					$.ajax({
						url: MoufInstanceManager.rootUrl + "src/direct/validate.php",
						data : {
							encode : "json",
							"class" : className,
							selfedit : this.selfEdit ? "true" : "false"
						},
						success: function(json){

							try {
								if (typeof(json) == "string") {
									ValidatorMessages.turnMessageIntoError(container, "Error while running validator for class '"+className+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre>").find("pre").text(json);
									return;
								}
								//var json = jQuery.parseJSON(text);
								
								if (json.code == "ok") {
									ValidatorMessages.turnMessageIntoSuccess(container, json.message).attr("title", "Validator for class '"+className+"'");
								} else if (json.code == "warn") {
									ValidatorMessages.turnMessageIntoWarn(container, json.message).attr("title", "Validator for class '"+className+"'");
								} else {
									ValidatorMessages.turnMessageIntoError(container, json.message).attr("title", "Validator for class '"+className+"'");
								}
							} catch (e) {
								ValidatorMessages.turnMessageIntoError(container, "Error while running validator for class '"+className+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre>").find("pre").text(json);
							}
						},
						error: function(jqXHR, textStatus, errorThrown) {
							ValidatorMessages.turnMessageIntoError(container, "<div class='alert alert-error'>Unable to run validator for class '"+className+"': "+textStatus+"</div>");
						}
										
					});
					
				});

				_.each(result.instances, function(instanceName) {
					ValidatorsCounter.incrementGlobal();
					
					var container = ValidatorMessages.addLoadingMessage("Running validator for instance '"+instanceName+"'");

					$.ajax({
						url: MoufInstanceManager.rootUrl + "src/direct/validate.php",
						data : {
							encode : "json",
							"instance" : instanceName,
							selfedit : this.selfEdit ? "true" : "false"
						},
						success: function(json){

							try {
								if (typeof(json) == "string") {
									ValidatorMessages.turnMessageIntoError(container, "Error while running validator for instance '"+instanceName+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre>").find("pre").text(json);
									return;
								}
								//var json = jQuery.parseJSON(text);
								
								if (json.code == "ok") {
									ValidatorMessages.turnMessageIntoSuccess(container, json.message).attr("title", "Validator for instance '"+instanceName+"'");
								} else if (json.code == "warn") {
									ValidatorMessages.turnMessageIntoWarn(container, json.message).attr("title", "Validator for instance '"+instanceName+"'");
								} else {
									ValidatorMessages.turnMessageIntoError(container, json.message).attr("title", "Validator for instance '"+instanceName+"'");
								}
							} catch (e) {
								ValidatorMessages.turnMessageIntoError(container, "Error while running validator for instance '"+instanceName+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre>").find("pre").text(text);
							}
						},
						error: function(jqXHR, textStatus, errorThrown) {
							ValidatorMessages.turnMessageIntoError(container, "<div class='alert alert-error'>Unable to run validator for instance '"+instanceName+"': "+textStatus+"</div>");
						}
										
					});
				});
				
			});

});
</script>