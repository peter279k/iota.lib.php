<?php

namespace Iota;

use Moontoast\Math\BigNumber;
use InvalidArgumentException;

class Utils
{
    private $inputValidator;

    private $makeRequest;

    private $curl;

    private $kerl;

    private $converter;

    private $signing;

    private $crypto; //phpseclib?

    private $ascii;

    private $extractJson;

    private $unitMap = [];

    public function getUnitMap()
    {
        $bigNumber = new BigNumber(10);
        $this->unitMap = [
            'i' => ['val' => $bigNumber(10)->pow(0), 'dp' => 0],
            'Ki' => ['val' => $bigNumber(10)->pow(3), 'dp' =>  3],
            'Mi' => ['val' => $bigNumber(10)->pow(6), 'dp' =>  6],
            'Gi' => ['val' => $bigNumber(10)->pow(9), 'dp' =>  9],
            'Ti' => ['val' => $bigNumber(10)->pow(12), 'dp' => 12],
            'Pi' => ['val' => $bigNumber(10)->pow(15), 'dp' => 15],// For the very, very rich
        ];
    }

    public function convertUnits(string $value, string $fromUnit, string $toUnit)
    {
        if (($this->unitMap[$fromUnit] ?? null) === null || ($this->unitMap[$toUnit] ?? null)) {
            throw new InvalidArgumentException('Invalid unit provided');
        }

        $valueBn = new BigNumber($value);
        $decimalString = explode('.', (string) $valueBn);
        $decimalPlaces = strlen($decimalString[1]);

        if ($decimalPlaces > $this->unitMap[$fromUnit]['dp']) {
            throw new InvalidArgumentException('Input value exceeded max fromUnit precision.');
        }

        $fromValue = $this->unitMap[$fromUnit]['val'];
        $toValue = $this->unitMap[$toUnit]['val'];

        $valueRaw = $valueBn->multiple($fromValue->getValue())->getValue();
        $valueScaled =$valueRaw->divide($toValue->getValue())->getValue();

        return $valueScaled;
    }

    public function addChecksum(array $inputValue, int $checksumLength, bool $isAddress = true)
    {
        $checksumLength = $checksumLength ?? 9;
        $validationLength = $isAddress ? 81 : null;

        $inputsWithChecksum = [];

        $converter = new Converter();

        foreach ($inputValue as $thisValue) {
            if ($inputValidator->isTrytes($thisValue, $validationLength)) {
                throw new InvalidArgumentException('Invalid input');
            }

            $kerl = new Kerl();
            $kerl->initialize();

            $addressTrits = $converter->trits($thisValue);

            $checksumTrits = [];

            // Absorb address trits
            $kerl->absorb($addressTrits, 0, strlen($addressTrits));

            // Squeeze checksum trits
            $kerl->squeeze($checksumTrits, 0, $curl->HASH_LENGTH);

            // First 9 trytes as checksum
            $checksum = substr($converter->trytes($checksumTrits), 81 - $checksumLength, $checksumLength);
            $inputsWithChecksum[] = $thisValue . $checksum;
        }

        return $inputsWithChecksum;
    }

    public function noCheckSum(array $address)
    {
        if (strlen($address[0]) === 81) {
            return $address[0];
        }

        $addressesWithChecksum = [];

        foreach ($address as $thisAddress) {
            $addressesWithChecksum[] = substr($thisAddress, 0, 81);
        }

        return $addressesWithChecksum;
    }

    public function isValidChecksum(string $addressesWithChecksum)
    {
        $addressesWithoutChecksum = $this->noCheckSum([$addressesWithChecksum]);
        $newChecksum = $this->addChecksum([$addressesWithoutChecksum]);

        return $newChecksum === $addressesWithChecksum;
    }

    public function transactionHash(array $transactionTrits)
    {
        if ($inputValidator->isTritArray($transactionTrits, 2673 * 3)) {
            throw InvalidArgumentException('Invalid transaction trits');
        }

        $hashTrits = [];
        $curl = new Curl();
        $curl->absorb($transactionTrits, 0, strlen($transactionTrits));
        $hashTrits = $curl->squeeze($hashTrits, 0, 243);

        return $hashTrits;
    }

