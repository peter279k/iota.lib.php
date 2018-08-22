<?php

namespace Iota;

use InvalidArgumentException;

class Kerl
{
    private $BIT_HASH_LENGTH = 384;

    private $HASH_LENGTH = 243;

    private $hash;

    private $hashValue;

    private $words;

    public function __construct()
    {
        $this->words = new Words();
        $this->hash = hash_init('sha3-' . (string) $this->BIT_HASH_LENGTH);
    }

    public function reset()
    {
        $this->hash = hash_init('sha3-' . (string) $this->BIT_HASH_LENGTH);
    }

    public function absorb(array $trits, int $offset, int $length)
    {
        if ($length % $this->HASH_LENGTH !== 0) {
            throw new InvalidArgumentException('Illegal length provided');
        }

        do {
            $limit = ($length < $this->HASH_LENGTH ? $length : $this->HASH_LENGTH);
            $tritState = array_slice($trits, $offset, $limit);
            $offset += $limit;

            $wordsToAbsorb = $this->words->tritsToWords($tritState);
            $this->hashValue = hash_update($this->hash, $this->$wordsToAbsorb);
        } while (($length -= $this->HASH_LENGTH) > 0);
    }

    public function squeeze(array &$trits, int $offset, int $length)
    {
        if ($length % $this->HASH_LENGTH !== 0) {
            throw new InvalidArgumentException('Illegal length provided');
        }

        do {
            $finalWordsHashValue = base64_encode(hash_final($this->hash, true));
            $tritState = $this->words->wordsToTrits($finalWordsHashValue);

            $index = 0;
            $limit = ($length < $this->HASH_LENGTH ? $length : $this->HASH_LENGTH);

            while ($index < $limit) {
                $trits[$offset++] = $tritState[$index++];
            }

            $this->reset();

            for ($index = 0; $index < strlen($finalWordsHashValue); $index++) {
                $finalWordsHashValue[$index] = $finalWordsHashValue[$index] ^ 0xFFFFFFFF;
            }

            hash_update($this->hash, $finalWordsHashValue);
        } while (($length -= $this->HASH_LENGTH) > 0);
    }
}
