<?php 
namespace Mouf\Reflection;

use Mouf\MoufException;

/**
 * This class represents one type.
 * 
 * @author David Négrier
 */
class TypeDescriptor {
	
	private $type;
	private $keyType;
	private $subType;
	
	public function __construct() {
		
	}
	
	/**
	 * Returns the type, as a string
	 * 
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}
	
	/**
	 * Returns the key type, as a string
	 * 
	 * @return string
	 */
	public function getKeyType() {
		return $this->keyType;
	}

	/**
	 * Returns the sub type (the type in the array), as a TypeDescriptor
	 *
	 * @return TypeDescriptor
	 */
	public function getSubType() {
		return $this->subType;
	}
	
	/**
	 * Parses the tokens (passed in reference!) and returns a TypeDescriptor for the parsed tokens.
	 * 
	 * 
	 * @param array $tokens
	 * @return TypeDescriptor
	 */
	public static function parseTokens(&$tokens) {
		$typeArray = array_shift($tokens);
		if ($typeArray['token'] != 'T_TYPE') {
			throw new MoufException("Invalid type! Expecting a type name. Got '".$typeArray['match']."'");
		}
		
		$type = new TypeDescriptor();
		$type->type = $typeArray['match'];
		
		if (empty($typeArray)) {
			return $type;
		}
		
		do {
			$continue = false;
			$nextToken = array_shift($tokens);
		
			switch ($nextToken['token']) {
				case 'T_ARRAY';
					$continue = true;
					$subType = $type;
					$type = new TypeDescriptor();
					$type->type = 'array';
					$type->subType = $subType;
					break;
				case 'T_END_ARRAY';
					break;
				case 'T_START_ARRAY';
					if ($tokens[1]['token'] == 'T_COMA') {
						if ($tokens[0]['token'] != 'T_TYPE') {
							throw new MoufException("Invalid type! Expecting a type name. Got ".$tokens['match']);
						}
						$type->keyType = $tokens[0]['match'];
						array_shift($tokens);
						array_shift($tokens);
						
						$type->subType = TypeDescriptor::parseTokens($tokens);
					}
					break;
				case 'T_OR':
					break;
			}
		} while ($continue);
		
		return $type;
	}
	
	public function toJson() {
		$array = array('type'=>$this->type);
		if ($this->keyType) {
			$array['keyType'] = $this->keyType;
		}
		if ($this->subType) {
			$array['subType'] = $this->subType->toJson();
		}
		return $array;
	}
}