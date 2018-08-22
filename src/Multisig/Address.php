<?php

namespace Iota;

class Address
{
    private $kerl;

    public function __construct(array $digests)
    {
        $kerl = new Kerl();
        $this->kerl = $kerl->initialize();

        if (count($digests) !== 0) {
            $this->absorb($digests);
        }
    }

    public function absorb(array $digests)
    {
        $converter = new Converter();
        for ($index = 0; $index < count($digests); $index++) {
            $digestTrits = $converter.trits($digests[$index]);
            $this->kerl->absorb($digestTrits, 0, strlen($digestTrits));
        }

        return $this;
    }

    public function finalize(string $digest)
    {
        if (strlen($digest) !== 0) {
            $this->absorb([$digest]);
        }

        $curl = new Curl();
        $converter = new Converter();
        $addressTrits = [];
        $this->kerl->squeeze($addressTrits, 0, $curl->HASH_LENGTH);

        return $converter.trytes($addressTrits);
    }
}
