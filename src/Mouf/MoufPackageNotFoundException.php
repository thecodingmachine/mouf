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
 * This happens if the package to be enabled depends on packages that cannot be found.
 * Most likely, this means that the dependencies section of the package.xml file is broken.
 * 
 * @author David
 */
class MoufPackageNotFoundException extends MoufException {
	
	public $group;
	public $name;
	public $requestedVersion;
	public $dependencyGroup;
	public $dependencyName;
	
	/**
	 * 
	 * @param string $group
	 * @param string $name
	 * @param string $dependencyGroup
	 * @param string $dependencyName
	 */
	public function __construct($group, $name, $dependencyGroup, $dependencyName, $requestedVersion) {
		parent::__construct("An exception occured while installing a package. The package $group/$name depends on $dependencyGroup/$dependencyName (version: $requestedVersion), but this package does not seem to exist.", 0);
		$this->group = $group;
		$this->name = $name;
		$this->dependencyGroup = $dependencyGroup;
		$this->dependencyName = $dependencyName;
		$this->requestedVersion = $requestedVersion;
	}	
}

?>