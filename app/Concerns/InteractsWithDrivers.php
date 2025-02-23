<?php

namespace App\Concerns;

use App\Contracts\VaultDriverInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Finder\Finder;

trait InteractsWithDrivers
{
    /**
     * The loaded supported drivers.
     *
     * @var array
     */
    protected static $drivers = [];

    /**
     * Load the drivers available in the app.
     *
     * @return array
     */
    public static function loadDrivers()
    {
        if (! empty(static::$drivers)) {
            return static::$drivers;
        }

        $finder = new Finder;

        foreach ($finder->files()->in(__DIR__.'/../Drivers') as $driver) {
            $class = "App\Drivers\\".$driver->getBasename('.php');
            static::$drivers[strtolower($driver->getBasename('.php'))] = $class;
        }

        return static::$drivers;
    }

    /**
     * Get the available drivers keys.
     */
    protected function getAvailableDrivers(bool $keys = false): array
    {
        $drivers = static::loadDrivers();

        return $keys === true ? array_keys($drivers) : $drivers;
    }

    /**
     * Get the driver instance for the given driver name.
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
