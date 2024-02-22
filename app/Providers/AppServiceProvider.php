<?php

namespace App\Providers;

use App\Concerns\InteractsWithDrivers;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use App\Exceptions\ExitException;

class AppServiceProvider extends ServiceProvider
{
    use InteractsWithDrivers;

    public function boot()
    {
        foreach (array_values($this->loadDrivers()) as $class) {
            $this->app->instance($class, new $class);
        }

        Collection::macro('assert', function ($name, $default = null, array|Closure|null $validation = null, string $dataType = 'configuration') {
            $value = $this->get($name, $default);

            if (is_callable($validation) && $result = $validation($value)) {
                throw new ExitException(is_string($result) ? $result : "The '{$name}' {$dataType} value is invalid.");
            }
            if (is_array($validation) && ! in_array($value, $validation)) {
                throw new ExitException("The '{$name}' {$dataType} value is invalid. Must be one of: ".implode(', ', $validation));
            }

            if (blank($value)) {
                throw new ExitException("The '{$name}' {$dataType} value is required.");
            }

            return $value;
        });

    }
}
