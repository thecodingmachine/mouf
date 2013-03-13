<?php 
namespace Mouf;

use Mouf\Utils\Log\LogInterface;

/**
 * A cache service dedicated to Mouf, accessible from admin and app mode.
 * This should be used by Mouf framework only.
 * For your applications, you should use a third party cache system instead (like https://packagist.org/packages/mouf/utils.cache.apc-cache)
 * 
 * This uses APC by default and falls back to file.
 * 
 * @author David Négrier
 */
class MoufCache {

	/**
	 * The default time to live of elements stored in the session (in seconds).
	 * Please note that if the session is flushed, all the elements of the cache will disapear anyway.
	 * If empty, the time to live will be the time of the session.
	 *
	 * @Property
	 * @var int
	 */
	public $defaultTimeToLive;
	
	/**
	 * The logger used to trace the cache activity.
	 *
	 * @Property
	 * @var LogInterface
	 */
	public $log;
	
	/**
	 * The directory the files are stored in.
	 * If none is specified, they are stored in the "filecache" directory.
	 * The directory must end with a trailing "/".
	 *
	 * @Property
	 * @var string
	 */
	public $cacheDirectory = "mouf_cache/";
	
	/**
	 * Whether the directory is relative to the system temp directory or not.
	 *
	 * @Property
	 * @var boolean
	 */
	public $relativeToSystemTempDirectory = true;
	
	private $useFallback = false;
	
	/**
	 * 
	 * @param LogInterface $log
	 */
	public function __construct($log = null) {
		$this->log = $log;
		
		if (!extension_loaded("apc")) {
			if ($this->log) {
				$this->log->info("APC extension not available. Using fallback.");
			}
			$this->useFallback = true;
		}
	}
	
	/**
	 * Returns the cached value for the key passed in parameter.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
		
		if ($this->useFallback) {
			$filename = $this->getFileName($key);
			
			if (is_readable($filename)) {
				$fp = fopen($filename, "r");
				$timeout = fgets($fp);
					
				if ($timeout > time() || $timeout==0) {
					$contents = "";
					while (!feof($fp)) {
						$contents .= fread($fp, 65536);
					}
					fclose($fp);
					$value = unserialize($contents);
					//$this->log->trace("Retrieving key '$key' from file cache: value returned:".var_export($value, true));
					if ($this->log) {
						$this->log->trace("Retrieving key '$key' from file cache.");
					}
					return $value;
				} else {
					fclose($fp);
					unlink($filename);
					if ($this->log) {
						$this->log->trace("Retrieving key '$key' from file cache: key outdated, cache miss.");
					}
					return null;
				}
			} else {
				if ($this->log) {
					$this->log->trace("Retrieving key '$key' from file cache: cache miss.");
				}
				return null;
			}
		}
		
		// If not fallback
	
		$success = false;
		$value = apc_fetch($key, $success);
	
		if ($success) {
			if ($this->log) {
				$this->log->trace("Retrieving key '$key' from file cache.");
			}
			return $value;
		} else {
			if ($this->log) {
				$this->log->trace("Retrieving key '$key' from file cache: cache miss");
			}
			return null;
		}
	}
	
	/**
	 * Sets the value in the cache.
	 *
	 * @param string $key The key of the value to store
	 * @param mixed $value The value to store
	 * @param float $timeToLive The time to live of the cache, in seconds.
	 */
	public function set($key, $value, $timeToLive = null) {
		
		if ($this->useFallback) {
			$filename = $this->getFileName($key);
			//$this->log->trace("Storing value in cache: key '$key', value '".var_export($value, true)."'");
			if ($this->log) {
				$this->log->trace("Storing value in cache: key '$key'");
			}
			
			if (!is_writable($filename)) {
				if (!file_exists($this->getDirectory())) {
					mkdir($this->getDirectory(), 0777, true);
				}
			}
			
			if ($timeToLive == null) {
				if (empty($this->defaultTimeToLive)) {
					$timeOut = 0;
				} else {
					$timeOut = time() + $this->defaultTimeToLive;
				}
			} else {
				$timeOut = time() + $timeToLive;
			}
			
			$fp = fopen($filename, "w");
			fwrite($fp, $timeOut."\n");
			fwrite($fp, serialize($value));
			fclose($fp);
			return;
		}
	
		if ($this->log) {
			$this->log->trace("Storing value in APC cache: key '$key'.");
		}
	
		if ($timeToLive == null) {
			if (empty($this->defaultTimeToLive)) {
				$timeOut = 0;
			} else {
				$timeOut = $this->defaultTimeToLive;
			}
		} else {
			$timeOut = time() + $timeToLive;
		}
	
		$ret = apc_store($key, $value, $timeOut);
		if ($ret == false) {
			if ($this->log) {
				$this->log->error("Error while caching the key '$key' with value '".var_export($value, true)."' in APC cache.");
			}
		}
	}
	
	/**
	 * Removes the object whose key is $key from the cache.
	 *
	 * @param string $key The key of the object
	 */
	public function purge($key) {
		
		if ($this->useFallback) {
			if ($this->log) {
				$this->log->trace("Purging key '$key' from file cache.");
			}
			$filename = $this->getFileName($key);
			unlink($filename);
			return;
		}
	
		if ($this->log) {
			$this->log->trace("Purging key '$key' from APC cache.");
		}
	
		apc_delete($key);
	}
	
	/**
	 * Removes all the objects from the cache.
	 *
	 */
	public function purgeAll() {
		
		if ($this->useFallback) {
			if ($this->log) {
				$this->log->trace("Purging the whole file cache.");
			}
			$files = glob($this->getDirectory()."*");
			foreach ($files as $filename) {
				unlink($filename);
			}
			return;
		}
		
		apc_clear_cache('user');
	}
	
	private function getDirectory() {
	
		$dir = "";
		if ($this->relativeToSystemTempDirectory) {
			$dir .= sys_get_temp_dir()."/";
		}
		if (!empty($this->cacheDirectory)) {
			$dir .= $this->cacheDirectory;
		} else {
			$dir .= "filecache/";
		}
		return $dir;
	}
	
	private function getFileName($key) {
		// Remove any "/" and ":" from the name, and replace those with "_" ...
		$key = str_replace(array("/", "\\", ":", "_"), array("_s_", "_b_", "_d_", "___"), $key);
	
		return $this->getDirectory().$key.".cache";
	}
}

?>