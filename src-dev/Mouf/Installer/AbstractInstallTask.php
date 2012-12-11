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
	
	const SCOPE_LOCAL = "local";
	const SCOPE_GLOBAL = "global";
	
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
	 * The scope of the install task.
	 * Can be InstallTask::SCOPE_LOCAL : the install task must be run on each machine
	 * or InstallTask::STATUS_GLOBAL : the install task is run once by the developer installing the package.
	 * 
	 * @var string
	 */
	private $scope;

	/**
	 * Returns the scope of the install task.
	 * Can be InstallTask::SCOPE_LOCAL : the install task must be run on each machine
	 * or InstallTask::STATUS_GLOBAL : the install task is run once by the developer installing the package.
	 * 
	 * @return string
	 */
	public function getScope() 
	{
	  return $this->scope;
	}
	
	/**
	 * Sets the scope of the install task.
	 * Can be InstallTask::SCOPE_LOCAL : the install task must be run on each machine
	 * or InstallTask::STATUS_GLOBAL : the install task is run once by the developer installing the package.
	 * 
	 * @param unknown $value
	 */
	public function setScope($value) 
	{
	  $this->scope = $value;
	}
	
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
	protected $status = self::STATUS_TODO;

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
	 * @param PackageInterface $package
	 */
	public function setPackage(PackageInterface $package) {
		$this->package = $package;
	}
	
	/**
	 * Returns an array representation of this object.
	 * The array representation is used to store anything that can help reference the object + the status of the task.
	 */
	abstract public function toArray();
}