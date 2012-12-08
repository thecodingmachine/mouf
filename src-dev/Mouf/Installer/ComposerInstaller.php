<?php 
namespace Mouf\Installer;

use Composer\Package\PackageInterface;

use Mouf\MoufException;

use Mouf\Composer\ComposerService;

/**
 * This class is in charge of finding what packages must be installed on the system.
 * 
 * @author David Negrier
 */
class ComposerInstaller {
	
	protected $selfEdit;
	protected $composerService;
	
	public function __construct($selfEdit = false) {
		$this->selfEdit = $selfEdit;
		$this->composerService = new ComposerService($selfEdit);	
	}
	
	
	/**
	 * Returns an ordered list of packages that have an install procedure, with a status saying if
	 * the installation has been performed or not.
	 */
	public function getInstalls() {
		$packages = $this->composerService->getLocalPackages();
		// TODO: is the list local?
		
		$installTasks = array();
		
		foreach ($packages as $package) {
			$extra = $package->getExtra();
			if (isset($extra['mouf']['install'])) {
				$installSteps = $extra['mouf']['install'];
				if (!is_array($installSteps)) {
					$this->io->write("Error while installing package in Mouf. The install parameter in composer.json (extra->mouf->install) should be an array of files/url to install.");
					return;
				}
					
				if ($installSteps) {
					if (self::isAssoc($installSteps)) {
						// If this is directly an associative array (instead of a numerical array of associative arrays)
						$installTasks = array_merge($installTasks, $this->getInstallStep($installSteps, $package));
					} else {
						foreach ($installSteps as $installStep) {
							$installTasks = array_merge($installTasks, $this->getInstallStep($installStep, $package));
						}
					}
				}
			}
		}
		
		// TODO: find the install status (TODO or complete)
		
		return $installTasks;
	}

	private function getInstallStep(array $installStep, PackageInterface $package) {
		
		if (!isset($installStep['type'])) {
			throw new MoufException("Warning! In composer.json, no type found for install file/url in package '".$package->getPrettyName()."'.");
		}
		
		if ($installStep['type'] == 'file') {
			$installer = new FileInstallTask();
			$installer->setFile($installStep['file']);
		} elseif ($installStep['type'] == 'url') {
			$installer = new UrlInstallTask();
			$installer->setUrl($installStep['url']);
		} elseif ($installStep['type'] == 'class') {
			$installer = new ClassInstallTask();
			$installer->setClassName($installStep['class']);
		} else {
			throw new \Exception("Unknown type during install process.");
		}
		$installer->setPackage($package);
		if (isset($installStep['description'])) {
			$installer->setDescription($installStep['description']);
		}
		return $installer;
	}
	
	/**
	 * Returns if an array is associative or not.
	 *
	 * @param array $arr
	 * @return boolean
	 */
	private static function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}
