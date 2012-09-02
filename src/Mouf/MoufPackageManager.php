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

class MoufPackageManager {
	
	/**
	 * The plugins directory path.
	 *
	 * @var string
	 */
	private $pluginsDir;
	
	/**
	 * The list of all packages found in the plugins directory.
	 *
	 * @var array<MoufPackage>
	 */
	private $packageList;
	
	/**
	 * The list of packages that has been loaded so far.
	 * The key is the path to the packacges.xml file relative to the "plugins" directory.
	 *
	 * @var array<string, MoufPackage>
	 */
	private $packages = array();

	/**
	 * The list of installed packages.
	 * To fill this list, you must call the getInstalledPackagesList method.
	 * 
	 * @var array<MoufPackage>
	 */
	private $installedPackages;
	
	/**
	 * Enter description here...
	 *
	 * @param string $pluginsDir The plugins directory path. Defaults to "../plugins"
	 */
	public function __construct($pluginsDir = "../plugins") {
		$this->pluginsDir = $pluginsDir;
	}
	
	/**
	 * Returns the list of packages that have been found in the "plugins" directory.
	 *
	 * @return array<MoufPackage>
	 */
	public function getPackagesList() {
		// Use the "local cache" if needed
		if ($this->packageList != null)
			return $this->packageList;
		
		$currentDir = getcwd();
		
		//var_dump(glob("*", GLOB_ONLYDIR));
		
		chdir($this->pluginsDir);
		$packages = $this->scanDir(".", array());
		chdir($currentDir);
		
		$this->packageList = $packages;
		return $packages;
	}
	
	/**
	 * Scans a directory. If the directory contains a package.xml file, the MoufPackage object is created,
	 * and subdirectories are not searched. Otherwise, subdirectories are searched for a
	 * package.xml file.
	 * 
	 * @param string $currentDir
	 * @param array<MoufPackage> $packageList
	 * @return array<MoufPackage>
	 */
	private function scanDir($currentDir, $packageList) {
		
		
		if (file_exists("$currentDir/package.xml")) {
			$package = $this->getPackage($currentDir."/package.xml");
			$packageList[] = $package;
			return $packageList;
		}
		
		$directories = glob("$currentDir/*", GLOB_ONLYDIR);
		foreach ($directories as $directory) {
			//echo "scanning ".$directory."<br/>";
			$packageList = $this->scanDir($directory, $packageList); 
		}
		return $packageList;
	}
	
	/**
	 * Creates an instance of the MoufPackage object from the package.xml file.
	 *
	 * @param string $fileName
	 * @return MoufPackage
	 */
	private function createPackage($fileName) {
		$package = new MoufPackage();
		
		$currentDir = getcwd();
		chdir($this->pluginsDir);
		$package->initFromFile($fileName);
		chdir($currentDir);
		
		$this->packages[$fileName] = $package;
		return $package;
	}
	
	/**
	 * Returns the MoufPackage object from the package.xml file (relative to the "plugins" directory).
	 *
	 * @param string $fileName
	 * @return MoufPackage
	 */
	public function getPackage($fileName) {
		if (isset($this->packages[$fileName])) {
			return $this->packages[$fileName];
		} else {
			return $this->createPackage($fileName);
		}
	}
	
	/**
	 * Returns the package from its group, name and version.
	 * 
	 * @param string $group
	 * @param string $name
	 * @param string $version
	 * @return MoufPackage
	 */
	public function getPackageByDefinition($group, $name, $version) {
		return $this->getPackage($group.'/'.$name.'/'.$version.'/package.xml');
	}
	
	/**
	 * Returns the list of dependencies (recursively) that need to be enabled for this package to be enabled.
	 * Dependencies are ordered.
	 * The most recent package possible is chosen.
	 *
	 * @param MoufPackage $package
	 * @param string $scope The scope the package is to be included (can be "app" for normal inclusion, or "admin" if the package should be required on the admin side).
	 * @param array<scope, array<string, MoufPackage>> $upgradeList List of packages that will be upgraded. The first key is the scope (app[admin), the second key is the [group]/[name]
	 * @return array<MoufPackage>
	 */
	public function getDependencies(MoufPackage $package, $scope, array $upgradeList = array()) {
		$orderedPackageList = $this->getOrderedPackagesList();
		
		if ($scope == MoufManager::SCOPE_APP) {
			$moufManager = MoufManager::getMoufManagerHiddenInstance(); 		
		} else {
			$moufManager = MoufManager::getMoufManager();
		}
		
		$packageDownloadService = MoufAdmin::getPackageDownloadService();
		$packageDownloadService->setMoufManager($moufManager);
		
		return $this->getRecursiveDependencies($package, $scope, array(), /*$moufManager,*/ $orderedPackageList, $packageDownloadService, $upgradeList);
	}

