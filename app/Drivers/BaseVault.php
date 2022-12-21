<?php

namespace App\Drivers;

use App\Contracts\VaultStore;

abstract class BaseVault implements VaultStore
{

    /**The path to the vault directory.*/
    protected string $vaultPath;

    /**Set the path to the .vault directory.*/
    public function setVaultPath(string $path)
    {
        $this->vaultPath = $path;
    }

    /**Make a path relative to the set vault path. */
    public function makeVaultPath(string $path): string
    {
        return vault_path($path, basePath: $this->vaultPath);
    }

    /**Bootstrap/configure things for the driver.*/
    public function boot()
    {

    }
}
