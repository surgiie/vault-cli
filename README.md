# vault-cli
A cli for unix based systems for storing data/content to the local filesystem or local sqlite database as encrypted json data.

## Install

`composer global require surgiie/vault-cli`

## What does it do?

It simply writes/reads encrypted content as json files in your home directory within a `.vault` directory when using the `local` driver or to a `sqlite` databse in the same directory when using the `sqlite` driver. Simply put, it's a cli around PHP's [PBKDF2](https://www.php.net/manual/en/function.hash-pbkdf2.php) and Laravel's [AES-256-CBC](https://laravel.com/docs/9.x/encryption) encryption features. This cli doesnt store your vault items anywhere other than your own device, it is only the interaction method for your vault, so where or what your vault is used for is up to you.


## Set your driver:

The first thing you should do to use the cli is set a driver by calling the `vault set:driver` command. This will store a small file at `~/.vault/driver` that the cli will use to be aware of which driver to use.

## Storing Items In Vault

`vault new:item --name="github_login" --content="somepassword" -- --password="<your-encryption-password>"`

This will store a json file at `/home/<user>/.vault/default` (more on namespaces below) with your content encrypted, but when decrypted the structure of the decrypted json for this example would be:

```json
{
    "name": "GITHUB_LOGIN",
    "content": "some_password",
    "email": "myemail@example.com"
}
```

**Note** - Vault item names will be normalized to upper/snake cased. This is so that vault items can be extracted to `.env` files/variables easily.

### From File:

If you prefer to load the content for your vault item from file, use the `--content-file` flag instead of `--content` to load the file content from a file:

`vault new:item --name="some_name" --content-file="/path/to/some/file" --password="<your-encryption-password>"`
### Set New Item Content On The Fly:

If you do not pass the `--content` or `--content-file` you will be asked if you want to set the content by opening up a tmp file in an editor as you run the command.

By default this editor is `vim`, but you can specify the binary of the editor if you wish to use something else, for example to open the file in vs code:

`vault new:item --name="some_name" --editor=code`

**Note** - Once you close file in editor, the command will finish up encrypting/writing the file. When using non terminal editors like vs code, it may not be obvious that the command has completed
once you have saved and closed the editor, check back to your terminal once you close your editor.

### With Extra Data:

If you want to store extra data along with the vault item, simply pass any arbitrary key/value options to the command:

```bash
vault new:item \
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
vault new:item \
        --name="some_name" \
        --content="some secret content" \
        --password="<your-encryption-password>" \
        --key-data-file="extra-data:/path/to/file/with/content"

```

### Categorizing Vault Items With Namespaces

By default vault items will be grouped/categorized in the `default` namespace. In the local driver, namespaces are simply directories/folders that vault items will go into while in the `sqlite` driver, it's a column for the item database record. Namespaces are a good way to categorize and filter items based on their use cases. To specify a custom namespace for an item, simply pass the `--namespace` flag:

`vault get:item --name=some-item --namespace=other`

## Edit Items In Vault:
To update a vault item, use the `edit:item` command. This command works pretty much like the `new:item` except we are merging data into the existing item:

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
vault edit:item \
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

## Password/Passing Methods

As shown in the above examples, you may pass your encryption password via the command option, but if you prefer other methods, the following methods can also be used:

-   Read password from the `VAULT_CLI_PASSWORD` environment variable:
-   Read password from the `--password-file` option that specifies path to file with password. The file should only contain the password and no other content. These method have precedence over the `VAULT_CLI_PASSWORD` env variable.

If none of the above methods are used, you will be prompted for your password during the command call.



## Custom Vault Path/Working With Multiple Vaults

By default, all cli data and vaults is stored in `~/.vault` but if you want to use a custom path or want to be able to have separate vault directories with different drivers, you can specify the

`--vault-path` option that points to the root of the directory you want the cli to treat as the vault to store data/items into.
