<?php
namespace Mouf\Reflection;

use Mouf\Reflection\MoufReflectionClass;

use Mouf\TestClasses\TestClass1;

require __DIR__.'/../../../vendor/autoload.php';

class TypesDescriptorTest extends \PHPUnit_Framework_TestCase {
	
	public function testRunLexer() {
		$tokens = TypesDescriptor::runLexer("test<tata,titi>|toto[]");
		
		$this->assertEquals("T_TYPE", $tokens[0]['token']);
		$this->assertEquals("T_START_ARRAY", $tokens[1]['token']);
		$this->assertEquals("T_TYPE", $tokens[2]['token']);
		$this->assertEquals("T_COMA", $tokens[3]['token']);
		$this->assertEquals("T_TYPE", $tokens[4]['token']);
		$this->assertEquals("T_END_ARRAY", $tokens[5]['token']);
		$this->assertEquals("T_OR", $tokens[6]['token']);
		$this->assertEquals("T_TYPE", $tokens[7]['token']);
		$this->assertEquals("T_ARRAY", $tokens[8]['token']);
	}
	
	public function testConstructor() {
		$types = TypesDescriptor::parseTypeString("test<tata,titi>|toto[]|MyType");
		
		$typesList = $types->getTypes();
		
		$this->assertEquals(3, count($typesList));
		$this->assertEquals("test", $typesList[0]->getType());
		$this->assertEquals("tata", $typesList[0]->getKeyType());
		$this->assertNotNull($typesList[0]->getSubType());
		$this->assertEquals("titi", $typesList[0]->getSubType()->getType());
		$this->assertEquals("array", $typesList[1]->getType());
		$this->assertEquals("toto", $typesList[1]->getSubType()->getType());
		$this->assertEquals("MyType", $typesList[2]->getType());
	}
	
	public function testLocalCache() {
		$types = TypesDescriptor::parseTypeString("test<tata,titi>|toto[]|MyType");
		$types2 = TypesDescriptor::parseTypeString("test<tata,titi>|toto[]|MyType");
		$types3 = TypesDescriptor::parseTypeString("test<tata,titi>|toto[]|MyType2");
		
		$this->assertEquals($types, $types2);
		$this->assertNotEquals($types, $types3);
	}
}