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
 * A package descriptor is an object describing the location of a package (from its group, name and version number).
 *
 */
class MoufPackageDescriptor {
	/**
	 * The version number of the package (comes from the path: group/name/version)
	 *
	 * @var string
	 */
	private $version;
	
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
	
	public function __construct($group, $name, $version) {
		$this->version = $version;
		$this->name = $name;
		$this->group = $group;
	}
	
	/**
	 * Returns a package descriptor object from the filename. 
	 * 
	 * @param string $fileName The path to the package.xml file, relative to the root of the plugins directory.
	 * @return MoufPackageDescriptor
	 */
	public static function getPackageDescriptorFromPackageFile($fileName) {
		$packageDir = dirname($fileName);
		
		
		// From the package dir, let's find the group, the name, and the version!
		$version = basename($packageDir);
		$tmpDir = dirname($packageDir);
		$name = basename($tmpDir);
		$tmpGroup = dirname($tmpDir);
		if (strpos($tmpGroup, "./") === 0) {
			$group = substr($tmpGroup, 2);
		} elseif (strpos($tmpGroup, "/") === 0) {
			$group = substr($tmpGroup, 1);
		}else {
			$group = $tmpGroup;
		}
		
		return new MoufPackageDescriptor($group, $name, $version);
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
	 * Returns the package directory (without a trailing slash), relative to the plugins directory.
	 *
	 * @return string
	 */
	public function getPackageDirectory() {
		return $this->group."/".$this->name."/".$this->version;
	}
	
	/**
	 * Returns the path to the package.xml file from the root "plugins" directory.
	 *
	 * @return string
	 */
	public function getPackageXmlPath() {
		return $this->group."/".$this->name."/".$this->version."/package.xml";
	}
	
	/**
	 * Compares the version number of one package to the version number of another package.
	 * Returns a negative number if v2 < v1
	 * Returns 0 if v1 = v2
	 * Returns a positive number if v2 > v1
	 * 
	 * @param string $v2
	 * @param string $v1
	 */
	public static function compareVersionNumber($v2, $v1) {
		$v1parts = array();
		
		$part = strtok($v1, " -_.,/\\°\t");
		while ($part !== false) {
			$v1parts[] = $part;
			$part = strtok(" -_.,/\\°\t");
		}
		
		$v2parts = array();
		
		$part = strtok($v2, " -_.,/\\°\t");
		while ($part !== false) {
			$v2parts[] = $part;
			$part = strtok(" -_.,/\\°\t");
		}
		
		// Now, let's compare v1parts and v2parts
		for ($i=0, $count=max(count($v1parts), count($v2parts)); $i<$count; $i++) {
			if (isset($v1parts[$i])) {
				$part1 = $v1parts[$i];
			} else {
				$part1 = 0;
			}
			if (isset($v2parts[$i])) {
				$part2 = $v2parts[$i];
			} else {
				$part2 = 0;
			}
			
			// Let's do a presort: alpha < beta < [number] < letter
			if (stripos($part1, "alpha") !== false) {
				$presort1 = 0;
			}
			else if (stripos($part1, "beta") !== false) {
				$presort1 = 1;
			}
			else if (stripos($part1, "RC") !== false) {
				$presort1 = 2;
			}
			else if (is_numeric($part1)) {
				$presort1 = 3;
			}
			else {
				$presort1 = 4;
			}
			if (stripos($part2, "alpha") !== false) {
				$presort2 = 0;
			}
			else if (stripos($part2, "beta") !== false) {
				$presort2 = 1;
			}
			else if (stripos($part2, "RC") !== false) {
				$presort2 = 2;
			}
			else if (is_numeric($part2)) {
				$presort2 = 3;
			}
			else {
				$presort2 = 4;
			}
			
			// Let's compare the presort, then the parts.
			if ($presort2 == $presort1) {
				if ($part1 === $part2) {
					continue;
				}
				if (is_numeric($part1) && is_numeric($part2)) {
					return $part2-$part1;
				} else {
					return strcasecmp($part1, $part2);
				}
			} else {
				return $presort2-$presort1;
			}
			
			
		}		
		
		return 0;
	}
	
}
?>