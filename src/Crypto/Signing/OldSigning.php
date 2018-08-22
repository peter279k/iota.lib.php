<?php

namespace Iota;

class OldSigning
{
    private $HASH_LENGTH = 243;

    private $converter;

    private $add;

    public function __construct()
    {
        $this->converter = new Converter();
        $this->add = new Adder();
    }

    public function key(array $seed, int $index, int $length)
    {
        while ((count($seed) < $this->HASH_LENGTH) !== 0) {
            $seed[] = 0;
        }

        $indexTrits = $this->converter->fromValue($index);
        $subSeed = $this->add($seed, $indexTrits);

        $curl = new Curl();

        $curl->initialize( );
        $curl->absorb($subSeed, 0, count($subSeed));
        $curl->squeeze($subSeed, 0, count($subSeed));

        $curl->initialize();
        $curl->absorb($subSeed, 0, count($subSeed));

        $key = [];
        $offset = 0;
        $buffer = [];

        while ($length-- > 0) {
            for ($index = 0; $index < 27; $index++) {
                $curl->squeeze($buffer, 0, count($subSeed));
                for ($j = 0; $j < 243; $j++) {
                    $key[$offset++] = $buffer[$j];
                }
            }
        }

        return $key;
    }

    public function digests(string $key)
    {
        $digests = [];
        $buffer = [];

        for ($index = 0; $index < floor(strlen($key) / 6561); $index++) {
            $keyFragment = substr($key, $index * 6561, 6561);

            for ($j = 0; $j < 27; $j++) {
                $buffer = substr($keyFragment, $j * 243, 243);

                for ($k = 0; $k < 26; $k++) {
                    $kCurl = new Curl();
                    $kCurl->initialize();
                    $kCurl->absorb($buffer, 0, strlen($buffer));
                    $kCurl->squeeze($buffer, 0, $kCurl->HASH_LENGTH);
                }

                for ($k = 0; $k < 243; $k++) {
                    $keyFragment[$j * 243 + $k] = $buffer[$k];
                }
            }

            $curl = new Curl();

            $curl->initialize();
            $curl->absorb($keyFragment, 0, strlen($keyFragment));
            $curl->squeeze($buffer, 0, $curl->HASH_LENGTH);

            for ($j = 0; $j < 243; $j++) {
                $digests[$index * 243 + $j] = $buffer[$j];
            }
        }

        return $digests;
    }

    public function address(string $digests)
    {
        $addressTrits = [];

        $curl = new Curl();
        $curl->initialize();
        $curl->absorb($digests, 0, $digests);
        $addressTrits = $curl->squeeze($addressTrits, 0, $curl->HASH_LENGTH);

        return $addressTrits;
    }

    public function digest(array $normalizedBundleFragment, array $signatureFragment)
    {
        $buffer = [];

        $curl = new Curl();

        $curl->initialize();

        for ($index = 0; $index< 27; $index++) {
            $buffer = array_slice($signatureFragment, $index * 243, 243);

            for ($j = $normalizedBundleFragment[$index] + 13; $j-- > 0; ) {

                $jCurl = new Curl();

                $jCurl->initialize();
                $jCurl->absorb($buffer, 0, strlen($buffer));
                $jCurl->squeeze($buffer, 0, $jCurl->HASH_LENGTH);
            }

            $curl->absorb($buffer, 0, count($buffer));
        }

        $curl->squeeze($buffer, 0, $curl->HASH_LENGTH);

        return $buffer;
    }

    public function signatureFragment(array $normalizedBundleFragment, array $keyFragment)
    {
        $signatureFragment = $keyFragment;
        $hash = [];

        $curl = new Curl();

        for ($index = 0; $index < 27; $index++) {

            $hash = array_slice($signatureFragment, $index * 243, 243);

            for ($j = 0; $j < 13 - $normalizedBundleFragment[$index]; $j++) {

                $curl->initialize();
                $curl->absorb($hash, 0, strlen($hash));
                $curl->squeeze($hash, 0, $curl->HASH_LENGTH);
            }

            for ($j = 0; $j < 243; $j++) {
                $signatureFragment[$index * 243 + $j] = $hash[$j];
            }
        }

        return $signatureFragment;
    }

    public function validateSignatures(string $expectedAddress, string $signatureFragments, string $bundleHash)
    {
        $bundle = new Bundle();

        $normalizedBundleFragments = [];
        $normalizedBundleHash = $bundle->normalizedBundle($bundleHash);

        for ($index = 0; $index < 3; $index++) {
            $normalizedBundleFragments[$index] = array_slice($normalizedBundleHash, $index * 27, 27);
        }

        $digests = [];

        for ($index = 0; $index < strlen($signatureFragments); $index++) {
            $digestBuffer = $this->digest($normalizedBundleFragments[$index % 3], $this->converter->trits($signatureFragments[$index]));

            for ($j = 0; $j < 243; $j++) {
                $digests[$index * 243 + $j] = $digestBuffer[$j];
            }
        }

        $address = $this->converter->trytes($this->address($digests));

        return ($expectedAddress === $address);
    }
}
