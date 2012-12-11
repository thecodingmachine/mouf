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
}