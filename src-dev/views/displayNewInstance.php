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
<form action="createComponent" method="post">
<input type="hidden" name="selfedit" value="<?php echo plainstring_to_htmlprotected($selfedit); ?>" />


<h1>Create a new instance</h1>

<div>
<label for="instanceName">Instance name:</label><input type="text" name="instanceName" value="<?php echo plainstring_to_htmlprotected($instanceName) ?>" />
</div>

<div>
<label for="instanceClass">Class:</label>
<select name="instanceClass">
<?php 
foreach ($componentsList as $component) {
	echo "<option value='$component'";
	if ($instanceClass==$component) {
		echo "selected='selected'";	
	}
	echo ">$component</option>\n";
}
?>
</select>
</div>

<input type="submit" value="Create" />

</form>