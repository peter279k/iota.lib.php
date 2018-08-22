<?php

namespace Iota;

use Exception;

class InputErrors
{
    public function __construct() {}

    public function invalidAddress()
    {
        return new Exception('Invalid address provided');
    }

    public function invalidTrytes()
    {
        return new Exception('Invalid Trytes provided');
    }

    public function invalidSeed()
    {
        return new Exception('Invalid Seed provided');
    }

    public function invalidIndex()
    {
        return new Exception('Invalid Index option provided');
    }

    public function invalidSecurity()
    {
        return new Exception('Invalid Security option provided');
    }

    public function invalidChecksum(string $address)
    {
        return new Exception('Invalid Checksum supplied for address: ' . $address);
    }

    public function invalidAttachedTrytes()
    {
        return new Exception('Invalid attached Trytes provided');
    }

    public function invalidTransfers()
    {
        return new Exception('Invalid transfers object');
    }

    public function invalidKey()
    {
        return new Exception('You have provided an invalid key value');
    }

    public function invalidTrunkOrBranch(string $hash)
    {
        return new Exception('You have provided an invalid hash as a trunk/branch: ' . $hash);
    }

    public function invalidUri(string $uri)
    {
        return new Exception('You have provided an invalid URI for your Neighbor: ' . $uri);
    }

    public function notInt()
    {
        return new Exception('One of your inputs is not an integer');
    }

    public function invalidInputs()
    {
        return new Exception('Invalid inputs provided');
    }

    public function inconsistentSubtangle(string $tail)
    {
        return new Exception('Inconsistent subtangle: ' . $tail);
    }
}
