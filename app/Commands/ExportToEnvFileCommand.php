<?php

namespace App\Commands;

use App\Concerns\GathersInput;
use App\Concerns\InteractsWithDrivers;
use App\Support\Config;
use App\Support\Vault;
use Dotenv\Dotenv;
use InvalidArgumentException;

class ExportToEnvFileCommand extends BaseCommand
{
    use GathersInput, InteractsWithDrivers;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'export:env-file
                            {--export=* : The names of the items to export.}
                            {--include=* : Raw env key/value variables to include in the exported env file.}
                            {--env-file=.env : The env file to create or add to.}
                            {--password= : The password for the decryption.}
                            {--namespace=default : The namespace of the vault items.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Export content of vault items to an .env file.';

    /**
     * Quote the value if needed.
     */
    protected function quoteValue(string $value): string
    {
        if (! $value) {
            return '';
        }
        // Replace backslashes first to avoid double escaping
        $value = str_replace('\\', '\\\\', $value);
        // Escape double quotes
        $value = str_replace('"', '\"', $value);
        // quotes if white-space or the following characters: " \ = : . $ ( )
        if (preg_match('/\s|"|\\\\|=|:|\.|\$|\(|\)/u', $value)) {
            $value = '"'.$value.'"';
        }

        return $value;
    }

    /**
     * Parse a dot env file into an array of variables.
     */
    public function getEnvFileVariables(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("The env file '$path' does not exist.");
        }

        return Dotenv::parse(file_get_contents($path));
    }

    /**
     * Export vault items to an env file.
     */
    public function handle(): int
    {
        if (empty($this->option('export'))) {
            $this->exit('No items to export');
        }

        $password = $this->getEncryptionPassword($config = new Config);

        $vaultConfig = $config->getVaultConfig();

        $exports = $this->option('export');
        $envFile = $this->option('env-file');

        $vault = $this->getDriver($vaultConfig->assert('driver'), password: $password)->setConfig($vaultConfig);

        $env = ! is_file($envFile) ? [] : $this->getEnvFileVariables($envFile);

        // extract content to vars.
        foreach ($exports as $name) {
            [$name, $envName] = $this->parseKeyValueOption($name, 'export', onParseException: function () use ($name) {
                return [$name, $name];
            });

            $envName = $this->toUpperSnakeCase($envName);

            if (! $vault->has($hash = $this->hashItem($name), $this->option('namespace'))) {
                $this->exit("The vault item with the name '$name' does not exist.");
            }

            $item = $vault->get($hash, $this->arbitraryOptions, namespace: $this->option('namespace'));

            if (! $envName) {
                $this->exit("Blank env alias given for $name");
            }

            $env[$envName] = $item->data()['content'];

            // unset any renamed env vars
            if ($envName != ($nameUpper = $this->toUpperSnakeCase($name))) {
                unset($env[$nameUpper]);
            }
        }

        // include any custom env vars during the export
        foreach ($this->option('include') as $name) {
            [$name, $value] = $this->parseKeyValueOption($name, 'include');
            $env[$name] = $this->quoteValue($value);
        }

        $lines = [];

        foreach ($env as $name => $value) {
            $lines[] = "{$name}={$this->quoteValue($value)}".PHP_EOL;
        }

        $success = $this->runTask("Export vault items to $envFile file.", function () use ($envFile, $lines) {
            return file_put_contents($envFile, implode(PHP_EOL, $lines)) !== false;
        }, spinner: ! $this->app->runningUnitTests());

        if($success){
            $this->components->info("Exported vault items to: $envFile");
        }

        return $success === false ? 1 : 0;
    }
}
