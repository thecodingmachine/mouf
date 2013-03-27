<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Validator;

use Mouf\Html\HtmlElement\HtmlElementInterface;

/**
 * Service specialized in validating the environment.
 * The validator service centralizes the validation steps provided by "Validation Providers" (implementing the MoufValidationProviderInterface).
 * 
 * @Component
 */
class MoufValidatorService implements HtmlElementInterface {
	
	/**
	 * The array of validators that will be run when validation is triggered.
	 * 
	 * @Property
	 * @var array<MoufValidationProviderInterface>
	 */
	public $validators;
	
	/**
	 * Whether we are in selfEdit mode or not.
	 * Note: this is a string! It must be "true" to be in selfedit mode.
	 * 
	 * @var string
	 */
	public $selfEdit;
	
	public function toHtml() {
?>	
		<div id="validators"></div>
		<script type="text/javascript">
				
		function addValidator(name, url) {


			var container = ValidatorMessages.addLoadingMessage("Running "+name);

			jQuery.ajax({
				url: "<?php echo ROOT_URL ?>"+url,
				success: function(text){

					try {
						var json = jQuery.parseJSON(text);

						if (json.code == "ok") {
							ValidatorMessages.turnMessageIntoSuccess(container, json.html);
						} else if (json.code == "warn") {
							ValidatorMessages.turnMessageIntoWarn(container, json.html);
						} else {
							ValidatorMessages.turnMessageIntoError(container, json.html);
						}
					} catch (e) {
						ValidatorMessages.turnMessageIntoError(container, "Error while running '"+name+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre>").find("pre").text(text);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					ValidatorMessages.turnMessageIntoError(container, "Unable to run '"+name+"': "+textStatus);
				}
								
			});
			
		}
		jQuery(document).ready(function() {
			jQuery(document).on("click", ".seeErrorDetails", function(evt) {
				jQuery(evt.target).parent().find("pre").toggle();
			});
			
<?php 


			foreach ($this->validators as $validator) {
				/* @var $validator MoufValidationProviderInterface */
				echo "addValidator('".addslashes($validator->getName())."', '".addslashes($validator->getUrl())."')\n";
			}
?>
		});
		</script>
<?php 
		// TODO: add a script that searches for instances / classes in JS, and add a validator in JS directly.
		// Doing so in Ajax will allow us to be sure the page displays fast enough.
	}
	
	/**
	 * Registers dynamically a new validator. 
	 * 
	 * @param string $name
	 * @param string $url
	 * @param array<string> $propagatedUrlParameters
	 */
	public function registerBasicValidator($name, $url, $propagatedUrlParameters = null) {
		$this->validators[] = new MoufBasicValidationProvider($name, $url, $propagatedUrlParameters);
	}
	
	/**
	 * Registers dynamically a new validator. 
	 * 
	 * @param MoufValidationProviderInterface $validationProvider
	 */
	public function registerValidator(MoufValidationProviderInterface $validationProvider) {
		$this->validators[] = $validationProvider;
	}
}

?>