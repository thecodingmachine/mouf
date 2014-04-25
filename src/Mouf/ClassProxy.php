<?php
namespace Mouf;

use Mouf\Reflection\MoufReflectionProxy;

/**
 * An ClassProxy class is an object representing a class that will forward any static function call to a separate process that will execute the call. 
 * This is very useful to call a static method of a class of the application in the context of the admin interface.
 * You can use the `InstanceProxy` object if you need to call remote methods of instances declared in Mouf (instead of static ones).
 * 
 * @author David Negrier
 */
class ClassProxy {
	
	protected $className;
	
	protected $selfEdit;
	
	/**
	 * Creates the class proxy
	 * 
	 * @param string $className
	 * @param bool $selfEdit
	 */
	public function __construct($className, $selfEdit = false) {
		$this->className = $className;
		$this->selfEdit = $selfEdit;
	}
	
	/**
	 * Intercepts any call to any static function and forwards it to the proxy.
	 * 
	 * @param string $methodName
	 * @param array $arguments
	 */
	public function __call($methodName, $arguments) {
		$postArray = array("class"=>$this->className, "method"=>$methodName, "args"=>serialize($arguments));
		
		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/proxy.php";
		
		$response = MoufReflectionProxy::performRequest($url, $postArray);
		
		$obj = @unserialize($response);
		
		if ($obj === false) {
			// Is this an unserialized "false" or an error in unserialization?
			if ($response != serialize(false)) {
				throw new MoufException("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".htmlentities($url)."'>".htmlentities($url)."</a>");
			}
		}
		
		return $obj;
	}
}