<?php

namespace App\Commands;

use App\Concerns\HandlesEncryption;
use Illuminate\Encryption\Encrypter;
use Surgiie\Console\Concerns\LoadsEnvFiles;
use Surgiie\Console\Concerns\WithTransformers;
use Surgiie\Console\Concerns\WithValidation;

class ExportToEnvFileCommand extends BaseCommand
{
    use WithTransformers, WithValidation, HandlesEncryption, LoadsEnvFiles;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'export:env-file
                            {--export=* : The names of the items to export.}
                            {--include=* : Raw env key/value variables to append/include to exported env file.}
                            {--env-file=.env : The env file path to create/add to.}
                            {--password= : The password for the decryption.}
                            {--namespace=default : The namespace to put the vault item in.}
                            {--password-file= : Read password from file instead of option.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Export content of vault items to an .env file.';

    /**Allow the command to accept arbritrary options.*/
    protected $arbitraryOptions = true;

    /**
     * The transformers for input arguments and options.
     *
     * @return array
     */
    public function transformers()
    {
        return [
            'export.*' => 'trim',
            'namespace' => 'trim',
            'vault-path' => 'trim',
            'password' => 'trim',
        ];
    }

    /**
     * The validation rules for input arguments and options.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'export' => ['required'],
            'export.*' => ['required','min:1'],
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

        $exports = $this->data->get('export');
        $envFile = $this->data->get('env-file');
        $driver = $this->getDriver();

        $password = $this->getEncryptionPassword();
  
        $env = ! is_file($envFile) ? [] : $this->getEnvFileVariables($envFile);

        foreach ($exports as $name) {
            [$name, $envName] = $this->parseKeyValueOption($name, 'export', function() use ($name) {
                return [$name, $name];
            });
      
            $name = $this->normalizeToUpperSnakeCase($name);
            $envName = $this->normalizeToUpperSnakeCase($envName);
            $itemHash = sha1($name);

            $encryptionKey = $this->deriveEncryptionKey($password, $itemHash);
            $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

            if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
                $this->exit("The $vaultName vault does not contain an item called '$name' in the $namespace namespace.");
            }

            $item = json_decode($encrypter->decrypt($driver->get($itemHash, $namespace)), true);

            if(!$envName){
                $this->exit("Blank env alias given for $name");
            }
            $env[$envName] = $item['content'];
        }

        foreach ($this->data->get('include') as $name) {
            [$name, $value] = $this->parseKeyValueOption($name, 'include');
            $env[$name] = $value;
        }
        

        $lines = [];
        foreach ($env as $name => $value) {
            $lines[] = "$name=\"$value\"";
        }

        $this->runTask("Export vault items to $envFile file.", function () use ($envFile, $lines) {
            return file_put_contents($envFile, implode(PHP_EOL, $lines)) !== false;
        });
    }
}
