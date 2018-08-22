<?php

namespace Iota;

use Exception;

class RequestErrors
{
    public function __construct() {}

    public function invalidResponse(string $response)
    {
        return new Exception('Invalid Response: ' . $response);
    }

    public function noConnection(string $host)
    {
        return new Exception('No connection to host: ' . $host);
    }

    public function requestError(string $error)
    {
        return new Exception('Request Error: ' . $error);
    }
}
