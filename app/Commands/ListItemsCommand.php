<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\LoadsEnvFiles;
use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class ListItemsCommand extends BaseCommand
{
    use WithTransformers, WithValidation, HandlesEncryption, LoadsEnvFiles;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:list
                            {--password= : The password for the decryption.}
                            {--vault-path= : The path to your .vault directory if not ~/.vault}
                            {--namespace=* : The namespaces to list items for.}
                            {--password-file= : Read password from file instead of option.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List vault item names from vault in a table.';

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'namespace.*' => 'trim',
            'vault-path' => 'trim',
            'password' => 'trim',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $driver = $this->getDriver();

        $driver->ensureVaultExists();

        $password = $this->getEncryptionPassword();
        $encryptionKey = $this->deriveEncryptionKey($password);

        $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

        $rows = [];
        $columns = ['Name', "Namespace"];

        
        $driver->all(function($item) use($encrypter, &$rows){
            $namespaces = $this->data->get('namespace', []);
            $json = json_decode($encrypter->decrypt($item['json']), true);

            if(!$namespaces || in_array($item['namespace'], $namespaces)){
                $rows[] = [$json['name'], $item['namespace']];
            }
        });

        if(empty($rows)){
            $this->exit("No vault items found.", level: "warn");
        }

        $this->table($columns, $rows);

    }
}
