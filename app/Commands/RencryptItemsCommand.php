<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithTransformers;

class RencryptItemsCommand extends BaseCommand
{
    use WithTransformers, HandlesEncryption;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'items:rencrypt 
                                {--old-password= : The old password previously used for encryption of this item.}
                                {--new-password= : The new password to use during encryption of this item.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Rencrypt all items with a new password.';

    /**
     * The transformers for input arguments and options.
     *
     * @return array
     */
    public function transformers()
    {
        return [
            'new-password' => 'trim',
            'old-password' => 'trim',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->checkVaultExists();

        $driver = $this->getDriver();

        $oldPassword = $this->getOrAskForInput('old-password', ['secret' => true]);
        $newPassword = $this->getOrAskForInput('new-password', ['secret' => true, 'confirm' => true]);

        $driver->all(function ($item) use ($oldPassword, $newPassword, $driver) {
            $oldEncryptionKey = $this->deriveEncryptionKey($oldPassword, $item['hash']);
            $newEncryptionKey = $this->deriveEncryptionKey($newPassword, $item['hash']);

            $oldEncrypter = new Encrypter($oldEncryptionKey, 'AES-256-CBC');
            $newEncrypter = new Encrypter($newEncryptionKey, 'AES-256-CBC');

            $json = json_decode($oldEncrypter->decrypt($item['json']), true);
            $itemNamespace = $item['namespace'];

            $itemName = $json['name'];

            $this->runTask("Rencrypt vault item called $itemName.", function () use ($itemName, $itemNamespace, $json, $driver, $newEncrypter) {
                $newItem = $newEncrypter->encrypt(json_encode($json));

                return $driver->store(sha1($itemName), $newItem, $itemNamespace);
            });
        });
    }
}
