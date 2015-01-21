<?php
/*
 * This file is part of the Mouf core package.
 *
 * (c) 2012-2015 David Negrier <david@mouf-php.com>
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */
namespace Mouf\Commands;

use Symfony\Component\Console\Application;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerAwareInterface;
use Symfony\Component\Console\Command\Command;
use Mouf\MoufException;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerBuilder;

/**
 * This class is the Symfony based console application that can use EmbeddedComposer.
 *
 */
class ConsoleApplication extends Application implements
		EmbeddedComposerAwareInterface {

	/**
	 * Simple alias to 'addCommands' so that we can use the Mouf UI.
	 * @param Command[] $commands
	 */
	public function setCommands(array $commands) {
		$this->addCommands($commands);
	}

	public function getEmbeddedComposer() {
		// Check where autoload would be
		
		if (!$classLoader = @include __DIR__.'/../../../vendor/autoload.php') {
			throw new MoufException('You must set up the project dependencies. Did you skip plugins when installing Mouf vie Composer?');
		}
		
		$embeddedComposerBuilder = new EmbeddedComposerBuilder(
				$classLoader,
				__DIR__.'/../../../'
		);
		
		$embeddedComposer = $embeddedComposerBuilder
			->setComposerFilename('../../../composer-harmony-dependencies.json')
			->setVendorDirectory('vendor-harmony')
			->build();
			
		return $embeddedComposer;
	}

}
?>