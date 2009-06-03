<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class XmlImporter
{
  /**
   * Получение данных из внешнего XML источника.
   *
   * Параметры:
   *   url — адрес источника.
   *   cache — срок кэширования, в секундах (= 600).
   */
  public static function on_get_import(Context $ctx)
  {
    if ($ttl = intval($ctx->get('cache')) < 600)
      $ttl = 600;

    if (!($url = $ctx->get('url')))
      throw new BadRequestException(t('Не указан адрес импортируемого XML канала (GET-параметр url).'));

    $cache = cache::getInstance();
    $ttl = floor(time() / $ttl);
    $ckey = sprintf('xmlimport.xml|%u|%s', $ttl, $url);

    if ($cached = $cache->$ckey)
      return new Response($cached, 'text/xml');

    $xml = http::fetch($url, http::CONTENT);
    $cache->$ckey = $xml;

    mcms::flog('Imported XML from ' . $url);

    return new Response($xml, 'text/xml');
  }
};
