<?php 
namespace Mouf\Installer;

use Composer\Package\Package;

/**
 * This class describes a single installation task done referencing directly a url.
 * (type = url) in composer.json
 * 
 * @author David Négrier
 */
class UrlInstallTask extends AbstractInstallTask {

	/**
	 * The url used to run the install process.
	 * @var string
	 */
	private $url;
	    
	/**
	 * Returns the url (relative to MOUF_URL) that will be called to run the install process.
	 * @return string
	 */
	public function getUrl() 
	{
	  return $this->url;
	}
	
	/**
	 * Sets the url (relative to MOUF_URL) that will be called to run the install process.
	 * 
	 * @param string $value
	 */
	public function setUrl($value) 
	{
	  $this->url = $value;
	}
	
	/**
	 * Returns an array representation of this object.
	 * The array representation is used to store anything that can help reference the object + the status of the task.
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
				"status"=>$this->getStatus(),
				"type"=>"url",
				"url"=>$this->getUrl(),
				"package"=>$this->package->getName()
		);
	}
	
	/**
	 * Returns true if the array passed in parameter (generated with "toArray"), matches this package.
	 *
	 * @param array $array
	 * @return bool
	 */
	public function matchesPackage(array $array) {
		if (isset($array['package']) && $array['package'] == $this->package->getName()
				&& isset($array['url']) && $array['url'] == $this->getUrl()) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Returns the URL that can be used to install this package.
	 *
	 * @param bool $selfEdit
	 * @return string
	 */
	public function getRedirectUrl($selfEdit) {
		$url = $this->getUrl();
		if (strpos($url, "?") !== false) {
			$url .= "&selfedit=".($selfEdit?"true":"false");
		} else {
			$url .= "?selfedit=".($selfEdit?"true":"false");
		}
		return $url;
	}
}