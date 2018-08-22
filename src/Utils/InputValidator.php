<?php

namespace Iota;

use Purl\Url\Url;

class InputValidator
{
    public function isAddress($address)
    {
        if (!$this->isString($address)) {
            return false;
        }

        if (strlen($address) === 81 && $this->isTrytes($address, strlen($address))) {
            return true;
        }

        if (strlen($address) === 90 && $this->isTrytes($address, strlen($address))) {
            return true;
        }

        return false;
    }

    public function isTrytes(string $trytes, $length = 0)
    {
        if ($length == 0) {
            $length = "0,";
        }

        return $this->isString($trytes) && preg_match('/^[9A-Z]{' . (string) $length . '}$/', $trytes);
    }

    public function isNinesTrytes(string $trytes)
    {
        return $this->isString($trytes) && preg_match('/^[9]+$/', $trytes);
    }

    public function isSafeString(string $input)
    {
        $ascii = new AsciiToTrytes();
        $trytesIsEqualToInput = ($ascii->fromTrytes($ascii->toTrytes($input))) === $input;

        return (bool) preg_match('/^[\x00-\x7F]*$/', $input) && $trytesIsEqualToInput;
    }

    public function isValue($value)
    {
        if ($this->isString($value)) {
            return false;
        }

        $castedInteger = (int) $value;
        if ($castedInteger != $value) {
            return false;
        }

        return is_int($value) || is_float($value);
    }

    public function isNum($input)
    {
        return (bool) preg_match('/^(\d+\.?\d{0,15}|\.\d{0,15})$/', $input);
    }

    public function isHash(string $hash)
    {
        if (!$this->isTrytes($hash, 81)) {
            return false;
        }

        return true;
    }

    public function isString($string)
    {
        return is_string($string);
    }

    public function isArray($array)
    {
        return is_array($array);
    }

