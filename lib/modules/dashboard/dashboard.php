<?php
// vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2:

interface iDashboard
{
  // Возвращает массив элементов с ключами: img, href, title.
  public static function getDashboardIcons();
};

class BebopDashboard extends Widget implements iAdminWidget
{
  public function __construct(Node $node)
  {
    parent::__construct($node);
  }

  public static function getWidgetInfo()
  {
    return array(
      'name' => t('Панель управления'),
      'description' => t("Возвращает описание основных разделов админки."),
      );
  }

  // Препроцессор параметров.
  public function getRequestOptions(RequestContext $ctx)
  {
    $options = array_merge(parent::getRequestOptions($ctx), array(
      'groups' => $this->user->getGroups(true),
      ));
    return $options;
  }

  // Обработка запросов.  Возвращает список действий, предоставляемых административными виджетами.
  public function onGet(array $options)
  {
    $result = array();

    foreach (bebop_get_interface_map('iDashboard') as $class) {
      if (is_array($items = call_user_func(array($class, 'getDashboardIcons')))) {
        foreach ($items as $v) {
          if (empty($v['weight']))
            $v['weight'] = 0;
          $result['list'][] = $v;
        }
      }
    }

    if (!empty($result['list']))
      usort($result['list'], array('BebopDashboard', 'usort'));

    return $result;
  }

  private function usort(array $a, array $b)
  {
    if (0 !== ($tmp = $a['weight'] - $b['weight']))
      return $tmp;

    return strcmp($a['title'], $b['title']);
  }
};