	/**
	 * Recurse through the dependencies.
	 *
	 * @param MoufPackage $package
	 * @param string $scope The scope the package is to be included (can be "app" for normal inclusion, or "admin" if the package should be required on the admin side).
	 * @param array[$scope]<MoufPackage> $packageDependencies
	 * @param MoufManager $moufManager
	 * @param MoufGroupDescriptor $orderedPackageList
	 * @param array[$scope]<string, MoufPackage> $upgradeList List of packages that will be upgraded. The key is [group]/[name] 
	 * @return array[$scope]<MoufPackage>
	 */	
	private function getRecursiveDependencies(MoufPackage $package,
			$scope, 
			array $packageDependencies, 
			//MoufManager $moufManager, 
			MoufGroupDescriptor $orderedPackageList, 
			MoufPackageDownloadService $packageDownloadService,
			array $upgradeList) {

		$dependencies = $package->getDependenciesAsDescriptors();
		
		
		// For each dependency of the package
		if ($dependencies) {
			foreach ($dependencies as $dependency) {
				/* @var $dependency MoufDependencyDescriptor */
				
				// First, if current scope is admin and dependency scope is admin, let's just ignore. There is no such thing as an admin for the admin scope.
				if ($dependency->getScope() == MoufManager::SCOPE_ADMIN && $scope == MoufManager::SCOPE_ADMIN) {
					continue;
				}
				
				if ($scope == MoufManager::SCOPE_APP && $dependency->getScope() == MoufManager::SCOPE_APP) {
					$moufManager = MoufManager::getMoufManagerHiddenInstance();
					$dependencyRealScope = MoufManager::SCOPE_APP;
				} else {
					$moufManager = MoufManager::getMoufManager();
					$dependencyRealScope = MoufManager::SCOPE_ADMIN;
				}
				
				// The list of exceptions encountered during the search for this dependency.
				$encounteredExceptions = array();
				
				// First, is the dependency already installed? If yes, is the version compatible with the requested version?
				$currentEnabledVersion = $moufManager->getVersionForEnabledPackage($dependency->getGroup(), $dependency->getName());
				
				if ($currentEnabledVersion !== null) {
					// A version of the package is already enabled.
					// Will we keep this package or are we planning to updgrade it?
					if (isset($upgradeList[$dependencyRealScope][$dependency->getGroup()."/".$dependency->getName()])) {
						// We have already planned to upgrade this package. Let's use this version instead.
						$upgradedPackage = $upgradeList[$dependencyRealScope][$dependency->getGroup()."/".$dependency->getName()];
						/* @var $upgradedPackage MoufPackage */
						$currentEnabledVersion = $upgradedPackage->getDescriptor()->getVersion();
					}
					
					// Is it compatible with the package we want to enable?
					if (!$dependency->isCompatibleWithVersion($currentEnabledVersion)) {
						// We are incompatible!
						// Let's throw an Exception (that will be catched and correctly displayed by the controller or be catched by the recursive mecanism).
						// Note: this is not a best practice to use Exceptions in the recursion, but this is very practical.
						
						// TODO (in process): before issuing an incompatible package exception, we might want to propose upgrading the package of the currently enabled version.
						// We might do this in 2 passes: one trying to do everything without upgrades, and the next one trying to authorize upgrades (that
						// should be signaled to the user, of course!)
						// We should also completely disallow downgrades.
						// Problem: this is more complex, as if the package upgraded is used by other packages, the other packages might need to be upgraded too!
						// We might want instead to do things manually: if such an exception is used at the top level, we might want to propose
						// upgrading to it (manually in the UI), then we run again with the 2 packages, and so on until we have all the packages updated.
						throw new MoufIncompatiblePackageException($package, $dependency, $currentEnabledVersion, true, $dependencyRealScope);
					} else {
						// The package is already installed, and already compatible.
						// Let's continue
						continue;
					}
				}
				
				// Second, let's get all the dependencies that are not yet installed but part of the recursive process.
				// Let's see if the current dependency is already installed.
				// If yes, let's see if the version is compatible.
				$isDependencyAlreadyPartOfInstallProcess = false;
				if (isset($packageDependencies[$dependencyRealScope])) {
					foreach ($packageDependencies[$dependencyRealScope] as $toBeInstalledPackage) {
						/* @var $toBeInstalledPackage MoufPackage */
						
						$toBeInstalledPackageDescriptor = $toBeInstalledPackage->getDescriptor();
						// Is the package already part of the list of packages to be installed?
						if ($toBeInstalledPackageDescriptor->getGroup() == $dependency->getGroup()
							&& $toBeInstalledPackageDescriptor->getName() == $dependency->getName()) {
						
							if (!$dependency->isCompatibleWithVersion($toBeInstalledPackageDescriptor->getVersion())) {
								// We are incompatible!
								// Let's throw an Exception (that will be catched and correctly displayed by the controller or be catched by the recursive mecanism).
								// Note: this is not a best practice to use Exceptions in the recursion, but this is very practical.
								throw new MoufIncompatiblePackageException($package, $dependency, $toBeInstalledPackageDescriptor->getVersion(), false, $dependencyRealScope);
							} else {
								// The package is already added to the list and is furthermore compatible.
								// We don't have to do anything, let's continue
								
								$isDependencyAlreadyPartOfInstallProcess = true;
								continue;
							}
						}
					}
				}
				if ($isDependencyAlreadyPartOfInstallProcess) {
					continue;	
				}
				
				$packageFound = false;
				// Let's get all the LOCAL versions available, and see if one version matches the dependency requirements.
				$versions = $this->getVersionsForPackage($dependency->getGroup(), $dependency->getName(), $orderedPackageList);
				// Note: the $versions are sorted in reverse order, which is exactly what we need.
				$foundCorrectVersion = false;
				if (!empty($versions->packages)) {
					$packageFound = true;
				}
				foreach ($versions->packages as $version=>$myPackage) {
					/* @var $myPackage MoufPackage */
					
					// Let's test each version.
					if ($dependency->isCompatibleWithVersion($version)) {
						// We found a compatible version! Yeah!
						$newPackageDependencies = $packageDependencies;
						$toAddPackage = $this->createPackage($myPackage->getDescriptor()->getGroup()."/".$myPackage->getDescriptor()->getName()."/".$version."/package.xml");
						//$newPackageDependencies[] = $toAddPackage;
						
						// PREVIOUS IDEA: Add the package to the beginning of the array
						// 	NOTE: is this really ok? This package might depend on a package that is in the $newPackageDependencies dependency list!!!!
						//  So we will add the package AFTER we find the recursive dependencies.
						//  TODO: add a defensive mechanism to avoid recursions (a package that refers itself or a package that refers a second package that refers the first, etc...)
						//array_unshift($newPackageDependencies, $toAddPackage);
						
						// Let's recurse
						try {
							$packageDependencies = $this->getRecursiveDependencies($myPackage, $dependencyRealScope, $newPackageDependencies, /*$moufManager,*/ $orderedPackageList, $packageDownloadService, $upgradeList);
						} catch (Exception $ex) {
							// If there is a problem, we try the next version.
							$encounteredExceptions[] = $ex; 
							continue;
						}
						
						$packageDependencies[$dependencyRealScope][] = $toAddPackage;
						
						// If there is no problem, we go to the next dependency for the package $package.
						$foundCorrectVersion = true;
						break;
					}
				}
				
				if ($foundCorrectVersion == false) {
					// If we are here, we failed finding a compatible version locally...
					// Let's try again, but using the repositories.
					$repositories = $packageDownloadService->getRepositories();
					
					foreach ($repositories as $repository) {
						/* @var $repository MoufRepository  */
						try {
							// Let's get all the REMOTE versions available for current explored repository, and see if one version matches the dependency requirements.
							$versions = $repository->getVersionsForPackage($dependency->getGroup(), $dependency->getName());
							// Note: the $versions are sorted in reverse order, which is exactly what we need.
							if (!empty($versions->packages)) {
								$packageFound = true;
							}
							if ($versions != null) {
								foreach ($versions->packages as $version=>$myPackage) {
									/* @var $myPackage MoufPackage */
									
									// Let's test each version.
									if ($dependency->isCompatibleWithVersion($version)) {
										// We found a compatible version! Yeah!
										$newPackageDependencies = $packageDependencies;
			
										$toAddPackage = $versions->getPackage($version);
										
										// Let's recurse
										try {
											$packageDependencies = $this->getRecursiveDependencies($myPackage, $dependencyRealScope, $newPackageDependencies, /*$moufManager,*/ $orderedPackageList, $packageDownloadService, $upgradeList);
										} catch (MoufIncompatiblePackageException $ex) {
											// If there is a problem, we try the next version.
											$encounteredExceptions[] = $ex; 
											continue;
										}
			
										// TODO: the line below has been replaced with line below, check this is correct. 
										//$newPackageDependencies[$dependencyRealScope][] = $toAddPackage;
										$packageDependencies[$dependencyRealScope][] = $toAddPackage;
										
										// If there is no problem, we go to the next dependency for the package $package.
										$foundCorrectVersion = true;
										break;
									}
								}
							}
							if ($foundCorrectVersion) {
								break;
							}
						} catch (MoufNetworkException $e) {
							// Ignore any network exception. We will do as if the repository was not available.
						}
					}
	
					// We couldn't find a compatible version locally or remotely, let's throw an exception.
					if (!$foundCorrectVersion) {
						if ($packageFound) {
							// Let's throw an exception.
							throw new MoufProblemInDependencyPackageException($package->getDescriptor()->getGroup(), $package->getDescriptor()->getName(), $package->getDescriptor()->getVersion(), $encounteredExceptions);
						} else {
							throw new MoufPackageNotFoundException($package->getDescriptor()->getGroup(), $package->getDescriptor()->getName(), $dependency->getGroup(), $dependency->getName(), $dependency->getVersion());
						}
					}
				}
			}	
		}
		
		// If we are here, there are no dependencies to the package, or they are all satisfied.
		// Let's return the list of dependencies.
		return $packageDependencies;
		
	}
	
	
	/**
	 * Returns a MoufPackageVersionsContainer that contains all the available versions for the package passed in parameter.
	 * 
	 * @param string $group
	 * @param string $name
	 * @return MoufPackageVersionsContainer
	 */
	private function getVersionsForPackage($group, $name, MoufGroupDescriptor $orderedPackageList) {		
		$packageGroup = $orderedPackageList; 
		$groups = explode("/", $group);
		foreach ($groups as $groupPart) {
			$packageGroup = $packageGroup->getGroup($groupPart);
		}
		return $packageGroup->getPackageContainer($name);
	}
	
	
	/**
	 * Returns the list of children (packages that depend upon this package (recursively) for this package.
	 * The version of the real installed packages can be replaced using the $updateList array that binds a group/name to a new version number.
	 * 
	 * @param MoufPackage $package
	 * @param MoufManager $moufManager
	 * @param array $updateList
	 * @return array<MoufPackage>
	 */
	public function getInstalledPackagesUsingThisPackage(MoufPackage $package, MoufManager $moufManager, array $updateList = array()) {
		return $this->getRecursiveChildren($package, array(), $moufManager, $updateList);
	}
	
