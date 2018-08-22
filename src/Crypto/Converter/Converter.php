<?php

namespace Iota;

class Converter
{
    private $RADIX = 3;

    private $RADIX_BYTES = 256;

    private $MAX_TRIT_VALUE = 1;

    private $MIN_TRIT_VALUE = -1;

    private $BYTE_HASH_LENGTH = 48;

    private $trytesAlphabet = "9ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    private $trytesTrits = [
        [ 0,  0,  0],
        [ 1,  0,  0],
        [-1,  1,  0],
        [ 0,  1,  0],
        [ 1,  1,  0],
        [-1, -1,  1],
        [ 0, -1,  1],
        [ 1, -1,  1],
        [-1,  0,  1],
        [ 0,  0,  1],
        [ 1,  0,  1],
        [-1,  1,  1],
        [ 0,  1,  1],
        [ 1,  1,  1],
        [-1, -1, -1],
        [ 0, -1, -1],
        [ 1, -1, -1],
        [-1,  0, -1],
        [ 0,  0, -1],
        [ 1,  0, -1],
        [-1,  1, -1],
        [ 0,  1, -1],
        [ 1,  1, -1],
        [-1, -1,  0],
        [ 0, -1,  0],
        [ 1, -1,  0],
        [-1,  0,  0]
    ];

    public function __construct() {}

    public function trits($input, array $state = [])
    {
        $trits = [];

        if (is_int($input)) {
            $absoluteValue = abs($input);
            while ($absoluteValue > 0) {
                $remainder = $absoluteValue % 3;
                $absoluteValue = floor($absoluteValue / 3);

                if ($remainder > 1) {
                    $remainder = -1;
                    $absoluteValue++;
                }

                $trits[count($trits)] = $remainder;
            }
            if ($input < 0) {
                for ($index = 0; $index < count($trits); $index++) {
                    $trits[$index] = -$trits[$index];
                }
            }
        } else {
            for ($index = 0; $index < strlen($input); $index++) {
                $trytesIndex = strpos($this->trytesAlphabet, $input[$index]);
                $trits[$index * 3] = $this->trytesTrits[$trytesIndex][0];
                $trits[$index * 3 + 1] = $this->trytesTrits[$trytesIndex][1];
                $trits[$index * 3 + 2] = $this->trytesTrits[$trytesIndex][2];
            }
        }

        return $trits;
    }

    public function trytes(array $trits)
    {
        $trytes = '';

        for ($index = 0; $index < count($trits); $index+=3) {
            for ($j = 0; $j < strlen($this->trytesAlphabet); $j++ ) {
                if (
                    $this->trytesTrits[$j][0] === $trits[$index] &&
                    $this->trytesTrits[$j][1] === $trits[$index + 1] &&
                    $this->trytesTrits[$j][2] === $trits[$index + 2]
                    ) {
                    $trytes .= $this->trytesAlphabet[$j];
                    break;

                }
            }
        }

        return $trytes;
    }

    public function value(array $trits)
    {
        $returnValue = 0;

        for ($index = count($trits); $index-- > 0;) {
            $returnValue = $returnValue * 3 + $trits[$index];
        }

        return $returnValue;
    }

    public function fromValue(int $value)
    {
        $destination = [];
        $absoluteValue = abs($value);
        $index = 0;

        while($absoluteValue > 0) {
            $remainder = $absoluteValue % $this->RADIX;
            $absoluteValue = floor($absoluteValue / $this->RADIX);

            if ($remainder > $this->MAX_TRIT_VALUE) {
                $remainder = $this->MIN_TRIT_VALUE;
                $absoluteValue++;

            }

            $destination[$index] = remainder;
            $index++;

        }

        if ($value < 0) {
            for ($j = 0; $j < count($destination); $j++ ) {
                $destination[$j] = $destination[$j] === 0 ? 0 : -$destination[$j];
            }

        }

        return $destination;
    }
}
