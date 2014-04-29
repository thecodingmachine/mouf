<?php 
use Mouf\Installer\AbstractInstallTask;

/* @var $this Mouf\Controllers\InstallController */

// Let's manage the position of the scrollbar in the page when it is displayed.
// We want to print the last 5 packages installed and the upcoming packages:
$nextTodoPosition = 0;
foreach ($this->installs as $installTask) {
	if ($installTask->getStatus()==AbstractInstallTask::STATUS_TODO) {
		break;
	};
	$nextTodoPosition++;
}

$count = 0;
?>
<h1>Processing installation</h1>

<table class="table">
	<tr>
		<th style="width: 35%">Package</th>
		<th style="width: 45%">Description</th>
		<th style="width: 20%">Status</th>
	</tr>
	<?php foreach ($this->installs as $installTask): 
		/* @var $installTask AbstractInstallTask */
	?>	
	<tr>
		<td><?php echo plainstring_to_htmlprotected($installTask->getPackage()->getName()); ?></td>
		<td><?php echo plainstring_to_htmlprotected($installTask->getDescription()); ?></td>
		<td><?php
		$count++;
		if ($count - 5 == $nextTodoPosition) {
			echo '<a name="toinstall"></a>';
		}
		
		if ($installTask->getStatus()==AbstractInstallTask::STATUS_TODO) {
			echo '<i class="icon-time"></i> Awaiting installation';
		} elseif ($installTask->getStatus()==AbstractInstallTask::STATUS_DONE) {
			echo '<i class="icon-ok"></i> Done';
		} else {
			echo plainstring_to_htmlprotected($installTask->getStatus());
		}
		?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>

<script type="text/javascript">
$("document").ready(function() {
	window.location.href = "<?php echo MOUF_URL."installer/processInstall?selfedit=".$this->selfedit; ?>";
});
</script>