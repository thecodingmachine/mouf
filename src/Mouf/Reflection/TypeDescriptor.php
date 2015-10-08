<?php 
namespace Mouf\Reflection;

use Mouf\MoufInstanceDescriptor;

use Mouf\MoufTypeParserException;

/**
 * This class represents one type.
 * 
 * Note: this class is Immutable (it cannot be changed after having been created)
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
	 * Returns true if the type is an array and it has a key defined.
	 *
	 * @return boolean
	 */
	public function isAssociativeArray() {
		return $this->keyType !== null;
	}
	
	/**
	 * Returns true if the type is an array.
	 *
	 * @return boolean
	 */
	public function isArray() {
		return $this->subType !== null;
	}

    /**
     * Parses the tokens (passed in reference!) and returns a TypeDescriptor for the parsed tokens.
     *
     *
     * @param array $tokens
     * @throws MoufTypeParserException
     * @return TypeDescriptor
     */
	public static function parseTokens(&$tokens) {
        do {
		    $typeArray = array_shift($tokens);
            if ($typeArray['token'] == "T_WHITESPACE"){
                continue;
            }
            else {
                break;
            }
        }while(true);

        if ($typeArray['token'] != 'T_TYPE') {
			throw new MoufTypeParserException("Invalid type! Expecting a type name. Got '".$typeArray['match']."'");
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
                    array_unshift($tokens, $nextToken);
					break;
				case 'T_START_ARRAY';
					$tok1 = self::getNthTokenWithoutWhitespace($tokens, 1);
					if ($tok1['token'] == 'T_COMA') {
						$tok0 = self::getNthTokenWithoutWhitespace($tokens, 0);
						if ($tok0['token'] != 'T_TYPE') {
							throw new MoufTypeParserException("Invalid type! Expecting a type name. Got ".$tokens['match']);
						}
						$type->keyType = $tok0['match'];
						// Let's shift twice, without counting whitespaces:
						while ($tokens[0] != $tok1) {
							array_shift($tokens);
						}
						array_shift($tokens);
						// TODO: with a state machine, we could handle whitespaces in a best way
						// TODO: develop a statemachine!
						while ($tokens[0]['token'] == 'T_WHITESPACE') {
							array_shift($tokens);
						}
						
					}
					$type->subType = TypeDescriptor::parseTokens($tokens);

                    while ($tokens[0]['token'] == 'T_WHITESPACE') {
                        array_shift($tokens);
                    }

                    if ($tokens[0]['token'] != 'T_END_ARRAY'){
                        throw new MoufTypeParserException("Invalid type! Expecting >. Got '".$tokens[0]['match']."'");
                    }

                    array_shift($tokens);

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
	 * Note: Once resolved, the qualified names always start with a \
	 * Returns a new TypeDescriptor (TypeDescriptor is an immutable class and therefore cannot be modified)
	 * 
	 * @param array<string, string> $useMap
	 * @param string $namespace
	 * @return TypeDescriptor
	 */
	public function resolveType($useMap, $namespace) {
		$newType = new self();
		$newType->type = $this->type;
		$newType->keyType = $this->keyType;
		
		if ($this->subType != null) {
			$newType->subType = $this->subType->resolveType($useMap, $namespace);
		}
		
		if ($newType->type == null || $newType->type == "array" || $newType->isPrimitiveType()) {
			return $newType;
		}
	
		$index = strpos($newType->type, '\\');
		if ($index === false) {
			if (isset($useMap[$newType->type])) {
				$newType->type = '\\'.$useMap[$newType->type];
				return $newType;
			} else {
				if ($namespace) {
					$newType->type = '\\'.$namespace.'\\'.$newType->type;
					return $newType;
				} else {
					$newType->type = '\\'.$newType->type;
					return $newType;
				}
			}
		}
		if ($index === 0) {
			// Starting with \. Already a fully qualified name.
			return $newType;
		}
		$leftPart = substr($newType->type, 0, $index);
		$rightPart = substr($newType->type, $index);
	
		if (isset($useMap[$leftPart])) {
			$newType->type = '\\'.$useMap[$leftPart].$rightPart;
			return $newType;
		} else {
			$newType->type = '\\'.$namespace.'\\'.$newType->type;
			return $newType;
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
	public function isPrimitiveType() {
		$lowerVarType = strtolower($this->type);
		return in_array($lowerVarType, array('string', 'char', 'bool', 'boolean', 'int', 'integer', 'double', 'float', 'real', 'mixed', 'number', 'null'));
	}
	
	/**
	 * Returns true if the type passed in parameter is primitive or an array of primitive
	 * type or an array of array of primitive type, etc...
	 *
	 * @param string $type
	 * @return bool
	 */
	public function isPrimitiveTypesOrRecursiveArrayOfPrimitiveTypes() {
		if ($this->isPrimitiveType()) {
			return true;
		}
		if ($this->subType) {
			return $this->subType->isPrimitiveTypesOrRecursiveArrayOfPrimitiveTypes();
		}
		return false;
	}
	
	/**
	 * Returns true if this type is compatible with the propertyDescriptor's value
	 * passed in parameter.
	 *
	 * @param string|array|MoufInstanceDescriptor|MoufInstanceDescriptor[] $instanceDescriptor
	 * @return bool
	 */
	public function isCompatible($value) {
		// If null, we are compatible
		if ($value === null) {
			return true;
		}
		
		// If the value passed is an array
		if (is_array($value)) {
			// Let's check if this type is an array.
			if (!$this->isArray()) {
				return false;
			}
			if (!$this->isAssociativeArray()) {
				// Let's check if the array passed in parameter has string values as keys.
				foreach ($value as $key=>$val) {
					if (is_string($key)) {
						return false;
					}
				}
			}
			// Now, let's test each subkey for compatibility
			foreach ($value as $key=>$val) {
				if (!$this->subType->isCompatible($val)) {
					return false;
				}
			}
			return true;
		} elseif ($value instanceof MoufInstanceDescriptor) {
			// Let's check if the instance descriptor is compatible with our type.
			if ($this->isPrimitiveType()) {
				return false;
			}
			if ($this->type == "array") {
				return false;
			}
			$classDescriptor = $value->getClassDescriptor();
			if (ltrim($this->type,'\\') == ltrim($classDescriptor->getName(), '\\')) {
				return true;
			}
			//$type = new MoufReflectionClass($this->getType());
			$result =  $classDescriptor->isSubclassOf($this->getType());
			return $result;
		} else {
			// The value is a primitive type.
			if ($this->isPrimitiveType()) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	/**
	 * Returns the nth token, skipping any whitespace.
	 * Return null, if $i is out of bounds.
	 * 
	 * @param array $tokens
	 * @param int $i position
	 */
	private static function getNthTokenWithoutWhitespace($tokens, $i) {
		$j = 0;
		foreach ($tokens as $token) {
			if ($token['token'] == 'T_WHITESPACE') {
				continue;
			}
			if ($i == $j) {
				return $token;
			}
			$j++;
		}
		return null;
	}
}
