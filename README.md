# vault-cli
A cli for unix based systems for storing content to the local filesystem or local sqlite database as `AES-256-CBC` encrypted json data using a master password.

## Install

`composer global require surgiie/vault-cli`

## What does it do?

It simply writes/reads encrypted content as json files in your home directory within a `.vault` directory when using the `local` driver or to a `sqlite` database in the same directory when using the `sqlite` driver. Simply put, it's a cli around PHP's [PBKDF2](https://www.php.net/manual/en/function.hash-pbkdf2.php) and `AES-256-CBC` encryption. This cli doesnt store your vault items anywhere other than your own device, it is only the interaction method for your vault, so where or what your vault is used for is up to you.


## Create Vault & Set Driver:

The first thing you should do to use the cli is create a new vault directory by calling the `vault new` command:

`vault new --vault-name="my_vault" --driver=<sqlite|local>`

This will create a directory to store your vault items and some other metadata files. By default it will attempt to create the directory in `~/.vault` but to specify a custom path use the `--vault-path` optionwhen running this command:

`vault new --vault-name="my_vault" --driver=<sqlite|local> --vault-path=/some/vault`


**Note** Be sure the `sqlite3` extension is installed if using that driver.

## Storing Items In Vault

`vault item:new --name="github_login" --content="somepassword"  --password="<your-encryption-password>"`

This will store encrypted json data in your vault, but when decrypted the structure of the json for this example would be:

```json
{
    "name": "GITHUB_LOGIN",
    "content": "some_password",
}
```

**Note:** Vault item names will be normalized to upper/snake cased. This is so that vault items can be extracted to `.env` files/variables easily.

### From File:

If you prefer to load the content for your vault item from file, use the `--content-file` flag instead of `--content` to load the item content from a file:

`vault item:new --name="some_name" --content-file="/path/to/some/file" --password="<your-encryption-password>"`

### Set New Item Content On The Fly:

If you do not pass the `--content` or `--content-file` you will be asked if you want to set the content by opening up a tmp file in `vim` as you run the command. Once you close vim, the command will create the vault item.

### With Extra Data:

If you want to store extra data along with the vault item, simply pass any arbitrary key/value options to the command:

```bash
vault item:new \
        --name="some_name" \
        --content="some secret content" \
        --password="<your-encryption-password>" \
        --something-else="example"
        --extra-data="foo"
```

**Note**: The options reserved for the command itself cannot be used for this.

This will store a json file with your content encrypted, but when decrypted the structure of the decrypted json for this example would be:

```json
{
    "name": "some_name",
    "content": "some secret content",
    "something-else": "example",
    "extra-data": "foo"
}
```

### Load Content For Extra Data Keys From Files

If you wish to load data for a specific key in extra item json data, use the `--key-data-file` option with a `<key>:<path>` format for the value.

For example, in the above example, if we wanted to load the content for the "extra-data" key, that command call would look like:

```bash
vault item:new \
        --name="some_name" \
        --content="some secret content" \
        --password="<your-encryption-password>" \
        --key-data-file="extra-data:/path/to/file/with/content"

```

### Categorizing Vault Items With Namespaces

By default vault items will be grouped/categorized in the `default` namespace. In the local driver, namespaces are simply directories/folders that vault items will go into while in the `sqlite` driver, it's a column for the item database record. Namespaces are a good way to categorize and filter items based on their use cases. To specify a custom namespace for an item, simply pass the `--namespace` flag:

`vault item:get --name=some-item --namespace=other`

## Retrieve Items From Vault

To output the content of an item, you may do so with the `item:get` command:

`vault item:get --name=some_name`

This will output the decrypted content out.

### Retrieve the full json output

By default, only the `content` field is printed to the terminal, if you want the entire vault item json to be printed out, run the command with the `--json` flag:

`vault item:get --name=some_name --json`


### Copy to item content clipboard
 
To copy a vault item to clipboard use the `--copy` flag:

`vault item:get --name=some_name --copy`

To copy the full json payload, combine with the `--json` flag:

`vault item:get --name=some_name --copy --json`

Copy a specific key from the json, simply pass a value to the `--copy` option


`vault item:get --name=some_name --copy=some-key`

**Note**: On wsl2/ubuntu for windows, `copy.exe` will be utilized for this, but on linux, `xclip` will be used and assumed to be  installed to copy vault content to clipboard.


## Edit Items In Vault:
To update a vault item, use the `item:edit` command. This command works pretty much like the `item:new` except we are merging data into the existing item:

So considering a vault item with the following decrypted json:

```json
{
    "name": "SOME_WEBSITE_SERVICE",
    "content": "some_password",
    "email": "myemail@example.com",
    "username": "example"
}
```
You can merge/overwrite data into it
```bash
vault item:edit \
        --name="some_name" \
        --content="new_password" \
        --password="<your-encryption-password>" \
        --username="changed_username" \
        --something-new="example"
```
And youll end up with a vault item when decrypted that looks like:

```
{
    "name": "SOME_WEBSITE_SERVICE",
    "content": "new_password",
    "email": "myemail@example.com",
    "username": "changed_username",
    "something-new": "example"
}

```

Just as when creating new vault items, `--key-data-files` are also supported when editing items.


### Edit Full Json

If you want to edit the full json data of your item pass the `--edit-json` flag instead of options:

```bash
vault item:edit \
        --name="some_name" \
        --password="<your-encryption-password>" \
        --edit-json
```

This will open a tmp file in vim for you to edit the full json data, giving you more control over editing the item for your vault. 

## Password/Passing Methods

As shown in the above examples, you may pass your encryption password via the command option, but if you prefer other methods, the following methods can also be used:

-   When you created a vault a file within the root of your vault directory called `name` was written that contains the vault name, if its not present then write this file manually. This name will be read and the cli will attempt to load the `VAULT_CLI_<VAULT_NAME>_PASSWORD` environment variable. Your vault name will be normalized to upper/snake case for the env name. For example if your vault name is `my-vault` the resulting env would be `VAULT_CLI_MY_VAULT_PASSWORD`. This is allows you to use separate env variables for passwords if you happen to be working with multiple vaults. 
-   Next the cli will attempt to read password from the more generic `VAULT_CLI_PASSWORD` environment variable if a named variable is not set.
-   Read password from the `--password-file` option that specifies path to file with password. The file should only contain the password and no other content. These method have precedence over the `VAULT_CLI_PASSWORD` env variable.

If none of the above methods are used, you will be prompted for your password during the command call.

## Custom Vault Path/Working With Multiple Vaults

By default, all cli data and vaults is stored in `~/.vault` but if you want to use a custom path or want to be able to have separate vault directories with different drivers, you can specify the

`--vault-path` option that points to the root of the directory you want the cli to treat as the vault to store data/items into.
### Set Default Vault:

By default, `~/.vault` is considered the vault for all commands, unless the `--vault-path` is provided. If you find it cumbersome to always pass the `--vault-option` option, you can use the `VAULT_CLI_DEFAULT_PATH` environment variable to persist/change the default vault path.

## Exporting Vault Item Content To Env Files:

If you want to export the `content` field of your vault items to an env file, you can do so with the `export:env-file` command, as an example:

`vault export:env-file --export="some-item-name" --export="some-other-item-name"`

This will export the vault item with names that match the `--export` values you pass to the `.env` file in `/some/.env` directory. This is useful if exporting items to an application.


In the above example your .env file would have the following variables written:
```
SOME_ITEM_NAME="The content"
SOME_OTHER_ITEM_NAME="The other content"
```
**Note** This will append to an existing .env file or create if it doesnt exist and overwrite any variables that previously exist. By default this will add/create to `.env` in the current directory, to specify custom name/path, use `--env-file` option.

### Aliasing/Custom Env Names

If your vault item names are not named in the desired name for the .env file, you can use aliases by using the `<vault-item-name>:<env-var-name>` format when passing the `--export` options. For example:

`vault export:env-file --export="some-item-name:SOME_CUSTOM_NAME" --export="some-other-item-name:SOME_OTHER_CUSTOM_NAME"` will generate the .env file with the custom env names instead of names of the vault items:

```
SOME_CUSTOM_NAME="The content"
SOME_OTHER_CUSTOM_NAME="The other content"
```

### Including Other/Non-Vault Env Variables In Export.
If you want to include some other env variables in your env file that are not your vault items in the exported `.env` file, you can use the `--include` option:

`vault export:env-file --export="some-item-name" --include="SOME_ENV_VARIABLE_NAME:THE_VALUE` :

In this example, `SOME_ENV_VARIABLE_NAME="THE_VALUE"` will be included in your exported .env file.

## Exporting Vault Item Content To Files:

If you have vault item content you want to export to a file, you can run the `export:file` command. For example, if your vault contains a private ssh key named `ssh_private` and you wish to export that content to the `.ssh` directory:

`vault export:file --item="ssh_private:/home/someuser/.ssh/id_rsa"` 


**Note** This will prompt you for confirmation as it overwrites existing files, if you want to overwrite without prompt, use `--force` flag.


**Using Sudo**
If you are exporting to files where you need elevated permissions/sudo, consider running with the `-E` flag, i.e `sudo -E vault export:file`, so you preserve any `VAULT_CLI*` env variables.

### Export File Permissions:

To set specific ownership/permissions on the exported vault item file, you can use the `--user`, `--group`, `--permissions` flag:

`vault export:file --item="example:/home/someuser/example" --user="someuser" --group="somegroup" --permissions="0700"` 

OR 

You may persist this data by adding it to the vault item itself with the following json keys:

`vault item:edit --name=example --vault-export-user="someuser" --vault-export-group="somegroup" --vault-export-permissions="0700"`

This will be used by default when the options are not passed.


## Rencrypt all items with new password
<a href="https://emoji.gg/emoji/navi"><img src="https://cdn3.emoji.gg/emojis/navi.png" width="64px" height="64px" alt="navi"></a> Watch out! Consider creating a backup of your vault directory before running this command in the event of failure.

If you would like to rencrypt all your vault items with a new vault password:

`vault items:rencrypt --new-passwod="<new-password>" --old-password="<old-password>"` 


**Note** This only works if you have used the same password for all vault items. 

