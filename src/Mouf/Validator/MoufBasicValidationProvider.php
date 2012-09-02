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


/**
 * This component is a basic validator describer. It can be used
 * to run validation steps that will be displayed on Mouf validation screen (the front page).
 * 
 * @author David
 * @Component
 */
class MoufBasicValidationProvider implements MoufValidationProviderInterface {
	
	/**
	 * The name of the validator
	 * 
	 * @Property
	 * @Compulsory
	 * @var string
	 */
	public $name;
	
	/**
	 * The url of the validator
	 * 
	 * @Property
	 * @Compulsory
	 * @var string
	 */
	public $url;
	
		
	/**
	 * A list of parameters that are propagated by the link.
	 * For instance, if the parameter "mode" is set to 42 on the page (because the URL is http://mywebsite/myurl?mode=42),
	 * then if you choose to propagate the "mode" parameter, the URL will have "mode=42" as a parameter.
	 *
	 * @Property
 	 * @var array<string>
	 */
	public $propagatedUrlParameters;
	
	public function __construct($name = null, $url = null, $propagatedUrlParameters = null) {
		$this->name = $name;
		$this->url = $url;
		$this->propagatedUrlParameters = $propagatedUrlParameters;
	}
	
	/**
	 * Returns the name of the validator.
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns the URL that will be called for that validator. The URL is relative to the ROOT_URL.
	 * The URL will return HTML code that will directly be displayed in the Mouf validation screen.
	 * 
	 * @return string
	 */
	public function getUrl() {
		$url = $this->url;
		
		$params = array();
		// First, get the list of all parameters to be propagated
		if (is_array($this->propagatedUrlParameters)) {
			foreach ($this->propagatedUrlParameters as $parameter) {
				if (isset($_REQUEST[$parameter])) {
					$params[$parameter] = get($parameter);
				}
			}
		}
		
		if (!empty($params)) {
			if (strpos($url, "?") === FALSE) {
				$url .= "?";
			} else {
				$url .= "&";
			}
			$paramsAsStrArray = array();
			foreach ($params as $key=>$value) {
				$paramsAsStrArray[] = urlencode($key).'='.urlencode($value);
			}
			$url .= implode("&", $paramsAsStrArray);
		}
		
		return $url;
	}
	
	
}

?>