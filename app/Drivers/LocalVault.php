<?php

namespace App\Drivers;

use App\Drivers\BaseVault;

class LocalVault extends BaseVault
{
    /**Ensure the vault exists. */
    public function ensureVaultExists()
    {
        @mkdir($this->vaultPath);
    }

    /**Store a new item in the vault. */
    public function store(string $itemHash, string $json,  string $namespace = "default"): bool
    {
        $itemPath = $this->makeVaultPath("$namespace/$itemHash");

        @mkdir(dirname($itemPath), recursive: true);

        return file_put_contents($itemPath, $json) !== false;
        // if(is_file($itemPath)){
        //     $this->exit("There is already a vault item called $name in $folder folder.");
        // }
    }

    /**Check if the item with the given item hash exists in vault.*/
    public function exists(string $itemHash, string $namespace = "default"): bool
    {
        return is_file($this->makeVaultPath("$namespace/$itemHash"));
    }

    /**Retrieve the item with the given item hash from vault.*/
    public function get(string $itemHash = null, string $namespace = null): bool
    {
        return file_get_contents("$namespace/$itemHash");
    }

}
