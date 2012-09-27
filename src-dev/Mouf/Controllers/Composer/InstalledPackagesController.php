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

use Mouf\MoufPackage;
use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller managing the list of composer packages included in the project.
 *
 * @Component
 */
class InstalledPackageController extends Controller {
	
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
}