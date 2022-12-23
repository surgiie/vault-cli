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
                            {--env-file= : The env file path to create/add to.}
                            {--password= : The password for the decryption.}
                            {--vault-path= : The path to your .vault directory if not ~/.vault}
                            {--namespace=default : The namespace to put the vault item in.}
                            {--password-file= : Read password from file instead of option.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get decrypted items from vault and add the content value of each item to an .env file.';

    /**Allow the command to accept arbritrary options.*/
    protected $arbitraryOptions = true;

    /**Transform inputs.*/
    public function transformers()
    {
        return [
            'export.*' => 'trim',
            'namespace' => 'trim',
            'vault-path' => 'trim',
            'password' => 'trim',
        ];
    }

    /**Transform inputs.*/
    public function rules()
    {
        return [
            'export' => ['required'],
            'export.*' => ['min:1'],
            'env-file' => ['required'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $exports = $this->data->get('export');
        $envFile = $this->data->get('env-file');
        $driver = $this->getDriver();
        $vaultPath = $this->getVaultPath();

        $driver->ensureVaultExists();

        $password = $this->getEncryptionPassword();
        $encryptionKey = $this->deriveEncryptionKey($password);
        $encrypter = new Encrypter($encryptionKey, 'AES-256-CBC');

        $env = ! is_file($envFile) ? [] : $this->getEnvFileVariables($envFile);

        foreach ($exports as $name) {
            $name = $this->normalizeItemName($name);
            $itemHash = sha1($name);

            if (! $driver->exists($itemHash, $namespace = $this->data->get('namespace'))) {
                $this->exit("[Vault:$vaultPath][Namespace:$namespace] - The vault item $name does not exist.");
            }

            $item = json_decode($encrypter->decrypt($driver->get($itemHash, $namespace)), true);

            $env[$name] = $item['content'];
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
