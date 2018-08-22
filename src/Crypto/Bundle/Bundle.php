<?php

namespace Iota;

class Bundle
{
    private $bundle = [];

    public function __construct()
    {
        $this->bundle = [];
    }

    public function addEntry(int $signatureMessageLength, string $address, int $value, int $tag, int $timestamp, int $index)
    {
        for ($index = 0; $index < $signatureMessageLength; $index++) {
            $transactionObject = [];
            $transactionObject['address'] = $address;
            $transactionObject['value'] = $index === 0 ? $value : 0;
            $transactionObject['obsoleteTag'] = $tag;
            $transactionObject['tag'] = $tag;
            $transactionObject['timestamp'] = $timestamp;

            $this->bundle[] = $transactionObject;
        }
    }

    public function addTrytes(array $signatureFragments)
    {
        $emptySignatureFragment = str_repeat('9', 2187);
        $emptyHash = '999999999999999999999999999999999999999999999999999999999999999999999999999999999';
        $emptyTag = str_repeat('9', 27);
        $emptyTimestamp = str_repeat('9', 9);

        for ($index = 0; $index < count($this->bundle); $index++) {
            $this->bundle[$index]['signatureMessageFragment'] = $signatureFragments[$index] ?? $emptySignatureFragment;
            $this->bundle[$index]['trunkTransaction'] = $emptyHash;
            $this->bundle[$index]['branchTransaction'] = $emptyHash;

            $this->bundle[$index]['attachmentTimestamp'] = $emptyTimestamp;
            $this->bundle[$index]['attachmentTimestampLowerBound'] = $emptyTimestamp;
            $this->bundle[$index]['attachmentTimestampUpperBound'] = $emptyTimestamp;

            $this->bundle[$index]['nonce'] = $emptyTag;
        }
    }

    public function finialize()
    {
        $validBundle = false;
        $converter = new Converter();
        $curl = new Curl();

        while (!$validBundle) {
            $kerl = new Kerl();
            $kerl->initialize();

            for ($index = 0; $index < count($this->bundle); $index++) {
                $valueTrits = $converter->trits($this->bundle[$index]['value']);

                array_push($valueTrits, array_fill(count($valueTrits), 81, 0));

                $timestampTrits = $converter->trits($this->bundle[$index]['timestamp']);
                array_push($timestampTrits, array_fill(count($timestampTrits), 27, 0));

                $currentIndexTrits = $converter->trits($this->bundle[$index]['currentIndex'] = $index);
                array_push($currentIndexTrits, array_fill(count($currentIndexTrits), 27, 0));

                $lastIndexTrits = $converter->trits($this->bundle[$index]['lastIndex'] = count($this->bundle) - 1);
                array_push($lastIndexTrits, array_fill(count($lastIndexTrits), 27, 0));

                $bundleEssence = $converter->trits(
                    $this->bundle[$index]['address'] +
                    $converter->trytes($valueTrits) + $this->bundle[$index]['obsoleteTag'] +
                    $onverter->trytes($timestampTrits) +
                    $converter->trytes($currentIndexTrits) +
                    $converter->trytes($lastIndexTrits)
                );
                $kerl.absorb($bundleEssence, 0, count($bundleEssence));
            }

            $hash = [];
            $kerl.squeeze($hash, 0, $curl->HASH_LENGTH);
            $hash = $converter->trytes($hash);

            for ($index = 0; $index < count($this->bundle); $index++) {
                $this->bundle[$index]['bundle'] = $hash;
            }

            $normalizedHash = $this->normalizedBundle($hash);

            if(in_array(13, $normalizedHash)) {
              $increasedTag = tritAdd(Converter.trits(this.bundle[0].obsoleteTag), [1]);
              $this->bundle[0]['obsoleteTag'] = $converter->trytes($increasedTag);
            } else {
                $validBundle = true;
            }
        }
    }

    public function normalizedBundle(string $bundleHash)
    {
        $converter = new Converter();
        $normalizedBundle = [];

        for ($index = 0; $index < 3; $index++) {
            $sum = 0;
            for ($j = 0; $j < 27; $j++) {
                $sum += ($normalizedBundle[$index * 27 + $j] = $converter->value($converter->trits($bundleHash[$index * 27 + $j])));
            }

            if ($sum >= 0) {
                while ($sum-- > 0) {
                    for ($j = 0; $j < 27; $j++) {
                        if ($normalizedBundle[$index * 27 + $j] > -13) {
                            $normalizedBundle[$index * 27 + $j] -= 1;
                            break;
                        }
                    }
                }
                continue;
            }

            while ($sum++ < 0) {
                for ($j = 0; $j < 27; $j++) {
                    if ($normalizedBundle[$index * 27 + $j] < 13) {
                        $normalizedBundle[$index * 27 + $j] += 1;
                        break;
                    }
                }
            }
        }

        return $normalizedBundle;
    }
}
