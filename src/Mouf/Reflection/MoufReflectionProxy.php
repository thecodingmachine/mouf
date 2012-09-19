<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Reflection;

use Exception;

/**
 * Class specialized in forwarding a reflexion request to another script that will perform it.
 * It is useful to perform reflexion in a separate script because in another script, the 
 * context of the Mouf management is not loaded.
 *
 */
class MoufReflectionProxy {

	/**
	 * Returns a MoufXmlReflectionClass representing the class we are going to analyze.
	 *
	 * @param string $className
	 * @param boolean $selfEdit
	 * @return MoufXmlReflectionClass
	 */
	public static function getClass($className, $selfEdit) {
		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/get_class.php?class=".$className."&selfedit=".(($selfEdit)?"true":"false");

		$response = self::performRequest($url);
		
		return new MoufXmlReflectionClass($response);
	}
	
	/**
	 * Returns a list of all the components that are of a class that extends or implements $baseClass
	 *
	 * @param string $baseClass The class or interface name.
	 * @return array<string>
	 */
	public static function getInstances($baseClass, $selfEdit) {
		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/get_instances.php?class=".$baseClass."&selfedit=".(($selfEdit)?"true":"false");
		
		$response = self::performRequest($url);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	
	public static function getComponentsList($selfEdit) {
		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/get_components_list.php?selfedit=".(($selfEdit)?"true":"false");

		$response = self::performRequest($url);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
		
	}
	
	public static function getEnhancedComponentsList($selfEdit) {
		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/get_enhanced_components_list.php?selfedit=".(($selfEdit)?"true":"false");

		$response = self::performRequest($url);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".\plainstring_to_htmlprotected($url)."'>".\plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
		
	}
	

	/**
	 * Returns the array of all constants defined in the config.php file at the root of the project. 
	 * 
	 * @return array
	 */
	public static function getConfigConstants($selfEdit) {

		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/get_defined_constants.php?selfedit=".(($selfEdit)?"true":"false");
		
		$response = self::performRequest($url);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	
	/**
	 * Analyzes the include files.
	 * Returns an empty array if everything is fine, or an array like this if there is an error:
	 * 	array("errorType"=>"crash", "errorMsg"=>"txt");
	 * 
	 * errorTypes can be "crash" or "outputStarted" or "filedoesnotexist"
	 * 
	 * @param string $selfEdit
	 * @throws Exception
	 */
	public static function analyzeIncludes($selfEdit) {
		$url = MoufReflectionProxy::getLocalUrlToProject()."src/direct/analyze_includes.php?selfedit=".(($selfEdit)?"true":"false");
		
		$response = self::performRequest($url);

		// Let's strip the invalid parts:
		$arr = explode("\nX4EVDX4SEVX548DSVDXCDSF489\n", $response);
		if (count($arr) < 2) {
			// No delimiter: there has been a crash.
			return array("errorType"=>"crash", "errorMsg"=>$response);
		}
		$msg = $arr[count($arr)-1]; 
		
		$obj = unserialize($msg);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	
	private static function performRequest($url) {
		// preparation de l'envoi
		$ch = curl_init();
				
		curl_setopt( $ch, CURLOPT_URL, $url);
		
		//curl_setopt( $ch, CURLOPT_HEADER, FALSE );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		//curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POST, FALSE );
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		//curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
	
		if (isset($_SERVER['HTTPS'])) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		}
		
		// Let's forward all cookies so the session in preserved.
		// Problem: because the session file is locked, we cannot do that without closing the session first
		session_write_close();
		
		$cookieArr = array();
		foreach ($_COOKIE as $key=>$value) {
			$cookieArr[] = $key."=".urlencode($value);
		}
		$cookieStr = implode("; ", $cookieArr);
		curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
		
		
		$response = curl_exec( $ch );

		// And let's reopen the session...
		session_start();
		
		
		if( curl_error($ch) ) { 
			throw new \Exception("An error occured: ".curl_error($ch));
		}
		curl_close( $ch );
		
		return $response;
	}
	
	public static function getLocalUrlToProject(){
		if (isset($_SERVER['HTTPS'])) {
			$url = "https://".$_SERVER['SERVER_NAME'].ROOT_URL;
		} else {
			$url = "http://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].ROOT_URL;
		}
		return $url;
	}
}
?>