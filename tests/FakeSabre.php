<?php

namespace Santosdave\SabreWrapper\Tests;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * A Guzzle handler standing in for Sabre's REST APIs: issues numbered tokens, answers the
 * airline lookup (or a status you choose), and remembers every request.
 */
class FakeSabre
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    public int $airlineStatus = 200;

    /** @var array<string, list<int>> path => statuses for its next calls (then 200 with {}) */
    public array $statuses = [];

    private int $tokens = 0;

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $this->requests[] = $request;

        $path = $request->getUri()->getPath();
        if (array_key_exists($path, $this->statuses)) {
            $status = array_shift($this->statuses[$path]) ?? 200;

            return Create::promiseFor($status === 200
                ? self::json(200, ['confirmationId' => 'ABC123'])
                : self::json($status, ['errors' => [['category' => 'SERVICE', 'type' => 'UNAVAILABLE', 'description' => "Error {$status}"]]]));
        }

        if ($request->getUri()->getPath() === '/v3/auth/token') {
            $this->tokens++;

            return Create::promiseFor(self::json(200, [
                'access_token' => 'token-'.$this->tokens,
                'token_type' => 'bearer',
                'expires_in' => 604800,
            ]));
        }

        if ($request->getUri()->getPath() === '/v1/lists/utilities/airlines') {
            return Create::promiseFor($this->airlineStatus === 200
                ? self::json(200, ['AirlineInfo' => [['AirlineCode' => 'KQ', 'AirlineName' => 'Kenya Airways']]])
                : self::json($this->airlineStatus, ['status' => 'NotProcessed', 'message' => 'Unknown airline']));
        }

        return Create::promiseFor(self::json(404, ['message' => 'Not faked: '.$request->getUri()->getPath()]));
    }

    /**
     * @return list<RequestInterface>
     */
    public function tokenRequests(): array
    {
        return array_values(array_filter($this->requests, fn (RequestInterface $r) => $r->getUri()->getPath() === '/v3/auth/token'));
    }

    /**
     * @return list<RequestInterface>
     */
    public function apiRequests(): array
    {
        return array_values(array_filter($this->requests, fn (RequestInterface $r) => $r->getUri()->getPath() !== '/v3/auth/token'));
    }

    private static function json(int $status, array $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }
}
