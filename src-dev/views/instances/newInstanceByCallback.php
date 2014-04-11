<?php 
/* @var $this Mouf\Controllers\MoufController */

?>
<div id="messages"></div>


<form id="createInstanceForm" class="form-horizontal" method="post" action="createInstanceByCode">
<input type="hidden" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit); ?>" />


<legend>Create a new instance using PHP code</legend>

<div class="control-group">
	<label for="instanceName" class="control-label">Instance name:</label>
	<div class="controls">
		<input type="text" name="instanceName" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" placeholder="Your instance name" required />
	</div>
</div>

<div class="control-group">
	<div class="controls">
		<button type="submit" value="Create" class="btn btn-danger">Create</button>
	</div>
</div>

</form>

<div class="row">
	<div class="span12">
		<div class="alert">
			Although it is usually a better idea to declare an instance using the <a href="newInstance2">web based UI</a>,
			it is not always possible to use the UI to instanciate all classes. 
			
			<ul>
			<li>Sometimes, a third-party package will force you to use a factory to create
			an instance.</li>
			<li>Sometimes, the annotations in the class are not good enough for Mouf to provide
			a helpful UI.</li>
			<li>Sometimes, you want to inject a value that is computed using complex code...</li>
			</ul>
			
			For all those cases, you can use the <strong>instance declaration via PHP code</strong>.
		</div>
	</div>
</div>
