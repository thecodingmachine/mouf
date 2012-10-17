<?php
namespace Mouf;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\TestClasses\TestClass1;

require __DIR__.'/../../vendor/autoload.php';

class MoufManagerTest extends \PHPUnit_Framework_TestCase {
	
	public function testConstructor() {
		$container = new MoufManager();
		
		$instanceDescriptorClass2 = $container->createInstance("Mouf\TestClasses\TestClass2");
		
		$instanceDescriptor = $container->createInstance("Mouf\TestClasses\TestClass1");
		$instanceDescriptor->getProperty("constructorParamA")->setValue(1);
		$instanceDescriptor->getProperty("constructorParamB")->setValue($instanceDescriptorClass2);
		$instanceDescriptor->getProperty("constructorParamC")->setValue($instanceDescriptorClass2);
		$instanceDescriptor->setName("testClass1");
		
		$instance = $container->getInstance("testClass1");
		/* @var $instance \Mouf\TestClasses\TestClass */
		$this->assertEquals(1, $instance->getConstructorParamA());
		$this->assertInstanceOf("Mouf\TestClasses\TestClass2", $instance->getConstructorParamB());
		$this->assertInstanceOf("Mouf\TestClasses\TestClass2", $instance->getConstructorParamC());
		$this->assertEquals($instance->getConstructorParamB(), $instance->getConstructorParamC());
	}
	
	static function main() {
		$suite = new \PHPUnit_Framework_TestSuite( __CLASS__);
		\PHPUnit_TextUI_TestRunner::run( $suite);
	}
}

if (!defined('PHPUnit_MAIN_METHOD')) {
	MoufManagerTest::main();
}