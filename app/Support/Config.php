<?php

namespace App\Support;

use App\Contracts\VaultDriverInterface;
use App\Support\Testing\Fakes\ConfigFake;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use App\Exceptions\ExitException;
use Symfony\Component\Yaml\Yaml;

class Config
{
    /**
     * The configuration data.
     */
    protected array $data = [];

    /**
     * Whether the configuration is faked.
     *
     * @var bool
     */
    protected static $faked = false;

    /**
     * Construct a new Config instance.
     */
    public function __construct(?array $data = null)
    {
        if (! is_null($data)) {
            $this->data = $data;
        } elseif (! file_exists($path = $this->path())) {
            $this->data = static::default();
            $this->save();
        } else {
            $this->data = Yaml::parseFile($path);
        }
    }

    /**
     * Set a configuration value.
     *
     * @param  mixed  $value
     */
    public function set(string $key, $value): static
    {
        data_set($this->data, $key, $value);

        return $this;
    }

    /**
     * Get the the set vault's configuration.
     */
    public function getVaultConfig(): Collection
    {
        $defaultVault = $this->assert('use-vault');

        try {
            $config = new Collection($this->assert("vaults.{$defaultVault}"));
            // inject the name into the config for convenience
            $config->put('name', $defaultVault);

            return $config;
        } catch (ExitException $e) {
            throw new ExitException('A set vault is not configured. Set the use-vault option or run `vault use`.');
        }
    }

    /**
     * Update a vault's configuration in the configuration file.
     */
    public function saveVaultConfig(array|VaultDriverInterface $vault)
    {
        $data = is_array($vault) ? $vault : $vault->toArray();

        $name = $data['name'];

        $this->set("vaults.$name", $data);

        $this->save();
    }

    /**
     * Get a configuration value.
     *
     * @param  mixed  $value
     * @return mixed
     *
     * @throws \Surgiie\Console\Exceptions\ExitException
     */
    public function get(string $key, $default = null)
    {
        $result = data_get($this->data, $key, $default);

        return $result;
    }

    /**
     * Get a configuration value but assert its not empty.
     *
     * @param  mixed  $value
     * @return mixed
     *
     * @throws \Surgiie\Console\Exceptions\ExitException
     */
    public function assert(string $key, $default = null)
    {
        $result = $this->get($key, $default);

        if ($key === 'use-vault' && blank($result)) {
            throw new ExitException('A vault is not selected, set the `use-vault` config option in your ~/.vault/config.yaml file  or run `vault use`.');
        }

        if (blank($result)) {
            throw new ExitException("The configuration value '$key' is not set.");
        }

        return $result;
    }

    /**
     * Check if configuration has a configuration value.
     *
     * @param  mixed  $value
     */
    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Generate a base path to the configuration file directory.
     */
    public static function basePath(string $path = ''): string
    {
        if (static::$faked) {
            return ConfigFake::basePath($path);
        }

        $user = posix_getpwuid(posix_geteuid())['name'];

        $path = trim($path, '/');

        return "/home/$user/.vault/$path";
    }

    /**
     * Fake the configuration for testing.
     */
    public static function fake(bool $fake = true): void
    {
        static::$faked = $fake;
    }

    /**
     * Get path to the configuration file.
     */
    public function path(): string
    {
        if (static::$faked) {
            return (new ConfigFake())->path();
        }

        return static::basePath('config.yaml');
    }

    /**
     * Return the default configuration data.
     */
    public static function default(): array
    {
        return [
            'use-vault' => null,
            'editor' => 'vim',
            'vaults' => [],
        ];
    }

    /**
     * Write the current set configuration data to the configuration file.
     */
    public function save(): bool
    {
        @mkdir(Config::basePath());

        $yaml = Yaml::dump($this->data, 4);

        return file_put_contents($this->path(), $yaml) !== false;
    }
}
