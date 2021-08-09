<?php


namespace sketch\database;


class UUID
{

    static public function createUUID($trim = true){

        // Windows
        if (function_exists('com_create_guid') === true) {
            $value = com_create_guid();
            if ($trim === true) return trim($value, '{}');
            return $value;
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((double)microtime() * 10000);
        $charID = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        return $lbrace.
            substr($charID,  0,  8).$hyphen.
            substr($charID,  8,  4).$hyphen.
            substr($charID, 12,  4).$hyphen.
            substr($charID, 16,  4).$hyphen.
            substr($charID, 20, 12).
            $rbrace;
    }


}