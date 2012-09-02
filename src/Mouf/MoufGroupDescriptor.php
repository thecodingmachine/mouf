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
 * This class describes a "group": a set of subgroups, and packages version containers.
 * Note: a package version container is a set of packages that have the same name, but different versions.
 * 
 * @author David
 */
class MoufGroupDescriptor {
	
	/**
	 * The list of subgroups that are part of this group.
	 * 
	 * @var array<string, MoufGroupDescriptor>
	 */
	public $subGroups = array();
	
	
	/**
	 * The list of packages versions (a package version container is a group of packages with the same name and different version numbers).
	 * 
	 * @var array<string, MoufPackageVersionsContainer>
	 */
	public $packages = array();
	
	/**
	 * Returns the subgroup whose name is "$name".
	 * The group is created if it does not exist.
	 * If the group name contains slashes (if this represents a set of groups), the last subgroup will be returned.
	 * 
	 * @param string $name
	 * @return MoufGroupDescriptor
	 */
	public function getGroup($name) {
		$names = explode("/", $name);
		
		$parent = $this;
		foreach ($names as $tmpName) {
			if (!isset($parent->subGroups[$tmpName])) {
				$newGroup = new MoufGroupDescriptor();
				$parent->subGroups[$tmpName] = $newGroup;
				$parent = $newGroup;
			} else {
				$parent = $parent->subGroups[$tmpName];
			}
		}
		return $parent;
	}
	
	/**
	 * Returns the package version container whose name is "$name".
	 * The container is created if it does not exist.
	 * 
	 * @param string $name
	 * @return MoufPackageVersionsContainer
	 */
	public function getPackageContainer($name) {
		if (!isset($this->packages[$name])) {
			$newPackageContainer = new MoufPackageVersionsContainer();
			$this->packages[$name] = $newPackageContainer;
		}
		return $this->packages[$name];
	}
	
	/**
	 * Returns true if the group contains the package container passed in parameter.
	 * False otherwise.
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function hasPackageContainer($name) {
		if (isset($this->packages[$name])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Returns true if the group contains the subgroup passed in parameter.
	 * False otherwise.
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function hasSubgroup($name) {
		if (isset($this->subGroups[$name])) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Returns a PHP array that describes the group.
	 * The array does not contain all available information, only enough information to display the list of packages in the Mouf interface.
	 * 
	 * The structure of the array is:
	 * 	array("subGroups" => array('subGroupName' => subGroupArray, 'packages' => array('packageName', packageArray)
	 * 
	 * return array
	 */
	public function getJsonArray() {
		$array = array();
		if (!empty($this->subGroups)) {
			$array['subgroups'] = array();
			foreach ($this->subGroups as $name => $subGroup) {
				$array['subgroups'][$name] = $subGroup->getJsonArray();
			}
		}
		if (!empty($this->packages)) {
			$array['packages'] = array();
			foreach ($this->packages as $name => $package) {
				/* @var $package MoufPackageVersionsContainer */
				$array['packages'][$name] = $package->getJsonArray();
			}
		}
		return $array;		
	}
	
	/**
	 * Returns a MoufGroupDescriptor from a PHP array describing the group.
	 * Note: since the PHP array does not contain all the available information, the object will be incomplete.
	 * However, it has enough information to display the list of packages available for download.
	 * 
	 * The structure of the array is:
	 * 	array("subGroups" => array('subGroupName' => subGroupArray, 'packages' => array('packageName', packageArray)
	 * 
	 * @param array $array
	 * @return MoufGroupDescriptor
	 */
	public static function fromJsonArray(array $array, MoufRepository $repository, $parentGroup = "") {
		$moufGroupDescriptor = new MoufGroupDescriptor();
		if (isset($array['subgroups'])) {
			foreach ($array['subgroups'] as $name => $subgrouparray) {
				$moufGroupDescriptor->subGroups[$name] = self::fromJsonArray($subgrouparray, $repository, $parentGroup."/".$name);
			}
		}
		if (isset($array['packages'])) {
			foreach ($array['packages'] as $name => $package) {
				$moufGroupDescriptor->packages[$name] = MoufPackageVersionsContainer::fromJsonArray($package, $parentGroup, $name, $repository);
			}
		}
		return $moufGroupDescriptor;
	}
}

?>