    public function transactionObject(string $trytes, string $hash)
    {
        if (strlen($trytes) === 0) {
            return null;
        }

        for ($index = 2279; $index < 2295; $index++) {
            if ($trytes.charAt($index) !== "9") {
                return null;

            }
        }

        $thisTransaction = [];
        $transactionTrits = $converter->trits($trytes);
        $transactionTrits = is_string($transactionTrits) ? [$transactionTrits] : $transactionTrits;

        if ($inputValidator->isHash($hash)) {
            $thisTransaction['hash'] = $hash;
        } else {
            $thisTransaction['hash'] = $converter->trytes($this->transactionHash($transactionTrits));
        }

        $thisTransaction['signatureMessageFragment'] = substr($trytes, 0, 2187);
        $thisTransaction['address'] = substr($trytes, 2187, 2268 - 2187);
        $thisTransaction['value'] = $converter->value(substr($transactionTrits, 6804, 6837 - 6804));
        $thisTransaction['obsoleteTag'] = substr($trytes, 2295, 2322);
        $thisTransaction['timestamp'] = $converter->value(substr($transactionTrits, 6966, 6993 - 6966));
        $thisTransaction['currentIndex'] = $converter->value(substr($transactionTrits, 6993, 7020 - 6993));
        $thisTransaction['lastIndex'] = $converter->value(substr($transactionTrits, 7020, 7047 - 7020));
        $thisTransaction['bundle'] = substr($trytes, 2349, 2430 - 2349);
        $thisTransaction['trunkTransaction'] = substr($trytes, 2430, 2511 - 2430);
        $thisTransaction['branchTransaction'] = substr($trytes, 2511, 2592 - 2511);

        $thisTransaction['tag'] = substr($trytes, 2592, 2619 - 2592);
        $thisTransaction['attachmentTimestamp'] = $converter->value(substr($transactionTrits, 7857, 7884 - 7857));
        $thisTransaction['attachmentTimestampLowerBound'] = $converter->value(substr($transactionTrits, 7884, 7911 - 7884));
        $thisTransaction['attachmentTimestampUpperBound'] = $converter.value(substr($transactionTrits, 7911, 7938 - 7911));
        $thisTransaction['nonce'] = substr($trytes, 2646, 2673 - 2646);

        return $thisTransaction;
    }

    public function transactionTrytes(array $transaction)
    {
        $valueTrits = $converter->trits($transaction['value']);
        if (count($valueTrits) < 81) {
            array_push($valueTrits, array_fill(0, 81 - count($valueTrits), 0));
        }

        $timestampTrits = $converter->trits($transaction['timestamp']);
        if (count($timestampTrits) < 27) {
            array_push($timestampTrits, array_fill(0, 27 - count($timestampTrits), 0));
        }

        $currentIndexTrits = $converter->trits($transaction['currentIndex']);
        if (count($currentIndexTrits) < 27) {
            array_push($currentIndexTrits, array_fill(0, 27 - count($currentIndexTrits), 0));
        }

        $lastIndexTrits = $converter->trits($transaction['lastIndex']);
        if (count($lastIndexTrits) < 27) {
            array_push($lastIndexTrits, array_fill(0, 27 - count($lastIndexTrits), 0));
        }

        $attachmentTimestampTrits = $converter->trits($transaction['attachmentTimestamp'] ?? 0);
        if (count($attachmentTimestampTrits) < 27) {
            array_push($attachmentTimestampTrits, array_fill(0, 27 - count($attachmentTimestampTrits), 0));
        }

        $attachmentTimestampLowerBoundTrits = $converter->trits($transaction['attachmentTimestampLowerBound'] ?? 0);
        if (count($attachmentTimestampLowerBoundTrits) < 27) {
            array_push($attachmentTimestampLowerBoundTrits, array_fill(0, 27 - count($attachmentTimestampLowerBoundTrits), 0));
        }

        $attachmentTimestampUpperBoundTrits = $converter->trits($transaction['$attachmentTimestampUpperBound'] ?? 0);
        if (count($attachmentTimestampUpperBoundTrits) < 27) {
            array_push($attachmentTimestampUpperBoundTrits, array_fill(0, 27 - count($attachmentTimestampUpperBoundTrits), 0));
        }

        $transaction['tag'] = $transaction['tag'] ?? $transaction['obsoleteTag'];

        return $transaction['signatureMessageFragment']
        . $transaction['address']
        . $converter->trytes($valueTrits)
        . $transaction['obsoleteTag']
        . $converter->trytes($timestampTrits)
        . $converter->trytes($currentIndexTrits)
        . $converter->trytes($lastIndexTrits)
        . $transaction['bundle']
        . $transaction['trunkTransaction']
        . $transaction['branchTransaction']
        . $transaction['tag']
        . $converter->trytes($attachmentTimestampTrits)
        . $converter->trytes($attachmentTimestampLowerBoundTrits)
        . $converter->trytes($attachmentTimestampUpperBoundTrits)
        . $transaction['nonce'];
    }

    public function isTransactionHash($input, int $minWeightMagnitude)
    {
        $isTxObject = $inputValidator->isArrayOfTxObjects([$input]);

        $tritIsZero = true;
        $tritArray = $converter->trits($isTxObject ? $input['hash'] : $input);
        $tritValue = array_slice($tritArray, -$minWeightMagnitude);
        foreach ($tritValue as $trit) {
            if ($trit !== 0) {
                $tritIsZero = false;
                break;
            }
        }

        $transactionTrit = $this->transactionHash($converter->trits($this->transactionTrytes($input)));
        $isHashEqual = ($input['hash'] === $converter->trytes($transactionTrit));
        $isTxObjectResult = $isTxObject ? $isHashEqual : $inputValidator->isHash($input);

        return ($minWeightMagnitude ? $tritIsZero : true) && $isTxObjectResult;
    }

