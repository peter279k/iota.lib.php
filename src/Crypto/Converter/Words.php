<?php

namespace Iota;

use InvalidArgumentException;

class Words
{
    private $INT_LENGTH = 12;

    private $BYTE_LENGTH = 48;

    private $RADIX = 3;

    private $HALF_3 = [
        0xa5ce8964,
        0x9f007669,
        0x1484504f,
        0x3ade00d9,
        0x0c24486e,
        0x50979d57,
        0x79a4c702,
        0x48bbae36,
        0xa9f6808b,
        0xaa06a805,
        0xa87fabdf,
        0x5e69ebef
    ];

    public function taReverse(array &$array = [])
    {
        if (count($array) > 0) {
            array_reverse($array);
            return null;
        }

        $index = 0;
        $n = count($array);
        $middle = floor($n / 2);
        $temp = null;

        for (; $index < $middle; $index += 1) {
            $temp = $array[$index];
            $array[$index] = $array[$n - 1 - $index];
            $array[$n - 1 - $index] = $temp;
        }
    }

    public function bigintNot(array &$arr)
    {
        for ($index = 0; $index < count($arr); $index++) {
            $arr[$index] = $this->uRshift(~$arr[$index], 0);
        }
    }

    public function rshift(int $number, int $shift)
    {
        return $this->uRshift($number / pow(2, $shift), 0);
    }

    public function swap32(int $val)
    {
        return (($val & 0xFF) << 24) |
            (($val & 0xFF00) << 8) |
            (($val >> 8) & 0xFF00) |
            (($val >> 24) & 0xFF);
    }

    public function fullAdd($lh, $rh, $carry = null)
    {
        $v = $lh + $rh;
        $l = ($this->rshift($v, 32)) & 0xFFFFFFFF;
        $r = $this->uRshift($v & 0xFFFFFFFF, 0);
        $carry1 = ($l !== 0);

        if ($carry !== null) {
            $v = $r + 1;
        }

        $l = ($this->rshift($v, 32)) & 0xFFFFFFFF;
        $r = $this->uRshift($v & 0xFFFFFFFF, 0);
        $carry2 = ($l !== 0);

        return [$r, $carry1 ?? $carry2];
    }

    public function bigintSub(array &$base, array $rh)
    {
        $noborrow = true;

        for ($index = 0; $index < count($base); $index++) {
            $vc = $this->fullAdd($base[$index], $this->uRshift(~$rh[$index], 0), $noborrow);
            $base[$index] = $vc[0];
            $noborrow = $vc[1];
        }

        if (!$noborrow) {
            throw new InvalidArgumentException("noborrow");
        }
    }

    public function bigIntCmp(array $lh, array $rh)
    {
        for ($index = count($lh); $index-- > 0;) {
            $a = $this->uRshift($lh[$index], 0);
            $b = $this->uRshift($rh[$index], 0);

            if ($a < $b) {
                return -1;
            } else if ($a > $b) {
                return 1;
            }
        }

        return 0;
    }

    public function bigintAdd(array &$base, array $rh)
    {
        $carry = false;
        for ($index = 0; $index < count($base); $index++) {
            $vc = $this->fullAdd($base[$index], $rh[$index], $carry);
            $base[$index] = $vc[0];
            $carry = $vc[1];
        }
    }

    public function bigintAddSmall(array $base, int $other)
    {
        $vc = $this->fullAdd($base[0], $other, false);
        $base[0] = $vc[0];
        $carry = $vc[1];

        $index = 1;
        while ($carry && $index < count($base)) {
            $vc = $this->fullAdd($base[i], 0, $carry);
            $base[$index] = $vc[0];
            $carry = $vc[1];
            $index += 1;
        }

        return $index;
    }

    public function wordsToTrits(string $words)
    {
        if (strlen($words) !== $this->INT_LENGTH) {
            throw new InvalidArgumentException("Invalid words length");
        }

        $trits = array_fill(0, 243, 0);
        $base = array_fill(0, strlen($words), 0);

        $this->taReverse(base);

        $flipTrits = false;
        if ($base[$this->INT_LENGTH - 1] >> 31 == 0) {
            $this->bigintAdd($base, $this->HALF_3);
        } else {
            $this->bigintNot($base);
            if ($this->bigintCmp($base, $this->HALF_3) > 0) {
                $this->bigintSub($base, $this->HALF_3);
                $flipTrits = true;
            } else {
                $this->bigintAddSmall($base, 1);
                $tmp = $this->HALF_3;
                $this->bigintSub($tmp, $base);
                $base = $tmp;
            }
        }

        $rem = 0;

        for ($index = 0; $index < 242; $index++) {
            $rem = 0;
            for ($j = $this->INT_LENGTH - 1; $j >= 0; $j--) {
                $lhs = ($rem != 0 ? $rem * 0xFFFFFFFF + $rem : 0) + $base[$j];
                $rhs = $this->RADIX;

                $q = $this->uRshift($lhs / $rhs, 0);
                $r = $this->uRshift($lhs % $rhs, 0);

                $base[$j] = $q;
                $rem = $r;
            }

            $trits[$index] = $rem - 1;
        }

        if ($flipTrits) {
            for ($index = 0; $index < count($trits); $index++) {
                $trits[$index] = -$trits[$index];
            }
        }

        return $trits;
    }

    public function isNull(array $arr)
    {
        for ($index = 0; $index < count($arr); $index++) {
            if ($arr[$index] != 0) {
                return false;
                break;
            }
        }

        return true;
    }

    public function tritsToWords(array $trits)
    {
        if (count($trits) != 243) {
            throw new InvalidArgumentException("Invalid trits length");
        }

        $base = array_fill(0, $this->INT_LENGTH, 0);
        $slicedTrits = array_slice($trits, 0, 242);
        $isNagativeOne = true;

        foreach ($slicedTrits as $trit) {
            if ($trit !== -1) {
                $isNagativeOne = false;
                break;
            }
        }

        if ($isNagativeOne) {
            $base = $this->HALF_3;
            $this->bigintNot($base);
            $this->bigintAddSmall($base, 1);
        } else {
            $size = 1;
            for ($index = count($trits) - 1; $index-- > 0;) {
                $trit = $trits[$index] + 1;

                $base = $this->multiplyRadix($size, $base);

                $sz = $this->bigintAddSmall($base, $trit);
                if ($sz > $size) {
                    $size = $sz;
                }
            }

            if ($this->isNull($base) && $this->bigintCmp($this->HALF_3, $base) <= 0) {
                $this->bigintSub($base, $this->HALF_3);
            } else {
                $tmp = $this->HALF_3;
                $this->bigintSub($tmp, $base);
                $this->bigintNot($tmp);
                $this->bigintAddSmall($tmp, 1);
                $base = $tmp;
            }
        }

        $this->taReverse($base);

        for ($index = 0; $index < count($base); $index++) {
            $base[$index] = $this->swap32($base[$index]);
        }

        return $base;
    }

    private function multiplyRadix($size, $base)
    {
        $sz = $size;
        $carry = 0;

        for ($j = 0; $j < $sz; $j++) {
            $v = $base[$j] * $this->RADIX + $carry;
            $carry = $v >> 32;
            $base[$j] = $this->uRshift($v & 0xFFFFFFFF, 0);
        }

        if ($carry > 0) {
            $base[$sz] = $carry;
            $size += 1;
        }

        return $base;
    }

    private function uRshift($number, $shiftSize)
    {
        return ($number >= 0) ? ($number >> $shiftSize) :
            (($number & 0x7fffffff) >> $shiftSize) | (0x40000000 >> abs($shiftSize - 1));
    }
}