	/**
	 * Recurse through the children.
	 * The version of the real installed packages can be replaced using the $updateList array that binds a group/name to a new version number.
	 *
	 * @param MoufPackage $package
	 * @param array<MoufPackage> $packageDependencies
	 * @param MoufManager $moufManager
	 * @return array<MoufPackage>
	 */
	private function getRecursiveChildren(MoufPackage $package, array $packageChildren, MoufManager $moufManager, array $updateList) {
		$chilrenPackages = $this->getInstalledPackagesUsingPackage($package, $moufManager, $updateList);
		foreach ($chilrenPackages as $child) {
			if (array_search($child, $packageChildren)) {
				continue;
			}
			
			$packageChildren = $this->getRecursiveChildren($child, $packageChildren, $moufManager, $updateList);
			$packageChildren[] = $child;
		}
		return $packageChildren;
	}
	
	/**
	 * Returns the list of packages that are using this package.
	 * This function is only returning the first level of packages.
	 * Use getRecursiveChildren to get the full list of all children and grand-children.
	 * The version of the real installed packages can be replaced using the $updateList array that binds a group/name to a new version number.
	 *
	 * @param MoufPackage $parentPackage
	 * @param MoufManager $moufManager
	 * @return array<MoufPackage>
	 */
	private function getInstalledPackagesUsingPackage(MoufPackage $parentPackage, MoufManager $moufManager, array $updateList) {
		// The list of packages currently installed (with the version number updated if needed).
		$installedPackages = $this->getInstalledOrUpdatedPackageList($moufManager, $updateList);
		
		//$packageList = $this->getPackagesList();
		$children = array();
		foreach ($installedPackages as $package) {
			/* @var $package MoufPackage */
			$packageDependencies = $package->getDependenciesAsDescriptors();
			
			foreach ($packageDependencies as $dependencyDescriptor) {
				/* @var $dependencyDescriptor MoufDependencyDescriptor */
				
				if ($dependencyDescriptor->getGroup() == $parentPackage->getDescriptor()->getGroup()
					&& $dependencyDescriptor->getName() == $parentPackage->getDescriptor()->getName()) {
					$children[] = $package;
					break;
				}
				/*$fileName = $dependencyDescriptor->getPackageXmlPath();
				if ($fileName == $parentPackage->getDescriptor()->getPackageXmlPath()) {
					$children[] = $package;
				}*/
			}
		}
		return $children;
	}
	
