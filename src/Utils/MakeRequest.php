<?php

namespace Iota;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * TODO
*/
class MakeRequest
{
    private $provider;

    private $tokenOrUsername;

    private $password;

    private $timeout = -1;

    private $client;

    public function __construct(string $provider = null, string $tokenOrUsername = null, string $password = null)
    {
        $this->provider = $provider ?? 'http://localhost:14265';
        $this->tokenOrUsername = $tokenOrUsername ?? '';
        $this->password = $password ?? false;
    }

    public function setApiTimeout(int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function setProvider(string $provider)
    {
        $this->provider = $provider;
    }

    public function open()
    {
        $requestHeraders = [
            'User-Agent' => 'GuzzleHttp',
            'Content-Type' => 'application/json',
            'X-IOTA-API-Version' => '1',
        ];

        $client = new Client([
            'headers' => $requestHeraders,
            'base_uri' => $this->provider,
        ]);

        if ($this->password !== null) {
            $requestOptions = [
                'auth' => [$this->tokenOrUsername, $this->password],
                'timeout' => $this->timeout,
                'headers' => $requestHeraders,
            ];
        } else {
            $requestOptions = [
                'timeout' => $this->timeout,
                'headers' => $requestHeraders,
            ];
        }

        if ($this->password === null && $this->tokenOrUsername !== null) {
            $requestHeraders['Authorization'] = 'token ' . $this->tokenOrUsername;
        }

        return $client;
    }

    public function send(array $command)
    {
        $request = $this->open();
        $timeoutError = json_encode(['error' => 'Request timed out.']);

        try {
            $requestOptions['body'] = json_encode($command);
            $response = $client->request('POST', '/', $requestOptions);
        } catch (RequestException $e) {
            $requestError = new RequestErrors();
            $requestError->invalidResponse($e->getMessage());
        }

        return $this->prepareResult(strlen((string) $response->getBody()), $command['command']);
    }

    public function batchedSend(array $command, array $keys, int $batchSize)
    {
        $requestStack = [];

        foreach ($keys as $key) {
            $clone = $command[$key];
        }
    }
}
