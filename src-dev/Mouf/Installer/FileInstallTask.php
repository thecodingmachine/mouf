<?php 
namespace Mouf\Installer;

use Composer\Package\Package;

/**
 * This class describes a single installation task done referencing directly a file.
 * (type = file) in composer.json
 * 
 * @author David Négrier
 */
class FileInstallTask extends AbstractInstallTask {

	/**
	 * The PHP file used to run the install process.
	 * @var string
	 */
	private $file;
	    
	/**
	 * Returns the PHP file (relative to package) that will be called to run the install process.
	 * @return string
	 */
	public function getFile() 
	{
	  return $this->file;
	}
	
	/**
	 * Sets the PHP file (relative to package) that will be called to run the install process.
	 * 
	 * @param string $value
	 */
	public function setFile($value) 
	{
	  $this->file = $value;
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
				"type"=>"file",
				"file"=>$this->getFile(),
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
				&& isset($array['file']) && $array['file'] == $this->getFile()) {
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
		if ($selfEdit) {
			return "vendor/".$this->getPackage()->getName()."/".$this->getFile();
		} else {
			return "../../".$this->getPackage()->getName()."/".$this->getFile();
		}
	}
}