<?php

namespace App\Drivers;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SqliteVault extends BaseVault
{
    /**Return all vault items from vault and optionally execute the given callback on each item.*/
    public function all(?Closure $callback = null): array 
    {
        $results = [];   
        foreach(DB::connection('vault')->table('vault_items')->get() as $item){
            $item = [
                'json'=>$item->json,
                'hash'=>$item->hash,
                'namespace'=>$item->namespace
            ];

            $results[] = $item;
            if(is_callable($callback)){
                $callback($item); 
            }
            
        }
        return $results;
    }
    /**Configure the connection configuration.*/
    public function boot()
    {
        $name = get_vault_name();

        config([
            'database.connections.vault' => array_merge(config('database.connections.vault'), [
                'database' => vault_path("vaults/$name/database"),
            ]),
        ]);

        $database = vault_path("vaults/$name/database");

        if (! is_file($database)) {
            touch($database);
            Artisan::call('migrate');
        }

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
