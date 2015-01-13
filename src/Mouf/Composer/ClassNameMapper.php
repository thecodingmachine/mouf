<?php
namespace Mouf\Composer;

/**
 * The class maps a class name to one or many possible file names according to PSR-0 or PSR-4 rules.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class ClassNameMapper
{
	/**
	 * 
	 * @var array<namespace, path[]>
	 */
	private $psr0Namespaces = array();
	
	/**
	 *
	 * @var array<namespace, path[]>
	 */
	private $psr4Namespaces = array();
	
	/**
	 * Registers a PSR-0 namespace.
	 * 
	 * @param string $namespace The namespace to register
	 * @param string|array $path The path on the filesystem (or an array of paths)
	 */
	public function registerPsr0Namespace($namespace, $path) {
		// A namespace always ends with a \
		$namespace = trim($namespace, '\\').'\\';
		
		if (!is_array($path)) {
			$path = [$path];
		}
		// Paths always end with a /
		$paths = array_map(function($path) {
			return rtrim($path, '\\/').'/';
		}, $path);
		
		if (!isset($this->psr0Namespaces[$namespace])) {
			$this->psr0Namespaces[$namespace] = $paths;
		} else {
			$this->psr0Namespaces[$namespace] = array_merge($this->psr0Namespaces[$namespace], $paths);
		}		
	}
	
	/**
	 * Registers a PSR-4 namespace.
	 *
	 * @param string $namespace The namespace to register
	 * @param string|array $path The path on the filesystem (or an array of paths)
	 */
	public function registerPsr4Namespace($namespace, $path) {
		// A namespace always ends with a \
		$namespace = trim($namespace, '\\').'\\';
	
		if (!is_array($path)) {
			$path = [$path];
		}
		// Paths always end with a /
		$paths = array_map(function($path) {
			return rtrim($path, '\\/').'/';
		}, $path);
	
			if (!isset($this->psr4Namespaces[$namespace])) {
				$this->psr4Namespaces[$namespace] = $paths;
			} else {
				$this->psr4Namespaces[$namespace] = array_merge($this->psr4Namespaces[$namespace], $paths);
			}
	}
	
	/**
	 * 
	 * @param string $composerJsonPath
	 * @return \Mouf\Composer\ClassNameMapper
	 */
	public static function createFromComposerFile($composerJsonPath, $rootPath = null, $useAutoloadDev = false) {
		$classNameMapper = new ClassNameMapper();
		
		$classNameMapper->loadComposerFile($composerJsonPath, $rootPath, $useAutoloadDev);
		
		return $classNameMapper;
	}
	
	/**
	 * 
	 * @param string $composerJsonPath Path to the composer file
	 * @param string $rootPath Root path of the project (or null)
	 */
	public function loadComposerFile($composerJsonPath, $rootPath = null, $useAutoloadDev = false) {
		$composer = json_decode(file_get_contents($composerJsonPath), true);
		
		if ($rootPath) {
			$relativePath = self::makePathRelative(dirname($composerJsonPath), $rootPath);
		} else {
			$relativePath = null;
		}
		
		if (isset($composer["autoload"]["psr-0"])) {
			$psr0 = $composer["autoload"]["psr-0"];
			foreach ($psr0 as $namespace => $paths) {
				if ($relativePath != null) {
					if (!is_array($paths)) {
						$paths = [$paths];
					}
					$paths = array_map(function($path) use ($relativePath) {
						return rtrim($relativePath,'\\/').'/'.ltrim($path, '\\/');
					}, $paths);
				}
				$this->registerPsr0Namespace($namespace, $paths);
			}
		}
		
		if (isset($composer["autoload"]["psr-4"])) {
			$psr4 = $composer["autoload"]["psr-4"];
			foreach ($psr4 as $namespace => $paths) {
				if ($relativePath != null) {
					if (!is_array($paths)) {
						$paths = [$paths];
					}
					$paths = array_map(function($path) use ($relativePath) {
						return rtrim($relativePath,'\\/').'/'.ltrim($path, '\\/');
					}, $paths);
				}
				$this->registerPsr4Namespace($namespace, $paths);
			}
		}
		
		if ($useAutoloadDev) {
			if (isset($composer["autoload-dev"]["psr-0"])) {
				$psr0 = $composer["autoload-dev"]["psr-0"];
				foreach ($psr0 as $namespace => $paths) {
					if ($relativePath != null) {
						if (!is_array($paths)) {
							$paths = [$paths];
						}
						$paths = array_map(function($path) use ($relativePath) {
							return rtrim($relativePath,'\\/').'/'.ltrim($path, '\\/');
						}, $paths);
					}
					$this->registerPsr0Namespace($namespace, $paths);
				}
			}
			
			if (isset($composer["autoload-dev"]["psr-4"])) {
				$psr4 = $composer["autoload-dev"]["psr-4"];
				foreach ($psr4 as $namespace => $paths) {
					if ($relativePath != null) {
						if (!is_array($paths)) {
							$paths = [$paths];
						}
						$paths = array_map(function($path) use ($relativePath) {
							return rtrim($relativePath,'\\/').'/'.ltrim($path, '\\/');
						}, $paths);
					}
					$this->registerPsr4Namespace($namespace, $paths);
				}
			}
		}
		
		return $this;
	}
	
	/**
	 * Given an existing path, convert it to a path relative to a given starting path.
	 * Shamelessly borrowed to Symfony :). Thanks guys.
	 * Note: we do not include Symfony's "FileSystem" component to avoid adding too many dependencies. 
	 * 
	 * @param string $endPath Absolute path of target
	 * @param string $startPath Absolute path where traversal begins
	 *
	 * @return string Path of target relative to starting path
	 */
	private static function makePathRelative($endPath, $startPath)
	{
		// Normalize separators on Windows
		if ('\\' === DIRECTORY_SEPARATOR) {
			$endPath = strtr($endPath, '\\', '/');
			$startPath = strtr($startPath, '\\', '/');
		}
		// Split the paths into arrays
		$startPathArr = explode('/', trim($startPath, '/'));
		$endPathArr = explode('/', trim($endPath, '/'));
		// Find for which directory the common path stops
		$index = 0;
		while (isset($startPathArr[$index]) && isset($endPathArr[$index]) && $startPathArr[$index] === $endPathArr[$index]) {
			$index++;
		}
		// Determine how deep the start path is relative to the common path (ie, "web/bundles" = 2 levels)
		$depth = count($startPathArr) - $index;
		// Repeated "../" for each level need to reach the common path
		$traverser = str_repeat('../', $depth);
		$endPathRemainder = implode('/', array_slice($endPathArr, $index));
		// Construct $endPath from traversing to the common path, then to the remaining $endPath
		$relativePath = $traverser.(strlen($endPathRemainder) > 0 ? $endPathRemainder.'/' : '');
		return (strlen($relativePath) === 0) ? './' : $relativePath;
	}
	
	/**
	 * Returns a list of all namespaces that are managed by the ClassNameMapper.
	 * 
	 * @return string[]
	 */
	public function getManagedNamespaces() {
		return array_keys(array_merge($this->psr0Namespaces, $this->psr4Namespaces));
	}
	
	/**
	 * Returns a list of paths that can be used to store $className.
	 * 
	 * @param string $className
	 * @return string[]
	 */
	public function getPossibleFileNames($className) {
		$possibleFileNames = array();
		$className = ltrim($className, '\\');

		$psr0unfactorizedAutoload = $this->unfactorizeAutoload($this->psr0Namespaces);
		
		foreach ($psr0unfactorizedAutoload as $result) {
			$namespace = $result['namespace'];
			$directory = $result['directory'];
			
			if (strpos($className, $namespace) === 0) {
				$tmpClassName = $className;
				$fileName  = '';
				$fileNamespace = '';
				if ($lastNsPos = strripos($tmpClassName, '\\')) {
					$namespace = substr($tmpClassName, 0, $lastNsPos);
					$tmpClassName = substr($tmpClassName, $lastNsPos + 1);
					$fileName  = str_replace('\\', '/', $namespace) . '/';
				}
				$fileName .= str_replace('_', '/', $tmpClassName) . '.php';
				
				$possibleFileNames[] = $directory.$fileName;
			}
		}
		
		$psr4unfactorizedAutoload = $this->unfactorizeAutoload($this->psr4Namespaces);
		
		foreach ($psr4unfactorizedAutoload as $result) {
			$namespace = $result['namespace'];
			$directory = $result['directory'];
				
			if (strpos($className, $namespace) === 0) {
				$shortenedClassName = substr($className, strlen($namespace));
				
				$fileName  = '';
				$fileNamespace = '';
				if ($lastNsPos = strripos($shortenedClassName, '\\')) {
					$namespace = substr($shortenedClassName, 0, $lastNsPos);
					$shortenedClassName = substr($shortenedClassName, $lastNsPos + 1);
					$fileName  = str_replace('\\', '/', $namespace) . '/' . $shortenedClassName;
				} else {
					$fileName = $shortenedClassName;
				}
				$fileName .= '.php';
		
				$possibleFileNames[] = $directory.$fileName;
			}
		}

		return $possibleFileNames;
	}
	
	/**
	 * Takes in parameter an array like
	 * [{ "Mouf": "src/" }] or [{ "Mouf": ["src/", "src2/"] }] .
	 * returns
	 * [
	 * 	{"namespace"=> "Mouf", "directory"=>"src/"},
	 * 	{"namespace"=> "Mouf", "directory"=>"src2/"}
	 * ]
	 *
	 * @param array $autoload
	 * @return array<int, array<string, string>>
	 */
	private static function unfactorizeAutoload(array $autoload) {
		$result  = array();
		foreach ($autoload as $namespace => $directories) {
			if (!is_array($directories)) {
				$result[] = array(
						"namespace" => $namespace,
						"directory" => trim($directories, '/\\').'/'
				);
			} else {
				foreach ($directories as $dir) {
					$result[] = array(
							"namespace" => $namespace,
							"directory" => trim($dir, '/').'/'
					);
				}
			}
		}
		return $result;
	}
}
