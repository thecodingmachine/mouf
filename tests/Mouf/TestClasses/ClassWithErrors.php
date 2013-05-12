<?php 
namespace Mouf\TestClasses;

class ClassWithErrors {
	
	/**
	 * We should have a message saying the "param" annotation does not match the parameter
	 *
	 * @param int $host
	 */
	public function setSetterWithWrongParam($port) {
		
	}
	
}