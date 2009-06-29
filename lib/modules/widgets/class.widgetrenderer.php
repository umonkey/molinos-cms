<?php
/**
 * Поддержка виджетов для Molinos CMS.
 *
 * Виджеты являются устаревшим механизмом вывода данных.  Они снижают
 * производительность сайта и практически лишают смысла кэширование.
 * Вместо виджетов настоятельно рекомендуется использовать XMP API.
 *
 * http://code.google.com/p/molinos-cms/wiki/XMLAPI
 *
 * Этот класс перехватывает сообщение ru.molinos.cms.page.content
 * и возвращает XML блок с виджетами.
 * 
 */

class WidgetRenderer
{
  /**
   * Рендеринг виджетов для конкретного маршрута.
   * Возвращает результат в XML (<widgets/>).
   * @mcms_message ru.molinos.cms.page.content
   */
  public static function on_render(Context $ctx, array $pathinfo, $param = null)
  {
    if (!self::check_param($ctx, $param))
      throw new PageNotFoundException();

    $params = self::getWidgetParams($ctx, $pathinfo, $param);

    $content = html::wrap('request', self::getWidgetParamsXML($params) . $ctx->url()->getArgsXML());

    if (!empty($pathinfo['widgets'])) {
      $count = 0;
      $time = microtime(true);

      $tmp = '';
      $want = $ctx->get('widget');
      $widgets = Widget::loadWidgets($ctx);
      foreach ($pathinfo['widgets'] as $wname) {
        if (null !== $want and $want != $wname)
          continue;
        if (array_key_exists($wname, $widgets) and empty($widgets[$wname]['disabled'])) {
          if (null !== ($widget = Widget::getInstance($wname, $widgets[$wname]))) {
            try {
              $wxml = $widget->render($ctx, $params);
            } catch (Exception $e) {
              $wxml = html::em('widget', array(
                'name' => $wname,
                'error' => get_class($e),
                'message' => $e->getMessage(),
                ));
            }

            if ($wname == $ctx->get('widget')) {
              $r = new Response('<?xml version="1.0"?>' . $wxml, 'text/xml');
              $r->send();
            }
            $tmp .= $wxml;
            $count++;
          }
        }
      }

      $content .= html::wrap('widgets', $tmp, array(
        'count' => $count,
        'time' => microtime(true) - $time,
        ));
    }

    return $content;
  }

  /**
   * Возвращает информацию об объектах, относящихся к запрошенной странице.
   */
  private static function getWidgetParams(Context $ctx, array $pathinfo, $param)
  {
    $defaultsection = isset($pathinfo['defaultsection'])
      ? $pathinfo['defaultsection']
      : null;

    $ids = array();
    if (null !== $param)
      $ids[] = $param;
    if (null !== $defaultsection)
      $ids[] = $defaultsection;

    $params = array();

    $where = "n.id " . sql::in($ids, $params);

    if (null !== $param) {
      $where .= ' OR n.id IN (SELECT tid FROM node__rel WHERE nid = ?)';
      $params[] = $param;
    }

    $sql = "SELECT id, class, xml FROM node n WHERE n.deleted = 0 AND n.published = 1 AND ({$where})";

    $data = $ctx->db->getResultsK("id", $sql, $params);

    $result = array(
      'document' => null,
      'section' => null,
      'root' => null,
      );

    // Проверяем явно запрошенный объект.
    if (null !== $param and isset($data[$param])) {
      if ('tag' != $data[$param]['class'])
        $result['document'] = $data[$param];
      else
        $result['section'] = $data[$param];
    }

    // Используем запрошенный вручную раздел.
    if (null !== $result['document'] and $want = $ctx->get('section')) {
      if (!isset($data[$want]) or 'tag' != $data[$want]['class'])
        throw new PageNotFoundException();
      $result['section'] = $data[$want];
    }

    // Определяем раздел для документа.
    if (null !== $result['document'] and null === $result['section']) {
      foreach ($data as $node) {
        if ('tag' == $node['class'] and $node['id'] != $defaultsection) {
          $result['section'] = $node;
          break;
        }
      }
    }

    // Раздел по умолчанию для страницы.
    if (null !== $defaultsection and isset($data[$defaultsection])) {
      $tmp = $data[$defaultsection];
      if ('tag' == $tmp['class']) {
        $result['root'] = $tmp;
        if (null === $result['section'])
          $result['section'] = $tmp;
      }
    }

    return $result;
  }

  private static function getWidgetParamsXML(array $params)
  {
    $result = '';
    foreach ($params as $k => $v)
      if (!empty($v['xml']))
        $result .= html::em($k, $v['xml']);
    return $result;
  }

  private static function isSection(Context $ctx, $param)
  {
    $sth = $ctx->db->prepare("SELECT 1 FROM node WHERE id = ? AND class = 'tag'");
    $sth->execute(array($param));
    return $sth->fetchColumn(0);
  }

  private static function check_param(Context $ctx, $param)
  {
    if (empty($param))
      return true;

    $data = $ctx->db->fetch("SELECT `id`, `published`, `deleted`, `class`, `xml` FROM `node` WHERE `id` = ?", array($param));

    if (empty($data))
      throw new PageNotFoundException();
    elseif (empty($data['xml']))
      throw new PageNotFoundException(t('Этот документ не может быть отображён.'));
    elseif (empty($data['published']))
      throw new ForbiddenException(t('Этот документ не опубликован.'));
    elseif (!empty($data['deleted']))
      throw new ForbiddenException(t('Такого документа больше нет.'));
    elseif (!$ctx->user->hasAccess(ACL::READ, $data['class']))
      throw new ForbiddenException(t('У вас нет доступа к этому объекту.'));

    return true;
  }
}
