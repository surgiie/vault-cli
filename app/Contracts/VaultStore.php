<?php

namespace App\Contracts;

interface VaultStore
{
    /**Ensure the vault exists. */
    public function ensureVaultExists();

    /**Set the path to the .vault directory.*/
    public function setVaultPath(string $path);

    /**Make a path relative to the set vault path. */
    public function makeVaultPath(string $path): string;

    /**Retrieve the item with the given item hash from vault.*/
    public function get(string $itemHash, string $namespace = null): string;

    /**Check if the item with the given hash exists in vault. */
    public function exists(string $itemHash, string $namespace = "default"): bool;

    /**Store/update item in the vault. */
    public function store(string $itemHash, string $json, string $namespace = "default"): bool;
}
