<?php /* @var $this InstallController */ 
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
 

foreach ($this->actionsList as $actionDescriptor) {
	/* @var $actionDescriptor MoufActionDescriptor */
	try {
		if ($actionDescriptor->status != "error") {
			echo "<div class='".$actionDescriptor->status."'>".$actionDescriptor->getName()."</div>";
		} else {
			echo "<div class='".$actionDescriptor->status."'>";
			echo "Error: ".$actionDescriptor->getName()."<br/>";
			if ($this->exception != null) {
				UnhandledException($this->exception, true);
			}
			echo "</div>";
		}
	} catch (MoufInstanceNotFoundException $e) {
		// If we can't find the action provider, maybe it is not installed yet.
		// Let's just not display the name.
		echo "<div class='".$actionDescriptor->status."'>Install action provided by '".plainstring_to_htmlprotected($actionDescriptor->actionProviderName)."'...</div>";
	}
}

if ($this->done) {
	echo "<div class='good'>".$this->multiStepActionService->getConfirmationMessage()."</div>";
	echo "<p><a href='".plainstring_to_htmlprotected($this->multiStepActionService->getFinalUrlRedirect())."'>Continue</a></p>";
}