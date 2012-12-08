<?php 
namespace Mouf\Installer;

use Composer\Package\PackageInterface;

/**
 * This class describes a single installation task.
 * A package can have several installation tasks applied to it.
 * Each installation task has a status (done / todo)
 * 
 * @author David Négrier
 */
abstract class AbstractInstallTask {
	
	const STATUS_DONE = "done";
	const STATUS_TODO = "todo";
	
	/**
	 * The package this install task applies to.
	 * 
	 * @var PackageInterface
	 */
	protected $package;
	
	/**
	 * The description of this install task.
	 * 
	 * @var string
	 */
	protected $description;
	    
	/**
	 * Get the description of this install task.
	 * @return string
	 */
	public function getDescription() 
	{
	  return $this->description;
	}
	
	/**
	 * Set the description of this install task.
	 * 
	 * @param string $value
	 */
	public function setDescription($value) 
	{
	  $this->description = $value;
	}
	
	/**
	 * The status of the install task.
	 * Can be InstallTask::STATUS_DONE or InstallTask::STATUS_TODO.
	 * 
	 * @var string
	 */
	protected $status;

	/**
	 * Returns the status of the install task.
	 * Can be InstallTask::STATUS_DONE or InstallTask::STATUS_TODO.
	 * 
	 * @return string
	 */
	public function getStatus() 
	{
	  return $this->status;
	}
	
	/**
	 * Sets the status of the install task.
	 * Can be InstallTask::STATUS_DONE or InstallTask::STATUS_TODO.
	 * 
	 * @param string $value
	 */
	public function setStatus($value) 
	{
	  $this->status = $value;
	}
	
	/**
	 * Returns the package this install task applies to.
	 * @return Package
	 */
	public function getPackage() {
		return $this->package;
	}
	
	/**
	 * The package this install task applies to.
	 * @param Package $package
	 */
	public function setPackage(Package $package) {
		$this->package = $package;
	}
}