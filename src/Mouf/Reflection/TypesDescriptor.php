<?php 
namespace Mouf\Reflection;

use Mouf\MoufInstancePropertyDescriptor;

use Mouf\MoufInstanceDescriptor;

use Mouf\MoufTypeParserException;

use Mouf\MoufException;

/**
 * This class represents the potential types of a variable/parameter.
 * This class can represents many types at one.
 * For instance: @var MyInterface|array<MyInterface2>|null
 * 
 * Note: this class is Immutable (it cannot be changed after having been created)
 * 
 * @author David Négrier
 */
class TypesDescriptor {
	
	/**
	 * The list of detected types (as TypeDescriptor array)
	 * @var TypeDescriptor[]
	 */
	private $types = array();
	
	/**
	 * A local cache to avoid computing several times the same types.
	 * 
	 * @var array<string, TypesDescriptor>
	 */
	private static $localCache = array();
	
	/**
	 * An optional warning message.
	 * 
	 * @var string
	 */
	private $warningMessage;
	
	/**
	 * The list of detected types (as TypeDescriptor array)
	 * 
	 * @return \Mouf\Reflection\TypeDescriptor[]
	 */
	public function getTypes() {
		return $this->types;
	}
	
	/**
	 * 
	 * @param string $types
	 * @return TypesDescriptor
	 */
	public static function parseTypeString($types) {
		if (isset(self::$localCache[$types])) {
			return self::$localCache[$types];
		}
		self::$localCache[$types] = new self($types);
		return self::$localCache[$types];
	}
	
	public static function getEmptyTypes() {
		return self::parseTypeString(null);
	}
	
	private function __construct($types = null) {
		if (!empty($types)) {
			$this->analyze($types);
		}	
	}
	
	private function analyze($types) {
		try {
			$tokens = self::runLexer($types);
			
			while ($tokens) {
				if ($tokens[0]['token'] == 'T_OR') {
					array_shift($tokens);
				}
				
				$this->types[] = TypeDescriptor::parseTokens($tokens);
			}
		} catch (MoufTypeParserException $e) {
			throw new MoufTypeParserException("Error while parsing type string: '".$types."': ".$e->getMessage(), 0, $e);
		}
		
	}
	
	protected static $_terminals = array(
			"/^(<)/" => "T_START_ARRAY",
			"/^(>)/" => "T_END_ARRAY",
			"/^(\\[\\])/" => "T_ARRAY",
			"/^(\\|)/" => "T_OR",
			"/^(\s+)/" => "T_WHITESPACE",
			"/^(,)/" => "T_COMA",
			"/^([a-zA-Z_\\\\][a-zA-Z0-9_\\\\]*)/" => "T_TYPE",
	);
	
	protected static function _match($line, $offset) {
		$string = substr($line, $offset);
		 
		
		// TODO: optimise for speed: replace preg_match with $string{0}
		foreach(static::$_terminals as $pattern => $name) {
			if(preg_match($pattern, $string, $matches)) {
				return array(
						'match' => $matches[1],
						'token' => $name
				);
			}
		}
		
		return false;
	}
	
	/**
	 * Runs the analysis
	 * 
	 * @param string $source
	 * @throws \Exception
	 * @return array
	 */
	public static function runLexer($line) {
		$tokens = array();
		$offset = 0;
		while($offset < strlen($line)) {
			$result = static::_match($line, $offset);
			if($result === false) {
				throw new MoufException("Unable to parse line '".$line."'.");
			}
			$tokens[] = $result;
			$offset += strlen($result['match']);
		}
		 
		return $tokens;
	}
	
	/**
	 * Returns a PHP array representing the TypesDescriptor
	 * 
	 * @return array
	 */
	public function toJson() {
		$array = array("types"=>array());
		foreach ($this->types as $type) {
			$array["types"][] = $type->toJson();
		}
		if ($this->warningMessage) {
			$array["warning"] = $this->warningMessage;
		}
		return $array;
	}
	
	/**
	 * Give a fully qualified class name to all the types using declared "use" statements.
	 * Note: Once resulved, the qualified names always start with a \
	 * Returns a new TypesDescriptor (TypesDescriptor is an immutable class and therefore cannot be modified)
	 *
	 * @param array<string, string> $useMap
	 * @param string $namespace
	 * @return TypesDescriptor
	 */
	public function resolveType($useMap, $namespace) {
		$resolvedTypes = array(); 
		foreach ($this->types as $type) {
			/* @var $type TypeDescriptor */
			$resolvedTypes[] = $type->resolveType($useMap, $namespace);
		}
		$newTypes = new self();
		$newTypes->types = $resolvedTypes;
		$newTypes->warningMessage = $this->warningMessage;
		return $newTypes;
	}

	/**
	 * Returns the TypeDescriptor that is part of these types that is the most likely to fit the propertyDescriptor's value
	 * passed in parameter.
	 * Returns null if no type is compatible.
	 * 
	 * @param string|array|MoufInstanceDescriptor|MoufInstanceDescriptor[] $instanceDescriptor
	 * @return TypeDescriptor
	 */
	public function getCompatibleTypeForInstance($value) {
		foreach ($this->types as $type) {
			if ($type->isCompatible($value)) {
				return $type;
			}
		}
		return null;
	}
	
	/**
	 * Sets an optional warning message (if something seems wrong for these types).
	 * 
	 * @param string $warningMessage
	 */
	public function setWarningMessage($warningMessage) {
		$this->warningMessage = $warningMessage;
	}
	
	/**
	 * Returns the optional warning message (if something seems wrong for these types).
	 * 
	 * @return string
	 */
	public function getWarningMessage() {
		return $this->warningMessage;
	}
}