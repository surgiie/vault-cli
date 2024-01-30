<?php

namespace App\Contracts;

use App\Support\VaultItem;
use Closure;
use Illuminate\Support\Collection;

interface VaultDriverInterface
{
    /**
     * Check if the vault exists.

     *
     * @param  Collection  $config
     */
    public function exists(Collection $data): bool;

    /**
     * Set the vault configuration.
     */
    public function setConfig(Collection $config): static;

    /**
     * Call the callback for each item in the vault.
     *
     * @param  string  $namespaces
     */
    public function all(Closure $callback, array|string $namespaces = []): void;

    /**
     * Validate the data for creating the vault storage.
     */
    public function validateCreate(Collection $data): string;

    /**
     * Create the vault storage.
     */
    public function create(string $name, Collection $data): bool;

    /**
     * Check if the vault storage has an item by hash id.
     */
    public function has(string $hash, string $namespace = 'default'): bool;

    /**
     * Get an item in the vault by name.
     */
    public function get(string $hash, Collection $data, string $namespace = 'default'): VaultItem;

    /**
     * Remove an item in the vault by name.
     */
    public function remove(string $hash, Collection $data, string $namespace = 'default'): bool;

    /**
     * Set the vault's password.
     */
    public function setPassword(string $password): static;

    /**
     * Get the vault's password.
     */
    public function getPassword(): string;

    /**
     * Save item in the vault.
     */
    public function put(string $hash, array $data, string $namespace = 'default'): bool;

    /**
     * Generate a path to an item in the vault.
     */
    public function itemPath(string $path = ''): string;
}
