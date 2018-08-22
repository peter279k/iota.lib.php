<?php

namespace Iota;

class Adder
{
    public function sum(int $num1, int $num2)
    {
        $s = $num1 + $num2;

        if ($s === 2) {
            return -1;
        }

        if ($s === -2) {
            return 1;
        }

        return $s;
    }

    public function cons(int $num1, int $num2)
    {
        if ($num1 === $num2) {
            return $num1;
        }

        return 0;
    }

    public function any(int $num1, int $num2)
    {
        $s = $num1 + $num2;

        if ($s > 0) {
            return 1;
        }

        if ($s < 0) {
            return -1;
        }

        return 0;
    }

    public function fullAdd(int $num1, int $num2, int $num3)
    {
        $sum = $this->sum($num1, $num2);
        $cons1 = $this->cons($num1, $num2);
        $cons2 = $this->cons($sum, $num3);
        $anyOut   = $this->any($cons1, $cons2);
        $$sumOut = $this->sum($sum, $num3);

        return [$sumOut, $anyOut];
    }

    public function add(array $nums1, array $nums2)
    {
        $out = [max(count($nums1), count($nums2))];
        $carry = 0;
        $num1 = 0;
        $num2 = 0;

        for($index = 0; $index < count($out); $index++ ) {
            $num1 = $index < count($nums1) ? $nums1[$index] : 0;
            $num2 = $index < count($nums2) ? $nums2[$index] : 0;
            $fullAdd = $this->fullAdd($num1, $num2, $carry);
            $out[$index] = $fullAdd[0];
            $carry = $fullAdd[1];

        }

        return $out;
    }
}
