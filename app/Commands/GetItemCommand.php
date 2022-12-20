<?php

namespace App\Commands;

use App\Commands\BaseCommand;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithValidation;
use Surgiie\Console\Concerns\WithTransformers;

class GetItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'get:item {name : The name of the vault item.}
                                {--password= : The password to use during encryption of this item.}
                                {--password-file= : Read password from file instead of option.}
                                {--copy= : Copy a key value from the vault item json to clipboard.}
                                {--namespace=default : The namespace to put the vault item in.}
                                {--json : Display full json object instead of just the content value.}
                                ';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get and output a vault item\'s content.';


    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'name' => 'trim',
            'password' => 'trim',
            'folder' => 'trim',
        ];
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // $name = $this->normalizeItemName($this->data->get('name'));

        // $shaName =  sha1($name);

        // $folder = $this->data->get('folder');

        // $itemPath = $this->vaultPath("$folder/$shaName");

        // if (!is_file($itemPath)) {
        //     $this->exit("There is no vault item called $name in $folder folder. Use --folder if this vault item is stored in a different folder.");
        // }

        // $password = $this->getPassword();

        // $encryptionKey = $this->deriveKey($password);

        // $name = $this->data->get('name');

        // $fileContent = file_get_contents($itemPath);

        // $encrypter = new Encrypter($encryptionKey,  "AES-256-CBC");

        // $item = json_decode($encrypter->decrypt($fileContent), true);
        // $itemString = json_encode($item, JSON_PRETTY_PRINT);

        // if ($this->data->get('json')) {
        //     $output = $itemString;
        // } else {
        //     $output = $item["content"];
        // }

        // if ($attribute = $this->data->get('copy')) {
        //     if (!array_key_exists($attribute, $item)) {
        //         $this->exit("Vault item $name does not contain a key called $attribute.");
        //     }

        //     $this->copyToClipboard($item[$attribute]);
        // } else if (is_null($attribute)) {
        //     $this->copyToClipboard($this->data->get('json') ?  $itemString : $item['content']);
        // }

        // $this->line($output);
    }
}
