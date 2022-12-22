<?php

namespace App\Commands;

use App\Commands\BaseCommand;
use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithValidation;
use Surgiie\Console\Concerns\WithTransformers;

class GetItemCommand extends BaseCommand
{
    use WithTransformers, WithValidation, HandlesEncryption;
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:get 
                                {--name= : The name of the vault item.}
                                {--password= : The password to use during encryption of this item.}
                                {--password-file= : Read password from file instead of option.}
                                {--copy= : Copy a key value from the vault item json to clipboard.}
                                {--namespace=default : The namespace to put the vault item in.}
                                {--json : Display full json object instead of just the content value.}
                                {--vault-path= : The path to your .vault directory if not ~/.vault}';

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
            'namespace' => 'trim',
        ];
    }

    /**Validation rules.*/
    public function rules()
    {
        return [
            'name' => 'required',
        ];
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->normalizeItemName($this->data->get('name'));

        $driver = $this->getDriver($vault = $this->data->get('vault-path', ''));

        $itemHash = sha1($name);
        $vaultPath = $vault ?: vault_path();

        $driver->ensureVaultExists();

        if (!$driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
            $this->exit("[Vault:$vaultPath][Namespace:$namespace] - The vault item $name does not exist.");
        }

        $password = $this->getEncryptionPassword();

        $encryptionKey = $this->deriveEncryptionKey($password);


        $encrypter = new Encrypter($encryptionKey,  "AES-256-CBC");

        $item = json_decode($encrypter->decrypt($driver->get($itemHash, $namespace)), true);

        $itemString = json_encode($item, JSON_PRETTY_PRINT);

        if ($this->data->get('json')) {
            $output = $itemString;
        } else {
            $output = $item["content"];
        }

        if ($attribute = $this->data->get('copy')) {
            if (!array_key_exists($attribute, $item)) {
                $this->exit("Vault item $name does not contain a key called $attribute.");
            }

            copy_to_clipboard($item[$attribute], fn ($e) => $this->exit("Could not copy item to clipboard:" . $e->getMessage()));
        } else if (is_null($attribute)) {
            copy_to_clipboard($this->data->get('json') ?  $itemString : $item['content'], fn ($e) => $this->exit("Could not copy item to clipboard:" . $e->getMessage()));
        }

        $this->line($output);
    }
}
