<?php 
use Mouf\Installer\AbstractInstallTask;

/* @var $this Mouf\Controllers\InstallController */
?>
<script type="text/javascript">
$(document).ready(function() {
	$("table.table button").click(function() {
		return confirm("Are you sure you want to perform this action? It is usually wiser to use the 'Run all install processes' button at the top of the screen.");
	});
});
</script>

<h1>Install tasks</h1>

<?php if ($this->installs) { ?>
<form action="install" method="post">
	<input type="hidden" id="selfedit" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit) ?>" />
	<button class="btn btn-success btn-large"><i class="icon-white icon-chevron-right"></i> Run all install processes</button>
</form>

<table class="table">
	<tr>
		<th style="width: 30%">Package</th>
		<th style="width: 40%">Description</th>
		<th style="width: 15%">Status</th>
		<th style="width: 15%">Action</th>
	</tr>
	<?php foreach ($this->installs as $installTask): 
		/* @var $installTask AbstractInstallTask */
	?>	
	<tr>
		<td><?php echo plainstring_to_htmlprotected($installTask->getPackage()->getName()); ?></td>
		<td><?php echo plainstring_to_htmlprotected($installTask->getDescription()); ?></td>
		<td><?php 
		if ($installTask->getStatus()==AbstractInstallTask::STATUS_TODO) {
			echo '<i class="icon-time"></i> Awaiting installation';
		} elseif ($installTask->getStatus()==AbstractInstallTask::STATUS_DONE) {
			echo '<i class="icon-ok"></i> Done';
		} else {
			echo plainstring_to_htmlprotected($installTask->getStatus());
		}
		?>
		</td>
		<td>
		<form action="install" method="post" style="margin: 0px">
		<input type="hidden" id="selfedit" name="selfedit" value="<?php echo plainstring_to_htmlprotected($this->selfedit) ?>" />
		<input type="hidden" name="task" value="<?php echo plainstring_to_htmlprotected(serialize($installTask->toArray())); ?>">
		<button class="btn btn-success"><i class="icon-white icon-chevron-right"></i> 
		<?php 
		if ($installTask->getStatus()=="todo") { 
			echo "Manual install";  
		} else {
			echo "Reinstall";
		}
		?></button>
		</form>
		</td>
	</tr>
	<?php endforeach; ?>
</table>

<?php } else { ?>
	No installed packages have install processes
<?php } ?>