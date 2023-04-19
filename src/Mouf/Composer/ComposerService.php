<?php
namespace Mouf\Composer;

use Composer\EventDispatcher\EventDispatcher;

use Composer\IO\BufferIO;
use Mouf\Installer\MoufUIFileWritter;

use Mouf\Installer\PackagesOrderer;

use Composer\Repository\PlatformRepository;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\Repository\CompositeRepository;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Package\CompletePackageInterface;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;

/**
 * A service to access composer functions.
 *
 * @author David Négrier
 */
class ComposerService {

	/**
	 * @var Composer
	 */
	protected $composer;

	/**
	 * @var IOInterface
	 */
	protected $io;

	protected $selfEdit;

	protected $classMap = array();

	protected $outputBufferedJs;

	public function __construct($selfEdit = false, $outputBufferedJs = false) {
		$this->selfEdit = $selfEdit;
		$this->outputBufferedJs = $outputBufferedJs;
		self::registerAutoloader();
	}

	/**
	 * Returns the classmap array.
	 * This map associates the name of the classes and the PHP file they are declared in.
	 *
     * @param string $mode Mode can have only those 3 values:
	 * 						"ROOT_PACKAGE": only classes from the root package are returned
	 *   					"VENDOR": only classes from (dev-)dependencies packages are returned
	 * 						"NO_FILTER": all classes are returned
     * @return array<string, string>
	 */
	public function getClassMap($mode = 'NO_FILTER') {
		if (isset($this->classMap[$mode])) {
			return $this->classMap[$mode];
		}

		if (!in_array($mode, array('NO_FILTER', 'ROOT_PACKAGE', 'VENDOR'), true)) {
			throw new \Exception('Unexpected mode passed to getClassMap');
		}

		$composer = $this->getComposer();

		$dispatcher = new EventDispatcher($composer, $this->io);
		$autoloadGenerator = new \Composer\Autoload\AutoloadGenerator($dispatcher);

		$installationManager = $composer->getInstallationManager();
		$localRepos = new CompositeRepository(array($composer->getRepositoryManager()->getLocalRepository()));
		$package = $composer->getPackage();
		$config = $composer->getConfig();

		$packageMap = $autoloadGenerator->buildPackageMap($installationManager, $package, $localRepos->getPackages());

		//var_dump($packageMap);
		$autoloads = $autoloadGenerator->parseAutoloads($packageMap, $package);

		$targetDir = "composer";

		$filesystem = new Filesystem();
		$filesystem->ensureDirectoryExists($config->get('vendor-dir'));
		$vendorPath = strtr(realpath($config->get('vendor-dir')), '\\', '/');
		$targetDir = $vendorPath.'/'.$targetDir;
		$filesystem->ensureDirectoryExists($targetDir);
		$basePath = $filesystem->normalizePath(realpath(getcwd()));

		// flatten array
		$classMap = array();

		// Scan the PSR-0/4 directories for class files, and add them to the class map
		foreach (array('psr-0', 'psr-4') as $psrType) {
			foreach ($autoloads[$psrType] as $namespace => $paths) {
				foreach ($paths as $dir) {
					$isAbsolute = $filesystem->isAbsolutePath($dir);
					// vendor packages are absolute while local packages are not!
                    if ($mode === 'ROOT_PACKAGE' && $isAbsolute) {
						continue;
					}
                    if ($mode === 'VENDOR' && !$isAbsolute) {
						continue;
					}

                    $dir = $filesystem->normalizePath($isAbsolute ? $dir : $basePath.'/'.$dir);
					if (!is_dir($dir)) {
						continue;
					}
					$whitelist = sprintf(
							'{%s/%s.+(?<!(?<!/)Test\.php)$}',
							preg_quote($dir),
							($psrType === 'psr-0' && strpos($namespace, '_') === false) ? preg_quote(strtr($namespace, '\\', '/')) : ''
					);
					foreach (ClassMapGenerator::createMap($dir, $whitelist) as $class => $path) {
						if ('' === $namespace || 0 === strpos($class, $namespace)) {

							if (strrpos($class, '\\') == strlen($class)-1) {
								// For some reason, Composer can rarely put namespaces instead of classes here.
								// Let's ignore that.
								continue;
							}

							if (!isset($classMap[$class])) {
								$classMap[$class] = $path;
							}
						}
					}
				}
			}
		}

		$autoloads['classmap'] = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($autoloads['classmap']));
		foreach ($autoloads['classmap'] as $dir) {
            $isAbsolute = $filesystem->isAbsolutePath($dir);
            // vendor packages are absolute while local packages are not!
            if ($mode === 'ROOT_PACKAGE' && $isAbsolute) {
                continue;
            }
            if ($mode === 'VENDOR' && !$isAbsolute) {
                continue;
            }
			$dir = $filesystem->normalizePath($isAbsolute ? $dir : $basePath.'/'.$dir);
			foreach (ClassMapGenerator::createMap($dir) as $class => $path) {
// 				$path = '/'.$filesystem->findShortestPath(getcwd(), $path, true);
				//$classMap[$class] = '$baseDir . '.var_export($path, true).",\n";
				$classMap[$class] = $path;
			}
		}

