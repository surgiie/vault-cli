# vault-cli

A PHP command-line interface for storing encrypted `AES-256` or `AES-128` json data using an encryption key derived from a master password.

![Tests](https://github.com/surgiie/vault-cli/actions/workflows/tests.yml/badge.svg)

## Install

To install, run the following command:

```bash
composer global require surgiie/vault-cli
```

## Supported Options

### Storage

- AWS S3
- Local Filesystem

### Ciphers

- aes-128-cbc
- aes-256-cbc
- aes-128-gcm
- aes-256-gcm

### PBKDF Hashing Algorithms

- sha256
- sha512

**Learn more** - [hash_pbkdf2](https://www.php.net/manual/en/function.hash-pbkdf2.php)

## Getting Started

To get started with a new vault, run the following command:

```bash
vault new <name>
```

## Configuration

Once you have a vault to work with, you can start using the cli. The cli reads a configuration from the `~/.vault/config.yaml` file. This file will contain your vault's and various other options for the cli to work with. For example, in order for the cli to know how to encrypt/decrypt your vault items, you will need to set the encryption options and register your vaults in the config file:

```yaml
vaults:
  your-vault-name:
  algorithm: sha256
  cipher: aes-128-cbc
  driver: local
  iterations: 600000
```

**Note** - The `~/.vault/config.yaml` file will be created for you when you run the `vault new` command and register your vault automatically.


### S3 Driver Configuration

If you are using the S3 driver, you will need to set the necessary credentials and options in the `~/.vault/config.yaml` file:


```yaml

vaults:
  your-vault-name:
    # ... other options
    s3:
      access_key_id: <aws-access-key-id>
      secret_access_key: <aws-secret-access-key>
      region: <aws-region>
      bucket: <aws-bucket>
```

Alternatively, you can set the following environment variables, if you dont wish to store your credentials or s3 options in the config file:

```bash
export VAULT_CLI_AWS_ACCESS_KEY_ID=<aws-access-key-id>
export VAULT_CLI_AWS_SECRET_ACCESS_KEY=<aws-secret-access-key>
export VAULT_CLI_AWS_REGION=<aws-region>
export VAULT_CLI_AWS_BUCKET=<aws-bucket>
```

## Selecting a Vault

To select the vault the cli should work with, use the `vault use` command:

```bash
vault use <your-vault-name>
```

Alternatively, you can set the `use-vault` option in the `~/.vault/config.yaml` file:

```yaml
use-vault: <your-vault-name>
vaults:
    your-vault-name:
        # ... your vault options
```
## Storing Items

To store an item in your vault, run the `item:new` command:


`vault item:new GITHUB_LOGIN --content="somepassword"  --password="<your-encryption-password>"`

This will store encrypted JSON data in the vault. When decrypted, the JSON structure for this example would be:


```json
{
    "name": "GITHUB_LOGIN",
    "content": "some_password",
}
```

## Naming Convention:

Vault item names will be converted to upper & snake cased. This is done for a couple of reasons:

- To ensure that the names are unique and not case sensitive.
- Simplify the process of exporting vault items to .env files

For example, an item name of `my-item` or `my_item` will be written/read as `MY_ITEM` within the vault. Either convention will be accepted, but when listed or echoed out, the `MY_ITEM` version will be used.

### Loading Content From a File:

If you prefer to load the content for your vault item from a file, use the `--content-file` flag instead of `--content` to load the item content from a file:

`vault item:new some_name --content-file="/path/to/some/file" --password="<your-encryption-password>"`

### Set New Item Content In Terminal Editor:

If you do not pass the `--content` or `--content-file` flag, you will be prompted to set the content by opening a temporary file your configured terminal editor as you run the command. Once you close the terminal editor, the command will create the vault item.

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

### Load Content For JSON Keys From Files:

If you want to load the value for a specific key in the JSON data, use the `--key-data-file` option in the format `<key>:<path>`.

For example, to load the content for the `extra-data` key, the command would be:

```bash
vault item:new \
        some_name \
        --content="some secret content" \
        --password="<your-encryption-password>" \
        --key-data-file="extra-data:/path/to/file/with/content"

```

### Categorizing Vault Items With Namespaces

Vault items are grouped/categorized in the `default` namespace. Namespaces are simply directories/folders or filters that vault items will go into. Namespaces are a good way to categorize and filter items based on their use cases. To specify a custom namespace for an item, use the `--namespace` flag:

`vault item:get some-item --namespace=other`


### Reencrypting Vault Items
```bash
vault reencrypt --old-password=<old-password> --password=<new-password>
```

#### Rencrypting With New Encryption Options

If you are updating encryption options, such as switching hashing algorithms or changing iterations, you can overwrite configuration options with the following command line options:

```bash
vault reencrypt
    # old options to decrypt the items first
    --decrypt-algorithm=sha256 \
    --decrypt-iterations=100000 \
    --old-password=<old-password>
    # new options to encrypt the items with
    --algorithm=sha512 \
    # recommended iterations as documented by owasp https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html#pbkdf2
    --iterations=210000 \
    --cipher=aes-256-cbc \
    --password=<your-master-password>
```

**Note** - This command will automatically save your new options to the `~/.vault/config.yaml` file.

## Retrieve Items From Vault

To output the content of an item, use the `item:get` command:

`vault item:get some_item_name`

This will output the decrypted content out.

## Remove Items From Vault

Items maybe removed from the selected vault with the `item:remove` command:

`vault item:remove --name="some_item_name"`

**Note** The `--name` option maybe passed multiple times to remove several items in a single command call.

### Retrieve Full JSON

By default, only the `content` field is printed to the terminal. To print the entire vault item JSON, run the command with the `--json` flag:

`vault item:get some_item_name --json`


### Copy to item content clipboard

To copy a vault item's `content` to the clipboard, use the `--copy` flag:

`vault item:get some_item_name --copy`

To copy the full json payload, combine with the `--json` flag:

`vault item:get some_item_name --copy --json`

Copy a specific key from the json, simply pass a value to the `--copy` option

`vault item:get some_name --copy=some-key`

**Note**: The default binary program used for this is `copy.exe` on windows/WSL2 and `xclip` on linux. If you want to use a custom command to copy the vault item to clipboard set the `VAULT_CLI_COPY_COMMAND` environment variable with the `:value:` placeholder. e.g `someprogram :value:`.

## Exporting Vault Item Content to Env Files

To export the `content` field of vault items to an env file, use the `export:env-file` command. For example:

`vault export:env-file --export="some-item-name" --export="some-other-item-name"`

This will export the vault item values to a `.env` file in the current directory

In the above example, your `.env` file would have the following variables written:
```
SOME_ITEM_NAME="The content"
SOME_OTHER_ITEM_NAME="The other content"
```
**Note** This will append to an existing `.env` file or create one if it doesn't exist, and overwrite any variables that previously exist. By default, this will add/create to .env in the current directory. To specify a custom name/path, use the `--env-file` option.

### Aliasing/Custom Env Names

If the names of the vault items are not desired for the .env file, you can use aliases by using the `<vault-item-name>:<env-var-name>` format when passing the `--export` options. For example:


`vault export:env-file --export="some-item-name:SOME_CUSTOM_NAME" --export="some-other-item-name:SOME_OTHER_CUSTOM_NAME"` will generate the .env file with the custom env names instead of names of the vault items:

```
SOME_CUSTOM_NAME="The content"
SOME_OTHER_CUSTOM_NAME="The other content"
```

### Including Other/Non-Vault Env Variables In Export.
If you want to include some other env variables in your env file that are not your vault items in the exported `.env` file, you can use the `--include` option:

`vault export:env-file --export="some-item-name" --include="SOME_ENV_VARIABLE_NAME:THE_VALUE`

In this example, `SOME_ENV_VARIABLE_NAME="THE_VALUE"` will be included in your exported .env file.