<?php

namespace App\Drivers;

use App\Support\Config;
use App\Support\Vault;
use App\Support\VaultItem;
use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Surgiie\Console\Exceptions\ExitException;
use Symfony\Component\Finder\Finder;

class Local extends Vault
{
    /**
     * Create the vault storage.
     */
    public function create(string $name, Collection $data): bool
    {
        dump("??");
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
        $encrypter = new Encrypter($this->deriveEncryptionKey($hash), $this->config->get('cipher'));

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
     * Call the callback for each item in the vault.
     *
     * @param  string  $namespace
     */
    public function all(Closure $callback, array|string $namespaces = []): void
    {

        $iterate = function ($namespace = null) use ($callback) {
            $finder = new Finder();

            $path = $this->itemPath($namespace ? "$namespace/" : '');

            if (! is_dir($path)) {
                return;
            }

            $files = $finder->files()->in($path);

            foreach ($files as $file) {
                $itemHash = last(explode('/', $file->getPathName()));

                $encrypter = new Encrypter($this->deriveEncryptionKey($itemHash), $this->config->assert('cipher'));

                $content = file_get_contents($file->getPathName());

                try {
                    $content = json_decode($encrypter->decrypt($content), true);
                } catch (DecryptException $e) {
                    throw new ExitException('Could not decrypt item with set encryption options: '.$e->getMessage());
                }

                if (is_callable($callback)) {
                    $callback(
                        new VaultItem(
                            name: $content['name'],
                            data: $content,
                            hash: $itemHash,
                            namespace: Str::after($file->getPath(), 'items/'),
                        )
                    );

                }
            }
        };

        if (empty($namespaces)) {
            $iterate();

            return;
        }

        foreach (Arr::wrap($namespaces) as $namespace) {
            $iterate($namespace);
        }
    }

    /**
     * Check if the vault storage has an item by hash id.
     */
    public function has(string $hash, string $namespace = 'default'): bool
    {
        return is_file($this->itemPath("$namespace/$hash"));
    }

    /**
     * Get an item in the vault by name.
     */
    public function get(string $hash, Collection $data, string $namespace = 'default'): VaultItem
    {
        $encrypter = new Encrypter($this->deriveEncryptionKey($hash), $this->config->assert('cipher'));

        $content = file_get_contents($this->itemPath("$namespace/$hash"));

        try {
            $content = json_decode($encrypter->decrypt($content), true);
        } catch (DecryptException $e) {
            throw new ExitException('Could not decrypt item with given password: '.$e->getMessage());
        }

        return new VaultItem(
            name: $content['name'],
            data: $content,
            hash: $hash,
            namespace: $namespace,
        );
    }
}
