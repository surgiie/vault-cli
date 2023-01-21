<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
use Illuminate\Contracts\Encryption\DecryptException;
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
                            {--namespace=* : Filter items by namespace with this option.}
                            {--password-file= : Read password from file instead of option.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List vault item names from vault in a table.';

    /**
     * The transformers for input arguments and options.
     *
     * @return array
     */
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
        $this->checkVaultExists();

        $vaultName = get_selected_vault_name();

        $driver = $this->getDriver();

        $password = $this->getEncryptionPassword();

        $rows = [];
        $columns = ['Name', "Namespace", "Hash"];
        
        $driver->all(function($item) use($password, &$rows){
            $namespaces = $this->data->get('namespace', []);
            $encryptionKey = $this->deriveEncryptionKey($password, $item['hash']);

            $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');
    
            try {
                $json = json_decode($encrypter->decrypt($item['json']), true);
            }catch (DecryptException $e){
                $this->exit("Could not decrypt items with password: ".$e->getMessage());
            }

            if(!$namespaces || in_array($item['namespace'], $namespaces)){
                $rows[] = [$json['name'], $item['namespace'], $item['hash']];
            }
        });

        if(empty($rows)){
            $this->exit("No vault items found.", level: "warn");
        }

        usort($rows, function ($item1, $item2) {
            return $item1[0] <=> $item2[0];
        });
        
        $this->table($columns, $rows);

        $this->line("Total Items: ". count($rows));
        $this->line("Vault: ". $vaultName);
    }
}
