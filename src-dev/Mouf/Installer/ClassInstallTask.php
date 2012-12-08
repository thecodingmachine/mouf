<?php 
namespace Mouf\Installer;

use Composer\Package\Package;

/**
 * This class describes a single installation task done using a class implementing the PackageInstallerInterface.
 * (type = class) in composer.json
 * 
 * @author David Négrier
 */
class ClassInstallTask extends AbstractInstallTask {

	/**
	 * The className used to run the install process.
	 * @var string
	 */
	private $className;
	    
	/**
	 * Returns the className (relative to MOUF_URL) that will be called to run the install process.
	 * @return string
	 */
	public function getClassName() 
	{
	  return $this->className;
	}
	
	/**
	 * Sets the className (relative to MOUF_URL) that will be called to run the install process.
	 * 
	 * @param string $value
	 */
	public function setClassName($value) 
	{
	  $this->className = $value;
	}
}