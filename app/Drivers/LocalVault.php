<?php

namespace App\Drivers;

use Closure;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class LocalVault extends BaseVault
{
    /**
     * Return all vault items from vault and optionally execute the given callback on each item.
     *
     * @param  Closure|null  $callback
     * @return array
     */
    public function all(?Closure $callback = null): array
    {
        $results = [];
        $finder = new Finder();
        $name = get_selected_vault_name();
        $itemsPath = vault_path("vaults/$name/items");

        if (! is_dir($itemsPath)) {
            return $results;
        }
        $files = $finder->files()->in($itemsPath);

        foreach ($files as $file) {
            $item = [
                'hash' => last(explode('/', $file->getPathName())),
                'json' => file_get_contents($file->getPathName()),
                'namespace' => Str::after($file->getPath(), 'items/'),
            ];

            $results[] = $item;
            if (is_callable($callback)) {
                $callback($item);
            }
        }

        return $results;
    }

    /**
     * Store a new item in the vault.
     *
     * @param  string  $itemHash
     * @param  string  $json
     * @param  string  $namespace
     * @return bool
     */
    public function store(string $itemHash, string $json, string $namespace = 'default'): bool
    {
        $name = get_selected_vault_name();
        $itemPath = vault_path("vaults/$name/items/$namespace/$itemHash");

        @mkdir(dirname($itemPath), recursive: true);

        return file_put_contents($itemPath, $json) !== false;
    }

    /**
     * Check if the item with the given item hash exists in vault.
     *
     * @param  string  $itemHash
     * @param  string  $namespace
     * @return bool
     */
    public function exists(string $itemHash, string $namespace = 'default'): bool
    {
        $name = get_selected_vault_name();

        return is_file(vault_path("vaults/$name/items/$namespace/$itemHash"));
    }

    /**
     * Retrieve the item with the given item hash from vault.
     *
     * @param  string  $itemHash
     * @param  string|null  $namespace
     * @return null|string
     */
    public function get(string $itemHash, string $namespace = null): null|string
    {
        $name = get_selected_vault_name();

        return file_get_contents(vault_path("vaults/$name/items/$namespace/$itemHash"));
    }

    /**
     * Remove an the item in vault.
     *
     * @param  string  $itemHash
     * @param  string  $namespace
     * @return bool
     */
    public function remove(string $itemHash, string $namespace = 'default'): bool
    {
        $name = get_selected_vault_name();

        return @unlink(vault_path("vaults/$name/items/$namespace/$itemHash"));
    }
}
