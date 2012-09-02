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

use Mouf\Splash\Controller;

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
	 * The default action will redirect to the MoufController defaultAction.
	 *
	 * @Action
	 * @Logged
	 */
	public function defaultAction($selfedit = "false") {
		if ($selfedit == "true") {
			
		}
		$this->template->addContentFile(ROOT_PATH."mouf/views/validate.php", $this);
		$this->template->draw();
	}
}
?>