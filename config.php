<?php

return [
    'id'                  => 'sudoku',
    'basePath'            => __DIR__ . '/',
    'controllerNamespace' => 'app\commands',
    'components'          => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
];

