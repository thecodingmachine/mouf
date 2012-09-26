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

use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * The controller displaying the PHP Info page.
 *
 * @Component
 */
class PhpInfoController extends Controller {
	
	/**
	 * Displays the PHP info page.
	 * 
	 * @Action
	 * @Logged
	 */
	public function defaultAction() {
		echo phpinfo();
	}
}
?>