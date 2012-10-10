<?php /* @var $this RepositorySourceController */
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
  ?>
<h1>Configured repositories</h1>

<?php
foreach ($this->repositoryUrls as $key=>$repUrl) {
?>
<div class="file">
<label>Name:</label> <?php echo $repUrl['name']; ?><br/>
<label>Url:</label> <?php echo $repUrl['url']; ?><br/>
<label>&nbsp;</label> <a href="edit?selfedit=<?php echo $this->selfedit ?>&id=<?php echo $key ?>">edit</a>
</div>
<?php 	
}
?>
<form action="add">
<input type="hidden" name="selfedit" value="<?php echo $this->selfedit ?>" />
<button>Add new repository</button>
</form>