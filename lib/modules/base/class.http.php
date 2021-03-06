<?php

class http
{
  const CONTENT = 1;
  const NO_CACHE = 2;

  public static function head($url, $loop = 10)
  {
    if (false === ($result = get_headers($url, 1)))
      throw new RuntimeException('Bad URL: ' . $url);

    $parts = explode(' ', $result[0], 3);

    list($result['_protocol'], $result['_status'], $result['_message']) = $parts;

    if ($loop and $result['_status'] >= 300 and $result['_status'] < 400)
      return self::head($result['Location'], $loop - 1);

    return $result;
  }

  public static function fetch($url, $options = null)
  {
    // FIXME: придумать нормальное решение!
    try {
      $tmpdir = Context::last()->config->getPath('main/tmpdir');
    } catch (Exception $e) {
      $tmpdir = null;
    }

    if (!$tmpdir)
      $tmpdir = 'tmp';

    $outfile = os::path(os::mkdir($tmpdir), 'mcms-fetch.'. md5($url));

    $ttl = mcms::config('file.cache.ttl', 3600);

    if (file_exists($outfile) and (($options & self::NO_CACHE) or ((time() - $ttl) > @filectime($outfile)))) {
      if (is_writable(dirname($outfile))) {
        Logger::log('removing cached copy of ' . $url, 'fetch');
        unlink($outfile);
      }
    }

    // Скачиваем файл только если его нет на диске во временной директории
    if (file_exists($outfile)) {
      Logger::log('found in cache: '. $url, 'fetch');
    } else {
      if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $fp = fopen($outfile, "w+");

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        if (null !== ($time = ini_get('max_execution_time')))
          curl_setopt($ch, CURLOPT_TIMEOUT, $time);

        curl_setopt($ch, CURLOPT_USERAGENT, 'Molinos.CMS/' . mcms::version() . '; http://' . MCMS_HOST_NAME);

        if (!ini_get('safe_mode'))
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        fclose($fp);

        if (200 != $code) {
          Logger::log($url . ': error ' . $code, 'fetch');
          unlink($outfile);
          return null;
        }
      }

      elseif ($f = @fopen($url, 'rb')) {
        if (!($out = fopen($outfile, 'w')))
          throw new RuntimeException(t('Не удалось сохранить временный файл %name', array('%name' => $outfile)));

        while (!feof($f))
          fwrite($out, fread($f, 1024));

        fclose($f);
        fclose($out);
      }

      else {
        Logger::log($url . ': failed.', 'fetch');
        throw new RuntimeException(t('Не удалось загрузить файл: '
          .'модуль CURL отсутствует, '
          .'открыть поток HTTP тоже не удалось.'));
      }

      if (function_exists('get_headers')) {
        $headers = get_headers($url, true);

        if (!empty($headers['Content-Length']) and ($real = $headers['Content-Length']) != ($got = filesize($outfile))) {
          unlink($outfile);
          throw new RuntimeException(t('Не удалось скачать файл: вместо %real байтов было получено %got.', array('%got' => $got, '%real' => $real)));
        }
      }
    }

    if ($options & self::CONTENT) {
      $content = file_get_contents($outfile);
      return $content;
    } else {
      return $outfile;
    }
  }
}
