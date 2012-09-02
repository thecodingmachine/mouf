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
<h1>Add/edit constant</h1>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#type").change(function() {
		if (jQuery("#type").val() == 'bool') {
			jQuery("#booldefaultvalue").show();
			jQuery("#booldefaultvalue").removeAttr("disabled");
			jQuery("#textdefaultvalue").hide();
			jQuery("#textdefaultvalue").attr("disabled", "true");
			jQuery("#boolvalue").show();
			jQuery("#boolvalue").removeAttr("disabled");
			jQuery("#textvalue").hide();
			jQuery("#textvalue").attr("disabled", "true");
		} else {
			jQuery("#booldefaultvalue").hide();
			jQuery("#booldefaultvalue").attr("disabled", "true");
			jQuery("#textdefaultvalue").show();
			jQuery("#textdefaultvalue").removeAttr("disabled");
			jQuery("#boolvalue").hide();
			jQuery("#boolvalue").attr("disabled", "true");
			jQuery("#textvalue").show();
			jQuery("#textvalue").removeAttr("disabled");
		}
	});
});
</script>

<form action="registerConstant" method="post">
<input type="hidden" name="selfedit" id="selfedit" value="<?php echo $this->selfedit; ?>" />
<?php 
if ($this->type == "bool") {
	$hideBool = "";
	$hideText = " style='display:none' disabled='true' ";
} else {
	$hideText = "";
	$hideBool = " style='display:none' disabled='true' ";
}

?>
<div>
<label>Name:</label>
<input name="name" value="<?php echo plainstring_to_htmlprotected($this->name); ?>" />
</div>

<div>
<label>Type:</label>
<select id="type" name="type">
	<option value="string" <?php if ($this->type == "string") echo "selected='selected'"; ?>>String</option>
	<option value="float" <?php if ($this->type == "float") echo "selected='selected'"; ?>>Float</option>
	<option value="int" <?php if ($this->type == "int") echo "selected='selected'"; ?>>Integer</option>
	<option value="bool" <?php if ($this->type == "bool") echo "selected='selected'"; ?>>Boolean</option>
</select>
</div>

<div>
<label>Default value:</label>
<input id="booldefaultvalue" <?php echo $hideBool ?> type="checkbox" name="defaultvalue" value="true" <?php echo $this->defaultvalue?"checked='checked'":""; ?> />
<input id="textdefaultvalue" <?php echo $hideText ?> name="defaultvalue" value="<?php echo plainstring_to_htmlprotected($this->defaultvalue); ?>" />
</div>

<div>
<label>Value:</label>
<input id="boolvalue" <?php echo $hideBool ?> type="checkbox" name="value" value="true" <?php echo $this->value?"checked='checked'":""; ?> />
<input id="textvalue" <?php echo $hideText ?> name="value" value="<?php echo plainstring_to_htmlprotected($this->value); ?>" />
</div>

<div>
<label>Comments:</label>
<textarea name="comment"><?php echo plainstring_to_htmlprotected($this->comment); ?></textarea>
</div>

<?php // Type ?>

<div>
<button type="submit">Save</button>
<a href=".">Cancel</button>
</div>
</form>