<?php
/**
 * Created by PhpStorm.
 * User: Андрей Сергеевич
 * Date: 13.04.2020
 * Time: 15:29
 */
namespace app\wss;

use consik\yii2websocket\events\WSClientMessageEvent;
use consik\yii2websocket\events\WSClientEvent;
use consik\yii2websocket\WebSocketServer;
use Yii;

class SudokuServer extends WebSocketServer
{
    public $dimensions = 9;
    public function init()
    {
        parent::init();

        // build sudoku 9*9 field with random initial data

        $this->on(self::EVENT_CLIENT_MESSAGE, function (WSClientMessageEvent $e) {
            echo 'Message from client: '.$e->message.PHP_EOL;
            $e->client->send( $e->message );
        });
        $this->on(self::EVENT_CLIENT_CONNECTED, function (WSClientEvent $e) {
            echo 'Client connected to game:'.PHP_EOL;
            if ($this->currentMatrix){
                echo 'We allready have started game, send it to new client'.PHP_EOL;
                echo 'After this connection we have '.count($this->clients).' clients online:'.PHP_EOL;
            }
            else {
                echo 'First client, the game is not generated, let me do it, if no one cached before expired'.PHP_EOL;
                if(Yii::$app->cache->exists('sudokuMatrix')){
                    echo 'Found cached game, let me load it'.PHP_EOL;
                    $this->currentMatrix = json_decode(Yii::$app->cache->get('sudokuMatrix'), true);
                }else{
                    $this->generate();
                    Yii::$app->cache->set( 'sudokuMatrix', json_encode($this->currentMatrix),3600);
                }


            }
            $e->client->send(json_encode(['matrix'=>$this->currentMatrix]));
        });
    }

    private function generate(){
        for ($column = 1; $column <= $this->dimensions; $column++){
            for ($row = 1; $row <= $this->dimensions; $row++){
                    echo 'Generating value for ['.$column.':'.$row.']'.PHP_EOL;
                    $candidate=mt_rand(1,9);
                    $attempt=$this->setCellValue($column, $row, $candidate);
                    //print_r($attempt);
                    if ($attempt['isAcceptableValue']){
                        echo $candidate.' set for ['.$column.':'.$row.']'.PHP_EOL;
                    }
                    else {
                        echo $candidate.' declined for ['.$column.':'.$row.'], reason: '.$attempt['message'].PHP_EOL;
                        $this->currentMatrix[$column][$row]=0;
                    }
            }
        }
        echo 'Generation done'.PHP_EOL;
        //print_r($this->currentMatrix);
    }

    private $currentMatrix = [];

    /**
     * @param $column
     * @param $row
     * @param $value
     * @return array
     */
    private function setCellValue($column, $row, $value){
        echo 'Processing ['.$column.':'.$row.'] attempt with value '.$value.PHP_EOL;
        $cell=false;
        if (!$column or !$row or $column > $this->dimensions or $row > $this->dimensions or !$value){
            return ['isAcceptableValue'=>$cell, 'message'=>'Value or coordinates unacceptable'];
        }
        // search in row equal values
        // $column_ not the same as $column param, this is internal iterator
        for ($column_ = 1; $column_ <= $this->dimensions; $column_++){
            if (empty($this->currentMatrix[$column_][$row])){
                //echo 'Empty ['.$column_.':'.$row.']'.PHP_EOL;
                continue;
            }
            if ($this->currentMatrix[$column_][$row] == $value){
                return ['isAcceptableValue'=>$cell, 'message'=>'In row '.$row.' we already have value '.$value];
            }
        }
        // search in column equal values
        // $row_ not the same as $row param, this is internal iterator
        //echo 'Searching value '.$value.' in column '.$column.PHP_EOL;
        for ($row_ = 1; $row_ <= $this->dimensions; $row_++){
            if (empty($this->currentMatrix[$column][$row_])){
                //echo 'Empty ['.$column.':'.$row_.']'.PHP_EOL;
                continue;
            }
           // echo 'Compare with value '.$this->currentMatrix[$column][$row_].' in row '.$row_.PHP_EOL;
            if ($this->currentMatrix[$column][$row_] == $value){
                return ['isAcceptableValue'=>$cell, 'message'=>'In column '.$column.' we already have value '.$value];
            }
        }

        // find block of 3*3 by coordinates and check value in this block
        $maxBlockCoordinates=$this->dimensions/3;
        //detect block column & row index
        // 1 2 3
        // 4 5 6
        // 7 8 9
        $blockColumn=ceil($column/$maxBlockCoordinates);
        $blockRow=ceil($row/$maxBlockCoordinates);
        echo 'Checking equal values in block ['.$blockColumn.':'.$blockRow.']'.PHP_EOL;
        for ($column_ = $blockColumn*$maxBlockCoordinates-2; $column_ <= $blockColumn*$maxBlockCoordinates; $column_++){
            for ($row_ = $blockRow*$maxBlockCoordinates-2; $row_ <= $blockRow*$maxBlockCoordinates; $row_++) {
                if (empty($this->currentMatrix[$column_][$row_])) {
                    //echo 'Empty [' . $column_ . ':' . $row . '] in block processing' . PHP_EOL;
                    continue;
                }
                if ($this->currentMatrix[$column_][$row_] == $value) {
                    return ['isAcceptableValue' => $cell, 'message' => 'In block ['.$blockColumn.':'.$blockRow.'] we already have value ' . $value];
                }
            }
        }

        $this->currentMatrix[$column][$row]=$value;
        return ['isAcceptableValue'=>true, 'message'=>'Value successfully set'];

    }


}