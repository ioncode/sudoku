<?php

namespace unit;

use app\wss\DimensionException;
use app\wss\SudokuServer;
use PHPUnit\Framework\TestCase;
use Yii;


class DimensionExceptionTest extends TestCase
{
    public function test__runTest()
    {
        $this->expectExceptionObject(new DimensionException('Dimensions 10 must be dividable 3: 1'));
        new SudokuServer(10);
    }

}
