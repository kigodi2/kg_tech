<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ProductionCheckRedis extends Command
{
    protected $signature = 'production:check-redis';

    protected $description = 'Check Redis readiness without modifying application data.';

    public function handle(): int
    {
        $cacheStore = (string) config('cache.default');
        $sessionDriver = (string) config('session.driver');
        $queueConnection = (string) config('queue.default');
        $redisClient = (string) config('database.redis.client');

        $this->line('Current drivers:');
        $this->line("  cache: {$cacheStore}");
        $this->line("  session: {$sessionDriver}");
        $this->line("  queue: {$queueConnection}");
        $this->line("  redis_client: {$redisClient}");

        $redisConfigured = in_array('redis', [$cacheStore, $sessionDriver, $queueConnection], true);

        if (! $redisConfigured) {
            $this->info('PASS: Redis is not currently selected for cache, sessions, or queues. No connection attempt needed.');
            return self::SUCCESS;
        }

        try {
            $pong = Redis::connection()->ping();
            $this->info('PASS: Redis connection succeeded. Response: ' . (is_string($pong) ? $pong : json_encode($pong)));

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('FAIL: Redis is configured but the connection failed.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }
    }
}
