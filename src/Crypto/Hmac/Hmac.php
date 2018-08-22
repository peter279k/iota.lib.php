<?php

namespace Iota;

class Hmac
{
    private $HMAC_ROUNDS = 27;

    private $key = '';

    private $converter;

    public function __construct(string $key)
    {
        $this->converter = new Converter();
        $this->key = $this->converter->trits($key);
    }

    public function addHMAC(array &$bundle)
    {
        $curl = new Curl($this->HMAC_ROUNDS);
        $key = $this->key;

        for($index = 0; $index < strlen($bundle['bundle']); $index++) {
            if ($bundle['bundle'][$index]['value'] > 0) {
                $bundleHashTrits = $this->converter->trits($bundle['bundle'][$index]['bundle']);
                $hmac = array_fill(0, 243, 0);
                $curl.initialize();
                $curl.absorb($key, 0, strlen($key));
                $curl.absorb($bundleHashTrits, 0, count($bundleHashTrits));
                $curl.squeeze($hmac, 0, count($hmac));
                $hmacTrytes = $this->converter->trytes($hmac);
                $bundle['bundle'][$index]['signatureMessageFragment'] = $hmacTrytes +
                    substr($bundle['bundle'][$index]['signatureMessageFragment'], 81, 2187-81);
            }
        }
    }
}
