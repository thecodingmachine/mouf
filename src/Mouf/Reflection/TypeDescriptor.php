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
	/**
	 * 
	 * @var TypeDescriptor
	 */
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
	
	/**
	 * Give a fully qualified class name from the $type and declared "use" statements.
	 * Note: Once resulved, the qualified names always start with a \
	 *
	 * @param array<string, string> $useMap
	 * @param string $namespace
	 * @return unknown|string
	 */
	public function resolveType($useMap, $namespace) {
		if ($this->subType != null) {
			$this->subType->resolveType();
		}
		
		if ($this->type == null || $this->type == "array" || $this->isPrimitiveType()) {
			return;
		}
	
		$index = strpos($this->type, '\\');
		if ($index === false) {
			if (isset($useMap[$this->type])) {
				return '\\'.$useMap[$this->type];
			} else {
				if ($namespace) {
					return '\\'.$namespace.'\\'.$this->type;
				} else {
					return '\\'.$this->type;
				}
			}
		}
		if ($index === 0) {
			// Starting with \. Already a fully qualified name.
			return $this->type;
		}
		$leftPart = substr($this->type, 0, $index);
		$rightPart = substr($this->type, $index);
	
		if (isset($useMap[$leftPart])) {
			return '\\'.$useMap[$leftPart].$rightPart;
		} else {
			return '\\'.$namespace.'\\'.$this->type;
		}
	}
	
	/**
	 * Returns true if the type passed in parameter is primitive.
	 * It will return false if this is an array or an object.
	 *
	 * Accepted primitive types: string, char, bool, boolean, int, integer, double, float, real, mixed
	 * No type (empty type) is considered primitive type.
	 *
	 * @param string $type
	 * @return bool
	 */
	private function isPrimitiveType() {
		$lowerVarType = strtolower($this->type);
		return in_array($lowerVarType, array('string', 'char', 'bool', 'boolean', 'int', 'integer', 'double', 'float', 'real', 'mixed'));
	}
	
}