	/**
	 * Returns the list of all installed packages.
	 * Note: packages are read only on the first call. Subsequent call will return always the same list.
	 * 
	 * @param MoufManager $moufManager
	 * @return array<MoufPackage>
	 */
	private function getInstalledPackagesList(MoufManager $moufManager) {
		if ($this->installedPackages == null) {
			$installedPackagesFiles = $moufManager->listEnabledPackagesXmlFiles();
			$this->installedPackages = array();
			foreach ($installedPackagesFiles as $packagesFiles) {
				$this->installedPackages[] = $this->getPackage($packagesFiles);
			}
		}
		return $this->installedPackages;
	}
	
	/**
	 * Returns the list of all installed packages.
	 * The $updateList contains a list of packages that must be updated.
	 * This function will return the "to be updated" version of the packages, not the installed one.
	 * 
	 * @param MoufManager $moufManager
	 * @param array $updateList
	 * @return array<MoufPackage>
	 */
	private function getInstalledOrUpdatedPackageList(MoufManager $moufManager, array $updateList) {
		$packageList = $this->getInstalledPackagesList($moufManager);
		foreach ($packageList as $key=>$package) {
			/* @var $package MoufPackage */
			$name = $package->getDescriptor()->getGroup()."/".$package->getDescriptor()->getName();
			if (isset($updateList[$name])) {
				$packageList[$key] = $updateList[$name];
			}
		}
		return $packageList;
	}

