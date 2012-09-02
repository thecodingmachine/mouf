<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 
?>
<b>Search</b>
<form action="<?php echo ROOT_URL?>mouf/search">
	<input type="text" name="query" value="<?php echo plainstring_to_htmlprotected(get("query")); ?>" />
	<input type="hidden" name="selfedit" value="<?php echo plainstring_to_htmlprotected(get("selfedit")); ?>" />
	<button type="submit">Go</button>
</form>