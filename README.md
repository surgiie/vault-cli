# vault-cli
A PHP command-line interface (CLI) for Unix-based systems to store content on the local filesystem or a local SQLite database as AES-256-CBC encrypted JSON data using a master password.
## Install

To install, run the following command:

`composer global require surgiie/vault-cli`

## What does it do?

The CLI writes and reads encrypted content as JSON files in the user's home directory within a .vault directory when using the local driver, or to a SQLite database in the same directory when using the sqlite driver. It serves as a CLI around PHP's  [PBKDF2](https://www.php.net/manual/en/function.hash-pbkdf2.php) and AES-256-CBC encryption. The CLI does not store vault items anywhere other than on the user's device; it only provides an interaction method for the vault.
## Creating a Vault and Setting the Driver

To use the CLI, create a new vault directory by running the following command:

`vault new <vault-name> --driver=<sqlite|local>`

Then, set the vault for the CLI to use with:

`vault select <vault-name>`

Note: If using the sqlite driver, make sure the sqlite3 extension is installed.



## Storing Items In Vault

To store an item in the vault, run the following command:


`vault item:new GITHUB_LOGIN --content="somepassword"  --password="<your-encryption-password>"`

This will store encrypted JSON data in the vault. When decrypted, the JSON structure for this example would be:


```json
{
    "name": "GITHUB_LOGIN",
    "content": "some_password",
}
```

## Naming Convention:

Vault item names will be normalized to upper/snake cased. This is so that vault items can be easily extracted to .env files/variables. For example, an item name of `my-item` or `my_item` will be saved as `MY_ITEM` within the vault. Either convention will be accepted, but when listed or echoed out, the `MY_ITEM` convention will be used.

### Loading Content From a File:

If you prefer to load the content for your vault item from a file, use the `--content-file` flag instead of `--content` to load the item content from a file:

`vault item:new some_name --content-file="/path/to/some/file" --password="<your-encryption-password>"`

### Set New Item Content On The Fly:

If you do not pass the `--content` or `--content-file` flag, you will be prompted to set the content by opening a temporary file in vim as you run the command. Once you close vim, the command will create the vault item.

### Storing Extra Data

If you want to store extra data along with the vault item, you can pass any arbitrary key/value options to the command:

```bash
vault item:new SOME_NAME
        --content="some secret content" \
        --password="<your-encryption-password>" \
        --something-else="example"
        --extra-data="foo"
```

**Note**: There are options reserved for the command itself and cannot be used for extra data, you can see those listed with the `--help` menu.

This will store a json file with your content encrypted, but when decrypted the structure for this example would be:

```json
{
    "name": "SOME_NAME",
    "content": "some secret content",
    "something-else": "example",
    "extra-data": "foo"
}
```

### Load Content For Extra Data Keys From Files

If you want to load data for a specific key in the extra item JSON data, use the --key-data-file option in the format `<key>:<path>`.

For example, to load the content for the "extra-data" key, the command would be:

```bash
vault item:new \
        some_name \
        --content="some secret content" \
        --password="<your-encryption-password>" \
        --key-data-file="extra-data:/path/to/file/with/content"

```

### Categorizing Vault Items With Namespaces

Vault items are grouped/categorized in the default namespace by `default`. In the local driver, namespaces are simply directories/folders that vault items will go into. In the `sqlite` driver, namespaces are a column in the item database record. Namespaces are a good way to categorize and filter items based on their use cases. To specify a custom namespace for an item, use the `--namespace` flag:

`vault item:get some-item --namespace=other`

## Retrieve Items From Vault

To output the content of an item, use the `item:get` command:

`vault item:get some_item_name`

This will output the decrypted content out.

## Remove Items From Vault

Items maybe removed from the selected vault with the `item:remove` command:

`vault item:remove --name="some_item_name"`

**Note** The `--name` option maybe passed multiple times to remove several items in a single command call.

### Retrieve the full json output

By default, only the `content` field is printed to the terminal. To print the entire vault item JSON, run the command with the `--json` flag:

`vault item:get some_item_name --json`


### Copy to item content clipboard
 
To copy a vault item to the clipboard, use the `--copy` flag:

`vault item:get some_item_name --copy`

To copy the full json payload, combine with the `--json` flag:

`vault item:get some_item_name --copy --json`

Copy a specific key from the json, simply pass a value to the `--copy` option


`vault item:get some_name --copy=some-key`

**Note**: On WSL2/Ubuntu for Windows, copy.exe will be used, but on Linux, xclip will be used and assumed to be installed to copy vault content to the clipboard.

## Edit Items In Vault:
To update a vault item, use the `item:edit` command. This command works similarly to the item:new command, except it merges data into the existing item.

For example, given a vault item with the following decrypted JSON:
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
        some_name \
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


### Edit Full JSON

To edit the full JSON data of an item, pass the `--edit-json` flag instead of options:


```bash
vault item:edit \
        some_name \
        --password="<your-encryption-password>" \
        --edit-json
```

This will open a temporary file in vim for you to edit the full JSON data, giving you more control over editing the item for your vault.
## Password/Passing Methods

When using the above examples, the encryption password can be passed via the command option. However, other methods can also be used:

- The CLI will attempt to load the VAULT_CLI_<VAULT_NAME>_PASSWORD environment variable, where the vault name is normalized to upper/snake case. For example, if the vault name is `my-vault`, the resulting environment variable would be `VAULT_CLI_MY_VAULT_PASSWORD`. This allows you to use separate environment variables for passwords if you are working with multiple vaults.

-   The CLI will then attempt to read the password from the more generic VAULT_CLI_PASSWORD environment variable if a named variable is not set.

-   The password can also be read from the `--password-file` option, which specifies the path to a file containing the password. The file should only contain the password and no other content. These methods have precedence over the `VAULT_CLI_PASSWORD` environment variable.

If none of the above methods are used, you will be prompted for your password during the command call.
## Exporting Vault Item Content to Env Files

To export the `content` field of vault items to an env file, use the `export:env-file` command. For example:

`vault export:env-file --export="some-item-name" --export="some-other-item-name"`

This will export the vault items that match the `--export` values to the .env file in the `/some/.env` directory. This is useful for exporting items to an application.

In the above example, your `.env` file would have the following variables written:
```
SOME_ITEM_NAME="The content"
SOME_OTHER_ITEM_NAME="The other content"
```
**Note** This will append to an existing `.env` file or create one if it doesn't exist, and overwrite any variables that previously exist. By default, this will add/create to .env in the current directory. To specify a custom name/path, use the `--env-file` option.

### Aliasing/Custom Env Names

If the names of the vault items are not desired for the .env file, you can use aliases by using the `<vault-item-name>:<env-var-name>` format when passing the --export options. For example:


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

To export vault item content to a file, use the `export:file` command. For example, if your vault contains a private SSH key named ssh_private and you want to export that content to the .ssh directory:

`vault export:file --item="ssh_private:/home/someuser/.ssh/id_rsa"` 


**Note**: This will prompt for confirmation as it overwrites existing files. To overwrite without prompt, use the --force flag.

**Using Sudo**
If you need elevated permissions/sudo when exporting to files, consider running the command with the `-E` flag, i.e `sudo -E vault export:file`, to preserve any `VAULT_CLI*` environment variables.
### Export File Permissions:

To set specific ownership/permissions on the exported vault item file, you can use the `--user`, `--group`, and `--permissions` flags:

`vault export:file --item="example:/home/someuser/example" --user="someuser" --group="somegroup" --permissions="0700"` 

Alternatively, you can persist this data by adding it to the vault item itself using the following JSON keys:

`vault item:edit example --vault-export-user="someuser" --vault-export-group="somegroup" --vault-export-permissions="0700"`

This will be used by default when the options are not passed.
## Listing out available vaults

To list out what vaults exist on the machine in a table, run `vault list-available`, though this is simply a pretty wrapper to `ls ~/.vault/vaults`.

## Rencrypt all items with new password
<a href="https://emoji.gg/emoji/navi"><img src="https://cdn3.emoji.gg/emojis/navi.png" width="64px" height="64px" alt="navi"></a> Watch out! Consider creating a backup of your vault directory before running this command in the event of failure.

If you would like to rencrypt all your vault items with a new vault password:

`vault items:rencrypt --new-passwod="<new-password>" --old-password="<old-password>"` 


**Note** This only works if you have used the same password for all vault items. 

