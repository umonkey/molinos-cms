<?php

class XMLImport
{
  /**
   * @mcms_message ru.molinos.cms.cron
   */
  public static function on_schedule(Context $ctx)
  {
    if ($sources = $ctx->config->getArray('modules/xml/import/sources')) {
      $ctx->db->beginTransaction();

      $sel = $ctx->db->prepare("SELECT 1 FROM `node__xmlimport` WHERE `source` = ? AND `guid` = ?");
      $ins = $ctx->db->prepare("INSERT INTO `node__xmlimport` (`source`, `guid`, `date`, `item`) VALUES (?, ?, ?, ?)");

      foreach ($sources as $source => $settings) {
        try {
          $xml = new SimpleXMLElement(http::fetch($settings['url'], http::CONTENT));
          foreach ($xml->channel->item as $item) {
            $date = gmdate('Y-m-d H:i:s', strtotime($item->pubDate));
            $guid = strval($item->guid);

            $sel->execute(array($source, $guid));
            if (!$sel->fetchColumn(0))
              $ins->execute(array($source, $guid, $date, $item->asXML()));
            $sel->closeCursor();
          }
        } catch (Exception $e) {
          Logger::log("{$source}: " . $e->getMessage());
        }
      }

      $ctx->db->commit();
    }
  }

  /**
   * Возвращает проимпортированные данные.
   * @route GET//api/xml/imported.xml
   */
  public static function on_get_imported(Context $ctx)
  {
    $conditions = $params = array();

    if ($tmp = $ctx->get('source')) {
      $conditions[] = '`source` = ?';
      $params[] = $tmp;
    }

    if ($tmp = $ctx->get('since')) {
      $conditions[] = '`date` >= ?';
      $params[] = $tmp;
    } elseif (is_numeric($tmp = $ctx->get('age'))) {
      $conditions[] = '`date` >= ?';
      $params[] = mcms::now(time() - $tmp);
    }

    $sql = sql::getSelect(array('item'), array('node__xmlimport'), $conditions);
    $sql .= ' ORDER BY `date` DESC';
    $sql .= ' LIMIT ' . intval($ctx->get('limit', 20));

    $data = (array)$ctx->db->getResultsV("item", $sql, $params);

    return new Response(html::em('items', implode('', $data)), 'text/xml');
  }
}
