<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Controllers;

use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\MoufPackage;

/**
 * The controller managing the download of the packages.
 *
 * @Component
 */
class PackageDownloadController extends Controller implements DisplayPackageListInterface {

	/**
	 * The pacakge download service.
	 *
	 * @Property
	 * @Compulsory
	 * @var MoufPackageDownloadService
	 */
	public $packageDownloadService;
	
	public $selfedit;
	
	/**
	 * The active MoufManager to be edited/viewed
	 *
	 * @var MoufManager
	 */
	public $moufManager;
	
	/**
	 * The template used by the main page for mouf.
	 *
	 * @Property
	 * @Compulsory
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @Property
	 * @Compulsory
	 * @var HtmlBlock
	 */
	public $contentBlock;
	
	/**
	 * The list of repositories.
	 * 
	 * @var array(array("name"=>string,"url"=>string))
	 */
	public $repositoryUrls = array();

	/**
	 * Lists all the repositories known of Mouf.
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function defaultAction($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		if ($this->moufManager->issetVariable("repositoryUrls")) {
			$this->repositoryUrls = $this->moufManager->getVariable("repositoryUrls");
		}
		
		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/packages/displayDownloadPackages.php", $this);
		$this->template->toHtml();	
	}
	
	protected $moufPackageRoot;
	
	/**
	 * This function acts as a simple proxy to bypass cross-site scripting mechanisms.
	 * 
	 * @Action
	 * @param string $url
	 */
	public function proxylist($url, $selfedit = "false") {

		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}

		$this->packageDownloadService->setMoufManager($this->moufManager);
		
		$repository = $this->packageDownloadService->getRepository($url);
		$this->moufPackageRoot = $repository->getRootGroup();
				
		// Let's echo the HTML directly:
		$this->loadFile("views/packages/ajaxDisplayPackagesList.php");
	}

	/**
	 * Display the rows of buttons below the package list.
	 * 
	 * @param MoufPackage $package The package to display
	 * @param string $enabledVersion The version of that package that is currently enabled, if any.
	 */
	function displayPackageActions(MoufPackage $package, $enabledVersion) {
		$packageXmlPath = $package->getDescriptor()->getPackageXmlPath();
		$isPackageEnabled = $this->moufManager->isPackageEnabled($packageXmlPath);
		
		// Is this package already downloaded?
		$locallyAvailable = false;
		$packageXmlFile = ROOT_PATH."plugins/".$package->getPackageDirectory()."/package.xml";
		if (file_exists($packageXmlFile)) {
			// The package is downloaded.
			$locallyAvailable = true;
		}
		
		$group = $package->getDescriptor()->getGroup();
		if (strpos($group, "/") === 0) {
			$group = substr($group, 1);
		}
		
		if ($locallyAvailable) {
			// Ok, the package is downloaded, but is it the latest revision?
			$localPackage = new MoufPackage();
			$localPackage->initFromFile(ROOT_PATH."plugins/".$package->getPackageDirectory()."/package.xml");
			
			if ($localPackage->getRevision() < $package->getRevision()) {
				echo "<div class='warning'>An updated revision of the package is available. You are using revision {$localPackage->getRevision()}. Revision {$package->getRevision()} is available to download.</div>";
				echo "<form action='".ROOT_URL."mouf/packages/enablePackage' method='POST'>";
				echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
				echo "<input type='hidden' name='group' value='".htmlentities($group)."' />";
				echo "<input type='hidden' name='name' value='".htmlentities($package->getDescriptor()->getName())."' />";
				echo "<input type='hidden' name='version' value='".htmlentities($package->getDescriptor()->getVersion())."' />";
				echo "<input type='hidden' name='origin' value='".htmlentities($package->getCurrentLocation()->getUrl())."' />";
				echo "<button>Download update</button>";
				echo "</form>";
			} elseif ($localPackage->getRevision() == $package->getRevision()) {
				echo "The package is already downloaded, and you are using the latest revision.";
			} else {
				echo "<div class='zoom'>Your local version is newer than the available version on the server. You are probably using a development version of this package.</div>";
			}
			
			if ($enabledVersion !== false && $enabledVersion != $package->getDescriptor()->getVersion()) {
				echo "<form action='".ROOT_URL."mouf/packages/upgradePackage' method='POST'>";
				echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
				echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
				if (MoufPackageDescriptor::compareVersionNumber($package->getDescriptor()->getVersion(), $enabledVersion) > 0) {
					echo "<button>Upgrade to this package</button>";
				} else {
					echo "<button>Downgrade to this package</button>";
				}
				echo "</form>";
			} else if (!$isPackageEnabled) {
				echo "<form action='".ROOT_URL."mouf/packages/enablePackage' method='POST'>";
				echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
				echo "<input type='hidden' name='group' value='".htmlentities($group)."' />";
				echo "<input type='hidden' name='name' value='".htmlentities($package->getDescriptor()->getName())."' />";
				echo "<input type='hidden' name='version' value='".htmlentities($package->getDescriptor()->getVersion())."' />";
				echo "<button>Enable</button>";
				echo "</form>";
			} else {
				echo "<form action='".ROOT_URL."mouf/packages/disablePackage' method='POST'>";
				echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
				echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
				echo "<button>Disable</button>";
				echo "</form>";
			}
				
		} else {
			echo "<form action='".ROOT_URL."mouf/packages/enablePackage' method='POST'>";
			echo "<input type='hidden' name='selfedit' value='".$this->selfedit."' />";
			//echo "<input type='hidden' name='name' value='".htmlentities($packageXmlPath)."' />";
			echo "<input type='hidden' name='group' value='".htmlentities($group)."' />";
			echo "<input type='hidden' name='name' value='".htmlentities($package->getDescriptor()->getName())."' />";
			echo "<input type='hidden' name='version' value='".htmlentities($package->getDescriptor()->getVersion())."' />";
			echo "<input type='hidden' name='origin' value='".htmlentities($package->getCurrentLocation()->getUrl())."' />";
			echo "<button>Download package</button>";
			echo "</form>";
		}
		
		
	}
	
	protected $package;
	
	
}
?>