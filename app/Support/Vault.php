<?php

namespace App\Support;

use App\Concerns\InteractsWithDrivers;
use App\Contracts\VaultDriverInterface;
use App\Exceptions\ExitException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Collection;

abstract class Vault implements Arrayable, VaultDriverInterface
{
    use InteractsWithDrivers;

    protected ?string $password = null;

    /** The supported ciphers and their properties.*/
    public const SUPPORTED_CIPHERS = [
        'aes-128-cbc' => ['size' => 16],
        'aes-256-cbc' => ['size' => 32],
        'aes-128-gcm' => ['size' => 16],
        'aes-256-gcm' => ['size' => 32],
    ];

    /** The supported hasing algorithms for pbkdf2 function. */
    public const HASH_ALGORITHMS = [
        'sha256',
        'sha512',
    ];

    /**
     * The vault configuration.
     */
    protected Collection $config;

    /**
     * The number of iterations for the pbkdf2 function.
     *
     * @var int
     *
     * @see https://www.php.net/manual/en/function.hash-pbkdf2.php
     * @see https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html#pbkdf2
     */
    const DEFAULT_ITERATIONS = [
        'sha256' => 600000,
        'sha512' => 210000,
    ];

    /**
     * The default cipher to use for encryption.
     *
     * @var string
     */
    const DEFAULT_CIPHER = 'aes-256-cbc';

    /**
     * Create a new Vault instance.
     */
    public function __construct(?Collection $config = null)
    {
        if (! is_null($config)) {
            $this->setConfig($config);
        }
    }

    /**
     * Set the vault config.
     */
    public function setConfig(Collection $config): static
    {
        $this->config = $config;

        $config->assert('driver', validation: $this->getAvailableDrivers(keys: true));
        $config->assert('algorithm', validation: static::HASH_ALGORITHMS);
        $config->assert('cipher', validation: array_keys(static::SUPPORTED_CIPHERS));

        // auotmatically convert the iterations value to an integer
        if (is_numeric($config->get('iterations'))) {
            $config->put('iterations', (int) $config->get('iterations'));
        }

        $config->assert('iterations', validation: function ($value) {
            return (! is_int($value)) ? 'The iterations value must be an integer.' : '';
        });

        return $this;
    }

    /**
     * Decrypt a vault item's content.
     */
    public function decrypt(string $content, string $hash, string $namespace): VaultItem
    {
        $encrypter = new Encrypter($this->computeEncryptionKey($hash), $this->config->assert('cipher'));

        try {
            $content = json_decode($encrypter->decrypt($content), true);
        } catch (DecryptException $e) {
            throw new ExitException('Could not decrypt item with set encryption options: '.$e->getMessage());
        }

        return new VaultItem(
            name: $content['name'],
            data: $content,
            hash: $hash,
            namespace: $namespace,
        );
    }

    /**
     * Derive the encryption key to use for the given item hash.
     */
    protected function computeEncryptionKey(string $itemHash): string
    {
        return compute_encryption_key(
            itemHash: $itemHash,
            password: $this->getPassword(),
            algorithm: $this->config->assert('algorithm'),
            iterations: $this->config->assert('iterations'),
            size: Vault::SUPPORTED_CIPHERS[$this->config->assert('cipher')]['size']
        );
    }

    /**
     * Set the vault's password.
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get the vault's password.
     */
    public function getPassword(): string
    {
        if (is_null($this->password)) {
            throw new ExitException('No password set for vault.');
        }

        return $this->password;
    }

    /**
     * Get the vault config.
     */
    public function getConfig(): Collection
    {
        return $this->config;
    }

    /**
     * Convert the vault to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->config->toArray();
    }
}
