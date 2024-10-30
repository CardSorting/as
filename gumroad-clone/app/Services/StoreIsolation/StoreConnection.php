<?php

namespace App\Services\StoreIsolation;

use App\Models\StoreSilo;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class StoreConnection
{
    private DatabaseManager $dbManager;
    private ?string $previousConnection = null;

    public function __construct(DatabaseManager $dbManager)
    {
        $this->dbManager = $dbManager;
    }

    public function connect(StoreSilo $store): void
    {
        // Store current connection
        $this->previousConnection = DB::getDefaultConnection();

        $connectionName = "store_{$store->id}";
        
        // Configure the store connection
        Config::set("database.connections.{$connectionName}", [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $this->dbManager->getDatabasePath($store),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Clear any cached configuration
        DB::purge($connectionName);
        
        // Switch to store connection
        DB::setDefaultConnection($connectionName);
    }

    public function disconnect(): void
    {
        if ($this->previousConnection) {
            DB::setDefaultConnection($this->previousConnection);
            $this->previousConnection = null;
        }
    }

    public function transaction(\Closure $callback)
    {
        return DB::transaction($callback);
    }
}
