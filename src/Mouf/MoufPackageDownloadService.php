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
 * This service is in charge of getting the list of packages from a repository and uploading/downloading content to/from the repository.
 * 
 * @Component
 */
class MoufPackageDownloadService {
	
	/**
	 * The Cache service to use to put the repositories in cache.
	 * 
	 * @Property
	 * @Compulsory
	 * @var CacheInterface
	 */
	public $cacheService;
	
	private $moufManager;
	private $moufPackageManager;
	
	/**
	 * Sets the MoufManager for this instance.
	 * 
	 * @param MoufManager $moufManager
	 */
	public function setMoufManager(MoufManager $moufManager) {
		$this->moufManager = $moufManager;
	}
	
	/**
	 * Retrieves the package list from the repository, and puts it in cache, so we can get it again quicker.
	 * 
	 * @param MoufRepository $repository
	 * @param bool $bypassCache Whether we should bypass the cache and download the repository anyway... or not.
	 * @return MoufGroupDescriptor
	 */
	public function getPackageListFromRepository(MoufRepository $repository, $bypassCache=false) {
		if (!$bypassCache) {
			$groupDescriptor = $this->cacheService->get("mouf_repositories.".$repository->getUrl());
		} else {
			$groupDescriptor = null;
		}
		
		if ($groupDescriptor == null) {
			$groupDescriptor = $this->downloadPackageListFromRepository($repository);
			$this->cacheService->set("mouf_repositories.".$repository->getUrl(), $groupDescriptor);
		}
		return $groupDescriptor;
	}
	
	/**
	 * Retrieves the package list from the repository.
	 * 
	 * @param MoufRepository $repository
	 * @return MoufGroupDescriptor
	 */
	private function downloadPackageListFromRepository(MoufRepository $repository) {
		$url = $repository->getUrl();
		
		// preparation de l'envoi
		$ch = curl_init();
		if (strrpos($url, "/") !== strlen($url)-1) {
			$url .= "/";
		}
		$url .= "packagesService";
		curl_setopt( $ch, CURLOPT_URL, $url);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_POST, FALSE );
		
		$response = curl_exec( $ch );
		
		if( curl_error($ch) ) { 
			throw new MoufNetworkException("An error occured: ".curl_error($ch));
		}
		curl_close( $ch );
		
		// Parse the JSON
		$phpArray = json_decode($response, true);
		
		if ($phpArray == null) {
			throw new MoufException("Error while decoding response from server. Message received: ".$response);
		}
		
		return MoufGroupDescriptor::fromJsonArray($phpArray, $repository);
		
	}

	private $repositories;
	
	/**
	 * Returns the list of repositories.
	 * 
	 * @return array<MoufRepository>
	 */
	public function getRepositories() {
		if ($this->repositories == null) {
			$repositoryUrls = $this->moufManager->getVariable("repositoryUrls");
			
			$this->repositories = array();
			if($repositoryUrls) {
				foreach ($repositoryUrls as $id=>$repositoryUrlArray) {
					$this->repositories[] = new MoufRepository($id, $repositoryUrlArray['name'], $repositoryUrlArray['url'], $this);
				}
			}
		}
		return $this->repositories;
	}
	
	/**
	 * Returns the repository at the requested URL.
	 * 
	 * @return MoufRepository
	 */
	public function getRepository($url) {

		if (!$this->moufManager->issetVariable("repositoryUrls")) {
			return;	
		}

		// Let's perform a basic check to be sure we are allowed to download.
		$repositoryUrls = $this->moufManager->getVariable("repositoryUrls");
		$found = false;
		foreach ($repositoryUrls as $id=>$arr) {
			if ($arr['url'] == $url) {
				$repository = new MoufRepository($id, $arr['name'], $arr['url'], $this);
				return $repository;
			}
		}
		
		throw new MoufException("The URL '$url' is not part of the repository list.");
	}
	
	/**
	 * Downloads a package and installs it in the plugins directory.
	 * 
	 * @param MoufRepository $repository
	 * @param string $group
	 * @param string $name
	 * @param string $version
	 */
	public function downloadAndUnpackPackage(MoufRepository $repository, $group, $name, $version) {
		$this->packageManager = new MoufPackageManager();
		 
		// Create a temporary file (that will be deleted at the end of the extract process).
		$fileName = tempnam(sys_get_temp_dir(), "moufpackage");
		$fp = fopen($fileName, "w");
		
		if($fp) {
			$url = $repository->getUrl();
			
			// preparation de l'envoi
			$ch = curl_init();
			if (strrpos($url, "/") !== strlen($url)-1) {
				$url .= "/";
			}
			$url .= "packagesService/download?group=".urlencode($group)."&name=".urlencode($name)."&version=".urlencode($version);
			curl_setopt( $ch, CURLOPT_URL, $url);
			curl_setopt( $ch, CURLOPT_POST, FALSE );
			/**
			 * Ask cURL to write the contents to a file
			 */
			curl_setopt($ch, CURLOPT_FILE, $fp);
//			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			
			curl_exec( $ch );
			
			if( curl_error($ch) ) { 
				throw new MoufNetworkException("An error occured (CURL): ".curl_error($ch));
			}
			curl_close( $ch );
			
			// CLose the file
			$isClose = fclose($fp);
			if(!$isClose) {
				throw new MoufException("An error occured at file close.");
			}
			
			// Due to a fclose bug close again the file if necessary
			// FIXME: http://www.mail-archive.com/php-bugs@lists.php.net/msg127687.html
			if(is_resource($fp)) {
        		$isCloseAgain = fclose($fp);
				if(!$isCloseAgain) {
					throw new MoufException("An error occured at file close again.");
				}
			}

			try {
				$this->packageManager->unpackPackage(new MoufPackageDescriptor($group, $name, $version), $fileName);
			} catch (MoufPackageUnzipException $zipException) {
				throw new MoufException("Error while unzipping ZIP file ".$fileName." that is supposed to contain the package ".$group."/".$name."/".$version.". Check the content of the file to understand what is wrong.");
			}
			
			if(is_file($fileName) == TRUE 
					&& file_exists($fileName)) {
				unlink(realpath($fileName));
			} else {
				throw new MoufException("File $fileName is not a file or doesn't exist.");
			}
		} else {
			throw new MoufException("File $fileName doesn't exist.");
		}
	}
}

?>