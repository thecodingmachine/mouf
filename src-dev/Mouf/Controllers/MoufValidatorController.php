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

/**
 * The controller that will call all validators on Mouf.
 *
 * @Component
 */
class MoufValidatorController extends Controller {
	
	/**
	 * The validation service.
	 * 
	 * @Property
	 * @Compulsory
	 * @var MoufValidatorService
	 */
	public $validatorService;
	
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
	 * The default action will redirect to the MoufController defaultAction.
	 *
	 * @Action
	 * @Logged
	 */
	public function defaultAction($selfedit = "false") {
		if ($selfedit == "true") {
			
		}
		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/validate.php", $this);
		$this->template->toHtml();
	}
}
?>