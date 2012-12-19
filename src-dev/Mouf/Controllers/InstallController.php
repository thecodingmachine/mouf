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

use Mouf\Installer\AbstractInstallTask;

use Mouf\Installer\ComposerInstaller;

use Mouf\Html\HtmlElement\HtmlBlock;

use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * This controller displays the packages installation page.
 *
 * @Component
 */
class InstallController extends Controller {

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
	 * The service that will take actions to be performed to install.
	 *
	 * @Property
	 * @Compulsory
	 * @var ComposerInstaller
	 */
	public $installService;
	
	/**
	 * 
	 * @var AbstractInstallTask[]
	 */
	protected $installs;
	
	/**
	 * Displays the page to install packages
	 * 
	 * @Action
	 * @Logged
	 *
	 * @param string $selfedit If true, we are in self-edit mode 
	 */
	public function index($selfedit = false) {
		$this->installService = new ComposerInstaller($selfedit == 'true');
		$this->installs = $this->installService->getInstallTasks();
		//var_dump($this->installs);exit;

		$this->contentBlock->addFile(dirname(__FILE__)."/../../views/installer/installTasksList.php", $this);
		$this->template->toHtml();	
	}
	
	protected $selfedit;
	
	/**
	 * This page starts the install proces of one task or all tasks in "todo" state.
	 * 
	 * @Action
	 * @Logged
	 *
	 * @param string $selfedit If true, we are in self-edit mode 
	 */
	public function install($selfedit = 'false', $task = null) {
		$this->selfedit = $selfedit;
		$this->installService = new ComposerInstaller($selfedit == 'true');
		
		if ($task !== null) {
			$taskArray = unserialize($task);
		} else {
			$taskArray = null;
		}
		
		if ($taskArray == null) {
			$this->installService->installAll();
		} else {
			$this->installService->install($taskArray);
		}
		// The call to install or installAll redirects to printInstallationScreen.
	}
	
	/**
	 * Installation screen is displayed and the user is directly redirected via Javascript to the install page.
	 * 
	 * @Action
	 * @param string $selfedit
	 */
	public function printInstallationScreen($selfedit = 'false') {
		$this->selfedit = $selfedit;
		$this->installService = new ComposerInstaller($selfedit == 'true');
		$this->installs = $this->installService->getInstallTasks();
		
		$this->contentBlock->addFile(dirname(__FILE__)."/../../views/installer/processing.php", $this);
		$this->template->toHtml();
	}
	
	/**
	 * Starts the installation process for one package registered with install or installAll.
	 * 
	 * @Action
	 * @param string $selfedit
	 */
	public function processInstall($selfedit = 'false') {
		// Let's process install now by redirecting HERE!
		$this->selfedit = $selfedit;
		$this->installService = new ComposerInstaller($selfedit == 'true');
		$installTask = $this->installService->getNextInstallTask();
		
		header("Location: ".MOUF_URL.$installTask->getRedirectUrl($selfedit == 'true'));
	}
	
	/**
	 * Action called when a full install step has completed.
	 * 
	 * @Action
	 * @param string $selfedit
	 */
	public function installTaskDone($selfedit = 'false') {
		$this->selfedit = $selfedit;
		$this->installService = new ComposerInstaller($selfedit == 'true');
		$this->installService->validateCurrentInstall();
		
		$installTask = $this->installService->getNextInstallTask();
		if ($installTask) {
			$this->printInstallationScreen($selfedit);
		} else {
			echo "Installation process succeeded!";
		}
		
		
	}
}
?>