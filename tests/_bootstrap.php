<?php

// This is global bootstrap for autoloading

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config.php');
//
$application = new yii\console\Application( $config );