    public function categorizeTransfers(array $transfers, array $addresses)
    {
        $categorized = [
            'sent' => [],
            'received' => [],
        ];

        foreach ($transfers as $bundle) {

            $spentAlreadyAdded = false;

            foreach ($bundle as $bundleEntry => $bundleIndex) {
                if (in_array($bundleEntry['address'], $addresses)) {

                    // Check if it's a remainder address
                    $isRemainder = ($bundleEntry['currentIndex'] === $bundleEntry['lastIndex']) && $bundleEntry['lastIndex'] !== 0;

                    // check if sent transaction
                    if ($bundleEntry['value'] < 0 && !$spentAlreadyAdded && !$isRemainder) {

                        array_merge($categorized['sent'], $bundle);

                        // too make sure we do not add transactions twice
                        $spentAlreadyAdded = true;
                    }
                    // check if received transaction, or 0 value (message)
                    // also make sure that this is not a 2nd tx for spent inputs
                    else if ($bundleEntry['value'] >= 0 && !$spentAlreadyAdded && !$isRemainder) {

                        array_merge($categorized['received'], bundle);

                    }
                }
            }
        }

        return $categorized;
    }

    public function validateSignatures(array $signedBundle, string $inputAddress)
    {

        $bundleHash = '';
        $signatureFragments = [];

        for ($index = 0; $index < count($signedBundle); $index++) {

            if ($signedBundle[$index]['address'] === $inputAddress) {

                $bundleHash = $signedBundle[$index]['bundle'];

                // if we reached remainder bundle
                if ($inputValidator->isNinesTrytes($signedBundle[$index]['signatureMessageFragment'])) {
                    break;
                }

                $signatureFragments[] = $signedBundle[$index]['signatureMessageFragment'];
            }
        }

        if ($bundleHash === '') {
            return false;
        }

        return $signing->validateSignatures($inputAddress, $signatureFragments, $bundleHash);
    }

    public function isBundle(array $bundle) {

        // If not correct bundle
        if ($inputValidator->isArrayOfTxObjects($bundle)) {
            return false;
        }

        $totalSum = 0;
        $lastIndex = 0;
        $bundleHash = $bundle[0]['bundle'];

        // Prepare to absorb txs and get bundleHash
        $bundleFromTxs = [];

        $kerl = new Kerl();
        $kerl.initialize();

        // Prepare for signature validation
        $signaturesToValidate = [];

        foreach ($bundle as $bundleTx => $index) {

            $totalSum += $bundleTx['value'];

            // currentIndex has to be equal to the index in the array
            if ($bundleTx['currentIndex'] !== $index) {
                return false;
            }

            // Get the transaction trytes
            $thisTxTrytes = $this->transactionTrytes($bundleTx);

            // Absorb bundle hash + value + timestamp + lastIndex + currentIndex trytes.
            $thisTxTrits = $converter->trits(substr($thisTxTrytes, 2187, 162));
            $kerl->absorb($thisTxTrits, 0, $thisTxTrits.length);

            // Check if input transaction
            if ($bundleTx['value'] < 0) {
                $thisAddress = $bundleTx['address'];

                $newSignatureToValidate = [
                    'address' => $thisAddress,
                    'signatureFragments' => [
                        $bundleTx['signatureMessageFragment']
                    ],
                ];

                // Find the subsequent txs with the remaining signature fragment
                for ($i = $index; $i < count($bundle) - 1; $i++) {
                    $newBundleTx = $bundle[$i + 1];

                    // Check if new tx is part of the signature fragment
                    if ($newBundleTx['address'] === $thisAddress && $newBundleTx['value'] === 0) {
                        $newSignatureToValidate['signatureFragments'][] = $newBundleTx['signatureMessageFragment'];
                    }
                }

                $signaturesToValidate[] = $newSignatureToValidate;
            }
        }

        // Check for total sum, if not equal 0 return error
        if ($totalSum !== 0) {
            return false;
        }

        // get the bundle hash from the bundle transactions
        $kerl->squeeze($bundleFromTxs, 0, $curl->HASH_LENGTH);
        $bundleFromTxs = $converter->trytes($bundleFromTxs);

        // Check if bundle hash is the same as returned by tx object
        if ($bundleFromTxs !== $bundleHash) {
            return false;
        }

        // Last tx in the bundle should have currentIndex === lastIndex
        if ($bundle[count($bundle) - 1]['currentIndex'] !== $bundle[count($bundle) - 1]['lastIndex']) {
            return false;
        }

        // Validate the signatures
        for ($i = 0; $i < count($signaturesToValidate); $i++) {

            $isValidSignature = $signing->validateSignatures($signaturesToValidate[$i]['address'], $signaturesToValidate[$i]['signatureFragments'], $bundleHash);

            if (!$isValidSignature) {
                return false;
            }
        }

        return true;
    }
}
