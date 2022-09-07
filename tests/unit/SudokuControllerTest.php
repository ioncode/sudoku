<?php

namespace unit;

use app\commands\SudokuController;
use app\wss\DimensionException;
use TypeError;
use Yii;

class SudokuControllerTest extends \Codeception\Test\Unit
{

    public function testActionStart(): void
    {
        $controller = Yii::$app->createControllerByID('sudoku');
        self::assertTrue(is_a($controller, SudokuController::class));
        try {
            $controller->run('start', ['Bad Argument']);
        } catch (\Throwable $throwable) {
            self::assertTrue(is_a($throwable, TypeError::class));
        }

        try {
            $controller->run('start', [3]);
        } catch (\Throwable $throwable) {
            self::assertTrue(is_a($throwable, DimensionException::class));
        }
    }
}
