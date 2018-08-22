<?php

namespace Iota;

class Curl
{
    private $NUMBER_OF_ROUNDS = 81;

    private $HASH_LENGTH = 243;

    private $STATE_LENGTH = 3 * 243;

    private $rounds;

    private $state;

    private $truthTable = [1, 0, -1, 2, 1, -1, 0, 2, -1, 1, 0];

    public function __construct(int $rounds)
    {
        $this->rounds = $rounds ?? $this->NUMBER_OF_ROUNDS;
        $this->truthTable = [];
    }

    public function initialize(array $state, int $length = 0)
    {
        $this->state = $state ?? array_fill(0, $this->STATE_LENGTH, 0);
    }

    public function reset()
    {
        $this->initialize(null);
    }

    public function absorb(array &$trits, int $offset, int $length)
    {
        do {
            $index = 0;
            $limit = $length < $this->HASH_LENGTH ? $length : $this->HASH_LENGTH;

            while ($index < $limit) {
                $this->state[$index++] = $trits[$offset++];
            }

            $this->transform();

        } while (($length -= $this->HASH_LENGTH ) > 0);
    }

    public function squeeze(array &$trits, int $offset, int $length)
    {
        do {
            $index = 0;
            $limit = ($length < $this->HASH_LENGTH ? $length : $this->HASH_LENGTH);

            while ($index < $limit) {
                $trits[$offset++] = $this->state[$index++];
            }

            $this->transform();

        } while (( $length -= $this->HASH_LENGTH ) > 0);
    }

    public function transform()
    {
        $index = 0;

        for ($round = 0; $round < $this->rounds; $round++) {
            $stateCopy = $this->state;

            for ($i = 0; $i < $this->STATE_LENGTH; $i++) {
                $this->state[$i] = $this->truthTable[$stateCopy[$index] +
                    ($stateCopy[$index += ($index < 365 ? 364 : -365)] << 2) + 5];
            }
        }
    }
}
