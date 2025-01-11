<?php

namespace Santosdave\SabreWrapper\Services\Core;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Santosdave\SabreWrapper\Exceptions\SabreApiException;
use Santosdave\SabreWrapper\Services\Auth\SabreAuthenticator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Santosdave\SabreWrapper\Notifications\HealthCheckAlert;

class HealthCheckService
{
    private array $config;
    private SabreAuthenticator $authenticator;
    private RateLimitService $rateLimiter;

    private array $checks = [];
    private array $results = [];

    public function __construct(
        SabreAuthenticator $authenticator,
        RateLimitService $rateLimiter,
        private string $environment = 'cert'
    ) {
        $this->authenticator = $authenticator;
        $this->rateLimiter = $rateLimiter;
        $this->loadConfig();
        $this->registerChecks();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'check_interval' => config('sabre.health.check_interval', 60),
            'timeout' => config('sabre.health.timeout', 5),
            'cache_ttl' => config('sabre.health.cache_ttl', 300),
            'notification_threshold' => config('sabre.health.notification_threshold', 3)
        ];
    }

    private function registerChecks(): void
    {
        $this->checks = [
            'authentication' => [
                'name' => 'Authentication Service',
                'check' => [$this, 'checkAuthentication'],
                'critical' => true
            ],
            'rate_limits' => [
                'name' => 'Rate Limits',
                'check' => [$this, 'checkRateLimits'],
                'critical' => true
            ],
            'api_endpoints' => [
                'name' => 'API Endpoints',
                'check' => [$this, 'checkApiEndpoints'],
                'critical' => true
            ],
            'queues' => [
                'name' => 'Job Queues',
                'check' => [$this, 'checkQueues'],
                'critical' => false
            ],
            'cache' => [
                'name' => 'Cache Service',
                'check' => [$this, 'checkCache'],
                'critical' => false
            ]
        ];
    }

    public function runHealthCheck(): array
    {
        $this->results = [
            'status' => 'healthy',
            'timestamp' => now(),
            'checks' => [],
            'meta' => [
                'version' => config('sabre.version'),
                'environment' => config('sabre.environment')
            ]
        ];

        foreach ($this->checks as $key => $check) {
            try {
                $result = call_user_func($check['check']);
                $this->results['checks'][$key] = array_merge(
                    ['name' => $check['name']],
                    $result
                );

                if ($result['status'] === 'error' && $check['critical']) {
                    $this->results['status'] = 'error';
                } elseif ($result['status'] === 'warning' && $this->results['status'] !== 'error') {
                    $this->results['status'] = 'warning';
                }
            } catch (\Exception $e) {
                $this->handleCheckError($key, $check, $e);
            }
        }

        $this->storeResults();
        $this->notifyIfNeeded();

        return $this->results;
    }

    private function checkAuthentication(): array
    {
        try {
            // Test token generation
            $token = $this->authenticator->getToken();

            return [
                'status' => 'healthy',
                'message' => 'Authentication service is working',
                'details' => [
                    'token_valid' => !empty($token),
                    'last_refresh' => Cache::get('sabre_last_token_refresh')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Authentication service error: ' . $e->getMessage(),
                'details' => [
                    'error_type' => get_class($e),
                    'last_success' => Cache::get('sabre_last_successful_auth')
                ]
            ];
        }
    }

    private function checkRateLimits(): array
    {
        $limits = $this->rateLimiter->getRateLimitInfo('health.check');
        $status = 'healthy';
        $message = 'Rate limits are within acceptable ranges';

        if ($limits['remaining'] < ($limits['limit'] * 0.2)) {
            $status = 'warning';
            $message = 'Rate limits are close to threshold';
        }

        return [
            'status' => $status,
            'message' => $message,
            'details' => $limits
        ];
    }

    private function checkApiEndpoints(): array
    {
        $endpoints = [
            'shopping' => '/v3/offers/shop',
            'orders' => '/v1/orders/view',
            'booking' => '/v1/trip/orders/getBooking'
        ];

        $results = [];
        $overallStatus = 'healthy';

        foreach ($endpoints as $name => $endpoint) {
            try {
                $response = $this->testEndpoint($endpoint);
                $results[$name] = [
                    'status' => 'healthy',
                    'response_time' => $response['time'],
                    'last_check' => now()->toIso8601String()
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'last_check' => now()->toIso8601String()
                ];
                $overallStatus = 'error';
            }
        }

        return [
            'status' => $overallStatus,
            'message' => $overallStatus === 'healthy'
                ? 'All endpoints are responding'
                : 'Some endpoints are not responding',
            'details' => $results
        ];
    }

    private function checkQueues(): array
    {
        $queues = ['sabre', 'sabre-high', 'sabre-low'];
        $results = [];
        $status = 'healthy';

        foreach ($queues as $queue) {
            $size = Queue::size($queue);
            $failed = DB::table('failed_jobs')
                ->where('queue', $queue)
                ->where('failed_at', '>=', now()->subHours(1))
                ->count();

            $queueStatus = 'healthy';
            if ($size > 1000 || $failed > 50) {
                $queueStatus = 'error';
                $status = 'error';
            } elseif ($size > 500 || $failed > 20) {
                $queueStatus = 'warning';
                if ($status === 'healthy') $status = 'warning';
            }

            $results[$queue] = [
                'status' => $queueStatus,
                'size' => $size,
                'failed_last_hour' => $failed
            ];
        }

        return [
            'status' => $status,
            'message' => $status === 'healthy'
                ? 'Queue systems are operating normally'
                : 'Queue system issues detected',
            'details' => $results
        ];
    }

    private function checkCache(): array
    {
        try {
            $key = 'sabre_health_check_' . uniqid();
            $value = now()->toIso8601String();

            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            return [
                'status' => $value === $retrieved ? 'healthy' : 'error',
                'message' => $value === $retrieved
                    ? 'Cache system is working correctly'
                    : 'Cache read/write mismatch',
                'details' => [
                    'driver' => config('cache.default'),
                    'write_test' => $value === $retrieved
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system error: ' . $e->getMessage(),
                'details' => [
                    'driver' => config('cache.default'),
                    'error_type' => get_class($e)
                ]
            ];
        }
    }

    private function handleCheckError(string $key, array $check, \Exception $e): void
    {
        $error = [
            'status' => 'error',
            'name' => $check['name'],
            'message' => "Check failed: {$e->getMessage()}",
            'exception' => get_class($e)
        ];

        $this->results['checks'][$key] = $error;

        if ($check['critical']) {
            $this->results['status'] = 'error';
        }

        Log::error('Health check failed', [
            'check' => $key,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    private function storeResults(): void
    {
        Cache::put(
            'sabre_health_status',
            $this->results,
            now()->addSeconds($this->config['cache_ttl'])
        );
    }

    private function notifyIfNeeded(): void
    {
        if ($this->results['status'] === 'error') {
            $this->sendNotification();
        }
    }


    private function testEndpoint(string $endpoint): array
    {
        $start = microtime(true);

        try {
            $response = Http::timeout($this->config['timeout'])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->authenticator->getToken()
                ])
                ->get(config("sabre.endpoints.{$this->environment}.rest") . $endpoint);

            $time = (microtime(true) - $start) * 1000; // Convert to milliseconds

            if (!$response->successful()) {
                throw new SabreApiException(
                    "Endpoint check failed: {$response->status()}",
                    $response->status()
                );
            }

            return [
                'time' => round($time, 2),
                'status_code' => $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('Endpoint test failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getLatestResults(): array
    {
        return Cache::get('sabre_health_status', [
            'status' => 'unknown',
            'timestamp' => now(),
            'checks' => []
        ]);
    }

    public function getHistoricalResults(int $hours = 24): array
    {
        $results = [];
        $now = now();

        for ($i = 0; $i < $hours; $i++) {
            $timestamp = $now->copy()->subHours($i)->format('Y-m-d H:00:00');
            $key = "sabre_health_status_history_{$timestamp}";
            $historicalResult = Cache::get($key);

            if ($historicalResult) {
                $results[] = $historicalResult;
            }
        }

        return array_reverse($results);
    }

    private function storeHistoricalResult(): void
    {
        $timestamp = now()->format('Y-m-d H:00:00');
        $key = "sabre_health_status_history_{$timestamp}";

        Cache::put($key, $this->results, now()->addDays(7));
    }

    private function sendNotification(): void
    {
        $lastNotification = Cache::get('sabre_health_last_notification');
        $cooldown = now()->subMinutes(5);

        if (!$lastNotification || $lastNotification < $cooldown) {
            try {
                Notification::route('mail', config('sabre.health.notification_email'))
                    ->notify(new HealthCheckAlert($this->results));

                Cache::put('sabre_health_last_notification', now(), now()->addHour());

                Log::info('Health check notification sent', [
                    'status' => $this->results['status'],
                    'checks' => array_keys($this->results['checks'])
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send health check notification', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function getMetrics(): array
    {
        return [
            'uptime' => $this->getUptime(),
            'response_times' => $this->getResponseTimes(),
            'error_rates' => $this->getErrorRates(),
            'rate_limits' => $this->getRateLimitMetrics(),
            'queue_metrics' => $this->getQueueMetrics()
        ];
    }

    private function getUptime(): array
    {
        $historicalResults = $this->getHistoricalResults(24);
        $total = count($historicalResults);
        $healthy = count(array_filter($historicalResults, fn($result) => $result['status'] === 'healthy'));

        return [
            'percentage' => $total > 0 ? ($healthy / $total) * 100 : 100,
            'last_24h' => $historicalResults
        ];
    }

    private function getResponseTimes(): array
    {
        $times = [];
        $key = 'sabre_response_times_' . now()->format('Y-m-d');
        $stored = Cache::get($key, []);

        foreach (['shopping', 'booking', 'orders'] as $service) {
            $times[$service] = [
                'avg' => $stored[$service]['avg'] ?? 0,
                'min' => $stored[$service]['min'] ?? 0,
                'max' => $stored[$service]['max'] ?? 0,
                'p95' => $stored[$service]['p95'] ?? 0
            ];
        }

        return $times;
    }

    private function getErrorRates(): array
    {
        $now = now();
        $rates = [];

        for ($i = 0; $i < 24; $i++) {
            $timestamp = $now->copy()->subHours($i)->format('Y-m-d H:00:00');
            $key = "sabre_errors_{$timestamp}";

            $rates[$timestamp] = [
                'total' => Cache::get("{$key}_total", 0),
                'by_type' => Cache::get("{$key}_by_type", [])
            ];
        }

        return $rates;
    }

    private function getRateLimitMetrics(): array
    {
        $metrics = [];
        foreach ($this->rateLimiter->getRateLimitInfo('all') as $key => $info) {
            $metrics[$key] = [
                'current' => $info['remaining'],
                'limit' => $info['limit'],
                'reset_at' => $info['reset'],
                'usage_percentage' => (($info['limit'] - $info['remaining']) / $info['limit']) * 100
            ];
        }
        return $metrics;
    }

    private function getQueueMetrics(): array
    {
        $queues = ['sabre', 'sabre-high', 'sabre-low'];
        $metrics = [];

        foreach ($queues as $queue) {
            $metrics[$queue] = [
                'size' => Queue::size($queue),
                'processed_last_hour' => $this->getProcessedJobCount($queue),
                'failed_last_hour' => $this->getFailedJobCount($queue),
                'processing' => $this->getCurrentlyProcessing($queue)
            ];
        }

        return $metrics;
    }

    private function getProcessedJobCount(string $queue): int
    {
        return (int) Cache::get("sabre_processed_jobs_{$queue}_" . now()->format('Y-m-d-H'), 0);
    }

    private function getFailedJobCount(string $queue): int
    {
        return DB::table('failed_jobs')
            ->where('queue', $queue)
            ->where('failed_at', '>=', now()->subHour())
            ->count();
    }

    private function getCurrentlyProcessing(string $queue): int
    {
        return (int) Cache::get("sabre_processing_{$queue}", 0);
    }

    public function resetErrorCount(string $service = null): void
    {
        if ($service) {
            Cache::forget("sabre_errors_{$service}_" . now()->format('Y-m-d-H'));
        } else {
            Cache::forget("sabre_errors_total_" . now()->format('Y-m-d-H'));
        }
    }

    public function incrementErrorCount(string $service, string $errorType): void
    {
        $timestamp = now()->format('Y-m-d-H');

        // Increment total errors
        Cache::increment("sabre_errors_{$timestamp}_total");

        // Increment service-specific errors
        Cache::increment("sabre_errors_{$service}_{$timestamp}_total");

        // Increment error type counter
        $key = "sabre_errors_{$service}_{$timestamp}_by_type";
        $errors = Cache::get($key, []);
        $errors[$errorType] = ($errors[$errorType] ?? 0) + 1;
        Cache::put($key, $errors, now()->addDay());
    }
}
