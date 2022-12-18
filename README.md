# vault-cli

A simple cli for storing data/content to a local encypted json files on unix based systems.

## Install

`composer global require surgiie/vault-cli`

## What does it do?

It simply writes/reads encrypted content to json files in your home directory within a `.vault` directory. Simply put, it's a cli around PHP's [PBKDF2](https://www.php.net/manual/en/function.hash-pbkdf2.php) and Laravel's [AES-256-CBC](https://laravel.com/docs/9.x/encryption) encryption features. This cli doesnt store your vault items anywhere other than your own device, it is only the interaction method for your vault, so where or what your vault is used for is up to you.

## Create Items In Vault

`vault new:item --name="github_login" --content="somepassword" -- --password="<your-encryption-password>"`

This will store a json file at `/home/<user>/.vault/default` (more on folders below) with your content encrypted, but when decrypted the structure of the decrypted json for this example would be:

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

### Password/Passing Methods

As shown in the above examples, you may pass your encryption password via the command option, but if you prefer other methods, the following methods can also be used:

-   Read password from the `VAULT_CLI_PASSWORD` environment variable:
-   Read password from the `--password-file` option that specifies path to file with password. The file should only contain the password and no other content. These method have precedence over the `VAULT_CLI_PASSWORD` env variable.

If none of the above methods are used, you will be prompted for your password during the command call.



