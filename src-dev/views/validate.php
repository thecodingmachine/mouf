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
				$validatorsDiv = $('#loadedValidatorsIndicators');
				
				_.each(result.classes, function(classValidator) {
					ValidatorsCounter.incrementGlobal();
					if (classValidator.error) {
						ValidatorsCounter.incrementError();
						$("<div/>").addClass("alert alert-error")
							.text(classValidator.error)
							.appendTo($validatorsDiv);
					} else {
						var container = $("<div/>").appendTo($validatorsDiv);
						$("<div/>").addClass("loading")
							.text(classValidator.title)
							.appendTo(container);

						$.ajax({
							url: MoufInstanceManager.rootUrl + "src/direct/validate.php",
							data : {
								encode : "json",
								"class" : classValidator.className,
								selfedit : this.selfEdit ? "true" : "false"
							},
							success: function(json){

								try {
									//var json = jQuery.parseJSON(text);
									
									if (json.code == "ok") {
										ValidatorsCounter.incrementSuccess();
										container.html("<div class='alert alert-success'>"+json.message+"</div>");
									} else if (json.code == "warn") {
										ValidatorsCounter.incrementWarn();
										container.html("<div class='alert alert-block'>"+json.message+"</div>");
									} else {
										ValidatorsCounter.incrementError();
										container.html("<div class='alert alert-error'>"+json.message+"</div>");
									}
								} catch (e) {
									ValidatorsCounter.incrementError();
									container.html("<div class='alert alert-error'>Error while running validator for class '"+classValidator.className+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre></div>").find("pre").text(text);
								}
							},
							error: function(jqXHR, textStatus, errorThrown) {
								ValidatorsCounter.incrementError();
								container.html("<div class='alert alert-error'>Unable to run validator for class '"+classValidator.className+"': "+textStatus+"</div>");
							}
											
						});
					}
				});

				_.each(result.instances, function(instanceValidator) {
					ValidatorsCounter.incrementGlobal();
					if (instanceValidator.error) {
						$("<div/>").addClass("alert alert-error")
							.text(instanceValidator.error)
							.appendTo($validatorsDiv);
					} else {
						var container = $("<div/>").appendTo($validatorsDiv);
						ValidatorsCounter.incrementError();
						$("<div/>").addClass("loading")
							.text(instanceValidator.title)
							.appendTo(container);

						$.ajax({
							url: MoufInstanceManager.rootUrl + "src/direct/validate.php",
							data : {
								encode : "json",
								"instance" : instanceValidator.instanceName,
								selfedit : this.selfEdit ? "true" : "false"
							},
							success: function(json){

								try {
									//var json = jQuery.parseJSON(text);
									
									if (json.code == "ok") {
										ValidatorsCounter.incrementSuccess();
										container.html("<div class='alert alert-success'>"+json.message+"</div>");
									} else if (json.code == "warn") {
										ValidatorsCounter.incrementWarn();
										container.html("<div class='alert alert-block'>"+json.message+"</div>");
									} else {
										ValidatorsCounter.incrementError();
										container.html("<div class='alert alert-error'>"+json.message+"</div>");
									}
								} catch (e) {
									ValidatorsCounter.incrementError();
									container.html("<div class='alert alert-error'>Error while running validator for class '"+instanceValidator.instanceName+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre></div>").find("pre").text(text);
								}
							},
							error: function(jqXHR, textStatus, errorThrown) {
								ValidatorsCounter.incrementError();
								container.html("<div class='alert alert-error'>Unable to run validator for class '"+instanceValidator.instanceName+"': "+textStatus+"</div>");
							}
											
						});
					}
				});
				
			});

});
</script>