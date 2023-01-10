<?php

namespace App\Drivers;

use App\Contracts\VaultStore;

abstract class BaseVault implements VaultStore
{
    /**Bootstrap/configure things for the driver.*/
    public function boot()
    {
    }
}
