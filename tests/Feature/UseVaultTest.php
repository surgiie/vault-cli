<?php

use App\Support\Vault;
use App\Support\Config;
use App\Support\VaultItem;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\Encrypter;

it("can switch vaults", function () {
    $this->artisan('use', [
        'name' => $name = 'local',
    ])->expectsOutputToContain("Now using vault: $name")->assertExitCode(0);

});
it("cannot switch to non configured vaults", function () {
    $this->artisan('use', [
        'name' => 'i-dont-exist'
    ])->expectsOutputToContain("The vault 'i-dont-exist' is not configured in ~/.vault/config.yaml")->assertExitCode(1);
});

