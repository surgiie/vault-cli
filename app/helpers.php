<?php

use Symfony\Component\Finder\Finder;


/**
 * Compute an encryption key using the given options.
 */
function compute_encryption_key(string $itemHash, string $password, string $algorithm, int $iterations, int $size): string
{
    // First compute a salt for pbkdf2. This method is not secure by any means, it is simply
    // a idempodent method of generating unique salt for each item we store in the vault by using
    // the item's hash as a base for the salt. Since a salt adds extra security to the encryption
    // and we dont want to rely on the user to provide a salt, we will compute one implicitly.

    // reverse the item hash
    $value = strrev($itemHash);
    // get the length of the string
    $num = strlen($value);
    $num = $num / 2;
    // split the string in half and reverse each half
    $first_half = strrev(substr($value, 0, $num));
    $second_half = strrev(substr($value, $num));

    // combine and create a limited sha1 string to use as a salt.
    $salt = substr(sha1(strrev($second_half).strrev($first_half)), 0, 32);

    return hash_pbkdf2(
        $algorithm,
        $password,
        $salt,
        iterations: $iterations,
        length: $size
    );
}
