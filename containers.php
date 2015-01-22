<?php
return [
    [
        'name' => 'database.doctrine-dbal-wrapper-container',
        'description' => 'Container for instances of the doctrine-dbal-wrapper-container package',
        'factory' => Mouf\Doctrine\DBAL\DBALContainerFactory::factory,
        'enable' => true,
    ],
    [
        'name' => 'default',
        'description' => 'Default Admin container',
        'factory' => function($c) { return new Mouf\AdminContainer($c); },
        'enable' => true,
    ],
];
