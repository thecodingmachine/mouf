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
<div class="searchbox">
<b>Search</b>
<form action="<?php echo ROOT_URL?>search/" class="form-search">
	<div class="input-append">
		<input type="text" name="query" value="<?php echo plainstring_to_htmlprotected(get("query")); ?>" class="input-medium search-query" placeholder="Search" />
		<button type="submit" class="btn btn-danger">Go</button>
	</div>
	<input type="hidden" name="selfedit" value="<?php echo plainstring_to_htmlprotected(get("selfedit")); ?>" />
</form>
</div>