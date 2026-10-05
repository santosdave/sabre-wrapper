<?php

namespace Santosdave\SabreWrapper\Tests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Santosdave\SabreWrapper\Services\Auth\SabreAuthenticator;
use Santosdave\SabreWrapper\Services\ServiceFactory;

/**
 * Getting and keeping REST tokens: once per account, never shared between accounts, and
 * never blocked by a lock a dead process left behind.
 */
class TokenReuseTest extends TestCase
{
    private function lookup(?ServiceFactory $factory = null): array
    {
        return ($factory ?? app(ServiceFactory::class))->createUtilityService()->getAirlineInfo('KQ');
    }

    public function test_the_first_token_is_reused(): void
    {
        $this->lookup();
        $this->lookup();
        $this->lookup();

        $this->assertCount(1, $this->sabre->tokenRequests());
    }

    public function test_two_accounts_keep_their_own_tokens(): void
    {
        $agencyA = new ServiceFactory(new SabreAuthenticator('user-a', 'pw-a', 'PCCA', 'cert', 'client-a', 'secret-a'), 'cert');
        $agencyB = new ServiceFactory(new SabreAuthenticator('user-b', 'pw-b', 'PCCB', 'cert', 'client-b', 'secret-b'), 'cert');

        foreach ([$agencyA, $agencyB, $agencyA, $agencyB, $agencyA, $agencyB] as $agency) {
            $this->lookup($agency);
        }

        $this->assertCount(2, $this->sabre->tokenRequests());
        $sent = array_map(fn ($r) => $r->getHeaderLine('Authorization'), $this->sabre->apiRequests());
        $this->assertSame(['Bearer token-1', 'Bearer token-2', 'Bearer token-1', 'Bearer token-2', 'Bearer token-1', 'Bearer token-2'], $sent);
    }

    public function test_a_stale_refresh_lock_is_taken_over(): void
    {
        // A lock left behind by a process that died while refreshing.
        Cache::put('sabre_lock_refresh_token_rest', ['owner' => 'dead-process', 'acquired_at' => time() - 3600], 3600);

        $result = $this->lookup();

        $this->assertSame('Kenya Airways', $result[0]['AirlineName']);
    }

    public function test_a_refresh_lock_held_by_a_dead_process_expires(): void
    {
        Carbon::setTestNow('2026-10-05 08:00:00');
        // Taken by a process that died before releasing it...
        $this->assertTrue(Cache::lock('sabre_lock_test-user_TPCC_refresh_token_rest', 10)->get());
        // ...and the lock outlives it by its timeout, not forever.
        Carbon::setTestNow('2026-10-05 08:00:11');

        $result = $this->lookup();

        $this->assertSame('Kenya Airways', $result[0]['AirlineName']);
        Carbon::setTestNow();
    }
}
