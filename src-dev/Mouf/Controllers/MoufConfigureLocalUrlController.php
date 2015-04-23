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

use Mouf\MoufException;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Reflection\MoufReflectionProxy;

/**
 * The controller that will enable you to set up the local URL (if needed)
 */
class MoufConfigureLocalUrlController extends Controller {
	
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

	protected $status;
	protected $localUrl;

	/**
	 * The default action will redirect to the MoufController defaultAction.
	 *
	 * @Action
	 * @Logged
	 */
	public function index($selfedit = "false") {

		$this->status = true;

		try {
			$this->status = MoufReflectionProxy::checkConnection();
		} catch (MoufException $e) {
			$this->status = false;
		}

		$this->localUrl = MoufReflectionProxy::getLocalUrlToProject();

		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/configureLocalUrl.php", $this);
		$this->template->toHtml();
	}
}
?>