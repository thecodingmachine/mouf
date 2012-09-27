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

use Mouf\Composer\ComposerService;
use Mouf\Composer\PackageInterface;

use Mouf\MoufManager;

use Mouf\MoufDocumentationPageDescriptor;

use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller displaying the documentation related to packages.
 *
 * @Component
 */
class DocumentationController extends Controller {

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
	 * The documentation menu.
	 * 
	 * @Property
	 * @Compulsory
	 * @var Menu
	 */
	public $documentationMenu;
	
	/**
	 * 
	 * @var MoufPackageManager
	 */
	//public $packageManager;
		
	/**
	 * 
	 * @var array<PackageInterface>
	 */
	protected $packageList;

	/**
	 * Current package (if we are on view page).
	 * @var MoufPackage
	 */
	protected $package;
	
	/**
	 * Displays the list of doc files from the packages
	 * 
	 * @Action
	 * @Logged
	 * @param string $selfedit
	 */
	public function index($selfedit = "false") {
		// TODO: CHANGE THE PACKAGE CONTROLLER SO WE CAN VIEW FROM THE APP SCOPE THE PACKAGES THAT ARE REQUESTED ON THE ADMIN SCOPE VIA A <scope>admin</scope> declaration.
		
		$this->selfedit = $selfedit;
		
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}
		
		$composerService = new ComposerService($this->selfedit == "true");
		
