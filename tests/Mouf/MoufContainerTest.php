<?php
namespace Mouf;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\TestClasses\TestClass1;
use Mouf\Reflection\MoufReflectionClassManager;


class MoufContainerTest extends \PHPUnit_Framework_TestCase {
	
	public function testConstructor() {
		if (file_exists(__DIR__."/../GeneratedClasses/instances.php")) {
			unlink(__DIR__."/../GeneratedClasses/instances.php");
		}
		if (file_exists(__DIR__."/../GeneratedClasses/Container.php")) {
			unlink(__DIR__."/../GeneratedClasses/Container.php");
		}
		
		$container = new MoufContainer(__DIR__."/../GeneratedClasses/instances.php", "GeneratedClasses\\Container", new MoufReflectionClassManager());
		
		$instanceDescriptorClass2 = $container->createInstance("Mouf\TestClasses\TestClass2");
		
		$instanceDescriptor = $container->createInstance("Mouf\TestClasses\TestClass1");
		$instanceDescriptor->getProperty("constructorParamA")->setValue(1);
		$instanceDescriptor->getProperty("constructorParamB")->setValue($instanceDescriptorClass2);
		$instanceDescriptor->getProperty("constructorParamC")->setValue($instanceDescriptorClass2);
		$instanceDescriptor->setName("testClass1");
		
		$instance = $container->get("testClass1");
		/* @var $instance \Mouf\TestClasses\TestClass */
		$this->assertEquals(1, $instance->getConstructorParamA());
		$this->assertInstanceOf("Mouf\TestClasses\TestClass2", $instance->getConstructorParamB());
		$this->assertInstanceOf("Mouf\TestClasses\TestClass2", $instance->getConstructorParamC());
		$this->assertEquals($instance->getConstructorParamB(), $instance->getConstructorParamC());
	}

	public function testCallbackInjection() {
		if (file_exists(__DIR__."/../GeneratedClasses/instances.php")) {
			unlink(__DIR__."/../GeneratedClasses/instances.php");
		}
		if (file_exists(__DIR__."/../GeneratedClasses/Container.php")) {
			unlink(__DIR__."/../GeneratedClasses/Container.php");
		}
		
		$container = new MoufContainer(__DIR__."/../GeneratedClasses/instances.php", "GeneratedClasses\\Container", new MoufReflectionClassManager());
	
		$instanceDescriptor = $container->createInstance("Mouf\TestClasses\TestClass1");
		$instanceDescriptor->getProperty("constructorParamA")->setOrigin('php')->setValue('return [];');
		$instanceDescriptor->setName("testClass1");
	
		// Note: we cannot retrieve the instance without first saving and loading the file.
		// This is not an issue.
	}
	
	public function testCreateContainer() {
		if (file_exists(__DIR__."/../GeneratedClasses/instances.php")) {
			unlink(__DIR__."/../GeneratedClasses/instances.php");
		}
		if (file_exists(__DIR__."/../GeneratedClasses/Container.php")) {
			unlink(__DIR__."/../GeneratedClasses/Container.php");
		}
		
		MoufContainer::createContainer(__DIR__."/../GeneratedClasses/instances.php", "GeneratedClasses\\Container", __DIR__."/../GeneratedClasses/Container.php");
		
		$this->assertTrue(file_exists(__DIR__."/../GeneratedClasses/instances.php"), "The configuration file is not found");
		$this->assertTrue(file_exists(__DIR__."/../GeneratedClasses/Container.php"), "The generated class file is not found");
		
	}
	
	public function testWrite() {
		if (file_exists(__DIR__."/../GeneratedClasses/instances.php")) {
			unlink(__DIR__."/../GeneratedClasses/instances.php");
		}
		if (file_exists(__DIR__."/../GeneratedClasses/Container.php")) {
			unlink(__DIR__."/../GeneratedClasses/Container.php");
		}
		
		$container = new MoufContainer(__DIR__."/../GeneratedClasses/instances.php", "GeneratedClasses\\Container", new MoufReflectionClassManager(), null, __DIR__."/../GeneratedClasses/Container.php");
	
		$instanceDescriptorClass2 = $container->createInstance("Mouf\TestClasses\TestClass2");
	
		$instanceDescriptor = $container->createInstance("Mouf\TestClasses\TestClass1");
		$instanceDescriptor->getProperty("constructorParamA")->setValue(1);
		$instanceDescriptor->getProperty("constructorParamB")->setValue($instanceDescriptorClass2);
		$instanceDescriptor->getProperty("constructorParamC")->setValue($instanceDescriptorClass2);
		$instanceDescriptor->setName("testClass1");
		
		$container->write();
		
		$container2 = new \GeneratedClasses\Container();
		$testClass1 = $container2->get('testClass1');
		$this->assertInstanceOf("Mouf\TestClasses\TestClass1", $testClass1);
	}
	
	
	static function main() {
		$suite = new \PHPUnit_Framework_TestSuite( __CLASS__);
		\PHPUnit_TextUI_TestRunner::run( $suite);
	}
}

if (!defined('PHPUnit_MAIN_METHOD')) {
	MoufManagerTest::main();
}