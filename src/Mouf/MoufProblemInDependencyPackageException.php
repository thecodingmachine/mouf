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
 * This happens if the package to be enabled cannot be installed because of one or many problems with dependencies.
 * 
 * @author David
 */
class MoufProblemInDependencyPackageException extends MoufException {
	
	public $group;
	public $name;
	public $version;
	/**
	 * The list of exceptions that triggerred this exception.
	 * @var array<Exception>
	 */
	public $exceptions;
	
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
	public function __construct($group, $name, $version, $exceptions) {
		$this->group = $group;
		$this->name = $name;
		$this->version = $version;
		$this->exceptions = $exceptions;
		
		$msg = "Could not install package $group/$name/$version because of ";
		if (count($exceptions) == 1) {
			$msg .= " a problem with a dependency:<br/>\n";
			$msg .= $exceptions[0]->getMessage()."<br/>\n";	
		} else {
			$msg .= " several problems with dependencies:<br/>\n";
			$msg .= "<ul>\n";
			foreach ($exceptions as $exception) {
				$msg .= "<li>".$exception->getMessage()."</li>\n";
			}
			$msg .= "</ul>\n";
		}
		
		parent::__construct($msg, 0);
	}	
}

?>