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
     * The transformers for input arguments and options.
     */
    public function transformers(): array
    {
        return [
            'name' => ['trim'],
            'password' => 'trim',
            'namespace' => 'trim',
        ];
    }

    /**
     * Retrieve a decrypted item's content/json from vault and display it.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->data->get('name') ?: text('Enter the name of the vault item to get', required: true);

        $password = $this->getEncryptionPassword($config = new Config);

        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        $hash = $this->hashItem($this->toUpperSnakeCase($name));

        if (! $vault->has($hash, $this->data->get('namespace'))) {
            $this->exit("Vault item '$name' does not exist.");
        }

        $item = $vault->get($hash, $this->arbitraryData, $this->data->get('namespace'));

        if ($this->data->get('json')) {
            $output = json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            $output = $item->data('content');
        }

        if (! $this->optionWasPassed('copy')) {
            $this->line($output);

            return 0;
        }

        $copyKey = $this->data->get('copy');

        if ($copyKey && ! array_key_exists($copyKey, $item->data())) {
            $this->exit("Vault item $name does not contain a key called $copyKey.");
        }

        if (! $copyKey) {
            $copyKey = 'content';
        }

        $this->copyToClipboard($item->data()[$copyKey], fn ($e) => $this->exit("Could not copy item field '$copyKey' to clipboard: ".$e->getMessage()));

        $this->components->info("Copied item $copyKey to clipboard");

        return 0;
    }
}
