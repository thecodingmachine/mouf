<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2013 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Reflection;

/**
 * This class acts as a MoufXmlReflectionClass factory (so is used when in admin mode
 * and looking for the application).
 * 
 */
class MoufXmlReflectionClassManager implements ReflectionClassManagerInterface {
	
	/**
	 * A list of descriptors.
	 *
	 * @var array<string, MoufReflectionClass>
	 */
	private $classDescriptors = array();
	
	/**
	 * Returns an object describing the class passed in parameter.
	 * This method should only be called in the context of the Mouf administration UI.
	 *
	 * @param string $className The name of the class to import
	 * @return MoufXmlReflectionClass
	*/
	public function getReflectionClass($className) {
		if (!isset($this->classDescriptors[$className])) {
			$this->classDescriptors[$className] = MoufReflectionProxy::getClass($className, false);
		}
		return $this->classDescriptors[$className];
	}
	
}
?>