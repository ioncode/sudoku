<?php

use AbcAeffchen\sudoku\Sudoku;
use app\wss\DimensionException;
use app\wss\SudokuServer;
use Codeception\Test\Unit;

class SudokuServerTest extends Unit
{

    public function testParentClassExists()
    {
        $this->assertTrue(class_exists(Sudoku::class));
    }

    public function testInit()
    {
        $this->assertTrue(class_exists(SudokuServer::class));
        $server = false;
        try {
            $server = new SudokuServer(10);
        } catch (Throwable $dimensionException) {
            $this->assertTrue(is_a($dimensionException, DimensionException::class));
        }
        $this->assertEmpty($server, 'Server protected from wrong initial dimensions');

        //$server->start();

    }

    public function testAddToTop()
    {

    }

    public function testGetTop()
    {

    }

    public function testSetCellValue()
    {

    }

    public function testBroadcast()
    {

    }
}
