<?php 
namespace Mouf\TestClasses;

use Mouf\TestClasses\Subname\Subclass;
use Mouf\TestClasses\Subname;

/**
 * @Component
 * @author david
 *
 */
class TestClass1 {
	
	
	/**
	 *
	 * @param string $constructorParamA
	 * @param TestClass2 $constructorParamC
	 */
	public function __construct($constructorParamA, TestClass2 $constructorParamB, $constructorParamC) {
		$this->constructorParamA = $constructorParamA;
		$this->constructorParamB = $constructorParamB;
		$this->constructorParamC = $constructorParamC;
	}
	
	private $constructorParamA;
	 
	public function getConstructorParamA()
	{
		return $this->constructorParamA;
	}
	
	private $constructorParamB;
	 
	public function getConstructorParamB()
	{
		return $this->constructorParamB;
	}
	
	private $constructorParamC;
	
	public function getConstructorParamC()
	{
		return $this->constructorParamC;
	}
	
	
	/**
	 * @Property
	 * @var array<int>
	 */
	public $indexedArray;
	
	/**
	 * @Property
	 * @var array<string, string>
	 */
	public $associativeArray;
	
	/**
	 * @Property
	 * @var int
	 */
	public $int;
	
	/**
	 * @Property
	 * @var TestClass1
	 */
	public $testClass1;

	/**
	 * @Property
	 * @var \Mouf\TestClasses\TestClass1
	 */
	public $testClass1FullyQualifiedNamespace;
	
	/**
	 * @Property
	 * @var Subname\Subclass
	 */
	public $testSubNamespace;
	
	/**
	 * @Property
	 * @var Subclass
	 */
	public $testUse;
	
	/**
	 * @Property
	 * @var Subname\Subclass
	 */
	public $testRelativeUse;
	
	
	/**
	 *
	 * @Property
	 * @param TestClass1 $value
	 */
	public function setSetterProperty1($value) {
	
	}
	
	/**
	 *
	 * @Property
	 */
	public function setSetterProperty2(TestClass1 $value) {
	
	}
}
?>