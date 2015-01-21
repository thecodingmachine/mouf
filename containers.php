<?php
return [
    [
        'name' => 'default',
        'description' => 'Default Admin container',
        'factory' => function($c) { return new Mouf\AdminContainer($c); },
        'enable' => true,
    ],
];
