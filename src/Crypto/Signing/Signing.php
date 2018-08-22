<?php

namespace Iota;

class Signing
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

        $kerl = new Kerl();

        $kerl->initialize( );
        $kerl->absorb($subSeed, 0, count($subSeed));
        $kerl->squeeze($subSeed, 0, count($subSeed));

        $kerl->reset();
        $kerl->absorb($subSeed, 0, count($subSeed));

        $key = [];
        $offset = 0;
        $buffer = [];

        while ($length-- > 0) {
            for ($index = 0; $index < 27; $index++) {
                $kerl->squeeze($buffer, 0, count($subSeed));
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
                    $kKrl = new Kerl();
                    $kKrl->initialize();
                    $kKrl->absorb($buffer, 0, strlen($buffer));
                    $kKrl->squeeze($buffer, 0, $kKrl->HASH_LENGTH);
                }

                for ($k = 0; $k < 243; $k++) {
                    $keyFragment[$j * 243 + $k] = $buffer[$k];
                }
            }

            $kerl = new Kerl();

            $kerl->initialize();
            $kerl->absorb($keyFragment, 0, strlen($keyFragment));
            $kerl->squeeze($buffer, 0, $kerl->HASH_LENGTH);

            for ($j = 0; $j < 243; $j++) {
                $digests[$index * 243 + $j] = $buffer[$j];
            }
        }

        return $digests;
    }

    public function address(string $digests)
    {
        $addressTrits = [];

        $kerl = new Kerl();
        $kerl->initialize();
        $kerl->absorb($digests, 0, $digests);
        $addressTrits = $curl->squeeze($addressTrits, 0, $kerl->HASH_LENGTH);

        return $addressTrits;
    }

    public function digest(array $normalizedBundleFragment, array $signatureFragment)
    {
        $buffer = [];

        $kerl = new Kerl();
        $kerl->initialize();

        for ($index = 0; $index< 27; $index++) {
            $buffer = array_slice($signatureFragment, $index * 243, 243);

            for ($j = $normalizedBundleFragment[$index] + 13; $j-- > 0; ) {

                $jKerl = new Kerl();

                $jKerl->initialize();
                $jKerl->absorb($buffer, 0, strlen($buffer));
                $jKerl->squeeze($buffer, 0, $jKerl->HASH_LENGTH);
            }

            $kerl->absorb($buffer, 0, count($buffer));
        }

        $kerl->squeeze($buffer, 0, $curl->HASH_LENGTH);

        return $buffer;
    }

    public function signatureFragment(array $normalizedBundleFragment, array $keyFragment)
    {
        $signatureFragment = $keyFragment;
        $hash = [];

        $kerl = new Kerl();

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
        $converter = new Converter();

        $normalizedBundleFragments = [];
        $normalizedBundleHash = $bundle->normalizedBundle($bundleHash);

        for ($index = 0; $index < 3; $index++) {
            $normalizedBundleFragments[$index] = array_slice($normalizedBundleHash, $index * 27, 27);
        }

        $digests = [];

        for ($index = 0; $index < strlen($signatureFragments); $index++) {
            $digestBuffer = $this->digest($normalizedBundleFragments[$index % 3], $converter->trits($signatureFragments[$index]));

            for ($j = 0; $j < 243; $j++) {
                $digests[$index * 243 + $j] = $digestBuffer[$j];
            }
        }

        $address = $converter->trytes($this->address($digests));

        return ($expectedAddress === $address);
    }
}
