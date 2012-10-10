<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Controllers\Composer;

use Mouf\Composer\ChunckedUtils;

use Mouf\Html\Utils\WebLibraryManager\WebLibrary;

use Mouf\Composer\OnPackageFoundInterface;

use Mouf\Composer\ComposerService;

use Mouf\MoufManager;

use Mouf\Html\HtmlElement\HtmlBlock;

use Mouf\MoufPackage;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\Template\TemplateInterface;

use Composer\Package\PackageInterface;

/**
 * The controller managing the list of composer packages included in the project.
 *
 * @Component
 */
class InstalledPackagesController extends Controller implements OnPackageFoundInterface {
	
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
	 *
	 * @var array<PackageInterface>
	 */
	protected $packageList;
	
	/**
	 * View the list of all installed composer packages.
	 * 
	 * @URL composer/view
	 */
	public function index($selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$composerService = new ComposerService($this->selfedit == "true");
		
		$this->packageList = $composerService->getLocalPackages();
		
		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/composer/index.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Searches the packages and returns a list of matching packages.
	 * 
	 * @URL composer/search
	 * @param string $text
	 */
	public function search($text) {
		$composerService = new ComposerService();
		
		ChunckedUtils::init();
		
		$msg = "<script>window.parent.Composer.consoleOutput('Composer is searching...<br/>')</script>";
		ChunckedUtils::writeChunk($msg);
		
		$msg = "<script>window.parent.Composer.setSearchStatus(true)</script>";
		ChunckedUtils::writeChunk($msg);
		
		$composerService->searchPackages($text, $this);
		
		$msg = "<script>window.parent.Composer.setSearchStatus(false)</script>";
		ChunckedUtils::writeChunk($msg);
		
		ChunckedUtils::close();		
	}
	
	public function onPackageFound(PackageInterface $package, $score) {
		//$html = $this->getHtmlForPackage($package);
		$packageArr = array();
		$packageArr['prettyName'] = $package->getPrettyName();
		$packageArr['name'] = $package->getName();
		$packageArr['version'] = $package->getVersion();
		$packageArr['homePage'] = $package->getHomepage();
		$packageArr['description'] = $package->getDescription();
		$extra = $package->getExtra();
		if (isset($extra['mouf']['logo'])) {
			$packageArr['logo'] = ROOT_URL."vendor".$package->getName()."/".$extra['mouf']['logo'];
		}
		
		$msg = "<script>window.parent.Composer.displayPackage(".json_encode($packageArr).", $score)</script>";
		ChunckedUtils::writeChunk($msg);
	}
	
	public function getHtmlForPackage(PackageInterface $package) {
		$version = str_replace(array(".9999999.9999999-dev", ".9999999-dev"), array("-dev", "-dev"), $package->getVersion());
		if ($version == "9999999-dev") {
			$version = "dev-master";
		}
		$html = '<div class="package">
		<div class="packageicon">';
		$extra = $package->getExtra();
		if (isset($extra['mouf']['logo'])) {
			$html .= '<img alt="" src="'.ROOT_URL."vendor".$package->getName()."/".$extra['mouf']['logo'].'">';
		}
		$html .= '
			</div>
			<div class="packagetext">
				<span class="packagename">'.$package->getPrettyName().'</span> <span class="packageversion">(version '.$version.')</span>
				<div class="packagehompage">Homepage: <a target="_blank" href="'.$package->getHomepage().'">'.$package->getHomepage().'</a></div>
				<div class="packagedescription">'.$package->getDescription().'</div>';
		if ($package->getName() != "__root__") {
			$html .= '
				<form action="uninstall" method="post">
					<input type="hidden" name="name" value="'.$package->getName().'" />
					<button>Uninstall</button>
				<form>';
		}
		$html .= '
			</div>
		</div>';
		return $html;
	}
	
	protected $name;
	protected $version;
		
	/**
	 * Triggers the installation of the composer package passed in parameter.
	 * This will actually display a screen with an iframe that will do the real install process.
	 * 
	 * @Post
	 * @URL composer/install
	 * @param string $name
	 * @param string $version
	 * @param string $selfedit
	 */
	public function install($name, $version, $selfedit = "false") {
		$this->selfedit = $selfedit;
		$this->name = $name;
		$this->version = $version;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/composer/install.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Actually performs the package installation.
	 * 
	 * @URL composer/doInstall
	 * @param string $name
	 * @param string $version
	 * @param string $selfedit
	 */
	public function doInstall($name, $version, $selfedit = "false") {
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$composerService = new ComposerService($this->selfedit == "true");
		
		ChunckedUtils::init();
		
		$msg = "<script>window.parent.Composer.consoleOutput('Starting installation process...<br/>')</script>";
		ChunckedUtils::writeChunk($msg);
				
		$composerService->install($name, $version);
		
		
		ChunckedUtils::close();
	}
	
	/**
	 * Triggers the removal of the composer package passed in parameter.
	 * This will actually display a screen with an iframe that will do the real uninstall process.
	 *
	 * @Post
	 * @URL composer/uninstall
	 * @param string $name
	 * @param string $selfedit
	 */
	public function uninstall($name, $selfedit = "false") {
		$this->selfedit = $selfedit;
		$this->name = $name;
	
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
	
		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/composer/uninstall.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Actually performs the package removal.
	 *
	 * @URL composer/doUninstall
	 * @param string $name
	 * @param string $selfedit
	 */
	public function doUninstall($name, $selfedit = "false") {
		$this->selfedit = $selfedit;
	
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
	
		$composerService = new ComposerService($this->selfedit == "true");
	
		ChunckedUtils::init();
	
		$msg = "<script>window.parent.Composer.consoleOutput('Starting uninstall process...<br/>')</script>";
		ChunckedUtils::writeChunk($msg);
	
		$composerService->uninstall($name);
	
	
		ChunckedUtils::close();
	}
}