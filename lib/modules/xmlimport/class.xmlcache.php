<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

class XmlCache
{
  /**
   * @route GET//api/xml/cached.xml
   */
  public static function on_get_import(Context $ctx)
  {
    if ($ttl = intval($ctx->get('cache')) < 600)
      $ttl = 600;

    if (!($url = $ctx->get('url')))
      throw new BadRequestException(t('Не указан адрес импортируемого XML канала (GET-параметр url).'));

    $cache = cache::getInstance();
    $ttl = floor(time() / $ttl);
    $ckey = sprintf('XmlCache|%u|%s', $ttl, $url);

    if ($cached = $cache->$ckey)
      return new Response($cached, 'text/xml');

    $xml = http::fetch($url, http::CONTENT);
    $cache->$ckey = $xml;

    Logger::log('Imported XML from ' . $url);

    return new Response($xml, 'text/xml');
  }
};
