<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;

/**
 * API client for talking to Star Wars API
 */
class StarWarsApiClient
{
    public const BASE_URL = 'https://www.swapi.tech/api';

    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::baseUrl(self::BASE_URL)->acceptJson();
    }

    public function client(): PendingRequest
    {
        return $this->client;
    }

    /**
     * @param string $url
     * @param array $query
     * @return object
     * @throws ConnectionException
     */
    public function get(string $url, array $query = []): object
    {
        return $this->client->get($url, $query)->object();
    }
}
