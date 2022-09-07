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
use Ratchet\ConnectionInterface;
use Yii;
use AbcAeffchen\sudoku\Sudoku;

class SudokuServer extends WebSocketServer
{
    protected int $dimensions;

    public function __construct(int $dimensions = 9, array $config = [])
    {
        if ($dimensions < 6 or ($dimensions % 3 !== 0)) {
            throw new DimensionException('Dimensions ' . $dimensions . ' must be dividable 3: ' . $dimensions % 3);
        }
        $this->dimensions = $dimensions;
        parent::__construct($config);
    }

    public function init(): void
    {
        parent::init();
        /** @var EventProcessorInterface $processor */
        $processor = new MessageProcessor();
        $this->on(self::EVENT_CLIENT_MESSAGE, function (WSClientMessageEvent $e) use ($processor) {
            $processor($e, $this);
        });
        $this->on(self::EVENT_CLIENT_CONNECTED, function (WSClientEvent $e) {
            echo 'Client connected to game:' . PHP_EOL;
            if ($this->currentMatrix) {
                echo 'We allready have started game, send it to new client' . PHP_EOL;
                echo 'Before this connection we had ' . count($this->clients) . ' clients online:' . PHP_EOL;
            } else {
                echo 'First client, the game is not generated, let me do it, if no one cached before expired' . PHP_EOL;
                if (Yii::$app->cache->exists('sudokuMatrix')) {
                    echo 'Found cached game, let me load it' . PHP_EOL;
                    $this->currentMatrix = json_decode(Yii::$app->cache->get('sudokuMatrix'), true);
                } else {
                    $this->generate();
                    Yii::$app->cache->set('sudokuMatrix', json_encode($this->currentMatrix), 3600);
                }

            }
            $e->client->send(json_encode(['matrix' => $this->currentMatrix, 'top' => $this->getTop()]));
        });
    }

    public function generate(): void
    {

        $this->currentMatrix = Sudoku::solve(Sudoku::generate($this->dimensions, 6));
        // due time limitations on solving self generated matrix let me show solved matrix without last 4 random cells
        for ($i = 0; $i < 3; $i++) {
            $this->currentMatrix[mt_rand(0, 8)][mt_rand(0, 8)] = null;
        }
        /*for ($column = 1; $column <= $this->dimensions; $column++){
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
        }*/
        echo 'Generation done' . PHP_EOL;
        //print_r($this->currentMatrix);
    }

    public array $currentMatrix = [];

    /**
     * @param $column
     * @param $row
     * @param $value
     * @return array
     */
    public function setCellValue($column, $row, $value): array
    {
        echo 'Processing [' . $column . ':' . $row . '] attempt with value ' . $value . PHP_EOL;
        $cell = false;
        if ($column > $this->dimensions or $row > $this->dimensions or !$value) {
            return ['isAcceptableValue' => $cell, 'message' => 'Value or coordinates unacceptable'];
        }
        if (!empty($this->currentMatrix[$column][$row])) {
            echo 'Value already set' . PHP_EOL;
            return ['isAcceptableValue' => $cell, 'message' => 'Value already set'];
        }
        // search in row equal values
        // $column_ not the same as $column param, this is internal iterator
        for ($column_ = 1; $column_ <= $this->dimensions; $column_++) {
            if (empty($this->currentMatrix[$column_][$row])) {
                //echo 'Empty ['.$column_.':'.$row.']'.PHP_EOL;
                continue;
            }
            if ($this->currentMatrix[$column_][$row] == $value) {
                return ['isAcceptableValue' => $cell, 'message' => 'In row ' . $row . ' we already have value ' . $value];
            }
        }
        // search in column equal values
        // $row_ not the same as $row param, this is internal iterator
        //echo 'Searching value '.$value.' in column '.$column.PHP_EOL;
        for ($row_ = 1; $row_ <= $this->dimensions; $row_++) {
            if (empty($this->currentMatrix[$column][$row_])) {
                //echo 'Empty ['.$column.':'.$row_.']'.PHP_EOL;
                continue;
            }
            // echo 'Compare with value '.$this->currentMatrix[$column][$row_].' in row '.$row_.PHP_EOL;
            if ($this->currentMatrix[$column][$row_] == $value) {
                return ['isAcceptableValue' => $cell, 'message' => 'In column ' . $column . ' we already have value ' . $value];
            }
        }

        // find block of 3*3 by coordinates and check value in this block
        $maxBlockCoordinates = $this->dimensions / 3;
        //detect block column & row index
        // 1 2 3
        // 4 5 6
        // 7 8 9
        $blockColumn = ceil(($column + 1) / $maxBlockCoordinates);
        $blockRow = ceil(($row + 1) / $maxBlockCoordinates);
        echo 'Checking equal values in block [' . $blockColumn . ':' . $blockRow . ']' . PHP_EOL;
        for ($column_ = $blockColumn * $maxBlockCoordinates - 3; $column_ < $blockColumn * $maxBlockCoordinates; $column_++) {
            for ($row_ = $blockRow * $maxBlockCoordinates - 3; $row_ < $blockRow * $maxBlockCoordinates; $row_++) {
                if (empty($this->currentMatrix[$column_][$row_])) {
                    //echo 'Empty [' . $column_ . ':' . $row . '] in block processing' . PHP_EOL;
                    continue;
                }
                if ($this->currentMatrix[$column_][$row_] == $value) {
                    return ['isAcceptableValue' => $cell, 'message' => 'In block [' . $blockColumn . ':' . $blockRow . '] we already have value ' . $value];
                }
            }
        }

        $this->currentMatrix[$column][$row] = $value;
        return ['isAcceptableValue' => true, 'message' => 'Value successfully set'];

    }

    public function broadcast(string $message): void
    {
        if (is_iterable($this->clients)) {
            /** @var ConnectionInterface $client */
            foreach ($this->clients as $client) {
                $client->send($message);
            }
        }
    }

    public function addToTop(string $clientName): void
    {
        if (Yii::$app->cache->exists('sudokuTop')) {
            $top = json_decode(Yii::$app->cache->get('sudokuTop'), true);
            $wasInTop = false;
            foreach ($top as $client => $wins) {
                print_r([$client, $wins]);
                if ($client === $clientName) {
                    $top[$clientName] = $wins + 1;
                    $wasInTop = true;
                    break;

                }
            }
            if (!$wasInTop) {
                $top[$clientName] = 1;
            }
        } else {
            $top[$clientName] = 1;
        }
        Yii::$app->cache->set('sudokuTop', json_encode($top));
        print_r($top);
    }

    public function getTop(int $places = 10): array
    {
        if (Yii::$app->cache->exists('sudokuTop')) {
            $top = json_decode(Yii::$app->cache->get('sudokuTop'), true);
            arsort($top);
            return array_slice($top, 0, $places);
        }
        return [];
    }

}
