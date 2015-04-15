<?php 
namespace Mouf\Composer;

use Composer\EventDispatcher\EventDispatcher;

use Composer\IO\BufferIO;
use Mouf\Installer\MoufUIFileWritter;

use Mouf\Installer\PackagesOrderer;

use Composer\Package\Link;

use Symfony\Component\Console\Application as BaseApplication;
use Composer\Repository\PlatformRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Composer\Command;
use Composer\Command\Helper\DialogHelper;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\IOInterface;
use Composer\IO\ConsoleIO;
use Composer\Util\ErrorHandler;
use Composer\Repository\CompositeRepository;
use Composer\Config;
use Composer\Installer\InstallationManager;
use Composer\Package\AliasPackage;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryInterface;
use Composer\Util\Filesystem;
use Composer\Package\CompletePackageInterface;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Installer;

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
	
	protected $classMap;
	
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
	 * @return array<string, string>
	 */
	public function getClassMap() {
		if ($this->classMap !== null) {
			return $this->classMap;
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
		$relVendorPath = $filesystem->findShortestPath(getcwd(), $vendorPath, true);
		//$vendorPathCode = $filesystem->findShortestPathCode(realpath($targetDir), $vendorPath, true);
		//$vendorPathToTargetDirCode = $filesystem->findShortestPathCode($vendorPath, realpath($targetDir), true);
		
				
		// flatten array
		$classMap = array();
		
		
		
		// Scan the PSR-0/4 directories for class files, and add them to the class map
		foreach (array('psr-0', 'psr-4') as $psrType) {
			foreach ($autoloads[$psrType] as $namespace => $paths) {
				foreach ($paths as $dir) {
					$dir = $filesystem->normalizePath($filesystem->isAbsolutePath($dir) ? $dir : $basePath.'/'.$dir);
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
							if (!isset($classMap[$class])) {
								//$path = $this->getPathCode($filesystem, $basePath, $vendorPath, $path);
								//$classMap[$class] = $path.",\n";
// 								$path = '/'.$filesystem->findShortestPath(getcwd(), $path, true);
								$classMap[$class] = $path;
							}
						}
					}
				}
			}
		}
		
		
			
		$autoloads['classmap'] = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($autoloads['classmap']));
		foreach ($autoloads['classmap'] as $dir) {
			$dir = $filesystem->normalizePath($filesystem->isAbsolutePath($dir) ? $dir : $basePath.'/'.$dir);
			foreach (ClassMapGenerator::createMap($dir) as $class => $path) {
// 				$path = '/'.$filesystem->findShortestPath(getcwd(), $path, true);
				//$classMap[$class] = '$baseDir . '.var_export($path, true).",\n";
				$classMap[$class] = $path;
			}
		}
		
		// FIXME: $autoloads['files'] seems ignored
		
		//var_dump($classMap);
		$this->classMap = $classMap;
		return $classMap;
		
		
	}
	
	/**
	 * Forces autoloading all classes for current context.
	 */
	/*public function forceAutoLoad() {
		$classMap = $this->getClassMap();
		foreach ($classMap as $className=>$file) {
			if (!class_exists($className, true)) {
				throw new \Exception("Unable to find class ".$className);
			}
		}
	}*/
	
	
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
			$loader->add($namespace, $path);
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
			$this->composer = Factory::create($this->io);
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
			\putenv('COMPOSER_HOME='.ROOT_PATH.".composer");
		}
		
	}
	
	/**
	 * Same code as in AutoloadGenerator, but it was protected there, so we had to overload it.
	 * 
	 * @param Filesystem $filesystem
	 * @param unknown_type $relVendorPath
	 * @param unknown_type $vendorPath
	 * @param unknown_type $path
	 * @return string
	 */
	/*protected function getPath(Filesystem $filesystem, $relVendorPath, $vendorPath, $path)
	{
		$path = strtr($path, '\\', '/');
		if (!$filesystem->isAbsolutePath($path)) {
			if (strpos($path, $relVendorPath) === 0) {
				// path starts with vendor dir
				return $vendorPath . substr($path, strlen($relVendorPath));
			}
	
			return strtr(getcwd(), '\\', '/').'/'.$path;
		}
	
		return $path;
	}*/
	
	/**
	 * Returns an array of Composer packages currently installed.
	 * 
	 * @return PackageInterface[]
	 */
	public function getLocalPackages() {
		$composer = $this->getComposer();
		$dispatcher = new EventDispatcher($composer, $this->io);
		$autoloadGenerator = new \Composer\Autoload\AutoloadGenerator($dispatcher);
		
		if ($this->selfEdit) {
			chdir(__DIR__."/../../..");
			\putenv('COMPOSER=composer-mouf.json');
		} else {
			chdir(__DIR__."/../../../../../..");
		}
		//TODO: this call is strange because it will return the same isntance as above.
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
		
		//$this->onlyName = $input->getOption('only-name');
		$this->onlyName = $onlyName;
		//$this->tokens = $input->getArgument('tokens');
		$this->tokens = explode(" ", $text);
		
		//$this->output = $output;
		$repos->filterPackages(array($this, 'processPackage'), 'Composer\Package\CompletePackage');
		
		/*foreach ($this->lowMatches as $details) {
			$output->writeln($details['name'] . '<comment>:</comment> '. $details['description']);
		}*/
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
	
			/*if (false !== ($pos = stripos($package->getName(), $token))) {
				$name = substr($package->getPrettyName(), 0, $pos)
				. '<strong>' . substr($package->getPrettyName(), $pos, strlen($token)) . '</strong>'
				. substr($package->getPrettyName(), $pos + strlen($token));
			} else {
				$name = $package->getPrettyName();
			}
	
			$description = strtok($package->getDescription(), "\r\n");
			if (false !== ($pos = stripos($description, $token))) {
				$description = substr($description, 0, $pos)
				. '<strong>' . substr($description, $pos, strlen($token)) . '</strong>'
				. substr($description, $pos + strlen($token));
			}*/
	
			/*if ($score >= 3) {
				$this->output->writeln($name . '<comment>:</comment> '. $description);
				$this->matches[$package->getName()] = true;
			} else {
				$this->lowMatches[$package->getName()] = array(
						'name' => $name,
						'description' => $description,
				);
			}*/
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
		
		/*if (!file_exists($file)) {
			$output->writeln('<error>'.$file.' not found.</error>');
		
			return 1;
		}
		if (!is_readable($file)) {
			$output->writeln('<error>'.$file.' is not readable.</error>');
		
			return 1;
		}*/
		
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