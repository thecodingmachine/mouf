<?php
namespace Mouf;

use Mouf\Reflection\MoufReflectionProxy;

/**
 * An InstanceProxy class is an object representing an instance that will forward any function call to a separate process that will execute the call. 
 * This is very useful to call a method of an instance of the application in the context of the admin interface.
 * 
 * @author David Negrier
 */
class InstanceProxy {
	
	protected $instanceName;
	
	protected $selfEdit;
	
	/**
	 * Creates the instance proxy
	 * 
	 * @param string $instanceName
	 * @param bool $selfEdit
	 */
	public function __construct($instanceName, $selfEdit = false) {
		$this->instanceName = $instanceName;
		$this->selfEdit = $selfEdit;
	}
	
	/**
	 * Intercepts any call to any function and forwards it to the proxy.
	 * 
	 * @param string $methodName
	 * @param array $arguments
	 */
	public function __call($methodName, $arguments) {
		$postArray = array("instance"=>$this->instanceName, "method"=>$methodName, "args"=>serialize($arguments));
		
		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/proxy.php";
		
		$response = MoufReflectionProxy::performRequest($url, $postArray);
		
		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new MoufException("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
}