	/**
	 * Returns the list of packages that have been found in the "plugins" directory, ordered by groups, package name, packages versions.
	 *
	 * @return MoufGroupDescriptor
	 */
	public function getOrderedPackagesList() {
		$moufPackageList = $this->getPackagesList();
		// Packages are almost sorted correctly.
		// However, we should make a bit of sorting to transform this:
		// javascript/jit
		// javascript/jquery/jquery
		// javascript/prototype
		// into this:
		// javascript/jit
		// javascript/prototype
		// javascript/jquery/jquery
		// (directories at the end)
		// Furthermore, we will sort packages with different version numbers by version number.
		// So we will sort by group, then package, then version:
		uasort($moufPackageList, array($this, "comparePackageGroup"));
		
		$rootDescriptor = new MoufGroupDescriptor();
		
		foreach ($moufPackageList as $package) {
			/* @var $package MoufPackage */
			$packageDescriptor = $package->getDescriptor();
			$group = $packageDescriptor->getGroup();
			$groupDirs = explode("/", $group);
			$currentGroup = $rootDescriptor;
			foreach ($groupDirs as $groupName) {
				$currentGroup = $currentGroup->getGroup($groupName);
			}
			$packageContainer = $currentGroup->getPackageContainer($packageDescriptor->getName());
			$packageContainer->setPackage($package);
		}
		return $rootDescriptor;
	}
	
