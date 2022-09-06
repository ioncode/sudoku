<?php

namespace app\wss;

use yii\base\Event;

interface EventProcessorInterface
{
    public function __invoke(Event $e, SudokuServer $server): void;
}
