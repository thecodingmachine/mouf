<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf;

/**
 * An exception thrown when enabling a new package.
 * This happens if the package to be enabled is not compatible with a previously installed package.
 * 
 * @author David
 */
class MoufIncompatiblePackageException extends MoufException {
	/**
	 * 
	 * @var MoufPackage
	 */
	public $parentPackage;
	/**
	 * 
	 * @var MoufDependencyDescriptor
	 */
	public $dependency;
	
	public $group;
	public $name;
	/**
	 * Version of the PARENT package (the package that owns the dependency that has a problem).
	 * @var string
	 */
	public $version;
	public $inPlaceVersion;
	public $requestedVersion;
	public $isInPlaceVersionInstalled;
	public $dependencyGroup;
	public $dependencyName;
	
	public $scope;
	
	/**
	 * 
	 * @param string $group
	 * @param string $name
	 * @param string $dependencyGroup
	 * @param string $dependencyName
	 * @param string $inPlaceVersion
	 * @param string $requestedVersion
	 * @param boolean $isInPlaceVersionInstalled True if the "inPlaceVersion" is currently installed, false if it is a previous dependency that is not yet installed.
	 */
	/*public function __construct($group, $name, $dependencyGroup, $dependencyName, $inPlaceVersion, $requestedVersion, $isInPlaceVersionInstalled) {
		if ($isInPlaceVersionInstalled) {
			parent::__construct("An exception occured while installing incompatible packages. The package $group/$name requires package $dependencyGroup/$dependencyName whose requested version must be $requestedVersion. Current installed version is $inPlaceVersion.", 0);
		} else {
			parent::__construct("An exception occured while installing incompatible packages. The package $group/$name requires package $dependencyGroup/$dependencyName whose requested version must be $requestedVersion. But a previous dependency required package version to be $inPlaceVersion. There is a compatibility issue inside the dependencies of $group/$name regarding package $dependencyGroup/$dependencyName.", 0);
		}
		$this->group = $group;
		$this->name = $name;
		$this->dependencyGroup = $dependencyGroup;
		$this->dependencyName = $dependencyName;
		$this->inPlaceVersion = $inPlaceVersion;
		$this->requestedVersion = $requestedVersion;
		$this->isInPlaceVersionInstalled = $isInPlaceVersionInstalled;
	}*/

	public function __construct(MoufPackage $parentPackage, MoufDependencyDescriptor $dependency, $inPlaceVersion, $isInPlaceVersionInstalled, $scope) {
		$this->parentPackage = $parentPackage;
		$this->dependency = $dependency;
		
		$this->group = $parentPackage->getDescriptor()->getGroup();
		$this->name = $parentPackage->getDescriptor()->getName();
		$this->version = $parentPackage->getDescriptor()->getVersion();
		$this->dependencyGroup = $dependency->getGroup();
		$this->dependencyName = $dependency->getName();
		$this->inPlaceVersion = $inPlaceVersion;
		$this->requestedVersion = $dependency->getVersion();
		$this->isInPlaceVersionInstalled = $isInPlaceVersionInstalled;

		$this->scope = $scope;
		
		if ($isInPlaceVersionInstalled) {
			parent::__construct("An exception occured while installing incompatible packages. The package $this->group/$this->name/$this->version requires package $this->dependencyGroup/$this->dependencyName whose requested version must be $this->requestedVersion. Current installed version is $inPlaceVersion.", 0);
		} else {
			parent::__construct("An exception occured while installing incompatible packages. The package $this->group/$this->name/$this->version requires package $this->dependencyGroup/$this->dependencyName whose requested version must be $this->requestedVersion. But a previous dependency required package version to be $inPlaceVersion. There is a compatibility issue inside the dependencies of $this->group/$this->name regarding package $this->dependencyGroup/$this->dependencyName.", 0);
		}
	}
}

?>