	public function comparePackageGroup(MoufPackage $package1, MoufPackage $package2) {
		$group1 = $package1->getDescriptor()->getGroup();
		$group2 = $package2->getDescriptor()->getGroup();
		$cmp = strcmp($group1, $group2);
		if ($cmp == 0) {
			$nameCmp = strcmp($package1->getDescriptor()->getName(), $package2->getDescriptor()->getName());
			if ($nameCmp != 0) {
				return $nameCmp;
			} else {
				return -MoufPackageDescriptor::compareVersionNumber($package1->getDescriptor()->getVersion(), $package2->getDescriptor()->getVersion());
			} 
		} else 
			return $cmp;
	}
	
	/**
	 * Returns the default path where we usually path the ZIP file package.
	 * 
	 * @param MoufPackage $moufPackage
	 * @return string The path to the ZIP file.
	 */
	public function getZipFilePath(MoufPackage $moufPackage) {
		$packageDir = ROOT_PATH."plugins/".$moufPackage->getPackageDirectory();
		$zipFileName = $moufPackage->getDescriptor()->getName()."-".$moufPackage->getDescriptor()->getVersion().".zip";
		return $packageDir.'/../'.$zipFileName;
	}
	
	/**
	 * Compresses the package into a ZIP file.
	 * The ZIP file is installed in the group/package directory. 
	 * 
	 * @param MoufPackage $moufPackage
	 * @throws MoufException
	 * @return string The name of the generated file.
	 */
	public function compressPackage(MoufPackage $moufPackage) {
		
		$packageDir = ROOT_PATH."plugins/".$moufPackage->getPackageDirectory();
		
		//echo $packageDir;
						
		//$zipFileName = $moufPackage->getDescriptor()->getName()."-".$moufPackage->getDescriptor()->getVersion().".zip";
		$zipFilePath = $this->getZipFilePath($moufPackage);
		
		if (file_exists($zipFilePath)) {
			if (!is_writable($zipFilePath)) {
				throw new MoufException("Cannot delete ZIP file ".$zipFilePath);
			}
			unlink($zipFilePath);
		}

		$oldcwd = getcwd();
		chdir($packageDir);
		
		// create object
		$zip = new ZipArchive();
		
		$dirname = dirname($zipFilePath);
		if (!is_writable($dirname)) {
			throw new MoufException("Cannot create ZIP file in directory ".$dirname.". Please check the directory rights.");
		}
		
		// open output file for writing
		if ($zip->open($zipFilePath, ZIPARCHIVE::CREATE) !== TRUE) {
		    throw new MoufException("Could not create the ZIP file");
		}

		$this->recurseAddDir($zip, ".");
		
		// close and save archive
		$zip->close();
		
		$fileName = realpath($zipFilePath);
		
		chdir($oldcwd);
		
		return $fileName;
	}
	
	private function recurseAddDir(ZipArchive $zip, $currentDir) {

		$files = glob("$currentDir/*");
		
		foreach ($files as $file) {
			if (strpos($file, "./") === 0) {
				$file = substr($file, 2);
			}
			
			// Ignore version control directories
			if ($file == ".svn" || $file == ".cvs" || $file == ".git") {
				continue;
			}
			
			if (is_dir($file)) {
				$this->recurseAddDir($zip, $file);
			} else {
				//echo "Adding ".$file."<br/>";
				$result = $zip->addFile($file, $file);
				if (!$result) {
					throw new MoufException("Could not add file ".$file." to package ZIP archive.");
				}
			}
		}
		/*$directories = glob("$currentDir/*", GLOB_ONLYDIR);
		foreach ($directories as $directory) {
			//echo "scanning ".$directory."<br/>";
			$packageList = $this->scanDir($directory, $packageList); 
		}
		return $packageList;*/
	}

