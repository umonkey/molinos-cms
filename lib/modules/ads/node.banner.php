<?php

class BannerNode extends Node
{
  public function save()
  {
    if ($this->isNew())
      $this->onSave("INSERT INTO `node__banners` (`id`, `time_limit`, `display_limit`) VALUES (%ID%, ?, ?)", array($this->time_limit, $this->display_limit));
    else
      $this->onSave("UPDATE `node__banners` SET `time_limit` = ?, `display_limit` = ? WHERE `id` = %ID%", array($this->time_limit, $this->display_limit));
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
    $schema['display_limit'] = array(
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
    TableInfo::check('node__banners', array(
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
      'display_limit' => array(
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
   * Вызов api/ads/get.xml
   *
   * Возвращает случайные баннеры.
   */
  public static function on_get_banners(Context $ctx)
  {
    $limit = intval($ctx->get('limit', 1));

    $sql = "SELECT `node`.`id`, `xml` FROM `node` "
      . "INNER JOIN `node__banners` ON `node__banners`.`id` = `node`.`id` "
      . "WHERE `class` = 'banner' AND `deleted` = 0 AND `published` = 1 "
      . "AND (`time_limit` IS NULL OR `time_limit` < ?) "
      . "AND (`display_limit` IS NULL OR `display_count` IS NULL OR `display_count` < `display_limit`) "
      . "ORDER BY RAND() LIMIT " . $limit;
    $data = $ctx->db->getResultsKV("id", "xml", $sql, array(mcms::now()));

    $params = array();
    $ctx->db->beginTransaction();
    $ctx->db->exec("UPDATE `node__banners` SET `display_count` = 0 WHERE `display_count` IS NULL");
    $ctx->db->exec("UPDATE `node__banners` SET `display_count` = `display_count` + 1 WHERE `id` " . sql::in(array_keys($data), $params), $params);
    $ctx->db->commit();

    return new Response(html::em('nodes', implode('', $data)), 'text/xml');
  }
}
