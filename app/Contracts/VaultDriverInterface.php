<?php

namespace App\Contracts;

use App\Support\VaultItem;
use Illuminate\Support\Collection;

interface VaultDriverInterface
{
    /**
     * Check if the vault exists.
     */
    public function exists(Collection $data): bool;

    /**
     * Set the vault configuration.
     */
    public function setConfig(Collection $config): static;

    /**
     * Retrieve all encrypted items from vault.
     */
    public function all(array|string $namespaces = []): array;

    /**
     * Decrypt a vault item's content.
     */
    public function decrypt(string $content, string $hash, string $namespace): VaultItem;

    /**
     * Encrypt the given data for storage in the vault.
     */
    public function encrypt(array $data, string $hash): string;

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
     * Fetch and return a decrypted item in the vault by name.
     */
    public function get(string $hash, Collection $data, string $namespace = 'default'): VaultItem;

    /**
     * Fetch an item in the vault by name.
     */
    public function fetch(string $hash, string $namespace = 'default'): string;

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
     * Save the encrypted content to the vault.
     */
    public function put(string $hash, string $content, string $namespace = 'default'): bool;

    /**
     * Generate a path to an item in the vault.
     */
    public function itemPath(string $path = ''): string;
}
