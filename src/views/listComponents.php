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
<h1>Available component instances</h1>

<?php
if (is_array($this->moufManager->getInstancesList())) {
	foreach ($this->moufManager->getInstancesList() as $key=>$value) {
	
		echo "<a href='".ROOT_URL."mouf/mouf/displayComponent?name=".plainstring_to_urlprotected($key)."&selfedit=".$this->selfedit."'>";
		echo plainstring_to_htmlprotected($key);
		echo "</a> - ".plainstring_to_htmlprotected($value)."<br/>";
	}
}
?>