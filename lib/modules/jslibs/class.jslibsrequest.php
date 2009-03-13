<?php

class JSLibsRequest
{
  /**
   * @mcms_message ru.molinos.cms.hook.request.after
   */
  public static function hookRequest(Context $ctx)
  {
    $libs = JSLibsConfig::getLibraries();

    foreach ($ctx->modconf('jslibs', 'use', array()) as $key)
      if (array_key_exists($key, $libs))
        mcms::extras($libs[$key]['url'], false);
  }
}
