<?php

namespace App\Commands;


class SetVaultCommand extends BaseCommand
{

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'select
                            {name=default : The name of the vault to select.}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Select the default vault the cli should work with.';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->data->get('name');

        $defaultFile = vault_path("default-vault");

        if(! is_dir(vault_path("vaults/$name"))){
            $this->exit("The vault '$name' does not exist");
        }
        
        file_put_contents($defaultFile, $name);

        $this->components->info("Set the default vault to: $name");
    }
}
