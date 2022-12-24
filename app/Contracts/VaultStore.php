<?php

namespace App\Contracts;

use Closure;

interface VaultStore
{
    /**Bootstrap/configure things for the driver.*/
    public function boot();
    
    /**Return all items from vault.*/
    public function all(?Closure $callback = null): array;
    
    /**Ensure the vault exists. */
    public function ensureVaultExists();
    
    /**Set the path to the .vault directory.*/
    public function setVaultPath(string $path);

    /**Make a path relative to the set vault path. */
    public function makeVaultPath(string $path): string;
    

    /**Retrieve the item with the given item hash from vault.*/
    public function get(string $itemHash, string $namespace = null): null|string;

    /**Check if the item with the given hash exists in vault. */
    public function exists(string $itemHash, string $namespace = 'default'): bool;

    /**Store/update item in the vault. */
    public function store(string $itemHash, string $json, string $namespace = 'default'): bool;

    /**Remove item from vault.*/
    public function remove(string $itemHash, string $namespace = 'default'): bool;
}
