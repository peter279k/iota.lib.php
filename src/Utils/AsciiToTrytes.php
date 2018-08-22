<?php

namespace Iota;

class AsciiToTrytes
{
    private static $TRYTE_VALUES = "9ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    public function toTrytes($input)
    {
        if (!is_string($input)) {
            return null;
        }

        $trytes = "";

        for ($index = 0; $index < strlen($input); $index++) {
            $char = $input[$index];
            $asciiValue = $this->getBianma($char[0]);

            if ($asciiValue > 255) {
                return null;
            }

            $firstValue = $asciiValue % 27;
            $secondValue = ($asciiValue - $firstValue) / 27;

            $trytesValue = self::$TRYTE_VALUES[$firstValue] . self::$TRYTE_VALUES[$secondValue];

            $trytes .= $trytesValue;
        }

        return $trytes;
    }

    private function getBianma(string $str)
    {
        $result = [];

        for($i = 0, $l = mb_strlen($str, "utf-8");$i < $l;++$i){
            $result[] = $this->uniord(mb_substr($str, $i, 1, "utf-8"));
        }

        return join(",", $result);
    }

    private function uniord(string $str, bool $fromEncoding = false)
    {
        $fromEncoding = $fromEncoding ? $fromEncoding : "UTF-8";
        if (strlen($str) == 1) {
            return ord($str);
        }

        $str = mb_convert_encoding($str, "UCS-4BE", $fromEncoding);
        $tmp = unpack("N", $str);

        return $tmp[1];
    }

    public function fromTrytes($inputTrytes)
    {
        if (!is_string($inputTrytes)) {
            return null;
        }

        if (strlen($inputTrytes) % 2 !== 0) {
            return null;
        }

        $outputString = "";

        for ($i = 0; $i < strlen($inputTrytes); $i += 2) {
            $trytes = $inputTrytes[$i] . $inputTrytes[$i + 1];

            $firstValueIndex = strpos(self::$TRYTE_VALUES, $trytes[0]);
            $secondValueIndex = strpos(self::$TRYTE_VALUES, $trytes[1]);

            $firstValue = ($firstValueIndex === false ? -1 : $firstValueIndex);
            $secondValue = ($secondValueIndex === false ? -1 : $secondValueIndex);

            $decimalValue = $firstValue + $secondValue * 27;

            $character = chr($decimalValue);

            $outputString .= $character;
        }

        return $outputString;
    }
}
