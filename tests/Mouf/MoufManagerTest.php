<?php
namespace Mouf;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\TestClasses\TestClass1;


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

	public function testCallbackInjection() {
		$container = new MoufManager();
	
		$instanceDescriptor = $container->createInstance("Mouf\TestClasses\TestClass1");
		$instanceDescriptor->getProperty("constructorParamA")->setOrigin('php')->setValue('return [];');
		$instanceDescriptor->setName("testClass1");
	
		// Note: we cannot retrieve the instance without first saving and loading the file.
		// This is not an issue.
	}
	
	
	static function main() {
		$suite = new \PHPUnit_Framework_TestSuite( __CLASS__);
		\PHPUnit_TextUI_TestRunner::run( $suite);
	}
}

if (!defined('PHPUnit_MAIN_METHOD')) {
	MoufManagerTest::main();
}