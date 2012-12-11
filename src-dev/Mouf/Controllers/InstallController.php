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
	 * Displays the page to install packages
	 * 
	 * @Action
	 * @Logged
	 *
	 * @param string $selfedit If true, we are in self-edit mode 
	 */
	public function index($selfedit = false) {
		$this->installService = new ComposerInstaller($selfedit == 'true');
		$installs = $this->installService->getInstalls();
		var_dump($installs);exit;

		$this->contentBlock->addFile(dirname(__FILE__)."/../../views/instances/viewInstance.php", $this);
		$this->template->toHtml();	
	}
}
?>