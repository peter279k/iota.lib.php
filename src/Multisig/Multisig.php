<?php

namespace Iota;

use Carbon\Carbon;
use InvalidArgumentException;

class Multisig
{
    private $makeRequest;

    public function __construct(MakeRequest $provider)
    {
        $this->makeRequest = $provider;
    }

    public function getKey(string $seed, int $index, int $security)
    {
        $converter = new Converter();
        $signing = new Signing();

        return $converter.trytes($signing->key($converter->trits($seed), $index, $security));
    }

    public function getDigest(string $seed, int $index, int $security)
    {
        $converter = new Converter();
        $signing = new Signing();

        $key = $signing->key($converter->trits($seed), $index, $security);

        return $converter.trytes($signing->digests($key));
    }

    public function validateAddress(string $multisigAddress, array $digests)
    {
        $kerl = new Kerl();
        $kerl->initialize();
        $converter = new Converter();
        $curl = new Curl();

        foreach ($digests as $keyDigest) {
            $trits = $converter.trits($keyDigest);
            $kerl->absorb($converter->trits($keyDigest), 0, count($trits));
        }

        $addressTrits = [];
        $kerl->squeeze($addressTrits, 0, $curl->HASH_LENGTH);

        return $converter->trytes($addressTrits) === $multisigAddress;
    }

    public function initiateTransfer(Input $input, string $remainderAddress, array $transfers)
    {
        $utils = new Utils();
        $inputValidator = new InputValidator();
        $errors = new InputErrors();

        foreach ($transfers as $thisTransfer) {
            $thisTransfer->message = $thisTransfer->message ?? '';
            $thisTransfer->tag = $thisTransfer->tag ?? '';
            $thisTransfer->address = $utils->noChecksum($thisTransfer->address);
        }

        if (!$inputValidator->isTransfersArray($transfers)) {
            $errors->invalidTransfers();
        }

        if (!$inputValidator->isValue($input->securitySum)) {
            $errors->invalidInputs();
        }

        if (!$inputValidator->isAddress($input->address)) {
            $errors->invalidTrytes();
        }

        if ($remainderAddress && !$inputValidator->isAddress($remainderAddress)) {
            $errors->invalidTrytes();
        }

        $signatureFragments = $this->buildSignatureFragments($transfers)[0];
        $totalValue = $this->buildSignatureFragments($transfers)[1];

        if ($totalValue === 0) {
            throw new InvalidArgumentException('Invalid value transfer: the transfer does not require a signature.');
        }

        if (isset($input->balance)) {
            $bundle = $this->createBundle($input->balance);
          } else {
            $command = [
                'command' => 'getBalances',
                'addresses' => [$input->address],
                'threshold' => 100,
            ];

            $balances = $this->makeRequest->send($command);
            $this->createBundle((int) $balances->balances[0]);
          }
    }

    public function addSignature(array $bundleToSign, string $inputAddress, string $key)
    {
        $bundle = new Bundle();
        $bundle->bundle = $bundleToSign;

        $converter = new Converter();
        $security = strlen($key) / 2187;
        $key = $converter->trits($key);

        $inputValidator = new InputValidator();
        $signing = new Signing();

        $numSignedTxs = 0;

        for ($index = 0; $index < count($bundle->bundle); $index++) {
            if ($bundle->bundle[$index]->address === $inputAddress) {
                if (!$inputValidator.isNinesTrytes($bundle->bundle[$index]->signatureMessageFragment)) {
                    $numSignedTxs++;
                    continue;
                }

                $bundleHash = $bundle->bundle[$index]->bundle;
                $firstFragment = substr($key, 0, 6561);

                $normalizedBundleHash = $bundle->normalizedBundle($bundleHash);
                $normalizedBundleFragments = [];

                for ($k = 0; $k < 3; $k++) {
                    $normalizedBundleFragments[$k] = substr($normalizedBundleHash, $k * 27, ($k + 1) * 27 - $k * 27);
                }

                $firstBundleFragment = $normalizedBundleFragments[$numSignedTxs % 3];

                $firstSignedFragment = $signing->signatureFragment($firstBundleFragment, $firstFragment);

                $bundle->bundle[$index]->signatureMessageFragment = $converter->trytes($firstSignedFragment);

                for ($j = 1; $j < $security; $j++) {
                    $nextFragment = substr($key, 6561 * $j, ($j + 1) * 6561 - 6561 * j);
                    $nextBundleFragment = $normalizedBundleFragments[($numSignedTxs + $j) % 3];
                    $nextSignedFragment = $signing->signatureFragment($nextBundleFragment, $nextFragment);
                    $bundle->bundle[$index + j]->signatureMessageFragment = $converter->trytes($nextSignedFragment);
                }
            }
        }

        return $bundle->bundle;
    }

    private function createBundle(int $totalValue, int $totalBalance, InputValidator $input, string $remainderAddress, array $signatureFragments)
    {
        $bundle = new Bundle();
        if ($totalBalance > 0) {
            $toSubtract = 0 - $totalBalance;
            $timestamp = floor(Carbon::now('Asia/Taipei')->timestamp / 1000);

            $bundle.addEntry($input->securitySum, $input->address, $toSubtract, $tag, $timestamp);
        }

        if ($totalValue > $totalBalance) {
            throw new InvalidArgumentException("Not enough balance.");
        }

        if ($totalBalance > $totalValue) {
            $remainder = $totalBalance - $totalValue;

            if (empty($remainderAddress)) {
                throw new InvalidArgumentException("No remainder address defined");
            }

            $bundle->addEntry(1, $remainderAddress, $remainder, $tag, $timestamp);
        }

        $bundle->finalize();
        $bundle->addTrytes($signatureFragments);

        return $bundle;
    }

    private function buildSignatureFragments(array $transfers)
    {
        $signatureFragments = [];
        $tag = '';
        $totalValue = 0;

        for ($index = 0; $index < count($transfers); $index++) {
            $signatureMessageLength = 1;
            if (strlen($transfers[$index]->message) <= 2187) {
                $fragment = '';

                if (isset($transfers[$index]->message)) {
                    $fragment = substr($transfers[$index]->message, 0, 2187);
                }

                for ($j = 0; strlen($fragment) < 2187; $j++) {
                    $fragment .= '9';
                }

                $signatureFragments[] = $fragment;

                continue;
            }

            $signatureMessageLength += floor(strlen($transfers[$index]->message) / 2187);

            $msgCopy = $transfers[$index]->message;
            while ($msgCopy) {
                $fragment = substr($msgCopy, 0, 2187);
                $msgCopy = substr($msgCopy, 2187, strlen($msgCopy) - 2187);

                for ($j = 0; strlen($fragment) < 2187; $j++) {
                    $fragment .= '9';
                }

                $signatureFragments[] = $fragment;
            }


            $timestamp = floor(Carbon::now('Asia/Taipei')->timestamp / 1000);
            $tag = $transfers[$index]->tag ?? '999999999999999999999999999';
            for ($j = 0; strlen($tag) < 27; $j++) {
                $tag .= '9';
            }

            $bundle->addEntry($signatureMessageLength, substr($transfers[$index]->address, 0, 81), $transfers[$index]->value, $tag, $timestamp);
            $totalValue += (int) $transfers[$index]->value;
        }

        return [$signatureFragments, $totalValue, $tag];
    }
}
