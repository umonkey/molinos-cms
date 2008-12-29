<?php

class JSLibsRequest implements iRequestHook
{
  public static function hookRequest(Context $ctx = null)
  {
    $libs = JSLibsConfig::getLibraries();

    foreach (mcms::modconf('jslibs', 'use', array()) as $key)
      if (array_key_exists($key, $libs))
        mcms::extras($libs[$key]['url'], false);
  }
}
