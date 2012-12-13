<?php 
/* @var $this Mouf\Controllers\InstallController */
?>
<h1>Install tasks</h1>

<table class="table">
	<tr>
		<th>Package</th>
		<th>Description</th>
		<th>Status</th>
		<th>Action</th>
	</tr>
	<?php foreach ($this->installs as $installTask): ?>
	<tr>
		<td><?php echo plainstring_to_htmlprotected($installTask->getPackage()->getName()); ?></td>
		<td><?php echo plainstring_to_htmlprotected($installTask->getDescription()); ?></td>
		<td><?php echo plainstring_to_htmlprotected($installTask->getStatus()); ?></td>
		<td></td>
	</tr>
	<?php endforeach; ?>
</table>