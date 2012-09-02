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
<h1>Add/edit repository</h1>

<form action="save">
<input type="hidden" name="selfedit" value="<?php echo $this->selfedit ?>" />
<?php if ($this->repositoryId !== null) { ?>
<input type="hidden" name="id" value="<?php echo $this->repositoryId ?>" />
<?php } ?>

<div>
<label>Repository name:</label>
<input type="text" name="name" value="<?php if ($this->repositoryId !== null) echo plainstring_to_htmlprotected($this->repositoryUrls[$this->repositoryId]["name"]) ?>" />
</div>

<div>
<label>Repository URL:</label>
<input type="text" name="url" value="<?php if ($this->repositoryId !== null) echo plainstring_to_htmlprotected($this->repositoryUrls[$this->repositoryId]["url"]) ?>" />
</div>

<?php if ($this->repositoryId !== null) { ?>
<button type="submit" name="delete" value="on">Delete</button>
<?php } ?>
<button type="submit" name="save" value="on">Save</button>
</form>