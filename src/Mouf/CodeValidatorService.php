<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2014 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf;

use PhpParser\Error;
use PhpParser\ParserFactory;

/**
 * This class is used to validate the PHP code that will be used as a callback
 * in the container. 
 *
 */
class CodeValidatorService {
	/**
	 * This function will throw a PhpParser\Error exception if a parsing error is met in the code.
	 *
	 * @param string $codeString
	 * @throws \PhpParser\Error
	 */
	public static function validateCode($codeString) {
		$code = "<?php \$a = function(ContainerInterface \$container) { ".$codeString."\n}\n?>";
	
		$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
	
		$stmts = $parser->parse($code);

		// If we are here, the code is correct.
		// Let's add a last check: whether there is a "return" keyword or not.
		if (stripos($code, "return") === false) {
			throw new Error("Missing 'return' keyword.", count(explode("\n", $codeString)));
		}
	}
}
