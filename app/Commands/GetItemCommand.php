<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\WithTransformers;

class GetItemCommand extends BaseCommand
{
    use WithTransformers, HandlesEncryption;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:get 
                                {name : The name of the vault item.}
                                {--password= : The password to use during encryption of this item.}
                                {--password-file= : Read password from file instead of option.}
                                {--copy= : Copy a key value from the vault item json to clipboard.}
                                {--namespace=default : The namespace to put the vault item in.}
                                {--json : Display full json object instead of just the content value.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get and output vault item content.';

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'name' => 'trim',
            'password' => 'trim',
            'namespace' => 'trim',
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
        $vaultName = get_vault_name();

        $name = $this->normalizeToUpperSnakeCase($this->data->get('name'));

        $driver = $this->getDriver();

        $itemHash = sha1($name);

        if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
            $this->exit("The $vaultName vault does not contain an item called '$name' in the $namespace namespace.");
        }

        $password = $this->getEncryptionPassword();

        $encryptionKey = $this->deriveEncryptionKey($password, $itemHash);

        $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

        try {
            $item = json_decode($encrypter->decrypt($driver->get($itemHash, $namespace)), true);
        }catch (DecryptException){
            $this->exit("Could not decrypt vault item '$name' with set/given password.");
        }

        $itemString = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($this->data->get('json')) {
            $output = $itemString;
        } else {
            $output = $item['content'];
        }

        global $argv;
        $isCopy = str_contains(implode(" ", $argv), '--copy');
        
        if ($copyField = $this->data->get('copy')) {
            if (! array_key_exists($copyField, $item)) {
                $this->exit("Vault item $name does not contain a key called $copyField.");
            }

            copy_to_clipboard($item[$copyField], fn ($e) => $this->exit('Could not copy item to clipboard:'.$e->getMessage()));
        } elseif ($isCopy && is_null($copyField)) {
            copy_to_clipboard($this->data->get('json') ? $itemString : $item['content'], fn ($e) => $this->exit('Could not copy item to clipboard:'.$e->getMessage()));
        }

        if(! $isCopy){
            $this->line($output);
        }else{
            $this->components->info("Copied item to clipboard");
        }
    }
}
