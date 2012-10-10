<?php
namespace Mouf\Composer;

/**
 * This class contains utility fonction to send "chuncked" HTTP responses.
 * These are useful to send "slow loading" iframes and other comet techniques.
 * 
 * @author David Négrier
 */
class ChunckedUtils {

	/**
	 * Sends the "chuncked" header to the browser announcing we will be sending chuncks.
	 */
	public static function init() {
		header("Transfer-Encoding: chunked");
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);
		@ini_set('implicit_flush', 1);
		for ($i = 0; $i < ob_get_level(); $i++)  ob_end_flush();
		ob_implicit_flush(1); flush();
		
		$pad = str_pad('',4096);
		echo dechex(strlen($pad))."\r\n";
		echo $pad;
		echo "\r\n";
	}
	
	/**
	 * Sends to the output a chunk.
	 * 
	 * @param string $chunk
	 */
	public static function writeChunk($chunk) {
		echo dechex(strlen($chunk))."\r\n";
		echo $chunk;
		echo "\r\n";
		flush();
	}
	
	/**
	 * Sends the last statement explaining to the browser we won't send anything afterwards.
	 */
	public static function close() {
		echo "0\r\n\r\n";
		flush();
	}
}