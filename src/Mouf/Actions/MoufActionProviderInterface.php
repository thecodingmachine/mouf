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
 * A component extending the MoufActionProviderInterface can be used to perform actions during an installation process. 
 * 
 * @author david
 */
interface MoufActionProviderInterface {
	
	/**
	 * Executes the action passed in parameter.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 * @return MoufActionResultInterface
	 */
	function execute(MoufActionDescriptor $actionDescriptor);
	
	/**
	 * Returns the text describing the action.
	 * 
	 * @param MoufActionDescriptor $actionDescriptor
	 */
	function getName(MoufActionDescriptor $actionDescriptor);
}

?>