    public function isAssocArray($array)
    {
        if (!$this->isArray($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    public function isTransfersArray(array $transfersArray)
    {
        if (!$this->isArray($transfersArray)) {
            return false;
        }

        if ($this->isAssocArray($transfersArray)) {
            return false;
        }

        for ($index = 0; $index < count($transfersArray); $index++) {
            $transfer = $transfersArray[$index];

            $address = $transfer['address'] ?? null;
            if ($address === null) {
                return false;
            }
            if (!$this->isAddress($address)) {
                return false;
            }

            $value = $transfer['value'] ?? null;
            if ($value === null) {
                return false;
            }
            if (!$this->isValue($value)) {
                return false;
            }

            $message = $transfer['message'] ?? null;
            if ($message === null) {
                return false;
            }
            if (!$this->isTrytes($message, "0,")) {
                return false;
            }

            $tag = $transfer['tag'] ?? $transfer['obsoleteTag'];
            if (!$this->isTrytes($tag, "0,27")) {
                return false;
            }

        }

        return true;
    }

    public function isArrayOfHashes($hashesArray)
    {
        if (!$this->isArray($hashesArray)) {
            return false;
        }

        if ($this->isAssocArray($hashesArray)) {
            return false;
        }

        $isHashedArray = [];
        for ($index = 0; $index < count($hashesArray); $index++) {
            $hash = $hashesArray[$index];

            if (strlen($hash) === 90 && $this->isTrytes($hash, strlen($hash))) {
                $isHashedArray[] = true;
            } else if (strlen($hash) === 81 && $this->isTrytes($hash, strlen($hash))) {
                $isHashedArray[] = true;
            } else {
                $isHashedArray[] = false;
            }
        }

        return in_array(false, $isHashedArray) ? false : true;
    }

    public function isArrayOfTrytes($trytesArray)
    {
        if (!$this->isArray($trytesArray)) {
            return false;
        }

        for ($index = 0; $index < count($trytesArray); $index++) {
            $tryteValue = $trytesArray[$index] ?? false;

            if ($tryteValue === false) {
                return false;
            }

            if (!$this->isTrytes($tryteValue, 2673)) {
                return false;
            }
        }

        return true;
    }

    public function isArrayOfAttachedTrytes($trytesArray)
    {
        if (!$this->isArray($trytesArray)) {
            return false;
        }

        if ($this->isAssocArray($trytesArray)) {
            return false;
        }

        for ($index = 0; $index < count($trytesArray); $index++) {
            $tryteValue = $trytesArray[$index];

            if (!$this->isTrytes($tryteValue, 2673)) {
                return false;
            }

            $lastTrytes = substr($tryteValue, 2673 - (3 * 81));

            if (preg_match('/^[9]+$/', $lastTrytes)) {
                return false;
            }
        }

        return true;
    }

    public function isArrayOfTxObjects($bundle)
    {
        if (!$this->isArray($bundle) || count($bundle) === 0) {
            return false;
        }

        $keysToValidate = $this->getKeysToValidate();

        $validArray = true;

        foreach ($bundle as $txObject) {
            for ($index = 0; $index < count($keysToValidate); $index++) {
                $key = $keysToValidate[$index]['key'];
                $validator = $keysToValidate[$index]['validator'];
                $args = $keysToValidate[$index]['args'];

                if (isset($txObject[$key])) {
                    if (!$this->{$validator}($txObject[$key], $args)) {
                        $validArray = false;
                        break;
                    }
                }

                if (isset($bundle[$key])) {
                    if (!$this->{$validator}($bundle[$key], $args)) {
                        $validArray = false;
                        break;
                    }
                }
            }
        }

        return $validArray;
    }

    public function isInputs(array $inputs)
    {
        if (!$this->isArray($inputs)) {
            return false;
        }

        for ($index = 0; $index < count($inputs); $index++) {

            $input = $inputs[$index];
            $security = $input['security'] ?? null;
            $keyIndex = $input['keyIndex'] ?? null;
            $address = $input['address'] ?? null;

            if (!$this->isAddress($input['address'])) {
                return false;
            }

            if (!$this->isValue($input['security'])) {
                return false;
            }

            if (!$this->isValue($input['keyIndex'])) {
                return false;
            }
        }

        return true;
    }

    public function isUri(string $node)
    {
        $urlInformation = parse_url($node);
        $scheme = $urlInformation['scheme'] ?? null;
        $host = $urlInformation['host'] ?? null;
        $port = $urlInformation['port'] ?? null;
        $path = $urlInformation['path'] ?? null;
        $query = $urlInformation['query'] ?? null;

        if ($scheme !== 'tcp' && $scheme !== 'udp') {
            return false;
        }

        if ($host !== null && $path !== null && $query !== null) {
            return false;
        }

        return true;
    }

    public function isTritArray($trits, $length = null)
    {
        $tritsIsArray = $this->isArray($trits);
        if (!$tritsIsArray) {
            return false;
        }

        $tritsHasValues = true;
        foreach ($trits as $trit) {
            if (!in_array($trit, [-1, 0, 1])) {
                $tritsHasValues = false;
                break;
            }
        }

        $lengthIsInteger = $this->isValue($length) ? count($trits) === $length : true;

        return $tritsHasValues && $lengthIsInteger;
    }

    private function getKeysToValidate()
    {
        return [
            [
                'key' => 'hash',
                'validator' => 'isHash',
                'args' => null,
            ], [
                'key' => 'signatureMessageFragment',
                'validator' => 'isTrytes',
                'args' => 2187,
            ], [
                'key' => 'address',
                'validator' => 'isHash',
                'args' => null,
            ], [
                'key' => 'value',
                'validator' => 'isValue',
                'args' => null,
            ], [
                'key' => 'obsoleteTag',
                'validator' => 'isTrytes',
                'args' => 27,
            ], [
                'key' => 'timestamp',
                'validator' => 'isValue',
                'args' => null,
            ], [
                'key' => 'currentIndex',
                'validator' => 'isValue',
                'args' => null,
            ], [
                'key' => 'lastIndex',
                'validator' => 'isValue',
                'args' => null,
            ], [
                'key' => 'bundle',
                'validator' => 'isHash',
                'args' => null,
            ], [
                'key' => 'trunkTransaction',
                'validator' => 'isHash',
                'args' => null,
            ], [
                'key' => 'branchTransaction',
                'validator' => 'isHash',
                'args' => null,
            ], [
                'key' => 'tag',
                'validator' => 'isTrytes',
                'args' => 27,
            ], [
                'key' => 'attachmentTimestamp',
                'validator' => 'isValue',
                'args' => null,
            ], [
                'key' => 'attachmentTimestampLowerBound',
                'validator' => 'isValue',
                'args' => null,
            ], [
                'key' => 'attachmentTimestampUpperBound',
                'validator' => 'isValue',
                'args' => null,
            ], [
                'key' => 'nonce',
                'validator' => 'isTrytes',
                'args' => 27,
            ],
        ];
    }
}
