<?php
/**
 * Created by PhpStorm.
 * User: Андрей Сергеевич
 * Date: 13.04.2020
 * Time: 15:31
 */
namespace app\commands;

use app\wss\SudokuServer;
use yii\console\Controller;

class SudokuController extends Controller
{
    public function actionStart(int $dimensions = 9, int $port = null):void
    {
        $server = new SudokuServer($dimensions);
        if ($port) {
            $server->port = $port;
        }
        $server->start();
    }
}
