<?php

class crypt
{
  public static function encrypt($input)
  {
    if (function_exists('mcrypt_create_iv') and ($key = mcms::config('guid'))) {
      $securekey = hash('sha256', $key, true);

      if (!function_exists('mcrypt_create_iv'))
        throw new RuntimeException(t('Function mcrypt_create_iv not found.'));

      $iv = mcrypt_create_iv(32);

      $input = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $securekey, $input, MCRYPT_MODE_ECB, $iv);
    }

    return rawurlencode(base64_encode($input));
  }

  public static function decrypt($input)
  {
    $input = base64_decode(rawurldecode($input));

    if (function_exists('mcrypt_create_iv') and ($key = mcms::config('guid'))) {
      $securekey = hash('sha256', $key, true);

      $iv = mcrypt_create_iv(32);

      $input = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $securekey, $input, MCRYPT_MODE_ECB, $iv);
    }

    return $input;
  }
}
