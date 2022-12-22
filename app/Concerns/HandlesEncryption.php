<?php
namespace App\Concerns;

trait HandlesEncryption 
{
    
     /**Get the password for encryption.*/
     public function getEncryptionPassword()
     {
         $env = getenv("VAULT_CLI_PASSWORD");

         if ($env && !$this->data->get('password') && (!$this->data->get('password-file') && $this->hasOption('password-file'))) {
             return $env;
         }

         return $this->getFromFileOptionOrAsk('password', ['secret'=>true, 'confirm'=>true, 'rules'=>['required']]);
     }

    /**Derive encryption key.*/
    public function deriveEncryptionKey(string $password)
    {
        $salt = $this->generateSaltFromPassword($password);

        $encryptionKey = hash_pbkdf2('sha256', $password,  $salt, iterations: 100000, length: 32);

        return $encryptionKey;
    }

    
    /**
     * Generate a substring sha1 from password to use as a salt.
     */
    protected function generateSaltFromPassword($password)
    {
        $password = strrev($password);

        $num = strlen($password);
        $num = $num / 2;

        $first_half = strrev(substr($password, 0, $num));
        $second_half = strrev(substr($password, $num));

        // limit the sha1 to 32 chars which is a recommended salt length.
        return substr(sha1(strrev($second_half) . strrev($first_half)), 0, 32);
    }
}