	/**
	 * Unpack the ZIP archive $filename into a package (the name of the package, and therefore its directory, is defined by $packageDescriptor)
	 * 
	 * @param MoufPackageDescriptor $packageDescriptor
	 * @param unknown_type $fileName
	 * @throws MoufException
	 */
	public function unpackPackage(MoufPackageDescriptor $packageDescriptor, $fileName) {
	
		// create object
		$zip = new ZipArchive();

		// Zip file does not exist
		if(!file_exists($fileName)) {
			throw new MoufException("ZIP file does not exist: '".$fileName."'");
		}
		
		// Test the file size
		if(filesize($fileName) == 0) {
			throw new MoufException("ZIP file is empty: '".$fileName."'");
		}
		
		// open output file for writing
		if ($zip->open($fileName) !== TRUE) {
		    throw new MoufException("Could not open the ZIP file '".$fileName."'");
		}
		
		if (!is_dir(ROOT_PATH."mouf/".$this->pluginsDir."/".$packageDescriptor->getGroup()."/".$packageDescriptor->getName()."/".$packageDescriptor->getVersion()."/")) {
			// Let's build the directory
			$oldumask = umask(0);
			$success = mkdir(ROOT_PATH."mouf/".$this->pluginsDir."/".$packageDescriptor->getGroup()."/".$packageDescriptor->getName()."/".$packageDescriptor->getVersion()."/", 0777, true);
			umask($oldumask);
			if (!$success) {
				throw new MoufException("Unable to create directory ".ROOT_PATH."mouf/".$this->pluginsDir."/".$packageDescriptor->getGroup()."/".$packageDescriptor->getName()."/".$packageDescriptor->getVersion()."/");
			}
		}
		
		if (!is_writable(ROOT_PATH."mouf/".$this->pluginsDir."/".$packageDescriptor->getGroup()."/".$packageDescriptor->getName()."/".$packageDescriptor->getVersion()."/")) {
			throw new MoufException("Unable to write in directory ".ROOT_PATH."mouf/".$this->pluginsDir."/".$packageDescriptor->getGroup()."/".$packageDescriptor->getName()."/".$packageDescriptor->getVersion()."/");
		}

	    $res = $zip->extractTo(ROOT_PATH."mouf/".$this->pluginsDir."/".$packageDescriptor->getGroup()."/".$packageDescriptor->getName()."/".$packageDescriptor->getVersion()."/");
		if (!$res) {
			throw new MoufException("Could not unpack the ZIP file '".$fileName."'. It seems to be corrupted.");
		}
	    
		// close archive
		$zip->close();
		
	}
	
	/**
	 * Returns the list of packages that are requiring $moufPackage and that must be updated.
	 * The $updateList is a list of packages that are already being updated.
	 * WARNING! The list of packages is returned as a list of MoufIncompatiblePackageException. This value is RETURNED, not THROWN. 
	 * 
	 * @param MoufPackage $moufPackage
	 * @param array<scope, <group/name, MoufPackage>> $updateList
	 * @param string $scope
	 * @return array<MoufIncompatiblePackageException>
	 */
	public function getParentPackagesRequiringUpdate(MoufPackage $moufPackage, array $updateList, $scope) {

		if ($scope == MoufManager::SCOPE_APP) {
			$moufManager = MoufManager::getMoufManagerHiddenInstance();
		} else {
			$moufManager = MoufManager::getMoufManager();
		}
		
		
		// FIXME: when in admin, some packages from the app scope might require us. We should detect that too.
		$parentPackages = $this->getInstalledPackagesUsingThisPackage($moufPackage, $moufManager, (isset($updateList[$moufManager->getScope()]))?$updateList[$moufManager->getScope()]:array());
		$toUpdateParentPackages = array();
		foreach ($parentPackages as $parentPackage) {
			/* @var $parentPackage MoufPackage */
			// For each package, let's get the dependency pointing to $moufPackage.
			foreach ($parentPackage->getDependenciesAsDescriptors() as $dependency) {
				/* @var $dependency MoufDependencyDescriptor */
				if ($dependency->getGroup() == $moufPackage->getDescriptor()->getGroup()
						&& $dependency->getName() == $moufPackage->getDescriptor()->getName()) {
					
					// Ok, we have the dependency and the current package. Are they compatible?
					// First, what is the version of the package?
					$packageVersion = $moufPackage->getDescriptor()->getVersion();
					$isInPlaceVersionInstalled = true;
					if (isset($updateList[$moufManager->getScope()][$dependency->getGroup()."/".$dependency->getName()])) {
						$packageVersion = $updateList[$moufManager->getScope()][$dependency->getGroup()."/".$dependency->getName()]->getDescriptor()->getVersion();
						$isInPlaceVersionInstalled = false;
					}
					
					if (!$dependency->isCompatibleWithVersion($packageVersion)) {
						$ex = new MoufIncompatiblePackageException($parentPackage,
								$dependency,
								$packageVersion,
								$isInPlaceVersionInstalled,
								$moufManager->getScope());
						$toUpdateParentPackages[] = $ex;
					}
					break;
				}
			}
			
		}
		
		return $toUpdateParentPackages;
	}
	