		$this->packageList = $composerService->getLocalPackages();
		
		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/doc/index.php", $this);
		$this->template->toHtml();	
	}
	
	/**
	 * Action that is run to view a documentation page.
	 *
	 * @Action
	 * @Logged
	 * @param string $selfedit
	 */
	public function view($selfedit = "false") {
		// First, let's find the list of depending packages.
		$this->selfedit = $selfedit;
		if ($selfedit == "true") {
			$this->moufManager = MoufManager::getMoufManager();
		} else {
			$this->moufManager = MoufManager::getMoufManagerHiddenInstance();
		}

		// TODO: ADD support for @URL doc/view/*
		
		$this->packageManager = new MoufPackageManager();
		
		// TODO
		
		$redirect_uri = $_SERVER['REDIRECT_URL'];

		$pos = strpos($redirect_uri, ROOT_URL);
		if ($pos === FALSE) {
			throw new Exception('Error: the prefix of the web application "'.$this->splashUrlPrefix.'" was not found in the URL. The application must be misconfigured. Check the ROOT_URL parameter in your MoufUniversalParameters.php file at the root of your project.');
		}

		$tailing_url = substr($redirect_uri, $pos+strlen(ROOT_URL));
		$args = explode("/", $tailing_url);
		// We remove the first 3 parts of the URL (mouf/doc/view)
		array_shift($args);
		array_shift($args);
		array_shift($args);
		
		$oldDir = getcwd();
		
		chdir(ROOT_PATH."plugins");
		
		$group = $this->packageManager->getOrderedPackagesList();
		
		$dirToPackage = "";
		$packageContainer = null;
		while ($args) {
			$dir = array_shift($args);
			
			if ($group->hasSubgroup($dir)) {
				$group = $group->getGroup($dir);
				$dirToPackage .= $dir.DIRECTORY_SEPARATOR;
			} elseif ($group->hasPackageContainer($dir)) {
				$packageContainer = $group->getPackageContainer($dir);
				$dirToPackage .= $dir.DIRECTORY_SEPARATOR;
				break;
			} else {
				Controller::FourOFour("Page not found", false);
				return;
			}
		}
		
		if ($packageContainer == null) {
			Controller::FourOFour("Page not found", false);
			return;
		}
		
		$dir = array_shift($args);
		
		if ($dir != "latest") {
			$this->package = $packageContainer->getPackage($dir);
			if ($this->package == null) {
				Controller::FourOFour("Page not found", false);
				return;
			}
			$dirToPackage .= $dir.DIRECTORY_SEPARATOR;
		} else {
			// Let's find the latest version!
			$packages = $packageContainer->getOrderedList();
			$this->package = array_pop($packages);
			$dirToPackage .= $this->package->getDescriptor()->getVersion().DIRECTORY_SEPARATOR;
		}
		
		
		/*$dirToPackage = "";
		$found = false;
		while ($args) {
			if (file_exists("package.xml")) {
				$found = true;
				break;
			}
			
			$dir = array_shift($args);
			if (!file_exists($dir) || !is_dir($dir)) {
				if ($dir == "latest") {
					// If the user decides to write "latest" instead of the version, let's find the latest version for him.
					
					$dir = "TODO: find the latest version!";
				} else {
					Controller::FourOFour("Page not found", false);
					return;
				}
			}
			chdir($dir);
			$dirToPackage .= $dir.DIRECTORY_SEPARATOR;
		}
		
		chdir($oldDir);
		*/
		
		
		//$this->package = $this->packageManager->getPackage($dirToPackage."package.xml");
		
		$docPath = implode("/", $args);

		$filename = ROOT_PATH."plugins/".$dirToPackage.$this->package->getDocumentationRootDirectory().DIRECTORY_SEPARATOR.$docPath;
			

		if (!file_exists($filename)) {
			Controller::FourOFour("Documentation page does not exist", false);
			return;
		}
		if (!is_readable($filename)) {
			Controller::FourOFour("Page not found", false);
			return;
		}

		if (strripos($filename, ".html") !== false) {
			$this->addMenu();
			
			$fileStr = file_get_contents($filename);

			$bodyStart = strpos($fileStr, "<body");
			if ($bodyStart === false) {
				$this->template->addContentText('<div class="staticwebsite">'.$fileStr.'</div>');
				$this->template->toHtml();
			} else {
				$bodyOpenTagEnd = strpos($fileStr, ">", $bodyStart);
	
				$partBody = substr($fileStr, $bodyOpenTagEnd+1);
	
				$bodyEndTag = strpos($partBody, "</body>");
				if ($bodyEndTag === false) {
					return '<div class="staticwebsite">'.$partBody.'</div>';
				}
				$body = substr($partBody, 0, $bodyEndTag);
	
				$this->template->addContentText('<div class="staticwebsite">'.$body.'</div>');
				$this->template->toHtml();
			}
		} elseif (strripos($filename, ".php") !== false) {
			// PHP files are not accessible
			Controller::FourOFour("Cannot access PHP file through doc", false);
			return;
		} else {
			readfile($filename);
			exit;
		}
		
		
	}
	
	protected function addMenu() {
		$docPages = $this->package->getDocPages();
		
		$documentationMenuMainItem = new MenuItem("Documentation for ".$this->package->getDisplayName());
		$this->fillMenu($documentationMenuMainItem, $docPages);
		$this->documentationMenu->addChild($documentationMenuMainItem);
	}
	
	private function fillMenu($menu, array $docPages) {
		$children = array();
		foreach ($docPages as $docPage) {
			/* @var $docPage MoufDocumentationPageDescriptor */
			$menuItem = new MenuItem();
			$menuItem->setLabel($docPage->getTitle());
			$menuItem->setUrl(ROOT_URL."mouf/doc/view/".$this->package->getDescriptor()->getGroup()."/".$this->package->getDescriptor()->getName()."/".$this->package->getDescriptor()->getVersion()."/".$docPage->getURL());
			$children[] = $menuItem;
			if ($docPage->getChildren()) {
				$this->fillMenu($menuItem, $menuItem->getChildren());
			}
		}
		$menu->setChildren($children);
	}

	protected function getLink(MoufDocumentationPageDescriptor $documentationPageDescriptor) {
		$link = $documentationPageDescriptor->getURL();
		if (strpos($link, "/") === 0
			|| strpos($link, "http://") === 0
			|| strpos($link, "https://") === 0
			|| strpos($link, "javascript:") === 0) {
				return $link;	
		}
		
		return ROOT_URL."mouf/doc/view/".$documentationPageDescriptor->getPackage()->getPackageDirectory()."/".$link;
	}
	
	/**
	 * Display the doc links for one package.
	 * 
	 * @param array<string, string> $docPages
	 * @param string $packageName
	 */
	public function displayDocDirectory($docPages, $packageName) {
		?>
		<ul>
		<?php 
		foreach ($docPages as $url=>$title):
			?>
			<li>
			<?php 
			if ($url) {
				echo "<a href='".$packageName."/doc/".$url."'>";
			}
			echo $title;
			if ($url) {
				echo "</a>";
			}
			/*if ($docPage->getChildren()) {
				displayDocDirectory($docPage->getChildren());
			}*/
			?>
			</li>
			<?php 
		endforeach;
		?>
		</ul>
		<?php
	}
	
}
?>