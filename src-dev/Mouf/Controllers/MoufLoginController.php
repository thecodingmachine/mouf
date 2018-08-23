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

use Mouf\Mvc\Splash\Annotations\Action;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Security\Controllers\SimpleLoginController;

/**
 * The MoufLoginController class provides the login page and login/logout mechanism for Mouf.
 * It is actually getting its behaviour from the SimpleLoginController with one simple addition:
 * if the MoufUsers.php file does not exist, it will guide the user towards a solution to be able to get logged.
 *
 */
class MoufLoginController extends SimpleLoginController {
	
	
	/**
	 * The index page will display the login form (from SimpleLoginController) or an explanation on how to setup users
	 * if users are not set up yet.
	 * 
	 * @Action()
	 * @param string $login The login to fill by default.
	 * @param string $redirecturl The URL to redirect to when login is done. If not specified, the default login URL defined in the controller will be used instead.
	 */
	public function defaultAction($login = null, $redirect = null) {
		/*if (!file_exists(ROOT_PATH."../../../mouf/MoufUsers.php")) {
			$this->contentBlock->addFile(dirname(__FILE__)."/../../views/missing_password_file.php", $this);
			$this->template->toHtml();
			return;
		}*/
		
		parent::defaultAction($login, $redirect);
	}
	
}
