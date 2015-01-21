#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';

use Mouf\RootContainer;
use Symfony\Component\Console\Application;

$console = RootContainer::get('console');
//$application->add(new RunCommand());
$console->run();