		// FIXME: $autoloads['files'] seems ignored

		//var_export($classMap);
		$this->classMap[$mode] = $classMap;
		return $classMap;


	}

	private static $loader;

	/**
	 * Register the autoloader for composer.
	 */
	public static function registerAutoloader()
	{
		if (null !== static::$loader) {
			return static::$loader;
		}

		static::$loader = $loader = new \Composer\Autoload\ClassLoader();
		$vendorDir = dirname(__DIR__);
		$baseDir = dirname($vendorDir);

		$map = require 'phar://'.__DIR__.'/../../../composer.phar/vendor/composer/autoload_namespaces.php';
		foreach ($map as $namespace => $path) {
			$loader->set($namespace, $path);
		}

		$map = require 'phar://'.__DIR__.'/../../../composer.phar/vendor/composer/autoload_psr4.php';
		foreach ($map as $namespace => $path) {
			$loader->setPsr4($namespace, $path);
		}

		$classMap = require 'phar://'.__DIR__.'/../../../composer.phar/vendor/composer/autoload_classmap.php';
		if ($classMap) {
			$loader->addClassMap($classMap);
		}

		$loader->register();

		return $loader;
	}

	/**
	 * Exposes the Composer object
	 *
	 * @return Composer
	 */
	public function getComposer() {
		if (null === $this->composer) {

			$this->configureEnv();

			if ($this->outputBufferedJs) {
				$this->io = new MoufJsComposerIO();
			} else {
				$this->io = new BufferIO();
			}
			$this->composer = Factory::create($this->io, null, true);
		}
		return $this->composer;
	}

	/**
	 * Changes the current working directory and set environment variables to be able to work with Composer.
	 */
	private function configureEnv() {
		if ($this->selfEdit) {
			chdir(__DIR__."/../../..");
			\putenv('COMPOSER=composer-mouf.json');
		} else {
			chdir(__DIR__."/../../../../../..");
		}

		$composerHome = getenv('COMPOSER_HOME');
		if (!$composerHome) {
			$composerTmpDir = sys_get_temp_dir().'/.mouf_composer/';
			if (function_exists('posix_getpwuid')) {
				$processUser = posix_getpwuid(posix_geteuid());
				$composerTmpDir .= $processUser['name'].'/';
			}
			\putenv('COMPOSER_HOME='.$composerTmpDir);
		}

	}

	/**
	 * Returns an array of Composer packages currently installed.
	 *
	 * @return PackageInterface[]
	 */
	public function getLocalPackages() {
		$composer = $this->getComposer();
		$dispatcher = new EventDispatcher($composer, $this->io);

		if ($this->selfEdit) {
			chdir(__DIR__."/../../..");
			\putenv('COMPOSER=composer-mouf.json');
		} else {
			chdir(__DIR__."/../../../../../..");
		}
		//TODO: this call is strange because it will return the same instance as above.
		$composer = $this->getComposer();

		$localRepos = new CompositeRepository(array($composer->getRepositoryManager()->getLocalRepository()));
		$package = $composer->getPackage();
		$packagesList = $localRepos->getPackages();
		$packagesList[] = $package;

		return $packagesList;
	}

	/**
	 * Returns the list of local packages, ordered by dependency.
	 * @return PackageInterface[]
	 */
	public function getLocalPackagesOrderedByDependencies() {
		$unorderedPackagesList = $this->getLocalPackages();

		return PackagesOrderer::reorderPackages($unorderedPackagesList);
	}

	protected $onlyName;
	protected $tokens;
	protected $lowMatches;
	protected $onPackageFoundCallback;

	/**
	 * Returns a list of packages matching the search query.
	 *
	 * @param string $text
	 * @param OnPackageFoundInterface $callback
	 * @param bool $onlyName
	 */
	public function searchPackages($text, OnPackageFoundInterface $callback, $onlyName = false, $includeLocal = true) {
		$this->onPackageFoundCallback = $callback;
		$composer = $this->getComposer();
		$platformRepo = new PlatformRepository;

		$searched = array();

		if ($includeLocal) {
			$localRepo = $composer->getRepositoryManager()->getLocalRepository();
			$searched[] = $localRepo;
		}
		$searched[] = $platformRepo;
		$installedRepo = new CompositeRepository($searched);
		$repos = new CompositeRepository(array_merge(array($installedRepo), $composer->getRepositoryManager()->getRepositories()));

		$this->onlyName = $onlyName;
		$this->tokens = explode(" ", $text);

		$repos->filterPackages(array($this, 'processPackage'), 'Composer\Package\CompletePackage');

	}

	public function processPackage($package)
	{
		if ($package instanceof AliasPackage || isset($this->matches[$package->getName()])) {
			return;
		}

		foreach ($this->tokens as $token) {
			if (!$score = $this->matchPackage($package, $token)) {
				continue;
			}

			$this->onPackageFoundCallback->onPackageFound($package, $score);

			return;
		}
	}

	/**
	 * tries to find a token within the name/keywords/description
	 *
	 * @param  CompletePackageInterface $package
	 * @param  string           $token
	 * @return boolean
	 */
	private function matchPackage(CompletePackageInterface $package, $token)
	{
		$score = 0;

		if (false !== stripos($package->getName(), $token)) {
			$score += 5;
		}

		if (!$this->onlyName && false !== stripos(join(',', $package->getKeywords() ?: array()), $token)) {
			$score += 3;
		}

		if (!$this->onlyName && false !== stripos($package->getDescription(), $token)) {
			$score += 1;
		}

		return $score;
	}

	public function install($name, $version, $requireDev = false, $preferSource = false, $dev = false) {
		// From the RequireCommand code:
		$this->configureEnv();

		$factory = new Factory;
		$file = $factory->getComposerFile();

		$json = new JsonFile($file);
		$composerJson = $json->read();

		//$requirements = $this->determineRequirements($input, $output, $input->getArgument('packages'));

		$requireKey = $dev ? 'require-dev' : 'require';
		$baseRequirements = array_key_exists($requireKey, $composerJson) ? $composerJson[$requireKey] : array();
		//$requirements = $this->formatRequirements($requirements);

		$requirements[$name] = $version;

		if (!$this->updateFileCleanly($json, $baseRequirements, $requirements, $requireKey)) {
			foreach ($requirements as $package => $version) {
				$baseRequirements[$package] = $version;
			}

			$composerJson[$requireKey] = $baseRequirements;
			$json->write($composerJson);
		}

		$composer = $this->getComposer();

		// Update packages
		$io = $this->io;

		// We cannot update packages if we are in "source" mode (because the git/svn checkout
		// can ask for questions about SSH authentication, ... and because we don't know how to
		// answer questions...)
		// TODO: find a way to know what composer will try to install and put a message if there
		// is a "dev" version in the lot.
		$io->write("Your composer.json file has been written. Go in the command line and run 'php composer.phar update' to install this new package.");

		/*$install = Installer::create($io, $composer);

		$install
		->setVerbose(true)
		->setPreferSource($preferSource)
		->setDevMode($dev)
		->setUpdate(true)
		->setUpdateWhitelist($requirements);
		;

		return $install->run() ? 0 : 1;*/

	}

	public function uninstall($name) {
		$this->configureEnv();

		$factory = new Factory;
		$file = $factory->getComposerFile();

		$json = new JsonFile($file);
		$composerJson = $json->read();

		if (isset($composerJson['require'])) {
			unset($composerJson['require'][$name]);
		}
		if (isset($composerJson['require-dev'])) {
			unset($composerJson['require'][$name]);
		}

		$json->write($composerJson);

		// Init IO
		$this->getComposer();
		$io = $this->io;
		$io->write("Your composer.json file has been written. Go in the command line and run '<strong>php composer.phar update</strong>' to complete the removal of your package.");
	}

	/**
	 * Copied from RequireCommand class...
	 *
	 * @param unknown_type $json
	 * @param array $base
	 * @param array $new
	 * @param unknown_type $requireKey
	 */
	private function updateFileCleanly($json, array $base, array $new, $requireKey)
	{
		$contents = file_get_contents($json->getPath());

		$manipulator = new JsonManipulator($contents);

		foreach ($new as $package => $constraint) {
			if (!$manipulator->addLink($requireKey, $package, $constraint)) {
				return false;
			}
		}

		file_put_contents($json->getPath(), $manipulator->getContents());

		return true;
	}

	/**
	 * Rewrites MoufUI.php (the actual rewrite is delegated to MoufUIFileWritter.
	 */
	public function rewriteMoufUi() {
		$composer = $this->getComposer();
		$moufUiFileWriter = new MoufUIFileWritter($composer);
		$moufUiFileWriter->writeMoufUI();
	}

	/**
	 * Returns the Composer config object
	 * @param string $param
	 * @return string
	 */
	public function getComposerConfig() {
		return $this->getComposer()->getConfig();
	}
}

?>
