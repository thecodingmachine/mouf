<?php 
namespace Mouf\Reflection;

/**
 * This class represents the potential types of a variable/parameter.
 * This class can represents many types at one.
 * For instance: @var MyInterface|array<MyInterface2>|null
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
	 * The list of detected types (as TypeDescriptor array)
	 * 
	 * @return \Mouf\Reflection\TypeDescriptor[]
	 */
	public function getTypes() {
		return $this->types;
	}
	
	public static function parseTypeString($types) {
		if (isset(self::$localCache[$types])) {
			return self::$localCache[$types];
		}
		self::$localCache[$types] = new self($types);
		return self::$localCache[$types];
	}
	
	private function __construct($types) {
		$this->analyze($types);
		
		// TODO: start by building a lexer!
		
	}
	
	private function analyze($types) {
		
		$tokens = self::runLexer($types);
		
		while ($tokens) {
			if ($tokens[0]['token'] == 'T_OR') {
				array_shift($tokens);
			}
			
			$this->types[] = TypeDescriptor::parseTokens($tokens);
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
				throw new Exception("Unable to parse.");
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
		$array = array();
		foreach ($this->types as $type) {
			$array[] = $type->toJson();
		}
		return $array;
	}
}