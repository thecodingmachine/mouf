<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Reflection;

/**
 * Classes implementing this interface act as a manager that handles retrieval
 * of MoufReflectionClassInterface objects.
 * 
 * Basically, you query it using a class name and depending on the implementation,
 * you will get a MoufReflectionClass or a MoufXmlReflectionClass.
 * 
 * @author David Negrier
 */
interface ReflectionClassManagerInterface {
	/**
	 * Returns the class descriptor.
	 *
	 * @param string $className The fully qualified class name.
	 * @return MoufReflectionClassInterface
	 */
	public function getReflectionClass($className);
}