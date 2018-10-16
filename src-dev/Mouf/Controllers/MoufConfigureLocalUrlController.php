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

use Mouf\Html\Template\TemplateInterface;
use Mouf\MoufException;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Mvc\Splash\HtmlResponse;
use Mouf\Reflection\MoufReflectionProxy;
use Mouf\Security\Logged;
use TheCodingMachine\Splash\Annotations\URL;

/**
 * The controller that will enable you to set up the local URL (if needed)
 */
class MoufConfigureLocalUrlController extends Controller {
	
	/**
	 * The template used by the main page for mouf.
	 *
	 * @var TemplateInterface
	 */
	private $template;
	
	/**
	 * The content block the template will be writting into.
	 *
	 * @var HtmlBlock
	 */
    private $contentBlock;

	protected $status;
	protected $localUrl;
	protected $selfedit;

    public function __construct(TemplateInterface $template, HtmlBlock $contentBlock)
    {
        $this->template = $template;
        $this->contentBlock = $contentBlock;
    }

	/**
	 * The default action will redirect to the MoufController defaultAction.
	 *
	 * @URL("configureLocalUrl/")
	 * @Logged()
	 */
	public function index($selfedit = "false") {
		$this->selfedit = $selfedit;
		$this->status = true;

		try {
			$this->status = MoufReflectionProxy::checkConnection();
		} catch (MoufException $e) {
			$this->status = false;
		}

		$this->localUrl = MoufReflectionProxy::getLocalUrlToProject();

		$this->contentBlock->addFile(ROOT_PATH."src-dev/views/configureLocalUrl.php", $this);
		return new HtmlResponse($this->template);
	}

	/**
	 * @URL("configureLocalUrl/setLocalUrl")
	 * @Logged()
	 */
	public function setLocalUrl(string $localUrl, string $selfedit = "false") {
		$this->selfedit = $selfedit;
		MoufReflectionProxy::setLocalUrlToProject($localUrl);
		return $this->index($selfedit);
	}
}
