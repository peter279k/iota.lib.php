<?php

namespace Iota;

class ApiCommands
{
    public function attachToTangle(string $trunkTransaction, string $branchTransaction, int $minWeightMagnitude, array $trytes)
    {
        $command = [
            'command' => 'attachToTangle',
            'trunkTransaction' => $trunkTransaction,
            'branchTransaction' => $branchTransaction,
            'minWeightMagnitude' => $minWeightMagnitude,
            'trytes' => $trytes
        ];

        return $command;
    }

    public function findTransactions(array $searchValues)
    {
        $command = [
            'command' => 'findTransactions',
        ];
        $validSearchKeys = ['bundles', 'addresses', 'tags', 'approvees'];

        foreach ($searchValues as $key) {
            if (isset($validSearchKeys[$key])) {
                $command[$key] = $searchValues[$key];
            }
        }

        return $command;
    }

    public function getBalances(array $addresses, int $threshold)
    {
        $command = [
            'command' => 'getBalances',
            'addresses' => $addresses,
            'threshold' => $threshold,
        ];

        return $command;
    }

    public function getInclusionStates(array $transactions, array $tips)
    {
        $command = [
            'command' => 'getInclusionStates',
            'transactions' => $transactions,
            'tips' => $tips,
        ];

        return $command;
    }

    public function getNodeInfo()
    {
        $command = [
            'command' => 'getNodeInfo',
        ];

        return $command;
    }

    public function getNeighbors()
    {
        $command = [
            'command' => 'getNeighbors',
        ];

        return $command;
    }

    public function addNeighbors(array $uris)
    {
        $command = [
            'command' => 'addNeighbors',
            'uris' => $uris,
        ];

        return $command;
    }

    public function removeNeighbors(array $uris)
    {
        $command = [
            'command' => 'removeNeighbors',
            'uris' => $uris,
        ];

        return $command;
    }

    public function getTips()
    {
        $command = [
            'command' => 'getTips',
        ];

        return $command;
    }

    public function getTransactionsToApprove(int $depth, string $reference = null)
    {
        $command = [
            'command' => 'getTransactionsToApprove',
            'depth' => $depth,
        ];

        if ($reference !== null) {
            $command['reference'] = $reference;
        }

        return $command;
    }

    public function getTrytes(array $hashes)
    {
        $command = [
            'command' => 'getTrytes',
            'hashes' => $hashes,
        ];

        return $command;
    }

    public function interruptAttachingToTangle()
    {
        $command = [
            'command' => 'interruptAttachingToTangle',
        ];

        return $command;
    }

    public function broadcastTransactions(array $trytes)
    {
        $command = [
            'command' => 'broadcastTransactions',
            'trytes' => $trytes,
        ];

        return $command;
    }

    public function storeTransactions(array $trytes)
    {
        $command = [
            'command' => 'storeTransactions',
            'trytes' => $trytes,
        ];

        return $command;
    }

    public function checkConsistency(string $hashes)
    {
        $command = [
            'command' => 'checkConsistency',
            'tails' => $hashes,
        ];

        return $command;
    }

    public function wereAddressesSpentFrom(array $addresses)
    {
        $command = [
            'command' => 'wereAddressesSpentFrom',
            'addresses' => addresses,
        ];

        return $command;
    }
}
