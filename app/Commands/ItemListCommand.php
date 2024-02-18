<?php

namespace App\Commands;

use App\Concerns\GathersInput;
use App\Concerns\InteractsWithDrivers;
use App\Support\Config;

class ItemListCommand extends BaseCommand
{
    use GathersInput, InteractsWithDrivers;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'item:list
                            {--password= : The password for the decryption.}
                            {--namespace=* : Filter items by namespace with this option.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List vault item names from vault in a table.';

    /**
     * List items in vault as a table.
     */
    public function handle(): int
    {
        $rows = [];
        $columns = ['Name', 'Namespace', 'Hash'];
        $password = $this->getEncryptionPassword($config = new Config);
        $vaultConfig = $config->getVaultConfig();

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        foreach ($vault->all(namespaces: $this->option('namespace', [])) as $item) {
            $item = $vault->decrypt($item['content'], $item['hash'], $item['namespace']);

            $rows[] = [
                $item->name(),
                $item->namespace(),
                $item->hash(),
            ];
        }
        if (empty($rows)) {
            $this->exit('No vault items found.', level: 'warn', code: 0);
        }

        usort($rows, function ($item1, $item2) {
            return $item1[0] <=> $item2[0];
        });

        $this->table($columns, $rows);

        return 0;
    }
}
