<?php

namespace Iota\Tests;

use Iota\Api;
use PHPUnit\Framework\TestCase;

class ApiTest
{
    /**
     * @dataProvider newAddressesProvider
     */
    public function testGetNewAddress()
    {
        sleep(10000);

        $api = new Api();
        $seed = 'SEED';
    }

    public function newAddressesProvider()
    {
        $address = [
            'FJHSSHBZTAKQNDTIKJYCZBOZDGSZANCZSWCNWUOCZXFADNOQSYAHEJPXRLOVPNOQFQXXGEGVDGICLMOXX',
            '9DZXPFSVCSSWXXQPFMWLGFKPBAFTHYMKMZCPFHBVHXPFNJEIJIEEPKXAUBKBNNLIKWHJIYQDFWQVELOCB',
            'OTSZGTNPKFSGJLUPUNGGXFBYF9GVUEHOADZZTDEOJPWNEIVBLHOMUWPILAHTQHHVSBKTDVQIAEQOZXGFB',
        ];
        $expected = '9DZXPFSVCSSWXXQPFMWLGFKPBAFTHYMKMZCPFHBVHXPFNJEIJIEEPKXAUBKBNNLIKWHJIYQDFWQVELOCB';

        return [
            [
                [
                    'wereAddressesSpentFrom' => [
                        [$addresses[0]] => [true],
                        [$addresses[1]] => [false],
                    ],
                    'findTransactions' => [
                        [$addresses[0]] => [],
                        [$addresses[1]] => [],
                    ],
                    'expected' => $expected,
                ]
            ],
            [
                [
                    'wereAddressesSpentFrom' => [
                        [$addresses[0]] => [false],
                        [$addresses[1]] => [false],
                    ],
                    'findTransactions' => [
                        [$addresses[0]]=> ['A'],
                        [$addresses[1]] => [],
                    ],
                    'expected' => $expected,
                ]
            ],
        ];
    }

    private function wereAddressesSpentFrom($address)
    {
        return;
    }

    private function findTransactions($query)
    {
        return;
    }
}
