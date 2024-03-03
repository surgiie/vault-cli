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
                                {--password= : The password to use during encryption of this item.}
                                {--copy : Copy a key value from the vault item json to clipboard.}
                                {--json-key=content : The specific key to output from the vault item json.}
                                {--namespace=default : The namespace to pull the vault item from.}';

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
            $this->exit("Item with name '$name' does not exist in the vault.");
        }

        $item = $vault->get($hash, $this->arbitraryOptions, $this->option('namespace'));

        if ($this->option('json-key') == '*') {
            $output = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $output = $item->data($this->option('json-key'));
        }

        if (! is_string($output)) {
            $output = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if (! $this->option('copy')) {
            $this->line($output);

            return 0;
        }

        $this->copyToClipboard($output, fn ($e) => $this->exit('Could not copy to clipboard: '.$e->getMessage()));

        $this->components->info('Copied to clipboard');

        return 0;
    }
}
