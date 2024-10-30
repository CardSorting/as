<?php

namespace App\Services\StoreIsolation;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class ConnectionManager
{
    private const LOCK_TTL = 30; // seconds
    private const RETRY_DELAY = 100; // milliseconds
    private const MAX_RETRIES = 10;

    public function withMainConnection(callable $callback)
    {
        return $this->withConnection('main', config('database.default'), $callback);
    }

    public function withStoreConnection(string $storeId, callable $callback)
    {
        return $this->withConnection("store_{$storeId}", "store_{$storeId}", $callback);
    }

    private function withConnection(string $lockKey, string $connection, callable $callback)
    {
        $lock = Redis::lock($lockKey, self::LOCK_TTL);
        $retries = 0;

        while (!$lock->get() && $retries < self::MAX_RETRIES) {
            usleep(self::RETRY_DELAY * 1000);
            $retries++;
        }

        if ($retries >= self::MAX_RETRIES) {
            throw new \RuntimeException("Could not acquire connection lock for {$lockKey}");
        }

        try {
            $previousConnection = DB::getDefaultConnection();
            DB::setDefaultConnection($connection);

            $result = $callback();

            DB::setDefaultConnection($previousConnection);
            return $result;
        } finally {
            $lock->release();
        }
    }

    public function transaction(string $connection, callable $callback)
    {
        return DB::connection($connection)->transaction($callback);
    }
}
