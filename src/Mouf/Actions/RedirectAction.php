<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Actions;

/**
 * A simple action that performs a Redirect.
 * This is very useful to redirect to a package custom installation process.
 * 
 * @author david
 * @Component
 */
class RedirectAction implements MoufActionProviderInterface {
	/**
	 * Executes the action passed in parameter.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 */
	public function execute(MoufActionDescriptor $actionDescriptor) {
		$redirect = $actionDescriptor->params['redirectUrl'];
		return new MoufActionRedirectResult($redirect);
	}
	
	/**
	 * Returns the text describing the action.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 */
	public function getName(MoufActionDescriptor $actionDescriptor) {
		return "Installation process for package ".$actionDescriptor->params['packageFile'].".";
	}
	
}