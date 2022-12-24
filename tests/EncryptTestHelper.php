<?php

namespace Tests;

use App\Concerns\HandlesEncryption;
use App\Contracts\VaultStore;
use Illuminate\Encryption\Encrypter;

class EncryptTestHelper
{
    use HandlesEncryption;

    public function __construct(string $password, VaultStore $driver)
    {
        $this->password = $password;
        $this->driver = $driver;
    }

    /**Get and decrypt vault item.**/
    public function decryptVaultItem(string $itemHash, string $namespace = 'default', bool $full = false)
    {
        $encrypter = new Encrypter($this->deriveEncryptionKey($this->password), 'AES-256-CBC');

        $item = json_decode($encrypter->decrypt($this->driver->get($itemHash, $namespace)), true);

        if ($full) {
            return $item;
        }

        return $item['content'];
    }
}
