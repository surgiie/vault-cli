<?php

namespace App\Drivers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SqliteVault extends BaseVault
{
    /**Configure the connection configuration.*/
    public function boot()
    {
        config([
            'database.connections.vault' => array_merge(config('database.connections.vault'), [
                'database' => $this->makeVaultPath('database'),
            ]),
        ]);
    }

    /**Ensure the vault exists. */
    public function ensureVaultExists()
    {
        @mkdir($this->vaultPath);

        $database = $this->makeVaultPath('database');

        if (! is_file($database)) {
            touch($database);
        }

        Artisan::call('migrate');
    }

    /**Store a new item in the vault. */
    public function store(string $itemHash, string $json, string $namespace = 'default'): bool
    {
        return DB::connection('vault')->table('vault_items')->updateOrInsert(
            [
                'hash' => $itemHash,
                'namespace' => $namespace,
            ],
            [
                'hash' => $itemHash,
                'json' => $json,
                'namespace' => $namespace,
            ]
        );
    }

    /**Check if the item with the given item hash exists in vault.*/
    public function exists(string $itemHash, string $namespace = 'default'): bool
    {
        return ! is_null($this->get($itemHash, $namespace));
    }

    /**Retrieve the item with the given item hash from vault.*/
    public function get(string $itemHash, string $namespace = null): null|string
    {
        $record = DB::connection('vault')->table('vault_items')->where('hash', $itemHash)->where('namespace', $namespace)->first();

        return is_null($record) ? null : $record->json;
    }

    /**Remove item from vault.*/
    public function remove(string $itemHash, string $namespace = 'default'): bool
    {
        return DB::connection('vault')->table('vault_items')->where('hash', $itemHash)->where('namespace', $namespace)->delete();
    }
}
