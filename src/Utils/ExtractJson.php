<?php

namespace Iota;

class ExtractJson
{
    public function extractJson(array $bundle)
    {
        $ascii = new AsciiToTrytes();

        // if wrong input return null
        if (!$inputValidator.isArray($bundle) || empty($bundle[0])) {
            return null;
        }

        // Sanity check: if the first tryte pair is not opening bracket, it's not a message
        $firstTrytePair = $bundle[0]['signatureMessageFragment'][0] + $bundle[0]['signatureMessageFragment'][1];

        if ($firstTrytePair !== "OD") {
            return null;
        }

        $index = 0;
        $notEnded = true;
        $trytesChunk = '';
        $trytesChecked = 0;
        $preliminaryStop = false;
        $finalJson = '';

        while ($index < count($bundle) && $notEnded) {

            $messageChunk = $bundle[$index]['signatureMessageFragment'];

            // We iterate over the message chunk, reading 9 trytes at a time
            for ($i = 0; $i < strlen($messageChunk); $i += 9) {

                // get 9 trytes
                $trytes = substr($messageChunk, $i, 9);
                $trytesChunk += $trytes;

                // Get the upper limit of the tytes that need to be checked
                // because we only check 2 trytes at a time, there is sometimes a leftover
                $upperLimit = strlen($trytesChunk) - strlen($trytesChunk) % 2;

                $trytesToCheck = substr($trytesChunk, $trytesChecked, $upperLimit);

                // We read 2 trytes at a time and check if it equals the closing bracket character
                for ($j = 0; $j < strlen($trytesToCheck); $j += 2) {

                    $trytePair = $trytesToCheck[$j] + $trytesToCheck[$j + 1];

                    // If closing bracket char was found, and there are only trailing 9's
                    // we quit and remove the 9's from the trytesChunk.
                    if ($preliminaryStop && $trytePair === '99' ) {

                        $notEnded = false;
                        // TODO: Remove the trailing 9's from trytesChunk
                        //var closingBracket = trytesToCheck.indexOf('QD') + 1;

                        //trytesChunk = trytesChunk.slice( 0, ( trytesChunk.length - trytesToCheck.length ) + ( closingBracket % 2 === 0 ? closingBracket : closingBracket + 1 ) );

                        break;
                    }

                    $finalJson += $ascii->fromTrytes($trytePair);

                    // If tryte pair equals closing bracket char, we set a preliminary stop
                    // the preliminaryStop is useful when we have a nested JSON object
                    if ($trytePair === "QD") {
                        $preliminaryStop = true;
                    }
                }

                if (!$notEnded)
                    break;

                $trytesChecked += strlen($trytesToCheck);
            }

            // If we have not reached the end of the message yet, we continue with the next
            // transaction in the bundle
            $index += 1;
        }

        // If we did not find any JSON, return null
        if ($notEnded) {
            return null;

        } else {
            return $finalJson;

        }
    }
}
