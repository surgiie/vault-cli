<?php

namespace App\Drivers;

use Closure;
use App\Support\Vault;
use App\Support\VaultItem;
use Illuminate\Support\Collection;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Exceptions\ExitException;
use Illuminate\Contracts\Encryption\DecryptException;

class S3 extends Vault
{
    /**
     * Create the vault storage.
     */
    public function create(string $name, Collection $data): bool
    {
        return true;
    }
    /**
     * Check if the vault storage has an item by hash id.
     */
    public function has(string $hash, string $namespace = 'default'): bool
    {
        return true;
    }
    /**
     * Get path to the items directory or file path relavent to items directory.
     */
    public function itemPath(string $path = ''): string
    {
        // return static::vaultPath($this->config->assert('name')."/items/$path");
        return "";
    }
    /**
     * Retrieve all encrypted items from vault.
     */
    public function all(array|string $namespaces = []): array
    {
        return [];
    }
    /**
     * Validate the data for creating the vault storage.
     */
    public function validateCreate(Collection $data): string
    {
        return '';
    }

    /**
     * Check if the vault exists.
     */
    public function exists(Collection $data): bool
    {
        $name = $this->config->assert('name');

        return true;
    }

   /**
    * Remove an item in the vault by name.
    */
   public function remove(string $hash, Collection $data, string $namespace = 'default'): bool
   {
       return true;
   }


    /**
     * Save item in the vault.
     */
    public function put(string $hash, array $data, string $namespace = 'default'): bool
    {
        return true;
    }

    /**
     * Get an item in the vault by name.
     */
    public function get(string $hash, Collection $data, string $namespace = 'default'): VaultItem
    {
        return null;
    }
}
