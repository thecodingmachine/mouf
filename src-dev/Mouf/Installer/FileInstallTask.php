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
}