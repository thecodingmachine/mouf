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
 * A MoufDependencyDescriptor describes a dependency from one package on another.
 * It is very similar to a MoufPackageDescriptor, except it can contain several versions.
 * 
 * @author David
 *
 */
class MoufDependencyDescriptor {
	/**
	 * The authorized version numbers for the package 
	 *
	 * @var string
	 */
	private $version;
	
	/**
	 * The version, parsed as a series of allowed operations.
	 * This can be represented as an array containing 2 elements array: array(0=>operation, 1=>version);
	 * 
	 * @var array<array>
	 */
	private $versionOperations;
	
	/**
	 * The name of the package (comes from the path: group/name/version)
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The group of the package (comes from the path: group/name/version)
	 *
	 * @var string
	 */
	private $group;
	
	/**
	 * The scope that should be used to load the package.
	 * Can be one of "app" or "admin".
	 * Defaults to "app".
	 * If "admin" scope is used, the package files will be loaded in the admin part of the application.
	 * 
	 * @var string
	 */
	private $scope;
	
	/**
	 * The minimum revision number of the package.
	 * 
	 * @var int
	 */
	private $revision;
		
	/**
	 * The URL of the repository this package can be found in.
	 * Please note this does not mean the package will be downloaded from that repository.
	 * It means that if the package is not found in the available repositories, the user does not have that repository, he will be proposed to
	 * add it to its repositories before continuing.
	 * On the other hand, if the package is available straight away from existing repositories,
	 * this value will be ignored.
	 * 
	 * @var string
	 */
	private $repository;
	
	/**
	 * Initializes the object.
	 * Note: the version can be a string in this form:
	 * 	2.0 		=> Version must be equal to 2.0
	 *  =2.0		=> Version must be equal to 2.0
	 *  >2.0		=> Version must be greater than 2.0
	 *  >=2.0		=> Version must be greater or equal to 2.0
	 *  <1.0-alpha1	=> Version must be less than 1.0-alpha1
	 *  <=1.0-alpha1	=> Version must be less or equal to 1.0-alpha1
	 *  2.0 2.1 	=> Version must be 2.0 or 2.1
	 *  ...
	 * 
	 * WARNING: default behaviour is OR
	 * TODO: implement support for AND to be able to support stuff like: >=2.0 & <3.0 
	 * TODO: implement a NOT
	 * 
	 * @param string $group
	 * @param string $name
	 * @param string $version
	 */
	public function __construct($group, $name, $version, $scope="app", $revision = 0, $repository = null) {
		$this->version = $version;
		$this->name = $name;
		$this->group = $group;
		$this->scope = $scope;
		$this->revision = $revision;
		$this->repository = $repository;
		
		if (strpos($name, "/") !== false) {
			throw new MoufException("In a package.xml file, you cannot declare a dependency with a name that contains a '/'. Package whose name is ".$name." (group: $group, version: $version) is not a valid dependency.");
		}
		
		$tokens = array();
		
		// Let's split the spaces
		$tok = strtok($this->version, " \t\n");
		while ($tok !== false) {
		    $tokens[] = $tok;
		    $tok = strtok(" \t\n");
		}
		
		$tokens2 = array();
		// Now, let's split again the > < = and ! signs
		foreach ($tokens as $tok) {
			if (strpos($tok, ">=")===0 || strpos($tok, "<=")===0) {
				$tokens2[] = substr($tok, 0, 2);
				$tokens2[] = substr($tok, 2);
			} elseif (strpos($tok, ">")===0 || strpos($tok, "<")===0 || strpos($tok, "=")===0) {
				$tokens2[] = substr($tok, 0, 1);
				$tokens2[] = substr($tok, 1);
			} else {
				$tokens2[] = $tok;
			}
		} 
		
		// Now, let's group all this into operations.
		// array() containing 2 elements array: array(0=>operation, 1=>version);
		$operations = array();
		$operation = "=";
		$operationsTable = array("=", ">", "<", ">=", "<=");
		foreach ($tokens2 as $tok) {
			if (array_search($tok, $operationsTable)) {
				$operation = $tok;
			} else {
				// This is a version number.
				$operations[] = array($operation, $tok);
				$operation = "=";
			}
		}
		
		$this->versionOperations = $operations;
	}
	
	/**
	 * Returns the version number of the package
	 *
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}
	
	/**
	 * Returns the name of the package
	 *
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns the group of the package
	 *
	 * @return string
	 */
	public function getGroup() {
		return $this->group;
	}
	
	/**
	 * Returns the scope of the package
	 *
	 * @return string
	 */
	public function getScope() {
		return $this->scope;
	}
	
	/**
	 * Returns the minimum revision number of the package
	 *
	 * @return int
	 */
	public function getMinimumRevision() {
		return $this->revision;
	}
	
	/**
	 * Returns the URL of the repository this package can be found in.
	 * Please note this does not mean the package will be downloaded from that repository.
	 * It means that if the package is not found in the available repositories, the user does not have that repository, he will be proposed to
	 * add it to its repositories before continuing.
	 * On the other hand, if the package is available straight away from existing repositories,
	 * this value will be ignored.
	 * 
	 * @return string
	 */
	public function getRepository() {
		return $this->repository;
	}
	
	/**
	 * Checks if the version passed in parameter is compatible with the dependency requirements.
	 * 
	 * @param string $v2 The version to match to the dependency.
	 */
	public function isCompatibleWithVersion($v2) {
		
		foreach ($this->versionOperations as $op) {
			$comp = MoufPackageDescriptor::compareVersionNumber($v2, $op[1]);
			switch ($op[0]) {
				case "=":
					if ($comp == 0)
						return true;
					break;
				case ">":
					if ($comp > 0)
						return true;
					break;
				case ">=":
					if ($comp >= 0)
						return true;
					break;
				case "<":
					if ($comp < 0)
						return true;
					break;
				case "<=":
					if ($comp <= 0)
						return true;
					break;
				default:
					throw new Exception("Unknown operand: ".$op[0]);
			}
		}
		
		// if none matched, return false.
		return false;		
	}

	/**
	 * Returns true if the package descriptor passed in parameter matches the dependency.
	 * 
	 * @param MoufPackageDescriptor $moufPackageDescriptor
	 */
	/*public function isCompatible(MoufPackageDescriptor $moufPackageDescriptor) {
		if ($moufPackageDescriptor->getGroup() == $this->group && $moufPackageDescriptor->getName() == $this->name && $this->isCompatibleWithVersion($moufPackageDescriptor->getVersion())) {
			return true;
		} else {
			return false;
		}
	}*/
	
	/**
	 * Returns a PHP array that describes the package.
	 * 
	 * The structure of the array is:
	 * 	array("version" => string, "name"=> string, "group"=>string, "revision"=>int, "repository"=>string)
	 * 
	 * return array<string, string>
	 */
	public function getJsonArray() {
		$array = array("version"=>$this->version,
			"name"=>$this->name,
			"group"=>$this->group,
			"scope"=>$this->scope,
			"revision"=>$this->revision,
			"repository"=>$this->repository);

		return $array;		
	}
	
	/**
	 * Returns a MoufDependencyDescriptor from a PHP array describing the package dependency descriptor.
	 * 
	 * The structure of the array is:
	 * 	array("version" => string, "name"=> string, "group"=>string, "revision"=>int, "repository"=>string)
	 * 
	 * @param array $array
	 * @return MoufDependencyDescriptor
	 */
	public static function fromJsonArray($array) {
		return new MoufDependencyDescriptor($array['group'], $array['name'], $array['version'], $array['scope'], $array['revision'], $array['repository']);
	}
	
}

?>