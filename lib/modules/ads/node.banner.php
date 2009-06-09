<?php

class BannerNode extends Node
{
  const table = 'node__banners';

  public function save()
  {
    $this->onSave("REPLACE INTO `" . self::table . "` (`id`, `time_limit`, `display_count`) VALUES (%ID%, ?, ?)", array($this->time_limit, $this->display_count));
    return parent::save();
  }

  public function getFormFields()
  {
    $schema = parent::getFormFields();

    $schema['time_limit'] = array(
      'type' => 'DateTimeControl',
      'label' => t('Показывать до'),
      'required' => false,
      'weight' => 100,
      'group' => t('Управление показами'),
      );
    $schema['display_count'] = array(
      'type' => 'NumberControl',
      'label' => t('Количество показов'),
      'required' => false,
      'weight' => 101,
      'group' => t('Управление показами'),
      );

    return $schema;
  }

  /**
   * Создание таблицы node__banners.
   * @mcms_message ru.molinos.cms.install
   */
  public static function on_install(Context $ctx)
  {
    TableInfo::check(self::table, array(
      'id' => array(
        'type' => 'integer',
        'key' => 'pri',
        ),
      'time_limit' => array(
        'type' => 'datetime',
        'key' => 'mul',
        ),
      'display_count' => array(
        'type' => 'integer',
        'key' => 'mul',
        ),
      ));
  }

  /**
   * Возвращает маршрут для получения баннеров.
   * @mcms_message ru.molinos.cms.route.poll
   */
  public static function on_route_poll()
  {
    return array(
      'GET//api/ads/get.xml' => array(
        'call' => __CLASS__ . '::on_get_banners',
        ),
      );
  }

  /**
   * Возвращает случайные баннеры.
   */
  public static function on_get_banners(Context $ctx)
  {
    $limit = intval($ctx->get('limit', 1));
    $data = $ctx->db->getResultsKV("id", "xml", "SELECT `id`, `xml` FROM `node` WHERE `class` = 'banner' AND `deleted` = 0 AND `published` = 1 AND `id` IN (SELECT `id` FROM `" . self::table . "` WHERE (`time_limit` IS NULL OR `time_limit` < ?) AND `display_count` <> 0) ORDER BY RAND() LIMIT {$limit}", array(mcms::now()));

    $params = array();
    $ctx->db->beginTransaction();
    $ctx->db->exec("UPDATE `" . self::table . "` SET `display_count` = `display_count` - 1 WHERE `display_count` IS NOT NULL AND `id` " . sql::in(array_keys($data), $params), $params);
    $ctx->db->commit();

    return new Response(html::em('nodes', implode('', $data)), 'text/xml');
  }
}
