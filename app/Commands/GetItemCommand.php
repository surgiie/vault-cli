<?php

namespace App\Commands;

use App\Concerns\GathersInput;
use App\Concerns\InteractsWithDrivers;
use App\Support\Config;

use function Laravel\Prompts\text;

class GetItemCommand extends BaseCommand
{
    use GathersInput, InteractsWithDrivers;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:get
                                {name? : The name of the vault item}
                                {--password= : The password to use during encryption of this item}
                                {--copy= : Copy a key value from the vault item json to clipboard}
                                {--namespace=default : The namespace to put the vault item in}
                                {--json : Display full json object instead of just the content value}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Get and output vault item content.';

    /**
     * Retrieve a decrypted item's content/json from vault and display it.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name') ?: text('Enter the name of the vault item to get', required: true);

        $password = $this->getEncryptionPassword($config = new Config);

        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        $hash = $this->hashItem($name);

        if (! $vault->has($hash, $this->option('namespace'))) {
            $this->exit("Vault item '$name' does not exist.");
        }

        $item = $vault->get($hash, $this->arbitraryOptions, $this->option('namespace'));

        if ($this->option('json')) {
            $output = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $output = $item->data('content');
        }

        if (! $this->optionWasPassed('copy')) {
            $this->line($output);

            return 0;
        }

        $copyKey = $this->option('copy');

        if ($copyKey && ! array_key_exists($copyKey, $item->data())) {
            $this->exit("Vault item $name does not contain a key called '$copyKey'.");
        }

        if (! $copyKey && ! $this->option('json')) {
            $output = $item->data()['content'];
        } elseif ($copyKey) {
            $output = $item->data()[$copyKey];
        }

        $this->copyToClipboard($output, fn ($e) => $this->exit("Could not copy item field '$copyKey' to clipboard: ".$e->getMessage()));

        $this->components->info('Copied to clipboard');

        return 0;
    }
}
