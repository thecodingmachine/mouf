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

// Fix autoloading that is broken for some reason...
require_once __DIR__.'/../../vendor/nikic/php-parser/lib/bootstrap.php';

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
	
		$parser = new \PhpParser\Parser(new \PhpParser\Lexer());
	
		//try {
		$stmts = $parser->parse($code);
		/*} catch (PhpParser\Error $e) {
		 echo 'Parse Error: ', $e->getMessage();
		}*/
	}
}
?>