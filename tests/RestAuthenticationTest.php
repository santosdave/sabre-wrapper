<?php

namespace Santosdave\SabreWrapper\Tests;

use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Services\ServiceFactory;

/**
 * REST calls and the token behind them.
 */
class RestAuthenticationTest extends TestCase
{
    private function lookup(?ServiceFactory $factory = null): array
    {
        return ($factory ?? app(ServiceFactory::class))->createUtilityService()->getAirlineInfo('KQ');
    }

    public function test_a_rest_call_sends_the_token_and_returns_the_payload(): void
    {
        $result = $this->lookup();

        $this->assertSame('Kenya Airways', $result[0]['AirlineName']);
        $call = $this->sabre->apiRequests()[0];
        $this->assertSame('Bearer token-1', $call->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $call->getHeaderLine('Accept'));
        $this->assertSame('airlinecode=KQ', $call->getUri()->getQuery());
    }

    public function test_the_token_request_uses_the_client_credentials_and_the_epr_pcc_user_name(): void
    {
        $this->lookup();

        $token = $this->sabre->tokenRequests()[0];
        $this->assertSame('POST', $token->getMethod());
        $this->assertSame('Basic '.base64_encode('test-client:test-secret'), $token->getHeaderLine('Authorization'));
        parse_str((string) $token->getBody(), $form);
        $this->assertSame(['grant_type' => 'password', 'username' => 'test-user-TPCC-AA', 'password' => 'test-password'], $form);
    }

    public function test_an_api_error_raises_with_its_status(): void
    {
        $this->sabre->airlineStatus = 404;

        try {
            $this->lookup();
            $this->fail('Expected an exception');
        } catch (SabreApiException $e) {
            $this->assertSame(404, $e->getCode());
        }
    }
}
