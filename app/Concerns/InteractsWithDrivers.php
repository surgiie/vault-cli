<?php

namespace App\Concerns;

use App\Contracts\VaultDriverInterface;
use Illuminate\Contracts\Container\BindingResolutionException;

trait InteractsWithDrivers
{
    /**
     * The available drivers.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Load the drivers available in the app.
     *
     * @return array
     */
    protected function loadDrivers()
    {
        return load_drivers();
    }
    /**
     * Get the available drivers keys.
     */
    protected function getAvailableDrivers(bool $keys = false): array
    {
        if(empty($this->drivers)){
            $this->drivers = $this->loadDrivers();
        }

        return $keys === true ? array_keys($this->drivers) : $this->drivers;
    }

    /**
     * Get the driver instance for the current driver name.
     */
    public function getDriver(string $name, ?string $password = null): VaultDriverInterface
    {
        try {
            $name = ucfirst($name);
            $class = "App\\Drivers\\{$name}";
            $driver = $this->app->make($class);
        } catch (BindingResolutionException) {
            $this->exit("The '{$name}' driver is not supported.");
        }

        if (! is_null($password)) {
            $driver->setPassword($password);
        }

        return $driver;
    }
}
