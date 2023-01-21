<?php

namespace App\Contracts;

use Closure;

interface VaultStore
{
    /**
     * Bootstrap/configure things for the driver.
     *
     * @return void
     */
    public function boot();
    
    /**
     * Return all items from vault.
     *
     * @param Closure|null $callback
     * @return array
     */
    public function all(?Closure $callback = null): array;
    
    /**
     * Retrieve the item with the given item hash from vault.
     *
     * @param string $itemHash
     * @param string|null $namespace
     * @return null|string
     */
    public function get(string $itemHash, string $namespace = null): null|string;

    /**
     * Check if the item with the given hash exists in vault.
     *
     * @param string $itemHash
     * @param string $namespace
     * @return boolean
     */
    public function exists(string $itemHash, string $namespace = 'default'): bool;

    /**
     * Store/update item in the vault.
     *
     * @param string $itemHash
     * @param string $json
     * @param string $namespace
     * @return boolean
     */
    public function store(string $itemHash, string $json, string $namespace = 'default'): bool;

    /**
     * Remove item from vault.
     *
     * @param string $itemHash
     * @param string $namespace
     * @return boolean
     */
    public function remove(string $itemHash, string $namespace = 'default'): bool;
}
