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
 * This class represents a repository in Mouf.
 * A repository is represented by a name, a URL, and its content (all packages available at the URL).
 * 
 * @author david
 */
class MoufRepository  {
	
	private $id;
	
	private $name;
	
	private $url;
	
	/**
	 * 
	 * @var MoufGroupDescriptor
	 */
	private $rootGroup;

	/**
	 * 
	 * @var MoufPackageDownloadService
	 */
	private $packageDownloadService;
	
	public function __construct($id, $name, $url, MoufPackageDownloadService $packageDownloadService) {
		$this->id = $id;
		$this->name = $name;
		$this->url = $url;
		$this->packageDownloadService = $packageDownloadService;
	}
	
	/**
	 * Returns the ID of the repository
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns the name of the repository
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns the URL of the repository
	 * @return int
	 */
	public function getUrl() {
		return $this->url;
	}
	
	/**
	 * Returns the root group of the directory.
	 * 
	 * @return MoufGroupDescriptor
	 */
	public function getRootGroup() {
		if ($this->rootGroup == null) {
			$this->rootGroup = $this->packageDownloadService->getPackageListFromRepository($this);
		}
		return $this->rootGroup;
	}
	
	/**
	 * Returns the list of all versions for the package specified, or null if no version is available in this repository.
	 * 
	 * @param string $group
	 * @param string $name
	 * @return MoufPackageVersionsContainer
	 */
	public function getVersionsForPackage($group, $name) {
		$groupDirs = explode("/", $group);
		if ($groupDirs[0] == ".") {
			array_shift($groupDirs);
		}
		
		$currentGroup = $this->getRootGroup();
		
		foreach ($groupDirs as $groupDir) {
			if (!$currentGroup->hasSubgroup($groupDir)) {
				return null;
			}
			$currentGroup = $currentGroup->getGroup($groupDir);
		}

		if (!$currentGroup->hasPackageContainer($name)) {
			return null;
		}
		$packageContainer = $currentGroup->getPackageContainer($name);
		
		return $packageContainer;
	}

	/**
	 * Returns the package specified, or null if this package is not available in this repository.
	 * 
	 * @param string $group
	 * @param string $name
	 * @param string $version
	 * @return MoufPackage
	 */
	public function getPackage($group, $name, $version) {
		$groupDirs = explode("/", $group);
		if ($groupDirs[0] == "." || $groupDirs[0] == "") {
			array_shift($groupDirs);
		}
		
		$currentGroup = $this->getRootGroup();
		
		foreach ($groupDirs as $groupDir) {
			if (!$currentGroup->hasSubgroup($groupDir)) {
				return null;
			}
			$currentGroup = $currentGroup->getGroup($groupDir);
		}

		if (!$currentGroup->hasPackageContainer($name)) {
			return null;
		}
		$packageContainer = $currentGroup->getPackageContainer($name);
		
		return $packageContainer->getPackage($version);
	}

	
	
}

?>