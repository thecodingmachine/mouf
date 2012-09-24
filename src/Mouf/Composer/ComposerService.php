<?php 
namespace Mouf\Composer;

use Symfony\Component\Console\Application as BaseApplication;
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
use Composer\Autoload\ClassMapGenerator;

/**
 * A service to access composer functions.
 * 
 * @author David Négrier
 * @Component
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
	
	public function __construct($selfEdit = false) {
		$this->selfEdit = $selfEdit;
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
		
		$autoloadGenerator = new \Composer\Autoload\AutoloadGenerator();
		
		if ($this->selfEdit) {
			chdir(__DIR__."/../../..");
			\putenv('COMPOSER=composer-mouf.json');
		} else {
			chdir(__DIR__."/../../../../..");
		}
		
		$composer = $this->getComposer(); 
		
		$installationManager = $composer->getInstallationManager();
		$localRepos = new CompositeRepository($composer->getRepositoryManager()->getLocalRepositories());
		$package = $composer->getPackage();
		$config = $composer->getConfig();
		
		
		$packageMap = $autoloadGenerator->buildPackageMap($installationManager, $package, $localRepos->getPackages());
		
		//var_dump($packageMap);
		$autoloads = $autoloadGenerator->parseAutoloads($packageMap);
		
		
		
		
		
		$targetDir = "composer";
		
		$filesystem = new Filesystem();
		$filesystem->ensureDirectoryExists($config->get('vendor-dir'));
		$vendorPath = strtr(realpath($config->get('vendor-dir')), '\\', '/');
		$targetDir = $vendorPath.'/'.$targetDir;
		$filesystem->ensureDirectoryExists($targetDir);
		$relVendorPath = $filesystem->findShortestPath(getcwd(), $vendorPath, true);
		//$vendorPathCode = $filesystem->findShortestPathCode(realpath($targetDir), $vendorPath, true);
		//$vendorPathToTargetDirCode = $filesystem->findShortestPathCode($vendorPath, realpath($targetDir), true);
		
		
		
		
		
		
		// flatten array
		$classMap = array();
		$autoloads['classmap'] = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($autoloads['classmap']));
		
		foreach ($autoloads['psr-0'] as $namespace => $paths) {
			foreach ($paths as $dir) {
				$dir = $this->getPath($filesystem, $relVendorPath, $vendorPath, $dir);
				$whitelist = sprintf(
						'{%s/%s.+(?<!(?<!/)Test\.php)$}',
						preg_quote(rtrim($dir, '/')),
						strpos($namespace, '_') === false ? preg_quote(strtr($namespace, '\\', '/')) : ''
				);
				foreach (ClassMapGenerator::createMap($dir, $whitelist) as $class => $path) {
					if ('' === $namespace || 0 === strpos($class, $namespace)) {
						$path = '/'.$filesystem->findShortestPath(getcwd(), $path, true);
						if (!isset($classMap[$class])) {
							$classMap[$class] = /*'$baseDir . '.var_export(*/$path/*, true).",\n"*/;
						}
					}
				}
			}
		}
		foreach ($autoloads['classmap'] as $dir) {
			foreach (ClassMapGenerator::createMap($dir) as $class => $path) {
				$path = '/'.$filesystem->findShortestPath(getcwd(), $path, true);
				$classMap[$class] = /*'$baseDir . '.var_export(*/$path/*, true).",\n"*/;
			}
		}
		
		
		
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
	
	protected function getComposer() {
		if (null === $this->composer) {
			$this->io = new MoufComposerIO();
			$this->composer = Factory::create($this->io);
		}
		return $this->composer;
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
	protected function getPath(Filesystem $filesystem, $relVendorPath, $vendorPath, $path)
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
	}
	
}

?>