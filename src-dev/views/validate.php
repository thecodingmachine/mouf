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
<h1>Mouf status</h1>
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
					if (classValidator.error) {
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
										container.html("<div class='alert alert-success'>"+json.message+"</div>");
									} else if (json.code == "warn") {
										container.html("<div class='alert alert-block'>"+json.message+"</div>");
									} else {
										container.html("<div class='alert alert-error'>"+json.message+"</div>");
									}
								} catch (e) {
									container.html("<div class='alert alert-error'>Error while running validator for class '"+classValidator.className+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre></div>").find("pre").text(text);
								}
							},
							error: function(jqXHR, textStatus, errorThrown) {
								container.html("<div class='alert alert-error'>Unable to run validator for class '"+classValidator.className+"': "+textStatus+"</div>");
							}
											
						});
					}
				});

				_.each(result.instances, function(instanceValidator) {
					if (instanceValidator.error) {
						$("<div/>").addClass("alert alert-error")
							.text(instanceValidator.error)
							.appendTo($validatorsDiv);
					} else {
						var container = $("<div/>").appendTo($validatorsDiv);
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
							success: function(text){

								try {
									var json = jQuery.parseJSON(text);
									
									if (json.code == "ok") {
										container.html("<div class='alert alert-success'>"+json.message+"</div>");
									} else if (json.code == "warn") {
										container.html("<div class='alert alert-block'>"+json.message+"</div>");
									} else {
										container.html("<div class='alert alert-error'>"+json.message+"</div>");
									}
								} catch (e) {
									container.html("<div class='alert alert-error'>Error while running validator for class '"+instanceValidator.instanceName+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre></div>").find("pre").text(text);
								}
							},
							error: function(jqXHR, textStatus, errorThrown) {
								container.html("<div class='alert alert-error'>Unable to run validator for class '"+instanceValidator.instanceName+"': "+textStatus+"</div>");
							}
											
						});
					}
				});
				
			});

});
</script>