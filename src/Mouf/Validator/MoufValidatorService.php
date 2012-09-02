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

use Mouf\HtmlElement\HtmlElementInterface;

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

			if (typeof(window.moufNbValidators) == "undefined") {
				window.moufNbValidators = 0;
			} else {
				window.moufNbValidators++;
			}
			var validatorNb = window.moufNbValidators;
			jQuery('#validators').append("<div id='validator"+validatorNb+"' class='validator'><div class='loading'>Running "+name+"</div></div>");


			jQuery(".seeErrorDetails").live("click", function(evt) {
				jQuery(evt.target).parent().find("pre").toggle();
			});

			jQuery.ajax({
				url: "<?php echo ROOT_URL ?>"+url,
				success: function(text){

					try {
						var json = jQuery.parseJSON(text);
						
						if (json.code == "ok") {
							jQuery('#validator'+validatorNb).html("<div class='good'>"+json.html+"</div>");
						} else if (json.code == "warn") {
							jQuery('#validator'+validatorNb).html("<div class='warning'>"+json.html+"</div>");
						} else {
							jQuery('#validator'+validatorNb).html("<div class='error'>"+json.html+"</div>");
						}
					} catch (e) {
						jQuery('#validator'+validatorNb).html("<div class='error'>Error while running '"+name+"', invalid message returned. <a class='seeErrorDetails' href='#'>See details</a><pre style='display:none'></pre></div>").find("pre").text(text);
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					jQuery('#validator'+validatorNb).html("<div class='error'>Unable to run '"+name+"': "+textStatus+"</div>");
				}
								
			});
			
		}
		jQuery(document).ready(function() {
<?php 


			foreach ($this->validators as $validator) {
				/* @var $validator MoufValidationProviderInterface */
				echo "addValidator('".addslashes($validator->getName())."', '".addslashes($validator->getUrl())."')\n";
			}
?>
		});
		</script>
<?php 
		
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