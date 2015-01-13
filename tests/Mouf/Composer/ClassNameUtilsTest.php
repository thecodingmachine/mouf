<?php
namespace Mouf\Composer;

/**
 * The class maps a class name to one or many possible file names according to PSR-0 or PSR-4 rules.
 *
 * @author David Négrier <david@mouf-php.com>
 */
class ClassNameUtilsTest extends \PHPUnit_Framework_TestCase {
	public function testGetNamespace() {
		$this->assertEquals('GeneratedClasses', ClassNameUtils::getNamespace('GeneratedClasses\\Container'));
		$this->assertEquals('GeneratedClasses', ClassNameUtils::getNamespace('\\GeneratedClasses\\Container'));
		$this->assertEquals('Toto\\Tata\\Toto', ClassNameUtils::getNamespace('\\Toto\\Tata\\Toto\\Container'));
	}
	
	public function testGetClassname() {
		$this->assertEquals('Container', ClassNameUtils::getClassName('GeneratedClasses\\Container'));
		$this->assertEquals('Container', ClassNameUtils::getClassName('\\GeneratedClasses\\Container'));
	}
}
