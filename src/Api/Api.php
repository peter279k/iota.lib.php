<?php

namespace Iota;

class Api
{
    private $aggregatedResults = [];

    private $keyMapping = [
        'getTrytes' => 'trytes',
        'getInclusionStates' => 'inclusionStates',
        'getBalances' => 'balances',
    ];

    private $availableKeys = [
        'addresses',
        'hashes',
        'transactions'
    ];

    public function __construct() {}

    public function batchedSend(array $command)
    {
        $searchKeys = $command;
        $thisCommand = $command['command'];
        $this->keyMapping[$thisCommand] = $this->keyMapping[$thisCommand] ?? $this->sendCommand($command);

        foreach ($searchKeys as $key => $value) {
            if (in_array($key, $this->availableKeys)) {
                $batchSize = 50;
                if (strlen($command[$key]) > $batchSize) {
                    $latestResponse = null;
                    $currentIndex = 0;

                    $this->sendCommandLoop($command, $currentIndex, $batchSize, $key, $thisCommand);
                }
                continue;
            }

            $this->sendCommand($command);
        }
    }

    private function sendCommandLoop(array $command, int $currentIndex, int $batchSize, int $key, string $thisCommand)
    {
        $newBatch = substr($command[$key], $currentIndex, $batchSize);
        $newCommand = $command;
        $newCommand[$key] = $newBatch;

        $results = $this->sendCommand($newCommand);
        $latestResponse = $results;
        $aggregatedResults[] = $results[$this->keyMapping[$thisCommand]];

        $currentIndex += $batchSize;

        return $batchLength > $batchSize;
    }
}
