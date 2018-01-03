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

<form action="registerConstant" method="post" class="form-horizontal">
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
<div class="control-group">
<label class="control-label">Name:</label>
<div class="controls">
	<input name="name" type="text" value="<?php echo plainstring_to_htmlprotected($this->name); ?>" placeholder="Constant name" />
</div>
</div>

<div class="control-group">
<label class="control-label">Type:</label>
<div class="controls">
<select id="type" name="type">
	<option value="string" <?php if ($this->type == "string") echo "selected='selected'"; ?>>String</option>
	<option value="float" <?php if ($this->type == "float") echo "selected='selected'"; ?>>Float</option>
	<option value="int" <?php if ($this->type == "int") echo "selected='selected'"; ?>>Integer</option>
	<option value="bool" <?php if ($this->type == "bool") echo "selected='selected'"; ?>>Boolean</option>
</select>
</div>
</div>

<div class="control-group">
<label class="control-label">Default value:</label>
<div class="controls">
	<input id="booldefaultvalue" <?php echo $hideBool ?> type="checkbox" name="defaultvalue" value="true" <?php echo $this->defaultvalue?"checked='checked'":""; ?> />
	<input id="textdefaultvalue" <?php echo $hideText ?> name="defaultvalue" type="text" value="<?php echo plainstring_to_htmlprotected($this->defaultvalue); ?>" />
</div>
</div>

<div class="control-group">
<label class="control-label">Value:</label>
<div class="controls">
	<input id="boolvalue" <?php echo $hideBool ?> type="checkbox" name="value" value="true" <?php echo $this->value?"checked='checked'":""; ?> />
	<input id="textvalue" <?php echo $hideText ?> name="value" type="text" value="<?php echo plainstring_to_htmlprotected($this->value); ?>" />
</div>
</div>

<div class="control-group">
<label class="control-label">Comments:</label>
<div class="controls">
	<textarea name="comment"><?php echo plainstring_to_htmlprotected($this->comment); ?></textarea>
</div>
</div>

<div class="control-group">
    <label class="control-label">Fetch from environment variable:</label>
    <div class="controls">
        <input type="checkbox" name="fetchFromEnv" value="true" <?php echo $this->fetchFromEnv?"checked='checked'":""; ?> />
        <span class="help-block">Environment variables are used in priority over the stored configuration. Configuration is used as a fallback if environement variable is not set.</span>
    </div>
</div>

<?php // Type ?>

<div class="control-group">
<div class="controls">
<button type="submit" class="btn btn-primary">Save</button>
<a href="." class="btn">Cancel</a>
</div>
</div>
</form>