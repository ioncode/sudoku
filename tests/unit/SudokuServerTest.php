<?php

use AbcAeffchen\sudoku\Sudoku;
use app\wss\DimensionException;
use app\wss\SudokuServer;
use Codeception\Test\Unit;

class SudokuServerTest extends Unit
{

    public function testParentClassExists(): void
    {
        $this->assertTrue(class_exists(Sudoku::class));
    }

    public function testInit(): void
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

    public function testGenerate(): void
    {
        $server = new SudokuServer();
        $server->generate();
        $this->assertNotFalse(Sudoku::solve($server->currentMatrix, true));
    }

    public function testAddToTop(): void
    {
        $server = new SudokuServer();
        self::assertTrue(Yii::$app->cache->delete('sudokuTop'));
        $server->generate();
        $player = 'Winner_' . mt_rand(1, 33);
        $server->addToTop($player);
        $top = json_decode(Yii::$app->cache->get('sudokuTop'), true);
        $this->assertTrue(array_key_exists($player, $top));

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
