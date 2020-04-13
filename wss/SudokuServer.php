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

class SudokuServer extends WebSocketServer
{

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
                $e->client->send(json_encode($this->currentMatrix));
                echo 'After this connection we have '.count($this->clients).' clients online:'.PHP_EOL;
            }
            else {
                echo 'First client, the game is not generated, let me do it'.PHP_EOL;
                $this->generate();
            }



        });
    }

    private function generate($dimensions = 9){
        for ($column = 1; $column <= $dimensions; $column++){
            for ($row = 1; $row <= $dimensions; $row++){
                    echo 'Generating value for ['.$column.':'.$row.']'.PHP_EOL;
                    $this->currentMatrix[$column][$row]=mt_rand(1,9);
            }
        }
        echo 'Generation done'.PHP_EOL;
        print_r($this->currentMatrix);
    }

    private $currentMatrix = [];


}