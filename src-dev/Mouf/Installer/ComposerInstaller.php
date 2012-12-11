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
	protected $globalInstallFile;
	protected $localInstallFile;
	
	/**
	 * 
	 * @var AbstractInstallTask[]
	 */
	protected $installTasks = null;
	
	public function __construct($selfEdit = false) {
		$this->selfEdit = $selfEdit;
		$this->composerService = new ComposerService($selfEdit);

		if ($this->selfEdit == false) {
			$this->globalInstallFile = __DIR__."/../../../../../mouf/installs_app.php";
			$this->localInstallFile = __DIR__."/../../../../../mouf/no_commit/local_installs_app.php";
		} else {
			$this->globalInstallFile = __DIR__."/../../../../../mouf/installs_moufui.php";
			$this->localInstallFile = __DIR__."/../../../../../mouf/no_commit/local_installs_moufui.php";
		}
	}
	
	
	/**
	 * Returns an ordered list of packages that have an install procedure, with a status saying if
	 * the installation has been performed or not.
	 */
	public function getInstallTasks() {
		if ($this->installTasks === null) {
			$this->load();
		}
		return $this->installTasks;
	}
	
	/**
	 * Saves any modified install task.
	 */
	public function save() {
		if ($this->installTasks === null) {
			return;
		}
		
		// Let's grab all install tasks and save them according to their scope.
		// If no scope is provided, we default to global scope.
		
		$localInstalls = array();
		$globalInstalls = array();
		
		foreach ($this->installTasks as $task) {
			switch ($task->getScope()) {
				case AbstractInstallTask::SCOPE_GLOBAL:
					$globalInstalls[] = $task->toArray();
					break;
				case AbstractInstallTask::SCOPE_LOCAL:
					$localInstalls[] = $task->toArray();
					break;
				default:
					throw new MoufException("Unknown install task scope.");
			}
		}
		
		$this->ensureWritable($this->globalInstallFile);
		$this->ensureWritable($this->localInstallFile);
		file_put_contents($this->globalInstallFile, var_export($globalInstalls, true));
		file_put_contents($this->localInstallFile, var_export($localInstalls, true));
	}
	
	/**
	 * Ensures that file $fileName is writtable.
	 * If it does not exist, this function will create the directory that contains the file if needed.
	 * If there is a problem with rights, it will throw an exception.
	 * 
	 * @param string $fileName
	 */
	private static function ensureWritable($fileName) {
		$directory = dirname($fileName);
		
		if (file_exists($fileName)) {
			if (is_writable($fileName)) {
				return;
			} else {
				throw new MoufException("File ".$fileName." is not writable.");
			}
		}
			
		if (!file_exists($directory)) {
			// The directory does not exist.
			// Let's try to create it.
			
			// Let's build the directory
			$oldumask = umask(0);
			$success = mkdir($directory, 0777, true);
			umask($oldumask);
			if (!$success) {
				throw new MoufException("Unable to create directory ".$directory);
			}
		}
		
		if (!is_writable($directory)) {
			throw new MoufException("Error, unable to create file ".$fileName.". The directory is not writtable.");
		}
	}
	
	private function load() {
		$packages = $this->composerService->getLocalPackages();
		// TODO: check the packages are in the right order?
		
		$this->installTasks = array();
		
		foreach ($packages as $package) {
			$extra = $package->getExtra();
			if (isset($extra['mouf']['install'])) {
				$installSteps = $extra['mouf']['install'];
				if (!is_array($installSteps)) {
					throw new MoufException("Error in package '".$package->getPrettyName()."' in Mouf. The install parameter in composer.json (extra->mouf->install) should be an array of files/url to install.");
				}
					
				if ($installSteps) {
					if (self::isAssoc($installSteps)) {
						// If this is directly an associative array (instead of a numerical array of associative arrays)
						$this->installTasks[] = $this->getInstallStep($installSteps, $package);
					} else {
						foreach ($installSteps as $installStep) {
							$this->installTasks[] = $this->getInstallStep($installStep, $package);
						}
					}
				}
			}
		}
		
		// TODO: find the install status (TODO or complete)
		
	}

	private function getInstallStep(array $installStep, PackageInterface $package) {
		
		if (!isset($installStep['type'])) {
			throw new MoufException("Warning! In composer.json, no type found for install file/url in package '".$package->getPrettyName()."'.");
		}
		
		if ($installStep['type'] == 'file') {
			$installer = new FileInstallTask();
			if (!isset($installStep['file'])) {
				throw new MoufException("Warning! In composer.json for package '".$package->getPrettyName()."', for install of type 'file', no file found.");
			}
			$installer->setFile($installStep['file']);
		} elseif ($installStep['type'] == 'url') {
			$installer = new UrlInstallTask();
			if (!isset($installStep['url'])) {
				throw new MoufException("Warning! In composer.json for package '".$package->getPrettyName()."', for install of type 'url', no URL found.");
			}
			$installer->setUrl($installStep['url']);
		} elseif ($installStep['type'] == 'class') {
			$installer = new ClassInstallTask();
			if (!isset($installStep['class'])) {
				throw new MoufException("Warning! In composer.json for package '".$package->getPrettyName()."', for install of type 'class', no class found.");
			}
			$installer->setClassName($installStep['class']);
		} else {
			throw new MoufException("Unknown type during install process.");
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
	
	private function getLocalFilePath() {
		
	}
}
