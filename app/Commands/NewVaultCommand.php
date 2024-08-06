<?php

namespace App\Commands;

use App\Concerns\InteractsWithDrivers;
use App\Support\Config;
use App\Support\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class NewVaultCommand extends BaseCommand
{
    use InteractsWithDrivers;

    /**
     * The command's signature text.
     */
    protected $signature = 'new {name? : The name for the new vault.}
                                {--driver= : The driver to use.}
                                {--algorithm= : The hashing algorithm to use for pbkdf.}
                                {--iterations= : The number of iterations to use during pbkdf. Default: 600000.}
                                {--cipher= : The cipher to use for encryption.}
                                {--use : Set the new vault as the vault the cli should use.}';

    /**
     * The command's description text.
     */
    protected $description = 'Create a new vault.';

    /**
     * Allow arbitrary options.
     */
    protected bool $arbritraryOptions = true;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = new Config;
        $vaultConfig = new Collection;
        $name = Str::kebab($this->argument('name') ?: text('What do you want to name the new vault?', required: true));

        if ($config->has("vaults.$name")) {
            $this->exit("A vault with the name '$name' is already configured in the ~/.vault/config.yaml file.");
        }

        $algorithm = $this->option('algorithm') ?: select(label: 'What hashing algorithm do you want to use to when deriving an encryption key from your master password?', options: Vault::HASH_ALGORITHMS, default: 'sha256');
        $driver = $this->option('driver') ?: select(label: 'What driver do you want to use?', options: $this->getAvailableDrivers(keys: true), default: 'local');
        $cipher = $this->option('cipher') ?: select(label: 'What cipher do you want to use for encryption?', options: array_keys(Vault::SUPPORTED_CIPHERS), default: Vault::DEFAULT_CIPHER);

        $vault = $this->getDriver($driver);
        $vaultConfig->put('algorithm', $algorithm);
        $vaultConfig->put('cipher', $cipher);
        $vaultConfig->put('driver', $driver);
        $vaultConfig->put('iterations', intval($this->option('iterations') ? $this->option('iterations') : Vault::DEFAULT_ITERATIONS[$algorithm]));

        $vault->setConfig($vaultConfig);

        $error = $vault->validateCreate($this->arbitraryOptions);

        if ($error) {
            $this->exit("Failed to create vault: $error");
        }

        $vault->create($name, $this->arbitraryOptions);

        $config->set("vaults.$name", $vault->toArray());

        if ($this->option('use')) {
            $config->set('use-vault', $name);
        }

        $config->save();
        $this->components->info("The $driver vault '$name' has been created.");

        return 0;
    }
}
