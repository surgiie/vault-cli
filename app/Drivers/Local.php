<?php

namespace App\Drivers;

use App\Support\Config;
use App\Support\Vault;
use App\Support\VaultItem;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Exceptions\ExitException;
use Symfony\Component\Finder\Finder;

class Local extends Vault
{
    /**
     * Create the vault storage.
     */
    public function create(string $name, Collection $data): bool
    {
        return @mkdir(Config::basePath($name));
    }

    /**
     * Validate the data for creating the vault storage.
     */
    public function validateCreate(Collection $data): string
    {
        return '';
    }

    /**
     * Get path to the items directory or file path relavent to items directory.
     */
    public function itemPath(string $path = ''): string
    {
        return static::vaultPath($this->config->assert('name')."/items/$path");
    }

    /**
     * Create a path relavent to the ~/.vault/vaults directory.
     */
    public static function vaultPath(string $path = ''): string
    {
        return Config::basePath("vaults/$path");
    }

    /**
     * Save item in the vault.
     */
    public function put(string $hash, array $data, string $namespace = 'default'): bool
    {
        $encrypter = new Encrypter($this->computeEncryptionKey($hash), $this->config->get('cipher'));

        $encodedData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $itemPath = $this->itemPath("$namespace/$hash");

        @mkdir(dirname($itemPath), recursive: true);

        return file_put_contents($itemPath, $encrypter->encrypt($encodedData)) !== false;
    }

    /**
     * Check if the vault exists.
     */
    public function exists(Collection $data): bool
    {
        $name = $this->config->assert('name');

        return is_dir(Config::basePath($name));
    }

    /**
     * Remove an item in the vault by name.
     */
    public function remove(string $hash, Collection $data, string $namespace = 'default'): bool
    {
        return unlink($this->itemPath("$namespace/$hash"));
    }

    /**
     * Retrieve all encrypted items from vault.
     */
    public function all(array|string $namespaces = []): array
    {
        $items = [];

        $iterate = function ($namespace = null) use (&$items) {
            $finder = new Finder();

            $path = $this->itemPath($namespace ? "$namespace/" : '');

            if (! is_dir($path)) {
                return;
            }

            $files = $finder->files()->in($path);

            foreach ($files as $file) {
                $itemHash = last(explode('/', $file->getPathName()));

                $content = file_get_contents($file->getPathName());

                $items[] = [
                    'hash' => $itemHash,
                    'content' => $content,
                    'namespace' => Str::after($file->getPath(), 'items/'),
                ];
            }
        };

        if (empty($namespaces)) {
            $iterate();

            return $items;
        }

        foreach (Arr::wrap($namespaces) as $namespace) {
            $iterate($namespace);
        }

        return $items;
    }

    /**
     * Check if the vault storage has an item by hash id.
     */
    public function has(string $hash, string $namespace = 'default'): bool
    {
        return is_file($this->itemPath("$namespace/$hash"));
    }

    /**
     * Fetch an item in the vault by name.
     */
    public function fetch(string $hash, string $namespace = 'default'): string
    {
        return file_get_contents($this->itemPath("$namespace/$hash"));
    }
    /**
     * Fetch and decrypt an item in the vault by name.
     */
    public function get(string $hash, Collection $data, string $namespace = 'default'): VaultItem
    {
        return $this->decrypt($this->fetch($hash, $namespace), $hash, $namespace);
    }
}
