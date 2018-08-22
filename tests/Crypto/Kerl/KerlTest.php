<?php

namespace Iota\Tests;

use Iota\Kerl;
use Iota\Converter;
use PHPUnit\Framework\TestCase;

class KerlTest extends TestCase
{
    public function testAbsorbMultiSqueezeIntegration()
    {
        $input = '9MIDYNHBWMBCXVDEFOFWINXTERALUKYYPPHKP9JJFGJEIUY9MUDVNFZHMMWZUYUSWAIOWEVTHNWMHANBH';
        $expected = 'G9JYBOMPUXHYHKSNRNMMSSZCSHOFYOYNZRSZMAAYWDYEIMVVOGKPJBVBM9TDPULSFUNMTVXRKFIDOHUXXVYDLFSZYZTWQYTE9SPYYWYTXJYQ9IFGYOLZXWZBKWZN9QOOTBQMWMUBLEWUEEASRHRTNIQWJQNDWRYLCA';
        $converter = new Converter();
        $trits = $converter->trits($input);

        $kerl = new Kerl();
        $kerl->absorb($trits, 0, count($trits));
        $hashTrits = [];
        $kerl->squeeze($hashTrits, 0, $kerl->HASH_LENGTH * 2);
        $hash = $converter->trytes($hashTrits);

        $this->assertSame($expected, $hash);
    }
}
