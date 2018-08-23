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

use Mouf\Html\HtmlElement\HtmlBlock;

use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\Utils\WebLibraryManager\WebLibrary;

/**
 * This controller displays the (not so) basic full ajax details page.
 */
class MoufAjaxInstanceController extends AbstractMoufInstanceController {

	/**
	 * @Property
	 * @var HtmlBlock
	 */
	public $rightBlock;
	
	/**
	 * Displays the page to edit an instance.
	 * 
	 * @Action
	 * @Logged
	 *
	 * @param string $name the name of the component to display
	 * @param string $selfedit If true, the name of the component must be a component from the Mouf framework itself (internal use only) 
	 */
	public function index($name, $selfedit = false) {
		$this->initController($name, $selfedit);

		$this->template->getWebLibraryManager()->addLibrary(new WebLibrary(["vendor/mouf/javascript.ace/src-min-noconflict/ace.js"]));
		
		$this->contentBlock->addFile(dirname(__FILE__)."/../../views/instances/viewInstance.php", $this);
		$this->rightBlock->addText("<div id='instanceList'></div>");
		$this->template->toHtml();	
	}
}
?>