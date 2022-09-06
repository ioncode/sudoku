<?php

namespace app\wss;

use consik\yii2websocket\events\WSClientMessageEvent;
use Yii;

class MessageProcessor implements EventProcessorInterface
{

    /**
     * @param WSClientMessageEvent $e
     * @param SudokuServer         $server
     * @return void
     */
    public function __invoke($e, SudokuServer $server): void
    {

        echo 'Message from client: ' . $e->message . PHP_EOL;
        if ($json = json_decode($e->message, true)) {
            echo 'Received json message, try extract commands' . PHP_EOL;
            print_r($json);
            if ($json['command'] == 'setCell') {
                echo 'Command to set cell [' . $json['coordinates']['column'] . ':' . $json['coordinates']['row'] . '] with value ' . $json['value'] . PHP_EOL;
                $attempt = $server->setCellValue($json['coordinates']['column'], $json['coordinates']['row'], $json['value']);
                echo $attempt['message'] . PHP_EOL;
                //print_r($this->currentMatrix);
                if ($attempt['isAcceptableValue']) {
                    echo 'Value accepted' . PHP_EOL;

                    // check if it was last cell set author to top list
                    $continue = false;
                    foreach ($server->currentMatrix as $column) {
                        foreach ($column as $cell) {
                            if ($cell === null) {
                                $continue = true;
                                break 2;
                            }
                        }
                    }
                    if ($continue) {
                        echo 'Game is not finished, waiting for new commands' . PHP_EOL;
                    } else {
                        // set client to top & broadcast new game & top 10
                        if (!empty($clientName = $json['clientName'])) {
                            echo $clientName . ' win!' . PHP_EOL;
                            $server->addToTop($clientName);
                        }
                        $server->generate();
                        Yii::$app->cache->set('sudokuMatrix', json_encode($server->currentMatrix), 3600);

                    }
                    // send updated matrix to every client
                    $server->broadcast(json_encode(['matrix' => $server->currentMatrix, 'top' => $server->getTop()]));
                } else {
                    $server->broadcast(json_encode(['message' => 'Value declined']));
                }
            }
        }

    }
}
