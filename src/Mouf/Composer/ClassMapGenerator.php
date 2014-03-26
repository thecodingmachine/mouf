<?php

/*
 * This file is copied from the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license MIT
 */

namespace Mouf\Composer;

use Symfony\Component\Finder\Finder;

/**
 * ClassMapGenerator
 *
 * @author Gyula Sallai <salla016@gmail.com>
 */
class ClassMapGenerator
{
    /**
     * Generate a class map file
     *
     * @param Traversable $dirs Directories or a single path to search in
     * @param string      $file The name of the class map file
     */
    public static function dump($dirs, $file)
    {
        $maps = array();

        foreach ($dirs as $dir) {
            $maps = array_merge($maps, static::createMap($dir));
        }

        file_put_contents($file, sprintf('<?php return %s;', var_export($maps, true)));
    }

    /**
     * Iterate over all files in the given directory searching for classes
     *
     * @param \Iterator|string $path      The path to search in or an iterator
     * @param string          $whitelist Regex that matches against the file path
     *
     * @return array A class map array
     *
     * @throws \RuntimeException When the path is neither an existing file nor directory
     */
    public static function createMap($path, $whitelist = null)
    {
        if (is_string($path)) {
            if (is_file($path)) {
                $path = array(new \SplFileInfo($path));
            } elseif (is_dir($path)) {
                $path = Finder::create()->files()->followLinks()->name('/\.(php|inc)$/')->in($path);
            } else {
                throw new \RuntimeException(
                    'Could not scan for classes inside "'.$path.
                    '" which does not appear to be a file nor a folder'
                );
            }
        }

        $map = array();

        foreach ($path as $file) {
            $filePath = $file->getRealPath();

            if (!in_array(pathinfo($filePath, PATHINFO_EXTENSION), array('php', 'inc'))) {
                continue;
            }

            if ($whitelist && !preg_match($whitelist, strtr($filePath, '\\', '/'))) {
                continue;
            }

            $classes = self::findClasses($filePath);

            foreach ($classes as $class) {
                $map[$class] = $filePath;
            }
        }

        return $map;
    }

    /**
     * Extract the classes in the given file
     *
     * @param string $path The file to check
     *
     * @return array The found classes
     */
    private static function findClasses($path)
    {
        $contents = file_get_contents($path);
        try {
        	//$nbResults = preg_match_all('{\b(?:class|interface|trait)\b}i', $contents);
        	//$nbResults = preg_match_all('{\b(?:class)\b}i', $contents, $results, PREG_OFFSET_CAPTURE);
        	
        	
        	/*$nbResults = preg_match_all('{\b(?:class)\b}i', $contents);
            if ($nbResults == 0) {
                return array();
            }*/
        	
            // Let's trim the content after the last "class" keyword line.
            $classKeywordPos = strrpos($contents, 'class');
            if ($classKeywordPos === false) {
            	return array();
            }
            //$classKeywordPos = $results[0][count($results[0])-1][1];
            
            // Jump 2 newlines after the last class keyword.
            $newLinePos = strpos($contents, "\n", $classKeywordPos+6);
            if ($newLinePos) {
	            $newLinePos = strpos($contents, "\n", $newLinePos+1);
	            if ($newLinePos) {
	            	$contents = substr($contents, 0, $newLinePos);
	            }
            }
            
            // Let's ignore any warning because we are cutting in the middle of a PHP file and we might cut in a 
            // comment.
            $tokens = @token_get_all($contents);
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not scan for classes inside '.$path.": \n".$e->getMessage(), 0, $e);
        }
        $T_TRAIT  = version_compare(PHP_VERSION, '5.4', '<') ? -1 : T_TRAIT;

        $classes = array();

        $namespace = '';
       	
        for ($i = 0, $max = count($tokens); $i < $max; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                continue;
            }

            $class = '';

            switch ($token[0]) {
                case T_NAMESPACE:
                    $namespace = '';
                    // If there is a namespace, extract it
                    while (($t = $tokens[++$i]) && is_array($t)) {
                        if (in_array($t[0], array(T_STRING, T_NS_SEPARATOR))) {
                            $namespace .= $t[1];
                        }
                    }
                    $namespace .= '\\';
                    break;
                case T_CLASS:
                //case T_INTERFACE:
                //case $T_TRAIT:
                    // Find the classname
            		
                    while (($t = $tokens[++$i]) && is_array($t)) {
                    	if (T_STRING === $t[0]) {
                            $class .= $t[1];
                        } elseif ($class !== '' && T_WHITESPACE == $t[0]) {
                            break;
                        }
                    }

                    $classes[] = ltrim($namespace . $class, '\\');
                    /*if ($nbResults == 1) {
                    	// Optim: if there is only one "class" keyword in the file, there is only one class, and we have it!
                    	return $classes;
                    }*/
                    break;
                default:
                    break;
            }
        }
        

        return $classes;
    }
}
