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

use Mouf\Html\Template\TemplateInterface;

use Mouf\Html\Widgets\MessageService\Service\UserMessageInterface;

use Mouf\Installer\AbstractInstallTask;

use Mouf\Installer\ComposerInstaller;

use Mouf\Html\HtmlElement\HtmlBlock;

use Mouf\Mvc\Splash\Controllers\Controller;


/**
 * This controller displays the Mouf install process (when Mouf is started the first time).
 *
 */
class MoufInstallController extends Controller {

	/**
	 * The template used by the main page for mouf.
	 *
	 * @var TemplateInterface
	 */
	public $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @var HtmlBlock
	 */
	public $contentBlock;
	
	/**
	 * Displays the page to install Mouf.
	 * Note: this is not a typical controller. This controller is called directly from index.php
	 * 
	 */
	public function index() {

		if (!extension_loaded("curl")) {
			$this->contentBlock->addFile(dirname(__FILE__)."/../../views/mouf_installer/missing_curl.php", $this);
		} else {
			$this->contentBlock->addFile(dirname(__FILE__)."/../../views/mouf_installer/welcome.php", $this);
		}
		
		$this->template->toHtml();	
	}
	
}
?>