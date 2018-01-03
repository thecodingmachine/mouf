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
<h1>Configuration</h1>

<script type="text/javascript">
function deleteConstant(name) {
	jQuery("#deleteName").val(name);
	jQuery("#deleteConstant").submit();
}
</script>

<form action="saveConfig" method="post" class="form-horizontal">
<input type="hidden" name="selfedit" id="selfedit" value="<?php echo $this->selfedit; ?>" />
<?php

if (!empty($this->constantsList)) {
	foreach ($this->constantsList as $key=>$def) {
		echo '<div class="constant">';
		echo '<div>';
		if (!$def["defined"]) {
			// TODO: correctly display bool
			echo "<div class='warning'>This constant '".plainstring_to_htmlprotected($key)."' is present in the <code>config.php</code> file but not declared in Mouf. <a href='register?name=".urlencode($key)."'>Please declare this value</a>.</div>";
		}
		if (isset($def['missinginconfigphp'])) {
			echo "<div class='warning'>This constant '".plainstring_to_htmlprotected($key)."' is declared in Mouf but not defined in the <code>config.php</code> file.
			Please choose a value, and click the Save button to add it to the <code>config.php</code> file.</div>";
		}
		echo '</div>';
		echo '<div class="control-group">';
		//echo '<input type="text" value="'.plainstring_to_htmlprotected($key).'" /> => ';
		echo '<label class="control-label">'.plainstring_to_htmlprotected($key).'</label>';
		echo '<div class="controls">';
		if (isset($def['type']) && $def['type'] == 'bool') {
			$val = isset($def['value'])?$def['value']:$def['defaultValue'];
			echo '<input type="checkbox" name="'.plainstring_to_htmlprotected($key).'" value="true" '.(($val==true)?"checked='checked' ":"").' />';
		} else {
			echo '<input type="text" name="'.plainstring_to_htmlprotected($key).'" value="'.plainstring_to_htmlprotected(isset($def['value'])?$def['value']:$def['defaultValue']).'" />';
		}
		echo "<a href='register?name=".urlencode($key)."'><img src='".ROOT_URL."vendor/mouf/famfamfam/icons/pencil.png' alt='Edit' /></a>";
		echo "<img src='".ROOT_URL."vendor/mouf/famfamfam/icons/cross.png' alt='Delete' onclick='deleteConstant(\"".plainstring_to_htmlprotected($key)."\")' />";
		echo '<span class="help-block">';
		if ($def["defined"]) {
			echo nl2br($def['comment'])."<br/>";
			if ($def['type'] == 'bool') {
				echo "<em>Default value: '".($def['defaultValue']?"true":"false")."'.</em>";
			} else {
				echo "<em>Default value: '".plainstring_to_htmlprotected($def['defaultValue'])."'.</em>";
			}
			echo '<br/>Fetch from environment variables (in priority): ';
			if (isset($def['fetchFromEnv']) && $def['fetchFromEnv']) {
                echo '<span class="label label-success">Yes</span>';
            } else {
			    echo '<span class="label label-important">No</span>';
            }
		}
		echo '</span>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}
} else {
	echo "No config parameters defined.";
}
?>
<div>
<button type="submit" class="btn btn-primary">Save</button>
<a href="register?selfedit=<?php echo $this->selfedit ?>" class="btn btn-danger">Add a new constant</a>
</div>
</form>

<form id="deleteConstant" action="deleteConstant" method="post">
<input id="deleteName" type="hidden" name="name" />
</form>