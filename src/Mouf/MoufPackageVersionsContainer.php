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
 * This class describes a set of packages, that have the same group, the same name, but different version numbers.
 * 
 * @author David
 */
class MoufPackageVersionsContainer {
		
	/**
	 * The list of packages.
	 * The key is the version number.
	 * 
	 * @var array<string, MoufPackage>
	 */
	public $packages = array();
	 
	/**
	 * Returns the package version whose version is "$version".
	 * Returns null of the version is not found.
	 * 
	 * @param string $name
	 * @return MoufPackage
	 */
	public function getPackage($version) {
		if (!isset($this->packages[$version])) {
			return null;
		}
		return $this->packages[$version];
	}
	
	public function setPackage(MoufPackage $package) {
		$this->packages[$package->getDescriptor()->getVersion()] = $package;
	}

	/**
	 * Returns a PHP array that describes the packages versions.
	 * The array does not contain all available information, only enough information to display the list of packages in the Mouf interface.
	 * 
	 * The structure of the array is:
	 * 	array(version => package)
	 * 
	 * return array<string, MoufPackage>
	 */
	public function getJsonArray() {
		$array = array();
		if (!empty($this->packages)) {
			foreach ($this->packages as $version => $package) {
				/* @var $package MoufPackage */
				$array[$version] = $package->getJsonArray();
			}
		}

		return $array;		
	}
	
	/**
	 * Returns a MoufPackageVersionsContainer from a PHP array describing the versions container.
	 * Note: since the PHP array does not contain all the available information, the object will be incomplete.
	 * However, it has enough information to display the list of packages available for download.
	 * 
	 * The structure of the array is:
	 * 	array(version => package)
	 * 
	 * @param array $packages
	 * @return MoufPackageVersionsContainer
	 */
	public static function fromJsonArray(array $packages, $groupName, $packageName, MoufRepository $repository) {
		$moufVersionsContainer = new MoufPackageVersionsContainer();
		if (!empty($packages)) {
			foreach ($packages as $version => $package) {
				$moufVersionsContainer->packages[$version] = MoufPackage::fromJsonArray($package, $groupName, $packageName, $version, $repository);
			}
		}
		return $moufVersionsContainer;
	}
	
	/**
	 * Returns an ordered list of the packages, ordered by ascending version number
	 * 
	 * @return array<MoufPackage>
	 */
	public function getOrderedList() {
		$packages = $this->packages;
		uksort($packages, array("MoufPackageDescriptor", "compareVersionNumber"));
		return $packages;
	}
}

?>