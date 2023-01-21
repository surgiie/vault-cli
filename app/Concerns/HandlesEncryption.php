<?php

namespace App\Concerns;

trait HandlesEncryption
{
    /**Get the password for encryption.*/
    public function getEncryptionPassword()
    {
        $name = $this->normalizeToUpperSnakeCase(get_vault_name());

        $env = getenv("VAULT_CLI_{$name}_PASSWORD");
        
        if(!$env){
            $env = getenv('VAULT_CLI_PASSWORD');
        }

        if ($env && is_null($this->data->get('password')) && (! $this->data->get('password-file') && $this->hasOption('password-file'))) {
            return $env;
        }
        return $this->getFromFileOptionOrAsk('password', ['secret' => true, 'confirm' => true, 'rules' => ['required'], 'label'=>'encryption password']);
    }

    /**Derive encryption key using master password and item hash.*/
    public function deriveEncryptionKey(string $password, string $itemHash)
    {
        $salt = $this->generateSalt($itemHash);

        $encryptionKey = hash_pbkdf2('sha256', $password, $salt, iterations: 100000, length: 32);

        return $encryptionKey;
    }

    /**
     * Generate a salt from the given value. This will be the item hash so that
     * We are generating a unique salt value from item to item in an idempotent
     * manner.
     */
    protected function generateSalt($value)
    {
        $value = strrev($value);

        $num = strlen($value);
        $num = $num / 2;

        $first_half = strrev(substr($value, 0, $num));
        $second_half = strrev(substr($value, $num));

        return substr(sha1(strrev($second_half).strrev($first_half)), 0, 32);
    }

}