	/**
	 * Returns the list of available versions for that package.
	 * The local and remote repositories are searched.
	 * If a package is available in several places, it will be returned from the
	 * local repository first, and if not found from the remote repositories, in order
	 * of appearance.
	 * 
	 * @param string $group
	 * @param string $name
	 * @param MoufManager $moufManager
	 * @return array<string, MoufPackage> the key is the version number.
	 */
	public function getAvailableVersionsForPackage($group, $name, MoufManager $moufManager) {
		$localMoufPackageRoot = $this->getOrderedPackagesList();
		$groupDescriptor = $localMoufPackageRoot->getGroup($group);
		$packageDescriptor = $groupDescriptor->getPackageContainer($name);
		// Key: the version, Value: the package.
		$versions = $packageDescriptor->packages;
		
		$packageDownloadService = MoufAdmin::getPackageDownloadService();
		$packageDownloadService->setMoufManager($moufManager);
		foreach ($packageDownloadService->getRepositories() as $repository) {
			/* @var $repository MoufRepository */
			$moufPackageRoot = $repository->getRootGroup();
			$groupDescriptor = $moufPackageRoot->getGroup($group);
			$packageDescriptor = $groupDescriptor->getPackageContainer($name);
			
			foreach ($packageDescriptor->packages as $version=>$thePackage) {
				if (!isset($versions[$version])) {
					$versions[$version] = $thePackage;
				}
			}
		}
		
		return $versions;
	}
	
	/**
	 * Returns the list of versions for the package $package that are compatible with $requestedVersions.
	 * The local and remote repositories are searched.
	 * If a package is available in several places, it will be returned from the
	 * local repository first, and if not found from the remote repositories, in order
	 * of appearance.
	 * 
	 * @param MoufDependencyDescriptor $requestedVersions
	 * @param MoufManager $moufManager
	 * @throws Exception
	 * @return array<string, MoufPackage>
	 */
	public function getCompatibleVersionsForPackage(MoufDependencyDescriptor $requestedVersions, MoufManager $moufManager) {
		$allVersions = $this->getAvailableVersionsForPackage($requestedVersions->getGroup(), $requestedVersions->getName(), $moufManager);

		$compatibleVersions = array();
		foreach ($allVersions as $key=>$thePackage) {
			if ($requestedVersions->isCompatibleWithVersion($key)) {
				$compatibleVersions[$key] = $thePackage;
			}
		}
		return $compatibleVersions;
	}
	
	
	/**
	 * Finds a package in the local or remote repositories.
	 * The local and remote repositories are searched.
	 * If a package is available in several places, it will be returned from the
	 * local repository first, and if not found from the remote repositories, in order
	 * of appearance.
	 * 
	 * Returns null if no package found.
	 * 
	 * @param string $group
	 * @param string $name
	 * @param string $version
	 * @param MoufManager $moufManager
	 * @return MoufPackage
	 */
	public function findPackage($group, $name, $version, MoufManager $moufManager) {
		// TODO: optimize with a local cache.
		
		// TODOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
		// TODOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
		// TODOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
		// TODOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
		// TODOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
		// TODOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO
		// Grave à optimiser!
		// On a pas besoin d'un appel à getOrderedPackagesList qui prend des méga plombent!
		$localMoufPackageRoot = $this->getOrderedPackagesList();
		$groupDescriptor = $localMoufPackageRoot->getGroup($group);
		$packageDescriptor = $groupDescriptor->getPackageContainer($name);
		// Key: the version, Value: the package.
		if (isset($packageDescriptor->packages[$version])) {
			return $packageDescriptor->packages[$version];
		}		
		
		$packageDownloadService = MoufAdmin::getPackageDownloadService();
		$packageDownloadService->setMoufManager($moufManager);
		foreach ($packageDownloadService->getRepositories() as $repository) {
			/* @var $repository MoufRepository */
			$moufPackageRoot = $repository->getRootGroup();
			$groupDescriptor = $moufPackageRoot->getGroup($group);
			$packageDescriptor = $groupDescriptor->getPackageContainer($name);

			if (isset($packageDescriptor->packages[$version])) {
				return $packageDescriptor->packages[$version];
			}
		}
		
		return null;
	}
}
?>