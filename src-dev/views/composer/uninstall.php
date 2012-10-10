<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

/* @var $this Mouf\Controllers\Composer\InstalledPackagesController */

use Mouf\Composer\PackageInterface;
?>
<h1>Uninstalling <?php echo $this->name; ?> - <?php echo $this->version; ?></h1>

<pre id="composeroutput"></pre>
<iframe id="tmploading" width="1" height="1" style="border: none" src="doUninstall?name=<?php echo $this->name; ?>&selfedit=<?php echo $this->selfedit; ?>"></iframe>

